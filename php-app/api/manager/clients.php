<?php
// php-app/api/manager/clients.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

$scope = get_manager_scope($authUser);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$empIds = $scope['employeeIds'];
$hasEmps = count($empIds) > 0;
$empPlaceholders = $hasEmps ? implode(',', array_fill(0, count($empIds), '?')) : "''";

// ── GET: Advocate assignment history for a client ───────────────────────────
if ($method === 'GET' && $action === 'advocate-history') {
    $clientId = $_GET['client_id'] ?? null;
    if (!$clientId) {
        http_response_code(400);
        echo json_encode(['error' => 'client_id is required']);
        exit;
    }
    $stmt = db()->prepare('SELECT * FROM advocate_assignment_history WHERE client_id = ? ORDER BY created_at DESC');
    $stmt->execute([$clientId]);
    echo json_encode(['history' => $stmt->fetchAll()]);
    exit;
}

// ── GET: Clients List ───────────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $search = trim($_GET['search'] ?? '');
        $caseStatus = $_GET['case_status'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, (int)($_GET['limit'] ?? 25));
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT c.*,
                   u.name as employee_name,
                   a.name as advocate_name,
                   a.registration_number as advocate_reg,
                   COALESCE((SELECT SUM(amount) FROM payments p WHERE p.client_id = c.id AND p.status = 'verified'), 0) as total_received,
                   (SELECT COUNT(*) FROM lenders len WHERE len.client_id = c.id) as lender_count,
                   (SELECT COUNT(*) FROM agreements ag WHERE ag.client_id = c.id) as agreement_count
            FROM clients c
            LEFT JOIN users u ON c.employee_id = u.id
            LEFT JOIN advocates a ON c.advocate_id = a.id
            WHERE 1=1
        ";
        $params = [];

        if (!$scope['isAdmin']) {
            $sql .= " AND (c.manager_id = ?" . ($hasEmps ? " OR c.employee_id IN ($empPlaceholders)" : "") . ")";
            $params = array_merge([$scope['managerId']], $hasEmps ? $empIds : []);
        }

        if ($search !== '') {
            $sql .= ' AND (c.name LIKE ? OR c.phone LIKE ? OR c.client_number LIKE ? OR c.city LIKE ?)';
            $s = "%$search%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if ($caseStatus && $caseStatus !== 'all') {
            $sql .= ' AND (c.case_status = ? OR c.status = ?)';
            $params[] = $caseStatus;
            $params[] = $caseStatus;
        }

        $countSql = preg_replace('/SELECT c\.\*.*?FROM clients c/s', 'SELECT COUNT(*) as total FROM clients c', $sql);
        $stmtCount = db()->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetch()['total'];

        $sql .= ' ORDER BY c.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rawClients = $stmt->fetchAll();

        $clients = array_map(function($c) {
            $fee = (float)($c['sx_fee'] ?: ($c['total_debt'] * 0.10));
            $received = (float)$c['total_received'];
            $c['pending_amount'] = max(0, $fee - $received);
            return $c;
        }, $rawClients);

        echo json_encode([
            'clients'    => $clients,
            'pagination' => [
                'total'      => $total,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => ceil($total / $limit)
            ]
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch manager clients: ' . $e->getMessage()]);
        exit;
    }
}

// ── POST / PATCH: Assign Advocate ───────────────────────────────────────────
if (($method === 'POST' || $method === 'PATCH') && $action === 'advocate') {
    if (!$scope['isAdmin'] && $scope['managerType'] !== 'LEGAL') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Only Legal Managers or Admin can assign advocates']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $clientId = $_GET['client_id'] ?? $input['client_id'] ?? null;
    $advocateId = $input['advocate_id'] ?? null;
    $notes = $input['notes'] ?? '';

    if (!$clientId) {
        http_response_code(400);
        echo json_encode(['error' => 'client_id is required']);
        exit;
    }

    $stmtClient = db()->prepare('SELECT * FROM clients WHERE id = ?');
    $stmtClient->execute([$clientId]);
    $client = $stmtClient->fetch();
    if (!$client) {
        http_response_code(404);
        echo json_encode(['error' => 'Client not found']);
        exit;
    }

    $advocateName = 'Unassigned';
    $targetAdvId = $advocateId;
    if ($advocateId) {
        $stmtAdv = db()->prepare('SELECT id, name FROM advocates WHERE id = ? OR advocate_id = ?');
        $stmtAdv->execute([$advocateId, $advocateId]);
        $adv = $stmtAdv->fetch();
        if ($adv) {
            $advocateName = $adv['name'];
            $targetAdvId = $adv['id'];
        }
    }

    $prevAdvName = 'None';
    $prevAdvId = null;
    if (!empty($client['advocate_id'])) {
        $stmtPrev = db()->prepare('SELECT id, name FROM advocates WHERE id = ?');
        $stmtPrev->execute([$client['advocate_id']]);
        $prev = $stmtPrev->fetch();
        if ($prev) {
            $prevAdvName = $prev['name'];
            $prevAdvId = $prev['id'];
        }
    }

    $stmtUp = db()->prepare('UPDATE clients SET advocate_id = ?, updated_at = NOW() WHERE id = ?');
    $stmtUp->execute([$targetAdvId ?: null, $client['id']]);

    // Save permanent history
    $historyId = 'aah-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $stmtHist = db()->prepare('
        INSERT INTO advocate_assignment_history (
            id, client_id, previous_advocate_id, previous_advocate_name,
            new_advocate_id, new_advocate_name, assigned_by_id, assigned_by_name,
            assigned_by_role, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ');
    $stmtHist->execute([
        $historyId,
        $client['id'],
        $prevAdvId,
        $prevAdvName,
        $targetAdvId ?: null,
        $advocateName,
        $authUser['id'],
        $authUser['name'],
        $authUser['role'],
        $notes ?: null
    ]);

    log_audit($authUser, 'Advocate Assigned', 'Clients', $client['id'], [
        'client'       => $client['client_number'],
        'newAdvocate'  => $advocateName,
        'prevAdvocate' => $prevAdvName
    ]);

    echo json_encode([
        'message'       => "Advocate $advocateName assigned to client {$client['client_number']}",
        'advocate_id'   => $targetAdvId,
        'advocate_name' => $advocateName
    ]);
    exit;
}

// ── POST / PATCH: Update Fee ────────────────────────────────────────────────
if (($method === 'POST' || $method === 'PATCH') && $action === 'fee') {
    if (!$scope['isAdmin'] && !in_array($scope['managerType'], ['FINANCE', 'FIN'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Only Finance Managers or Admin can update client fees']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $clientId = $_GET['client_id'] ?? $input['client_id'] ?? null;

    if (!$clientId) {
        http_response_code(400);
        echo json_encode(['error' => 'client_id is required']);
        exit;
    }

    $stmt = db()->prepare('
        UPDATE clients SET
            sx_fee = COALESCE(?, sx_fee),
            fees_date = COALESCE(?, fees_date),
            fees_status = COALESCE(?, fees_status),
            updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([
        isset($input['sx_fee']) ? (float)$input['sx_fee'] : null,
        $input['fees_date'] ?? null,
        $input['fees_status'] ?? null,
        $clientId
    ]);

    log_audit($authUser, 'Fee Updated', 'Clients', $clientId, [
        'newFee' => $input['sx_fee'] ?? null
    ]);

    echo json_encode(['message' => 'Client fee updated successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
