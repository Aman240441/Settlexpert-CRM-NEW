<?php
// php-app/api/employee/clients.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_employee_or_admin($authUser);

$method = $_SERVER['REQUEST_METHOD'];
$isScoped = ($authUser['role'] !== 'admin');
$empId = $authUser['id'];

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

// ── GET: List or single client ──────────────────────────────────────────────
if ($method === 'GET') {
    $clientId = $_GET['id'] ?? null;

    if ($clientId) {
        $sql = '
            SELECT c.*, u.name as employee_name, a.name as advocate_name
            FROM clients c
            LEFT JOIN users u ON c.employee_id = u.id
            LEFT JOIN advocates a ON c.advocate_id = a.id
            WHERE c.id = ?
        ';
        $params = [$clientId];
        if ($isScoped) {
            $sql .= ' AND (c.employee_id = ? OR c.manager_id = ?)';
            $params[] = $empId;
            $params[] = $empId;
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $client = $stmt->fetch();

        if (!$client) {
            http_response_code(404);
            echo json_encode(['error' => 'Client not found']);
            exit;
        }

        // Lenders
        $stmtLen = db()->prepare('SELECT * FROM lenders WHERE client_id = ?');
        $stmtLen->execute([$clientId]);
        $client['lenders'] = $stmtLen->fetchAll();

        // Agreements
        $stmtAgr = db()->prepare('SELECT * FROM agreements WHERE client_id = ? ORDER BY created_at DESC');
        $stmtAgr->execute([$clientId]);
        $client['agreements'] = $stmtAgr->fetchAll();

        // Monthly Payment Records
        $stmtMpr = db()->prepare('SELECT * FROM monthly_payment_records WHERE client_id = ? ORDER BY month_number ASC');
        $stmtMpr->execute([$clientId]);
        $client['monthly_records'] = $stmtMpr->fetchAll();

        // Payments (Receipts)
        $stmtPay = db()->prepare('SELECT * FROM payments WHERE client_id = ? ORDER BY payment_date DESC');
        $stmtPay->execute([$clientId]);
        $client['payments'] = $stmtPay->fetchAll();

        echo json_encode(['client' => $client]);
        exit;
    }

    $status = $_GET['status'] ?? null;
    $search = trim($_GET['search'] ?? '');

    $sql = '
        SELECT c.*, u.name as employee_name, a.name as advocate_name
        FROM clients c
        LEFT JOIN users u ON c.employee_id = u.id
        LEFT JOIN advocates a ON c.advocate_id = a.id
        WHERE 1=1
    ';
    $params = [];

    if ($isScoped) {
        $sql .= ' AND (c.employee_id = ? OR c.manager_id = ?)';
        $params[] = $empId;
        $params[] = $empId;
    }

    if ($status && $status !== 'all') {
        $sql .= ' AND (c.status = ? OR c.case_status = ?)';
        $params[] = $status;
        $params[] = $status;
    }

    if ($search !== '') {
        $sql .= ' AND (c.name LIKE ? OR c.phone LIKE ? OR c.client_number LIKE ?)';
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= ' ORDER BY c.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['clients' => $stmt->fetchAll()]);
    exit;
}

// ── PUT: Update client ──────────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $_GET['id'] ?? $input['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Client ID required']);
        exit;
    }

    if ($isScoped) {
        $chk = db()->prepare('SELECT id FROM clients WHERE id = ? AND (employee_id = ? OR manager_id = ?)');
        $chk->execute([$id, $empId, $empId]);
        if (!$chk->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
    }

    $stmt = db()->prepare('
        UPDATE clients SET 
            name = COALESCE(?, name),
            phone = COALESCE(?, phone),
            email = COALESCE(?, email),
            city = COALESCE(?, city),
            total_debt = COALESCE(?, total_debt),
            settlement_target = COALESCE(?, settlement_target),
            sx_fee = COALESCE(?, sx_fee),
            status = COALESCE(?, status),
            case_status = COALESCE(?, case_status),
            notes = COALESCE(?, notes),
            updated_at = NOW()
        WHERE id = ?
    ');

    $stmt->execute([
        $input['name'] ?? null,
        $input['phone'] ?? null,
        $input['email'] ?? null,
        $input['city'] ?? null,
        isset($input['total_debt']) ? (float)$input['total_debt'] : null,
        isset($input['settlement_target']) ? (float)$input['settlement_target'] : null,
        isset($input['sx_fee']) ? (float)$input['sx_fee'] : null,
        $input['status'] ?? null,
        $input['case_status'] ?? null,
        $input['notes'] ?? null,
        $id
    ]);

    log_audit($authUser, 'Client Updated', 'Clients', $id, "Client $id details updated");

    echo json_encode(['message' => 'Client updated successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
