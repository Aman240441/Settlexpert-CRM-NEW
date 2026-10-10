<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AuditLogController {
    private static function checkAdmin(): array {
        $config = require __DIR__ . '/../config/config.php';
        AuthMiddleware::authenticate($config['jwt_secret']);
        return AuthMiddleware::requireAdmin();
    }

    public static function index(): void {
        self::checkAdmin();
        $db = Database::getConnection();

        $cleanParam = function ($v): ?string {
            if ($v !== null && is_string($v) && $v !== 'undefined' && $v !== 'null' && trim($v) !== '') {
                return trim($v);
            }
            return null;
        };

        $search = $cleanParam($_GET['search'] ?? null);
        $module = $cleanParam($_GET['module'] ?? null);
        $role = $cleanParam($_GET['role'] ?? null);
        $date = $cleanParam($_GET['date'] ?? null);
        $fromDate = $cleanParam($_GET['from_date'] ?? null);
        $toDate = $cleanParam($_GET['to_date'] ?? null);
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 100;
        $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

        $whereClause = ' WHERE 1=1';
        $params = [];

        if ($search !== null) {
            $whereClause .= ' AND (user_name LIKE ? OR action LIKE ? OR module LIKE ? OR record_id LIKE ? OR details_json LIKE ?)';
            $s = "%{$search}%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if ($module !== null && $module !== 'all') {
            $whereClause .= ' AND module = ?';
            $params[] = $module;
        }

        if ($role !== null && $role !== 'all') {
            $whereClause .= ' AND role = ?';
            $params[] = $role;
        }

        if ($fromDate !== null && $toDate !== null) {
            $whereClause .= ' AND DATE(created_at) >= DATE(?) AND DATE(created_at) <= DATE(?)';
            $params[] = $fromDate;
            $params[] = $toDate;
        } elseif ($fromDate !== null) {
            $whereClause .= ' AND DATE(created_at) >= DATE(?)';
            $params[] = $fromDate;
        } elseif ($toDate !== null) {
            $whereClause .= ' AND DATE(created_at) <= DATE(?)';
            $params[] = $toDate;
        } elseif ($date !== null) {
            $whereClause .= ' AND DATE(created_at) = DATE(?)';
            $params[] = $date;
        }

        try {
            // Count query using prepared statements
            $countStmt = $db->prepare('SELECT COUNT(*) as total FROM audit_logs' . $whereClause);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            // Fetch logs with limit and offset
            $dataSql = 'SELECT * FROM audit_logs' . $whereClause . " ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
            $dataStmt = $db->prepare($dataSql);
            $dataStmt->execute($params);
            $logs = $dataStmt->fetchAll();

            Response::json(['logs' => $logs, 'total' => $total]);
        } catch (Throwable $e) {
            Response::error('Failed to fetch audit logs', 500);
        }
    }
}
