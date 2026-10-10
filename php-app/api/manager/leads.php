<?php
// php-app/api/manager/leads.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

$scope = get_manager_scope($authUser);
$method = $_SERVER['REQUEST_METHOD'];
$empIds = $scope['employeeIds'];
$hasEmps = count($empIds) > 0;
$empPlaceholders = $hasEmps ? implode(',', array_fill(0, count($empIds), '?')) : "''";

// ── GET: Manager Leads List ─────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? null;
        $employeeId = $_GET['employee_id'] ?? null;
        $date = $_GET['date'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, (int)($_GET['limit'] ?? 25));
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT l.*,
                   u.name as employee_name,
                   u.emp_or_mgr_id as employee_code,
                   (SELECT COUNT(*) FROM follow_ups f WHERE f.lead_id = l.id) as follow_up_count,
                   (SELECT next_follow_up_date FROM follow_ups f WHERE f.lead_id = l.id ORDER BY f.created_at DESC LIMIT 1) as next_follow_up_date
            FROM leads l
            LEFT JOIN users u ON l.employee_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!$scope['isAdmin']) {
            $sql .= " AND (l.manager_id = ? OR l.department_id = ?" . ($hasEmps ? " OR l.employee_id IN ($empPlaceholders)" : "") . ")";
            $params = array_merge([$scope['managerId'], $scope['departmentId']], $hasEmps ? $empIds : []);
        }

        if ($employeeId) {
            $sql .= ' AND l.employee_id = ?';
            $params[] = $employeeId;
        }

        if ($status && $status !== 'all') {
            $sql .= ' AND l.status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= ' AND (l.name LIKE ? OR l.phone LIKE ? OR l.lead_number LIKE ? OR l.city LIKE ?)';
            $s = "%$search%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if ($date) {
            $sql .= ' AND DATE(l.created_at) = DATE(?)';
            $params[] = $date;
        }

        // Count query
        $countSql = preg_replace('/SELECT l\.\*.*?FROM leads l/s', 'SELECT COUNT(*) as total FROM leads l', $sql);
        $stmtCount = db()->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetch()['total'];

        $sql .= ' ORDER BY l.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $leads = $stmt->fetchAll();

        echo json_encode([
            'leads'      => $leads,
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
        echo json_encode(['error' => 'Failed to fetch manager leads: ' . $e->getMessage()]);
        exit;
    }
}

// ── POST: Reassign Lead ─────────────────────────────────────────────────────
if ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    if ($action === 'assign') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $leadId = $_GET['lead_id'] ?? $input['lead_id'] ?? null;
        $newEmployeeId = $input['new_employee_id'] ?? null;
        $reason = $input['reason'] ?? 'Manager reassignment';

        if (!$leadId || !$newEmployeeId) {
            http_response_code(400);
            echo json_encode(['error' => 'lead_id and new_employee_id are required']);
            exit;
        }

        $stmtLead = db()->prepare('SELECT * FROM leads WHERE id = ?');
        $stmtLead->execute([$leadId]);
        $lead = $stmtLead->fetch();
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Lead not found']);
            exit;
        }

        $stmtEmp = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'employee'");
        $stmtEmp->execute([$newEmployeeId]);
        $newEmp = $stmtEmp->fetch();
        if (!$newEmp) {
            http_response_code(404);
            echo json_encode(['error' => 'Target employee not found']);
            exit;
        }

        if (!$scope['isAdmin'] && $newEmp['manager_id'] !== $scope['managerId'] && $newEmp['department_id'] !== $scope['departmentId']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: You can only assign leads to employees within your team']);
            exit;
        }

        $oldEmpName = 'Unassigned';
        if (!empty($lead['employee_id'])) {
            $stmtOld = db()->prepare('SELECT name FROM users WHERE id = ?');
            $stmtOld->execute([$lead['employee_id']]);
            $oldRow = $stmtOld->fetch();
            if ($oldRow) $oldEmpName = $oldRow['name'];
        }

        $stmtUp = db()->prepare('
            UPDATE leads SET
                employee_id = ?,
                manager_id = COALESCE(manager_id, ?),
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmtUp->execute([$newEmp['id'], $scope['managerId'] ?: $lead['manager_id'], $lead['id']]);

        // Insert followup record
        $fupId = 'fup-' . substr(bin2hex(random_bytes(8)), 0, 8);
        $stmtFup = db()->prepare('
            INSERT INTO follow_ups (
                id, lead_id, user_id, user_name, call_status,
                final_status, remark, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmtFup->execute([
            $fupId,
            $lead['id'],
            $authUser['id'],
            $authUser['name'],
            'System: Reassignment',
            'Reassigned',
            "Lead reassigned from $oldEmpName to {$newEmp['name']}. Reason: $reason"
        ]);

        log_audit($authUser, 'Lead Reassigned', 'Leads', $lead['id'], [
            'newEmployee'      => $newEmp['name'],
            'previousEmployee' => $oldEmpName,
            'reason'           => $reason
        ]);

        echo json_encode(['message' => "Lead {$lead['lead_number']} successfully reassigned to {$newEmp['name']}"]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
