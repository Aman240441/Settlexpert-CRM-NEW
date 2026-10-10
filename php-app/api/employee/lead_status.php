<?php
// php-app/api/employee/lead_status.php
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
$status = $input['status'] ?? null;
$callStatus = $input['call_status'] ?? 'Call Connected';
$remark = $input['remark'] ?? '';
$nextDate = $input['next_follow_up_date'] ?? null;

$validStatuses = ['new', 'contacted', 'interested', 'in_progress', 'converted', 'dropped', 'unassigned'];
if (!$leadId || !in_array($status, $validStatuses, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid lead_id and status are required']);
    exit;
}

$isScoped = ($authUser['role'] !== 'admin');
$empId = $authUser['id'];

// Check permission
if ($isScoped) {
    $stmt = db()->prepare('SELECT * FROM leads WHERE id = ? AND (employee_id = ? OR manager_id = ?)');
    $stmt->execute([$leadId, $empId, $empId]);
    $lead = $stmt->fetch();
    if (!$lead) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }
} else {
    $stmt = db()->prepare('SELECT * FROM leads WHERE id = ?');
    $stmt->execute([$leadId]);
    $lead = $stmt->fetch();
}

if (!$lead) {
    http_response_code(404);
    echo json_encode(['error' => 'Lead not found']);
    exit;
}

// 1. Update lead status
$stmtUp = db()->prepare('UPDATE leads SET status = ?, updated_at = NOW() WHERE id = ?');
$stmtUp->execute([$status, $leadId]);

// 2. Insert into follow_ups
$followId = 'fol-' . substr(bin2hex(random_bytes(8)), 0, 8);
$stmtFol = db()->prepare('
    INSERT INTO follow_ups (
        id, lead_id, user_id, user_name, call_status,
        interested_level, final_status, remark, next_follow_up_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
');
$stmtFol->execute([
    $followId,
    $leadId,
    $authUser['id'],
    $authUser['name'],
    $callStatus,
    $input['interested_level'] ?? 'Medium',
    $status,
    $remark,
    $nextDate
]);

log_audit($authUser, 'Lead Status Changed', 'Leads', $leadId, "Lead status updated to '$status'");

echo json_encode([
    'message'     => 'Lead status updated successfully',
    'status'      => $status,
    'follow_up_id'=> $followId
]);
