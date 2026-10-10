<?php
// php-app/api/employee/agreements.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_employee_or_admin($authUser);

$method = $_SERVER['REQUEST_METHOD'];
$isScoped = ($authUser['role'] !== 'admin');
$empId = $authUser['id'];

function generate_agreement_number(): string {
    $stmt = db()->query("SELECT agreement_number FROM agreements WHERE agreement_number LIKE 'AGR-%'");
    $maxNum = 0;
    while ($row = $stmt->fetch()) {
        if (preg_match('/^AGR-(\d+)$/', $row['agreement_number'], $m)) {
            $num = (int)$m[1];
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }
    return 'AGR-' . str_pad((string)($maxNum + 1), 4, '0', STR_PAD_LEFT);
}

// ── GET: List agreements or single agreement ────────────────────────────────
if ($method === 'GET') {
    $agreementId = $_GET['id'] ?? null;

    if ($agreementId) {
        $sql = '
            SELECT a.*, c.name as client_name, c.phone as client_phone, c.client_number,
                   c.total_debt as client_total_debt, c.employee_id as client_employee_id
            FROM agreements a
            LEFT JOIN clients c ON a.client_id = c.id
            WHERE a.id = ?
        ';
        $params = [$agreementId];
        if ($isScoped) {
            $sql .= ' AND (c.employee_id = ? OR c.manager_id = ? OR a.created_by = ?)';
            $params[] = $empId;
            $params[] = $empId;
            $params[] = $empId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $agr = $stmt->fetch();

        if (!$agr) {
            http_response_code(404);
            echo json_encode(['error' => 'Agreement not found']);
            exit;
        }

        echo json_encode(['agreement' => $agr]);
        exit;
    }

    $status = $_GET['status'] ?? null;
    $search = trim($_GET['search'] ?? '');

    $sql = '
        SELECT a.*, c.name as client_name, c.client_number, c.phone as client_phone
        FROM agreements a
        LEFT JOIN clients c ON a.client_id = c.id
        WHERE 1=1
    ';
    $params = [];

    if ($isScoped) {
        $sql .= ' AND (c.employee_id = ? OR c.manager_id = ? OR a.created_by = ?)';
        $params[] = $empId;
        $params[] = $empId;
        $params[] = $empId;
    }

    if ($status && $status !== 'all') {
        $sql .= ' AND a.status = ?';
        $params[] = $status;
    }

    if ($search !== '') {
        $sql .= ' AND (a.agreement_number LIKE ? OR a.name LIKE ? OR c.name LIKE ? OR a.phone LIKE ?)';
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= ' ORDER BY a.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['agreements' => $stmt->fetchAll()]);
    exit;
}

// ── POST: Create Agreement ──────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $clientId = $input['client_id'] ?? null;
    $totalFee = (float)($input['total_fee'] ?? 0);

    if (!$clientId || $totalFee <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid client_id and total_fee are required']);
        exit;
    }

    // Verify client access
    $stmtClient = db()->prepare('SELECT * FROM clients WHERE id = ?');
    $stmtClient->execute([$clientId]);
    $client = $stmtClient->fetch();
    if (!$client) {
        http_response_code(404);
        echo json_encode(['error' => 'Client not found']);
        exit;
    }

    $agrId = 'agr-' . substr(bin2hex(random_bytes(8)), 0, 10);
    $agrNumber = generate_agreement_number();

    $duration = $input['resolution_duration'] ?? '12 Months';
    $months = 12;
    if (preg_match('/(\d+)/', $duration, $m)) {
        $months = max(1, (int)$m[1]);
    }
    $monthlyFee = round($totalFee / $months, 2);

    $stmt = db()->prepare('
        INSERT INTO agreements (
            id, agreement_number, client_id, fee_plan_id, name, phone, email,
            address, pin_number, dob, start_date, end_date, total_fee,
            monthly_fee, resolution_duration, prepared_by, status, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?
        )
    ');

    $startDate = $input['start_date'] ?? date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$months months", strtotime($startDate)));

    $stmt->execute([
        $agrId,
        $agrNumber,
        $clientId,
        $input['fee_plan_id'] ?? null,
        $input['name'] ?? $client['name'],
        $input['phone'] ?? $client['phone'],
        $input['email'] ?? $client['email'],
        $input['address'] ?? null,
        $input['pin_number'] ?? null,
        $input['dob'] ?? null,
        $startDate,
        $endDate,
        $totalFee,
        $monthlyFee,
        $duration,
        $authUser['name'],
        $input['status'] ?? 'draft',
        $authUser['id']
    ]);

    // Create initial Monthly Payment Schedule
    for ($i = 1; $i <= $months; $i++) {
        $dueDate = date('Y-m-d', strtotime("+".($i-1)." months", strtotime($startDate)));
        $mprId = 'mpr-' . substr(bin2hex(random_bytes(8)), 0, 8);
        $stmtMpr = db()->prepare('
            INSERT INTO monthly_payment_records (
                id, client_id, agreement_id, month_number, due_date,
                expected_amount, received_amount, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, 0, ?)
        ');
        $stmtMpr->execute([$mprId, $clientId, $agrId, $i, $dueDate, $monthlyFee, 'Pending']);
    }

    log_audit($authUser, 'Agreement Created', 'Agreements', $agrId, "Agreement $agrNumber created for {$client['name']}");

    echo json_encode([
        'message'          => 'Agreement created successfully',
        'id'               => $agrId,
        'agreement_number' => $agrNumber,
        'monthly_fee'      => $monthlyFee
    ]);
    exit;
}

// ── PUT: Update Agreement ───────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $_GET['id'] ?? $input['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Agreement ID required']);
        exit;
    }

    $stmt = db()->prepare('
        UPDATE agreements SET 
            status = COALESCE(?, status),
            agreement_body = COALESCE(?, agreement_body),
            updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([
        $input['status'] ?? null,
        $input['agreement_body'] ?? null,
        $id
    ]);

    log_audit($authUser, 'Agreement Updated', 'Agreements', $id, "Agreement $id updated");

    echo json_encode(['message' => 'Agreement updated successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
