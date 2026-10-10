<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Jwt.php';
require_once __DIR__ . '/../helpers/IdGenerator.php';

class AuthMiddleware {
    private static ?array $currentUser = null;

    public static function getBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$header && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (!$header && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if ($header === '') {
            return null;
        }

        $parts = explode(' ', $header, 2);
        if (count($parts) < 2 || trim($parts[1]) === '') {
            return null;
        }
        return trim($parts[1]);
    }

    public static function authenticate(string $jwtSecret): array {
        $token = self::getBearerToken();
        if (!$token) {
            Response::error('Access token required', 401);
        }

        $user = Jwt::decode($token, $jwtSecret);
        if (!$user) {
            Response::error('Invalid or expired token', 403);
        }

        self::$currentUser = $user;
        return $user;
    }

    public static function getUser(): ?array {
        return self::$currentUser;
    }

    public static function requireAdmin(): array {
        $user = self::$currentUser;
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            Response::error('Forbidden: Administrator access required', 403);
        }
        return $user;
    }

    public static function requireEmployeeOrAdmin(): array {
        $user = self::$currentUser;
        $role = $user['role'] ?? '';
        if (!$user || !in_array($role, ['employee', 'admin', 'manager'], true)) {
            Response::error('Forbidden: Authorized CRM access required', 403);
        }
        return $user;
    }

    public static function requireManagerOrAdmin(): array {
        $user = self::$currentUser;
        $role = $user['role'] ?? '';
        if (!$user || !in_array($role, ['manager', 'admin'], true)) {
            Response::error('Forbidden: Manager or Administrator access required', 403);
        }
        return $user;
    }

    public static function requireAdvocateOrAdmin(): array {
        $user = self::$currentUser;
        $role = $user['role'] ?? '';
        if (!$user || !in_array($role, ['advocate', 'admin'], true)) {
            Response::error('Forbidden: Advocate or Administrator access required', 403);
        }
        return $user;
    }

    public static function getManagerScope(PDO $db, array $user): array {
        if (($user['role'] ?? '') === 'admin') {
            return [
                'isAdmin' => true,
                'managerId' => null,
                'departmentId' => null,
                'managerType' => 'ADMIN',
                'permissions' => null,
                'employeeIds' => []
            ];
        }

        $stmt = $db->prepare('
            SELECT u.*, mt.code as type_code, mt.name as type_name, mt.permissions_json, d.code as dept_code, d.name as dept_name
            FROM users u
            LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.id = ?
        ');
        $stmt->execute([$user['id']]);
        $managerUser = $stmt->fetch();

        $deptId = $managerUser['department_id'] ?? null;
        $empStmt = $db->prepare('SELECT id FROM users WHERE manager_id = ? OR (department_id IS NOT NULL AND department_id = ?)');
        $empStmt->execute([$user['id'], $deptId]);
        $employees = $empStmt->fetchAll();
        $employeeIds = array_column($employees, 'id');

        $permissions = [];
        if (!empty($managerUser['permissions_json'])) {
            $decoded = json_decode($managerUser['permissions_json'], true);
            if (is_array($decoded)) {
                $permissions = $decoded;
            }
        }

        return [
            'isAdmin' => false,
            'managerId' => $user['id'],
            'departmentId' => $deptId,
            'managerType' => strtoupper($managerUser['type_code'] ?? 'CUSTOM'),
            'permissions' => $permissions,
            'employeeIds' => $employeeIds
        ];
    }

    public static function getAdvocateScope(PDO $db, array $user): array {
        if (($user['role'] ?? '') === 'admin') {
            return [
                'isAdmin' => true,
                'advocateId' => null,
                'advocateRecord' => null
            ];
        }

        $adv = null;
        $empOrMgrId = $user['emp_or_mgr_id'] ?? null;
        if ($empOrMgrId) {
            $stmt = $db->prepare('SELECT * FROM advocates WHERE id = ? OR advocate_id = ?');
            $stmt->execute([$empOrMgrId, $empOrMgrId]);
            $adv = $stmt->fetch();
        }

        if (!$adv && !empty($user['email'])) {
            $stmt = $db->prepare('SELECT * FROM advocates WHERE LOWER(email) = LOWER(?)');
            $stmt->execute([$user['email']]);
            $adv = $stmt->fetch();
        }

        return [
            'isAdmin' => false,
            'advocateId' => $adv ? $adv['id'] : ($empOrMgrId ?: $user['id']),
            'advocateRecord' => $adv
        ];
    }

    public static function logAudit(PDO $db, ?array $user, string $action, string $module, string $recordId, mixed $details): void {
        try {
            $logId = IdGenerator::generateId('aud');
            $userId = $user['id'] ?? 'system';
            $userName = $user['name'] ?? 'System';
            $role = $user['role'] ?? 'admin';
            $detailsStr = is_array($details) || is_object($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : (string)($details ?? '');
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ipParts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ipAddress = trim($ipParts[0]);
            }
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'System/API';

            $stmt = $db->prepare('
                INSERT INTO audit_logs (id, user_id, user_name, role, action, module, record_id, details_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$logId, $userId, $userName, $role, $action, $module, (string)$recordId, $detailsStr, $ipAddress, $userAgent]);
        } catch (Throwable $e) {
            error_log('Audit Log Error: ' . $e->getMessage());
        }
    }
}
