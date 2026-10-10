<?php
// php-app/includes/auth.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

function get_bearer_token() {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!$header && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (preg_match('/Bearer\s(\S+)/i', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

function authenticate_token(): array {
    $token = get_bearer_token();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Access token required']);
        exit;
    }

    $decoded = jwt_decode($token, JWT_SECRET);
    if (!$decoded) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }

    return $decoded;
}

function require_admin(array $user): void {
    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Administrator access required']);
        exit;
    }
}

function require_employee_or_admin(array $user): void {
    $role = $user['role'] ?? '';
    if (!in_array($role, ['employee', 'admin', 'manager'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Authorized CRM access required']);
        exit;
    }
}

function require_manager_or_admin(array $user): void {
    $role = $user['role'] ?? '';
    if (!in_array($role, ['manager', 'admin'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Manager or Administrator access required']);
        exit;
    }
}

function require_advocate_or_admin(array $user): void {
    $role = $user['role'] ?? '';
    if (!in_array($role, ['advocate', 'admin'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Advocate or Administrator access required']);
        exit;
    }
}

function get_manager_scope(array $user): array {
    if ($user['role'] === 'admin') {
        return [
            'isAdmin'      => true,
            'managerId'    => null,
            'departmentId' => null,
            'managerType'  => 'ADMIN',
            'permissions'  => null,
            'employeeIds'  => []
        ];
    }

    $stmt = db()->prepare('
        SELECT u.*, mt.code as type_code, mt.name as type_name, mt.permissions_json, d.code as dept_code, d.name as dept_name
        FROM users u
        LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.id = ?
    ');
    $stmt->execute([$user['id']]);
    $managerUser = $stmt->fetch() ?: [];

    $stmtEmp = db()->prepare('SELECT id FROM users WHERE manager_id = ? OR department_id = ?');
    $stmtEmp->execute([$user['id'], $managerUser['department_id'] ?? null]);
    $employees = $stmtEmp->fetchAll();
    $employeeIds = array_column($employees, 'id');

    $permissions = [];
    if (!empty($managerUser['permissions_json'])) {
        $permissions = json_decode($managerUser['permissions_json'], true) ?: [];
    }

    return [
        'isAdmin'      => false,
        'managerId'    => $user['id'],
        'departmentId' => $managerUser['department_id'] ?? null,
        'managerType'  => strtoupper($managerUser['type_code'] ?? 'CUSTOM'),
        'permissions'  => $permissions,
        'employeeIds'  => $employeeIds
    ];
}

function get_advocate_scope(array $user): array {
    if ($user['role'] === 'admin') {
        return [
            'isAdmin'        => true,
            'advocateId'     => null,
            'advocateRecord' => null
        ];
    }

    $adv = null;
    if (!empty($user['emp_or_mgr_id'])) {
        $stmt = db()->prepare('SELECT * FROM advocates WHERE id = ? OR advocate_id = ?');
        $stmt->execute([$user['emp_or_mgr_id'], $user['emp_or_mgr_id']]);
        $adv = $stmt->fetch();
    }
    if (!$adv && !empty($user['email'])) {
        $stmt = db()->prepare('SELECT * FROM advocates WHERE LOWER(email) = LOWER(?)');
        $stmt->execute([$user['email']]);
        $adv = $stmt->fetch();
    }

    return [
        'isAdmin'        => false,
        'advocateId'     => $adv ? $adv['id'] : ($user['emp_or_mgr_id'] ?? $user['id']),
        'advocateRecord' => $adv ?: null
    ];
}

function log_audit($userOrReq, string $action, string $module, ?string $recordId = null, $details = null): void {
    try {
        $logId = 'aud-' . substr(bin2hex(random_bytes(8)), 0, 8);
        $userId = is_array($userOrReq) ? ($userOrReq['id'] ?? $userOrReq['user_id'] ?? 'system') : 'system';
        $userName = is_array($userOrReq) ? ($userOrReq['name'] ?? $userOrReq['user_name'] ?? 'System') : 'System';
        $role = is_array($userOrReq) ? ($userOrReq['role'] ?? 'admin') : 'admin';
        $detailsStr = is_array($details) ? json_encode($details) : (string)($details ?? '');
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'System/API';

        $stmt = db()->prepare('
            INSERT INTO audit_logs (id, user_id, user_name, role, action, module, record_id, details_json, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$logId, $userId, $userName, $role, $action, $module, (string)($recordId ?? ''), $detailsStr, $ip, $ua]);
    } catch (Exception $e) {
        error_log('Audit Log Error: ' . $e->getMessage());
    }
}
