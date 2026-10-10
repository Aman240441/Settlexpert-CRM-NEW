<?php
// php-app/api/manager/payments.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

$scope = get_manager_scope($authUser);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'queue';

// ── GET ?action=queue: Verification Queue ───────────────────────────────────
if ($method === 'GET' && $action === 'queue') {
    $stmt = db()->query("
        SELECT p.*,
               c.name as client_name, c.client_number, c.phone as client_phone, c.sx_fee,
               u.name as employee_name
        FROM payments p
        LEFT JOIN clients c ON p.client_id = c.id
        LEFT JOIN users u ON c.employee_id = u.id
        ORDER BY p.created_at DESC
    ");
    echo json_encode(['payments' => $stmt->fetchAll()]);
    exit;
}

// ── POST ?action=verify: Verify or Reject Payment ───────────────────────────
if ($method === 'POST' && $action === 'verify') {
    if (!$scope['isAdmin'] && !in_array($scope['managerType'], ['FINANCE', 'FIN'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Finance Manager access required to verify payments']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $paymentId = $_GET['payment_id'] ?? $input['payment_id'] ?? null;
    $status = $input['status'] ?? 'verified';
    $notes = $input['notes'] ?? '';

    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(['error' => 'payment_id required']);
        exit;
    }

    $stmtPay = db()->prepare('SELECT * FROM payments WHERE id = ?');
    $stmtPay->execute([$paymentId]);
    $payment = $stmtPay->fetch();
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['error' => 'Payment not found']);
        exit;
    }

    $stmtUp = db()->prepare('UPDATE payments SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?');
    $stmtUp->execute([$status, $authUser['name'], $paymentId]);

    // If verified, update client payment received
    if ($status === 'verified') {
        $stmtSum = db()->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE client_id = ? AND status = 'verified'");
        $stmtSum->execute([$payment['client_id']]);
        $totalVerified = (float)$stmtSum->fetch()['total'];

        $stmtCl = db()->prepare('SELECT * FROM clients WHERE id = ?');
        $stmtCl->execute([$payment['client_id']]);
        $client = $stmtCl->fetch();
        if ($client) {
            $totalFee = (float)$client['sx_fee'];
            $feesStatus = ($totalVerified >= $totalFee && $totalFee > 0) ? 'Paid' : ($totalVerified > 0 ? 'Partially Paid' : 'Pending');
            $stmtClUp = db()->prepare('UPDATE clients SET total_received = ?, pending_amount = ?, fees_status = ?, updated_at = NOW() WHERE id = ?');
            $stmtClUp->execute([$totalVerified, max(0, $totalFee - $totalVerified), $feesStatus, $client['id']]);
        }
    }

    log_audit($authUser, $status === 'verified' ? 'Payment Verified' : 'Payment Rejected', 'Payments', $paymentId, [
        'receiptNumber' => $payment['receipt_number'],
        'amount'        => $payment['amount'],
        'status'        => $status
    ]);

    echo json_encode(['message' => "Payment {$payment['receipt_number']} $status successfully"]);
    exit;
}

// ── GET ?action=clients: Payment Schedule for Clients ───────────────────────
if ($method === 'GET' && $action === 'clients') {
    $stmt = db()->query("
        SELECT c.id, c.client_number, c.name, c.phone, c.email, c.city, c.total_debt,
               c.sx_fee, c.fees_date, c.fees_status, c.case_status, c.created_at,
               u.name as employee_name,
               a.id as agreement_id, a.agreement_number, a.resolution_duration, a.monthly_fee, a.total_fee as agreement_total_fee,
               COALESCE((SELECT SUM(received_amount) FROM monthly_payment_records m WHERE m.client_id = c.id), 0) as total_received,
               COALESCE((SELECT SUM(expected_amount) FROM monthly_payment_records m WHERE m.client_id = c.id), 
                        COALESCE(a.total_fee, c.sx_fee, 48000)) as total_agreement_fee
        FROM clients c
        LEFT JOIN users u ON c.employee_id = u.id
        LEFT JOIN (
          SELECT * FROM agreements WHERE id IN (SELECT MAX(id) FROM agreements GROUP BY client_id)
        ) a ON a.client_id = c.id
        ORDER BY c.created_at DESC
    ");
    $raw = $stmt->fetchAll();

    $clients = array_map(function($c) {
        $duration = $c['resolution_duration'] ?: '6 Months';
        preg_match('/(\d+)/', $duration, $m);
        $months = !empty($m[1]) ? (int)$m[1] : 6;
        $monthlyFee = (float)($c['monthly_fee'] ?: ($c['total_agreement_fee'] / $months));
        $totalFee = (float)($c['total_agreement_fee'] ?: ($monthlyFee * $months));
        $received = (float)$c['total_received'];
        return [
            'id'                  => $c['id'],
            'client_number'       => $c['client_number'],
            'name'                => $c['name'],
            'phone'               => $c['phone'],
            'employee_name'       => $c['employee_name'],
            'total_debt'          => (float)$c['total_debt'],
            'monthly_fee'         => $monthlyFee,
            'duration_months'     => $months,
            'total_agreement_fee' => $totalFee,
            'total_received'      => $received,
            'pending_amount'      => max(0, $totalFee - $received),
            'fees_status'         => ($received >= $totalFee && $totalFee > 0) ? 'Paid' : ($received > 0 ? 'Partially Paid' : 'Pending')
        ];
    }, $raw);

    echo json_encode(['clients' => $clients]);
    exit;
}

// ── GET ?action=monthly-payments: Client Payment Schedule ───────────────────
if ($method === 'GET' && $action === 'monthly-payments') {
    $clientId = $_GET['client_id'] ?? null;
    if (!$clientId) {
        http_response_code(400);
        echo json_encode(['error' => 'client_id required']);
        exit;
    }

    $stmtCl = db()->prepare("
        SELECT c.*, u.name as employee_name, a.name as advocate_name
        FROM clients c
        LEFT JOIN users u ON c.employee_id = u.id
        LEFT JOIN advocates a ON c.advocate_id = a.id
        WHERE c.id = ?
    ");
    $stmtCl->execute([$clientId]);
    $client = $stmtCl->fetch();
    if (!$client) {
        http_response_code(404);
        echo json_encode(['error' => 'Client not found']);
        exit;
    }

    $stmtAgr = db()->prepare('SELECT * FROM agreements WHERE client_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmtAgr->execute([$clientId]);
    $agreement = $stmtAgr->fetch();

    $stmtMpr = db()->prepare('SELECT * FROM monthly_payment_records WHERE client_id = ? ORDER BY month_number ASC');
    $stmtMpr->execute([$clientId]);
    $records = $stmtMpr->fetchAll();

    $stmtHist = db()->prepare('SELECT * FROM monthly_payment_history WHERE client_id = ? ORDER BY created_at DESC');
    $stmtHist->execute([$clientId]);
    $history = $stmtHist->fetchAll();

    echo json_encode([
        'client'    => $client,
        'agreement' => $agreement,
        'records'   => $records,
        'history'   => $history
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
