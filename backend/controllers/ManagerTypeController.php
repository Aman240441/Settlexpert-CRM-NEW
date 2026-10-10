<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../helpers/IdGenerator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ManagerTypeController {
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
                    mt.*,
                    (SELECT COUNT(*) FROM users u WHERE u.manager_type_id = mt.id AND u.role = \'manager\') as manager_count
                FROM manager_types mt
                ORDER BY mt.name ASC
            ');
            $types = $stmt->fetchAll();
            foreach ($types as &$t) {
                $t['manager_count'] = (int)$t['manager_count'];
            }
            Response::json(['types' => $types]);
        } catch (Throwable $e) {
            Response::error('Failed to fetch manager types', 500);
        }
    }

    public static function create(): void {
        $user = self::checkAdmin();
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $name = trim((string)($body['name'] ?? ''));
        $code = trim((string)($body['code'] ?? ''));
        $description = isset($body['description']) && $body['description'] !== '' ? (string)$body['description'] : null;
        $permissions = $body['permissions'] ?? null;
        $status = (string)($body['status'] ?? 'active');

        if ($name === '' || $code === '') {
            Response::error('Name and Code are required', 400);
        }

        $stmt = $db->prepare('SELECT id FROM manager_types WHERE LOWER(name) = LOWER(?)');
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            Response::error('Manager type name already exists', 400);
        }

        $stmt = $db->prepare('SELECT id FROM manager_types WHERE LOWER(code) = LOWER(?)');
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            Response::error('Manager type code already exists', 400);
        }

        $id = IdGenerator::generateId('mt');
        $permsJson = is_array($permissions) || is_object($permissions) ? json_encode($permissions, JSON_UNESCAPED_UNICODE) : '{}';

        $stmt = $db->prepare('
            INSERT INTO manager_types (id, name, code, description, permissions_json, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$id, $name, strtoupper($code), $description, $permsJson, $status]);

        AuthMiddleware::logAudit($db, $user, 'Manager Type Created', 'Manager Types', $id, [
            'name' => $name,
            'code' => $code
        ]);

        Response::json(['message' => 'Manager type created successfully', 'id' => $id], 201);
    }

    public static function update(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $stmt = $db->prepare('SELECT * FROM manager_types WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Manager type not found', 404);
        }

        $name = isset($body['name']) ? trim((string)$body['name']) : null;
        $code = isset($body['code']) ? trim((string)$body['code']) : null;
        $description = array_key_exists('description', $body) ? ($body['description'] !== null && $body['description'] !== '' ? (string)$body['description'] : null) : $existing['description'];
        $permissions = $body['permissions'] ?? null;
        $status = array_key_exists('status', $body) ? (string)$body['status'] : $existing['status'];

        if ($name !== null && strcasecmp($name, $existing['name']) !== 0) {
            $stmt = $db->prepare('SELECT id FROM manager_types WHERE LOWER(name) = LOWER(?) AND id != ?');
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                Response::error('Manager type name already in use', 400);
            }
        }

        if ($code !== null && strcasecmp($code, $existing['code']) !== 0) {
            $stmt = $db->prepare('SELECT id FROM manager_types WHERE LOWER(code) = LOWER(?) AND id != ?');
            $stmt->execute([$code, $id]);
            if ($stmt->fetch()) {
                Response::error('Manager type code already in use', 400);
            }
        }

        $permsJson = $existing['permissions_json'];
        if ($permissions !== null) {
            $permsJson = is_array($permissions) || is_object($permissions) ? json_encode($permissions, JSON_UNESCAPED_UNICODE) : (string)$permissions;
        }

        $finalName = $name !== null ? $name : $existing['name'];
        $finalCode = $code !== null ? strtoupper($code) : $existing['code'];

        $stmt = $db->prepare('
            UPDATE manager_types SET
                name = ?,
                code = ?,
                description = ?,
                permissions_json = ?,
                status = ?
            WHERE id = ?
        ');
        $stmt->execute([$finalName, $finalCode, $description, $permsJson, $status, $id]);

        AuthMiddleware::logAudit($db, $user, 'Manager Type Updated', 'Manager Types', $id, [
            'name' => $finalName,
            'code' => $finalCode
        ]);

        Response::json(['message' => 'Manager type updated successfully']);
    }

    public static function delete(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT name FROM manager_types WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Manager type not found', 404);
        }

        $stmt = $db->prepare('DELETE FROM manager_types WHERE id = ?');
        $stmt->execute([$id]);

        AuthMiddleware::logAudit($db, $user, 'Manager Type Deleted', 'Manager Types', $id, 'Deleted manager type ' . $existing['name']);

        Response::json(['message' => 'Manager type deleted successfully']);
    }
}
