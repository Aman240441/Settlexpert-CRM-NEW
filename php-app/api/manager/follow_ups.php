<?php
// php-app/api/manager/follow_ups.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

$scope = get_manager_scope($authUser);
$filter = $_GET['filter'] ?? 'all';
$employeeId = $_GET['employee_id'] ?? null;
$empIds = $scope['employeeIds'];
$hasEmps = count($empIds) > 0;
$empPlaceholders = $hasEmps ? implode(',', array_fill(0, count($empIds), '?')) : "''";

try {
    $sql = "
        SELECT f.*,
               l.lead_number, l.name as lead_name, l.phone as lead_phone, l.city as lead_city,
               l.total_debt, l.status as lead_status,
               u.name as employee_name
        FROM follow_ups f
        JOIN leads l ON f.lead_id = l.id
        LEFT JOIN users u ON f.user_id = u.id
        WHERE 1=1
    ";
    $params = [];

    if (!$scope['isAdmin']) {
        $sql .= " AND (l.manager_id = ? OR l.department_id = ?" . ($hasEmps ? " OR l.employee_id IN ($empPlaceholders)" : "") . ")";
        $params = array_merge([$scope['managerId'], $scope['departmentId']], $hasEmps ? $empIds : []);
    }

    if ($employeeId) {
        $sql .= ' AND f.user_id = ?';
        $params[] = $employeeId;
    }

    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    if ($filter === 'today') {
        $sql .= " AND DATE(f.next_follow_up_date) = '$today'";
    } elseif ($filter === 'tomorrow') {
        $sql .= " AND DATE(f.next_follow_up_date) = '$tomorrow'";
    } elseif ($filter === 'overdue') {
        $sql .= " AND DATE(f.next_follow_up_date) < '$today' AND l.status NOT IN ('converted', 'dropped')";
    } elseif ($filter === 'upcoming') {
        $sql .= " AND DATE(f.next_follow_up_date) > '$today'";
    }

    $sql .= ' ORDER BY f.created_at DESC LIMIT 100';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['followUps' => $stmt->fetchAll()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load follow-ups: ' . $e->getMessage()]);
}
