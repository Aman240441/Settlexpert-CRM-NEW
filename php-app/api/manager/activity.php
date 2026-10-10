<?php
// php-app/api/manager/activity.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

$scope = get_manager_scope($authUser);
$empIds = $scope['employeeIds'];
$hasEmps = count($empIds) > 0;
$empPlaceholders = $hasEmps ? implode(',', array_fill(0, count($empIds), '?')) : "''";

try {
    $sql = 'SELECT a.* FROM audit_logs a WHERE 1=1';
    $params = [];

    if (!$scope['isAdmin']) {
        $sql .= " AND (a.user_id = ? OR a.module = ?" . ($hasEmps ? " OR a.user_id IN ($empPlaceholders)" : "") . ")";
        $params = array_merge([$scope['managerId'], $scope['managerType']], $hasEmps ? $empIds : []);
    }

    $sql .= ' ORDER BY a.created_at DESC LIMIT 100';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['logs' => $stmt->fetchAll()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load activity feed: ' . $e->getMessage()]);
}
