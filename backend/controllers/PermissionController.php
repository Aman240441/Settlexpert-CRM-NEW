<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../helpers/IdGenerator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class PermissionController {
    private const MODULE_LIST = [
        'Managers',
        'Employees',
        'Leads',
        'Clients',
        'Agreements',
        'Advocates',
        'Finance',
        'Payments',
        'Tasks',
        'Documents',
        'Reports',
        'Audit'
    ];

    private static function checkAdmin(): array {
        $config = require __DIR__ . '/../config/config.php';
        AuthMiddleware::authenticate($config['jwt_secret']);
        return AuthMiddleware::requireAdmin();
    }

    public static function modules(): void {
        self::checkAdmin();
        Response::json(['modules' => self::MODULE_LIST]);
    }

    public static function show(array $params): void {
        self::checkAdmin();
        $userId = $params['userId'] ?? $params['id'] ?? '';
        $db = Database::getConnection();

        try {
            $stmt = $db->prepare('
                SELECT u.id, u.name, u.email, u.role, u.manager_type_id, mt.permissions_json as default_perms
                FROM users u
                LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
                WHERE u.id = ?
            ');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                Response::error('User not found', 404);
            }

            $permStmt = $db->prepare('SELECT * FROM user_permissions WHERE user_id = ?');
            $permStmt->execute([$userId]);
            $explicitPerms = $permStmt->fetchAll();

            $defObj = [];
            if (!empty($user['default_perms'])) {
                $decoded = json_decode($user['default_perms'], true);
                if (is_array($decoded)) {
                    $defObj = $decoded;
                }
            }

            $matrix = [];
            foreach (self::MODULE_LIST as $mod) {
                $found = null;
                foreach ($explicitPerms as $p) {
                    if (strcasecmp($p['module'], $mod) === 0) {
                        $found = $p;
                        break;
                    }
                }

                if ($found !== null) {
                    $matrix[] = [
                        'module' => $mod,
                        'can_view' => (bool)$found['can_view'],
                        'can_create' => (bool)$found['can_create'],
                        'can_edit' => (bool)$found['can_edit'],
                        'can_delete' => (bool)$found['can_delete'],
                        'can_assign' => (bool)$found['can_assign'],
                        'can_verify' => (bool)$found['can_verify']
                    ];
                    continue;
                }

                $key = strtolower($mod);
                if (isset($defObj[$key]) && is_array($defObj[$key])) {
                    $matrix[] = [
                        'module' => $mod,
                        'can_view' => (bool)($defObj[$key]['view'] ?? false),
                        'can_create' => (bool)($defObj[$key]['create'] ?? false),
                        'can_edit' => (bool)($defObj[$key]['edit'] ?? false),
                        'can_delete' => (bool)($defObj[$key]['delete'] ?? false),
                        'can_assign' => (bool)($defObj[$key]['assign'] ?? false),
                        'can_verify' => (bool)($defObj[$key]['verify'] ?? false)
                    ];
                    continue;
                }

                $matrix[] = [
                    'module' => $mod,
                    'can_view' => false,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                    'can_assign' => false,
                    'can_verify' => false
                ];
            }

            Response::json(['user' => $user, 'matrix' => $matrix]);
        } catch (Throwable $e) {
            Response::error('Failed to fetch user permissions', 500);
        }
    }

    public static function update(array $params): void {
        $adminUser = self::checkAdmin();
        $userId = $params['userId'] ?? $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $permissions = $body['permissions'] ?? null;
        if (!is_array($permissions)) {
            Response::error('Permissions must be an array', 400);
        }

        $stmt = $db->prepare('SELECT id, name FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            Response::error('User not found', 404);
        }

        try {
            $delStmt = $db->prepare('DELETE FROM user_permissions WHERE user_id = ?');
            $delStmt->execute([$userId]);

            $insStmt = $db->prepare('
                INSERT INTO user_permissions (id, user_id, module, can_view, can_create, can_edit, can_delete, can_assign, can_verify)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');

            foreach ($permissions as $p) {
                $insStmt->execute([
                    IdGenerator::generateId('perm'),
                    $userId,
                    $p['module'] ?? '',
                    !empty($p['can_view']) ? 1 : 0,
                    !empty($p['can_create']) ? 1 : 0,
                    !empty($p['can_edit']) ? 1 : 0,
                    !empty($p['can_delete']) ? 1 : 0,
                    !empty($p['can_assign']) ? 1 : 0,
                    !empty($p['can_verify']) ? 1 : 0
                ]);
            }

            AuthMiddleware::logAudit($db, $adminUser, 'Permission Changed', 'Roles & Permissions', $userId, "Updated module permission matrix for {$targetUser['name']}");

            Response::json(['message' => 'Permissions updated successfully']);
        } catch (Throwable $e) {
            Response::error('Failed to save permissions', 500);
        }
    }
}
