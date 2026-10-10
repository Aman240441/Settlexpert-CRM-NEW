<?php
// php-app/api/admin/audit.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_admin($authUser);

try {
    $search = trim($_GET['search'] ?? '');
    $module = $_GET['module'] ?? null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 50));
    $offset = ($page - 1) * $limit;

    $sql = 'SELECT * FROM audit_logs WHERE 1=1';
    $params = [];

    if ($module && $module !== 'all') {
        $sql .= ' AND module = ?';
        $params[] = $module;
    }

    if ($search !== '') {
        $sql .= ' AND (user_name LIKE ? OR action LIKE ? OR details_json LIKE ?)';
        $s = "%$search%";
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    $countSql = preg_replace('/SELECT \* FROM/s', 'SELECT COUNT(*) as total FROM', $sql);
    $stmtCount = db()->prepare($countSql);
    $stmtCount->execute($params);
    $total = (int)$stmtCount->fetch()['total'];

    $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'logs'       => $stmt->fetchAll(),
        'pagination' => [
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'totalPages' => ceil($total / $limit)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load audit logs: ' . $e->getMessage()]);
}
