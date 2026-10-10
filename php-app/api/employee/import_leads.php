<?php
// php-app/api/employee/import_leads.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_employee_or_admin($authUser);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid CSV or XLSX file is required']);
    exit;
}

$tmpPath = $_FILES['file']['tmp_name'];
$origName = $_FILES['file']['name'];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

$rows = [];

// Simple and robust parser for CSV / TSV
if ($ext === 'csv' || $ext === 'txt') {
    if (($handle = fopen($tmpPath, 'r')) !== false) {
        $header = null;
        while (($data = fgetcsv($handle, 10000, ',')) !== false) {
            if (!$header) {
                $header = array_map('trim', $data);
            } else {
                if (count($data) >= 2) {
                    $row = [];
                    foreach ($header as $idx => $colName) {
                        $row[$colName] = $data[$idx] ?? '';
                    }
                    $rows[] = $row;
                }
            }
        }
        fclose($handle);
    }
} elseif ($ext === 'xlsx') {
    // Basic XLSX sharedStrings & sheet1 extraction without heavy libraries
    $zip = new ZipArchive();
    if ($zip->open($tmpPath) === true) {
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($stringsXml && $sheetXml) {
            $sharedStrings = [];
            $sXml = simplexml_load_string($stringsXml);
            foreach ($sXml->si as $val) {
                $sharedStrings[] = (string)($val->t ?? $val->r->t ?? '');
            }

            $wXml = simplexml_load_string($sheetXml);
            $parsedRows = [];
            foreach ($wXml->sheetData->row as $r) {
                $rowCells = [];
                foreach ($r->c as $c) {
                    $val = (string)$c->v;
                    $type = (string)$c['t'];
                    if ($type === 's' && isset($sharedStrings[(int)$val])) {
                        $val = $sharedStrings[(int)$val];
                    }
                    $rowCells[] = $val;
                }
                $parsedRows[] = $rowCells;
            }

            if (count($parsedRows) > 1) {
                $header = array_map('trim', $parsedRows[0]);
                for ($i = 1; $i < count($parsedRows); $i++) {
                    $row = [];
                    foreach ($header as $idx => $colName) {
                        $row[$colName] = $parsedRows[$i][$idx] ?? '';
                    }
                    $rows[] = $row;
                }
            }
        }
    }
}

if (empty($rows)) {
    http_response_code(400);
    echo json_encode(['error' => 'File contains no valid lead records or unsupported format']);
    exit;
}

// Sequential IDs
function get_next_lead_seq(): int {
    $stmt = db()->query("SELECT lead_number FROM leads WHERE lead_number LIKE 'LEAD-%'");
    $maxNum = 0;
    while ($r = $stmt->fetch()) {
        if (preg_match('/^LEAD-(\d+)$/', $r['lead_number'], $m)) {
            $maxNum = max($maxNum, (int)$m[1]);
        }
    }
    return $maxNum;
}

function get_next_imp_number(): string {
    $stmt = db()->query("SELECT import_number FROM lead_imports WHERE import_number LIKE 'IMP-%'");
    $maxNum = 0;
    while ($r = $stmt->fetch()) {
        if (preg_match('/^IMP-(\d+)$/', $r['import_number'], $m)) {
            $maxNum = max($maxNum, (int)$m[1]);
        }
    }
    return 'IMP-' . str_pad((string)($maxNum + 1), 4, '0', STR_PAD_LEFT);
}

$importId = 'imp-' . substr(bin2hex(random_bytes(8)), 0, 8);
$importNumber = get_next_imp_number();

$totalRows = count($rows);
$validCount = 0;
$duplicateCount = 0;
$importedCount = 0;

// Fetch all active employees for round-robin assignment
$employees = db()->query("SELECT id, name FROM users WHERE role IN ('employee', 'manager') AND status = 'active'")->fetchAll();
$empCount = count($employees);
$empIndex = 0;

$currentLeadSeq = get_next_lead_seq();

foreach ($rows as $r) {
    // Detect column aliases
    $name = trim($r['Name'] ?? $r['name'] ?? $r['Client Name'] ?? $r['Full Name'] ?? '');
    $phone = trim($r['Phone'] ?? $r['phone'] ?? $r['Mobile'] ?? $r['Contact'] ?? '');
    $email = trim($r['Email'] ?? $r['email'] ?? '');
    $debt = (float)($r['Total Outstanding Amount'] ?? $r['Debt'] ?? $r['total_debt'] ?? 0);
    $city = trim($r['City'] ?? $r['city'] ?? '');
    $bankName = trim($r['Bank Name'] ?? $r['bank_name'] ?? '');

    if ($name === '' || $phone === '') {
        continue;
    }

    $validCount++;

    // Normalize phone (last 10 digits for matching)
    $cleanPhone = substr(preg_replace('/[^\d]/', '', $phone), -10);

    // Duplicate check
    $chk = db()->prepare("SELECT id FROM leads WHERE RIGHT(REPLACE(REPLACE(phone, ' ', ''), '-', ''), 10) = ?");
    $chk->execute([$cleanPhone]);
    if ($chk->fetch()) {
        $duplicateCount++;
        continue;
    }

    // Round-robin assign
    $assignedEmp = null;
    if ($empCount > 0) {
        $assignedEmp = $employees[$empIndex % $empCount]['id'];
        $empIndex++;
    }

    $currentLeadSeq++;
    $leadNumber = 'LEAD-' . str_pad((string)$currentLeadSeq, 4, '0', STR_PAD_LEFT);
    $leadId = 'lead-' . substr(bin2hex(random_bytes(8)), 0, 10);

    $stmtIns = db()->prepare('
        INSERT INTO leads (
            id, lead_number, name, phone, email, city, total_debt,
            bank_name, status, employee_id, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?
        )
    ');

    $stmtIns->execute([
        $leadId,
        $leadNumber,
        $name,
        $phone,
        $email ?: null,
        $city ?: null,
        $debt,
        $bankName ?: null,
        'new',
        $assignedEmp ?: $authUser['id'],
        $authUser['name']
    ]);

    $importedCount++;
}

// Log Import Record
$stmtImp = db()->prepare('
    INSERT INTO lead_imports (
        id, import_number, file_name, uploaded_by, uploaded_by_name,
        uploaded_by_role, total_rows, valid_leads, duplicate_leads,
        imported_leads, distribution_status
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?
    )
');
$stmtImp->execute([
    $importId,
    $importNumber,
    $origName,
    $authUser['id'],
    $authUser['name'],
    $authUser['role'],
    $totalRows,
    $validCount,
    $duplicateCount,
    $importedCount,
    'distributed'
]);

log_audit($authUser, 'Leads Imported', 'Leads', $importId, "Imported $importedCount leads from $origName");

echo json_encode([
    'message'         => 'Leads processed and distributed successfully',
    'import_number'   => $importNumber,
    'total_rows'      => $totalRows,
    'valid_leads'     => $validCount,
    'duplicate_leads' => $duplicateCount,
    'imported_leads'  => $importedCount
]);
