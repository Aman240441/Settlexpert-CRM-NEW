<?php
// php-app/api/manager/team.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

$scope = get_manager_scope($authUser);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = "
            SELECT u.id, u.name, u.email, u.phone, u.emp_or_mgr_id, u.status, u.joining_date, u.id_type, u.profile_image,
                   d.name as department_name,
                   (SELECT COUNT(*) FROM leads l WHERE l.employee_id = u.id) as leads_count,
                   (SELECT COUNT(*) FROM clients c WHERE c.employee_id = u.id) as clients_count,
                   (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.id AND t.status != 'completed') as active_tasks_count
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.role = 'employee'
        ";
        $params = [];
        if (!$scope['isAdmin']) {
            $sql .= ' AND (u.manager_id = ? OR u.department_id = ?)';
            $params[] = $scope['managerId'];
            $params[] = $scope['departmentId'];
        }
        $sql .= ' ORDER BY u.name ASC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll();

        echo json_encode(['employees' => $employees]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load manager team']);
        exit;
    }
}

if ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    if ($action === 'reset-password') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $employeeId = $_GET['employee_id'] ?? $input['employee_id'] ?? null;
        $newPassword = $input['new_password'] ?? '';

        if (!$employeeId || strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid employee_id and password (min 6 chars) required']);
            exit;
        }

        $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'employee'");
        $stmt->execute([$employeeId]);
        $emp = $stmt->fetch();

        if (!$emp) {
            http_response_code(404);
            echo json_encode(['error' => 'Employee not found']);
            exit;
        }

        if (!$scope['isAdmin'] && $emp['manager_id'] !== $scope['managerId'] && $emp['department_id'] !== $scope['departmentId']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: You can only reset passwords for employees in your team']);
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmtUp = db()->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $stmtUp->execute([$hash, $employeeId]);

        log_audit($authUser, 'Employee Password Reset', 'Users', $employeeId, [
            'employee' => $emp['name'],
            'empId'    => $emp['emp_or_mgr_id']
        ]);

        echo json_encode(['message' => "Password reset successfully for {$emp['name']}"]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
