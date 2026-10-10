<?php
// php-app/api/manager/context.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

try {
    $scope = get_manager_scope($authUser);

    $stmt = db()->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.role, u.emp_or_mgr_id, u.status, u.joining_date,
               u.department_id, u.manager_type_id,
               d.name as department_name, d.code as department_code,
               mt.name as manager_type_name, mt.code as manager_type_code, mt.permissions_json
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
        WHERE u.id = ?
    ");
    $stmt->execute([$authUser['id']]);
    $user = $stmt->fetch();

    $stmtEmp = db()->prepare("
        SELECT id, name, email, phone, emp_or_mgr_id, status, joining_date, id_type
        FROM users 
        WHERE (manager_id = ? OR (department_id = ? AND role = 'employee'))
          AND role = 'employee'
        ORDER BY name ASC
    ");
    $stmtEmp->execute([$authUser['id'], $user ? $user['department_id'] : null]);
    $employees = $stmtEmp->fetchAll();

    echo json_encode([
        'user'              => $user,
        'managerType'       => $scope['managerType'],
        'department'        => $user ? ['id' => $user['department_id'], 'name' => $user['department_name'], 'code' => $user['department_code']] : null,
        'permissions'       => $scope['permissions'],
        'assignedEmployees' => $employees
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load manager context: ' . $e->getMessage()]);
}
