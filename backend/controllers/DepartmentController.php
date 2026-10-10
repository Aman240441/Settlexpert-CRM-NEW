<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../helpers/IdGenerator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class DepartmentController {
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
                    d.*,
                    u.name as head_manager_name,
                    u.email as head_manager_email,
                    (SELECT COUNT(*) FROM users m WHERE m.department_id = d.id AND m.role = \'manager\') as manager_count,
                    (SELECT COUNT(*) FROM users e WHERE e.department_id = d.id AND e.role = \'employee\') as employee_count,
                    (SELECT COUNT(*) FROM teams t WHERE t.department_id = d.id) as team_count
                FROM departments d
                LEFT JOIN users u ON d.head_manager_id = u.id
                ORDER BY d.name ASC
            ');
            $departments = $stmt->fetchAll();
            foreach ($departments as &$dept) {
                $dept['manager_count'] = (int)$dept['manager_count'];
                $dept['employee_count'] = (int)$dept['employee_count'];
                $dept['team_count'] = (int)$dept['team_count'];
            }
            Response::json(['departments' => $departments]);
        } catch (Throwable $e) {
            Response::error('Failed to fetch departments', 500);
        }
    }

    public static function create(): void {
        $user = self::checkAdmin();
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $name = trim((string)($body['name'] ?? ''));
        $code = trim((string)($body['code'] ?? ''));
        $description = isset($body['description']) && $body['description'] !== '' ? (string)$body['description'] : null;
        $headManagerId = isset($body['head_manager_id']) && $body['head_manager_id'] !== '' ? (string)$body['head_manager_id'] : null;
        $status = (string)($body['status'] ?? 'active');

        if ($name === '' || $code === '') {
            Response::error('Department name and code are required', 400);
        }

        $stmt = $db->prepare('SELECT id FROM departments WHERE LOWER(name) = LOWER(?)');
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            Response::error('Department name already exists', 400);
        }

        $stmt = $db->prepare('SELECT id FROM departments WHERE LOWER(code) = LOWER(?)');
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            Response::error('Department code already exists', 400);
        }

        $id = IdGenerator::generateId('dept');

        $stmt = $db->prepare('
            INSERT INTO departments (id, name, code, description, head_manager_id, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$id, $name, strtoupper($code), $description, $headManagerId, $status]);

        AuthMiddleware::logAudit($db, $user, 'Department Created', 'Departments', $id, [
            'name' => $name,
            'code' => $code,
            'head_manager_id' => $headManagerId
        ]);

        Response::json(['message' => 'Department created successfully', 'id' => $id], 201);
    }

    public static function update(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $stmt = $db->prepare('SELECT * FROM departments WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Department not found', 404);
        }

        $name = isset($body['name']) ? trim((string)$body['name']) : null;
        $code = isset($body['code']) ? trim((string)$body['code']) : null;
        $description = array_key_exists('description', $body) ? ($body['description'] !== null && $body['description'] !== '' ? (string)$body['description'] : null) : $existing['description'];
        $headManagerId = array_key_exists('head_manager_id', $body) ? ($body['head_manager_id'] !== null && $body['head_manager_id'] !== '' ? (string)$body['head_manager_id'] : null) : $existing['head_manager_id'];
        $status = array_key_exists('status', $body) ? (string)$body['status'] : $existing['status'];

        if ($name !== null && strcasecmp($name, $existing['name']) !== 0) {
            $stmt = $db->prepare('SELECT id FROM departments WHERE LOWER(name) = LOWER(?) AND id != ?');
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                Response::error('Department name already in use', 400);
            }
        }

        if ($code !== null && strcasecmp($code, $existing['code']) !== 0) {
            $stmt = $db->prepare('SELECT id FROM departments WHERE LOWER(code) = LOWER(?) AND id != ?');
            $stmt->execute([$code, $id]);
            if ($stmt->fetch()) {
                Response::error('Department code already in use', 400);
            }
        }

        $finalName = $name !== null ? $name : $existing['name'];
        $finalCode = $code !== null ? strtoupper($code) : $existing['code'];

        $stmt = $db->prepare('
            UPDATE departments SET
                name = ?,
                code = ?,
                description = ?,
                head_manager_id = ?,
                status = ?
            WHERE id = ?
        ');
        $stmt->execute([$finalName, $finalCode, $description, $headManagerId, $status, $id]);

        AuthMiddleware::logAudit($db, $user, 'Department Updated', 'Departments', $id, [
            'name' => $finalName,
            'code' => $finalCode,
            'head_manager_id' => $headManagerId
        ]);

        Response::json(['message' => 'Department updated successfully']);
    }

    public static function delete(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT name FROM departments WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Department not found', 404);
        }

        $stmt = $db->prepare('DELETE FROM departments WHERE id = ?');
        $stmt->execute([$id]);

        AuthMiddleware::logAudit($db, $user, 'Department Deleted', 'Departments', $id, 'Deleted department ' . $existing['name']);

        Response::json(['message' => 'Department deleted successfully']);
    }
}
