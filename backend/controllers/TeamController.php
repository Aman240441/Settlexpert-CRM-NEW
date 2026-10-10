<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../helpers/IdGenerator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class TeamController {
    private static function checkAdmin(): array {
        $config = require __DIR__ . '/../config/config.php';
        AuthMiddleware::authenticate($config['jwt_secret']);
        return AuthMiddleware::requireAdmin();
    }

    public static function index(): void {
        self::checkAdmin();
        $db = Database::getConnection();

        try {
            $stmt = $db->query('
                SELECT 
                    t.*,
                    d.name as department_name,
                    d.code as department_code,
                    m.name as manager_name,
                    m.emp_or_mgr_id as manager_code,
                    (SELECT COUNT(*) FROM users e WHERE e.team_id = t.id AND e.role = \'employee\') as member_count
                FROM teams t
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN users m ON t.manager_id = m.id
                ORDER BY t.created_at DESC
            ');
            $teams = $stmt->fetchAll();

            $memberStmt = $db->prepare('
                SELECT id, name, email, phone, emp_or_mgr_id, status 
                FROM users 
                WHERE team_id = ? AND role = \'employee\'
            ');

            foreach ($teams as &$team) {
                $team['member_count'] = (int)$team['member_count'];
                $memberStmt->execute([$team['id']]);
                $team['members'] = $memberStmt->fetchAll();
            }

            Response::json(['teams' => $teams]);
        } catch (Throwable $e) {
            Response::error('Failed to fetch teams', 500);
        }
    }

    public static function create(): void {
        $user = self::checkAdmin();
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $name = trim((string)($body['name'] ?? ''));
        $departmentId = isset($body['department_id']) && $body['department_id'] !== '' ? (string)$body['department_id'] : null;
        $managerId = isset($body['manager_id']) && $body['manager_id'] !== '' ? (string)$body['manager_id'] : null;
        $status = (string)($body['status'] ?? 'active');
        $employeeIds = isset($body['employee_ids']) && is_array($body['employee_ids']) ? $body['employee_ids'] : [];

        if ($name === '') {
            Response::error('Team name is required', 400);
        }

        $id = IdGenerator::generateId('team');

        $stmt = $db->prepare('
            INSERT INTO teams (id, name, department_id, manager_id, status)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$id, $name, $departmentId, $managerId, $status]);

        if (!empty($employeeIds)) {
            $upStmt = $db->prepare('
                UPDATE users 
                SET team_id = ?, 
                    department_id = COALESCE(?, department_id), 
                    manager_id = COALESCE(?, manager_id) 
                WHERE id = ?
            ');
            foreach ($employeeIds as $empId) {
                $upStmt->execute([$id, $departmentId, $managerId, $empId]);
            }
        }

        AuthMiddleware::logAudit($db, $user, 'Team Created', 'Teams', $id, [
            'name' => $name,
            'department_id' => $departmentId,
            'manager_id' => $managerId,
            'memberCount' => count($employeeIds)
        ]);

        Response::json(['message' => 'Team created successfully', 'id' => $id], 201);
    }

    public static function update(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $stmt = $db->prepare('SELECT * FROM teams WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Team not found', 404);
        }

        $name = isset($body['name']) ? trim((string)$body['name']) : null;
        $departmentId = array_key_exists('department_id', $body) ? ($body['department_id'] !== null && $body['department_id'] !== '' ? (string)$body['department_id'] : null) : $existing['department_id'];
        $managerId = array_key_exists('manager_id', $body) ? ($body['manager_id'] !== null && $body['manager_id'] !== '' ? (string)$body['manager_id'] : null) : $existing['manager_id'];
        $status = array_key_exists('status', $body) ? (string)$body['status'] : $existing['status'];
        $employeeIds = $body['employee_ids'] ?? null;

        $finalName = $name !== null ? $name : $existing['name'];

        $stmt = $db->prepare('
            UPDATE teams SET
                name = ?,
                department_id = ?,
                manager_id = ?,
                status = ?
            WHERE id = ?
        ');
        $stmt->execute([$finalName, $departmentId, $managerId, $status, $id]);

        if (is_array($employeeIds)) {
            $clearStmt = $db->prepare('UPDATE users SET team_id = NULL WHERE team_id = ?');
            $clearStmt->execute([$id]);

            $upStmt = $db->prepare('
                UPDATE users 
                SET team_id = ?, 
                    department_id = COALESCE(?, department_id), 
                    manager_id = COALESCE(?, manager_id) 
                WHERE id = ?
            ');
            foreach ($employeeIds as $empId) {
                $upStmt->execute([$id, $departmentId, $managerId, $empId]);
            }
        }

        AuthMiddleware::logAudit($db, $user, 'Team Updated', 'Teams', $id, [
            'name' => $finalName,
            'department_id' => $departmentId,
            'manager_id' => $managerId
        ]);

        Response::json(['message' => 'Team updated successfully']);
    }

    public static function delete(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT name FROM teams WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Team not found', 404);
        }

        $clearStmt = $db->prepare('UPDATE users SET team_id = NULL WHERE team_id = ?');
        $clearStmt->execute([$id]);

        $delStmt = $db->prepare('DELETE FROM teams WHERE id = ?');
        $delStmt->execute([$id]);

        AuthMiddleware::logAudit($db, $user, 'Team Deleted', 'Teams', $id, 'Deleted team ' . $existing['name']);

        Response::json(['message' => 'Team deleted successfully']);
    }
}
