<?php
// php-app/api/manager/advocates.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

try {
    $stmt = db()->query("
        SELECT a.*,
               (SELECT COUNT(*) FROM clients c WHERE c.advocate_id = a.id) as assigned_clients_count
        FROM advocates a
        ORDER BY a.name ASC
    ");
    echo json_encode(['advocates' => $stmt->fetchAll()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load advocates: ' . $e->getMessage()]);
}
