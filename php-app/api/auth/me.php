<?php
// php-app/api/auth/me.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/jwt.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$authUser = authenticate_token();

try {
    $stmt = db()->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.role, u.emp_or_mgr_id, 
               u.profile_image, u.department_id, u.team_id, u.manager_id, 
               u.manager_type_id, u.status, u.joining_date, u.id_type,
               d.name as department_name, mt.name as manager_type_name,
               COALESCE(sep.designation, mt.name, 
                   CASE 
                       WHEN u.role = 'admin' THEN 'Super Administrator' 
                       WHEN u.role = 'advocate' THEN 'Legal Advocate' 
                       WHEN u.role = 'manager' THEN 'Manager' 
                       ELSE 'Employee' 
                   END
               ) as designation
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
        LEFT JOIN staff_extended_profiles sep ON u.id = sep.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$authUser['id']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    echo json_encode(['user' => $user]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch user profile']);
}
