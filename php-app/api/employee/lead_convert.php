<?php
// php-app/api/employee/lead_convert.php
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

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$leadId = $input['lead_id'] ?? null;

if (!$leadId) {
    http_response_code(400);
    echo json_encode(['error' => 'lead_id is required']);
    exit;
}

$isScoped = ($authUser['role'] !== 'admin');
$empId = $authUser['id'];

// Get lead
$sql = 'SELECT * FROM leads WHERE id = ?';
$params = [$leadId];
if ($isScoped) {
    $sql .= ' AND (employee_id = ? OR manager_id = ?)';
    $params[] = $empId;
    $params[] = $empId;
}
$stmt = db()->prepare($sql);
$stmt->execute($params);
$lead = $stmt->fetch();

if (!$lead) {
    http_response_code(404);
    echo json_encode(['error' => 'Lead not found or permission denied']);
    exit;
}

function generate_client_number(): string {
    $stmt = db()->query("SELECT client_number FROM clients WHERE client_number LIKE 'CL-%'");
    $maxNum = 0;
    while ($row = $stmt->fetch()) {
        if (preg_match('/^CL-(\d+)$/', $row['client_number'], $m)) {
            $num = (int)$m[1];
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }
    return 'CL-' . str_pad((string)($maxNum + 1), 4, '0', STR_PAD_LEFT);
}

$clientId = 'cl-' . substr(bin2hex(random_bytes(8)), 0, 10);
$clientNumber = generate_client_number();

$totalDebt = (float)$lead['total_debt'];
$sxFee = (float)($lead['service_fee'] > 0 ? $lead['service_fee'] : round($totalDebt * 0.10, 2));

$stmtClient = db()->prepare('
    INSERT INTO clients (
        id, client_number, lead_id, name, email, phone, city,
        employment_status, employment_type, total_debt, monthly_income,
        credit_card_dues, personal_loan_dues, loan_type, paying_emis,
        harassment_calls, settlement_needed, consultation_timing,
        settlement_target, sx_fee, pending_amount, status, case_status,
        employee_id, manager_id
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?
    )
');

$stmtClient->execute([
    $clientId,
    $clientNumber,
    $lead['id'],
    $lead['name'],
    $lead['email'],
    $lead['phone'],
    $lead['city'],
    $lead['employment_status'],
    $lead['employment_type'],
    $totalDebt,
    $lead['monthly_income'],
    $lead['credit_card_dues'],
    $lead['personal_loan_dues'],
    $lead['service_needed'] ?: 'Credit Card & Personal Loan',
    $lead['paying_emis'],
    $lead['harassment_calls'],
    $lead['settlement_needed'],
    $lead['consultation_timing'],
    round($totalDebt * 0.50, 2), // Target ~50% settlement
    $sxFee,
    $sxFee,
    'active',
    'active',
    $lead['employee_id'] ?: $empId,
    $lead['manager_id']
]);

// Mark lead as converted
$stmtLeadUp = db()->prepare("UPDATE leads SET status = 'converted', updated_at = NOW() WHERE id = ?");
$stmtLeadUp->execute([$lead['id']]);

// Add default lender if bank_name specified
if (!empty($lead['bank_name'])) {
    $lenderId = 'len-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $stmtLen = db()->prepare('
        INSERT INTO lenders (id, client_id, bank_name, balance, status)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmtLen->execute([$lenderId, $clientId, $lead['bank_name'], $totalDebt, 'Defaulted']);
}

log_audit($authUser, 'Lead Converted to Client', 'Clients', $clientId, "Lead {$lead['lead_number']} converted to client $clientNumber");

echo json_encode([
    'message'       => 'Lead successfully converted to client',
    'client_id'     => $clientId,
    'client_number' => $clientNumber
]);
