<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../helpers/IdGenerator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ManagerController {
    private static function checkAdmin(): array {
        $config = require __DIR__ . '/../config/config.php';
        AuthMiddleware::authenticate($config['jwt_secret']);
        return AuthMiddleware::requireAdmin();
    }

    public static function index(): void {
        self::checkAdmin();
        $db = Database::getConnection();

        $search = isset($_GET['search']) && $_GET['search'] !== '' && $_GET['search'] !== 'undefined' && $_GET['search'] !== 'null' ? trim((string)$_GET['search']) : null;
        $department = isset($_GET['department']) && $_GET['department'] !== '' && $_GET['department'] !== 'undefined' && $_GET['department'] !== 'null' ? trim((string)$_GET['department']) : null;
        $type = isset($_GET['type']) && $_GET['type'] !== '' && $_GET['type'] !== 'undefined' && $_GET['type'] !== 'null' ? trim((string)$_GET['type']) : null;
        $status = isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== 'undefined' && $_GET['status'] !== 'null' ? trim((string)$_GET['status']) : null;

        $sql = "
            SELECT 
                u.id, u.name, u.email, u.phone, u.emp_or_mgr_id, u.role,
                u.department_id, u.manager_type_id, u.profile_image, 
                u.joining_date, u.id_type, u.id_front, u.id_back, u.status, u.created_at,
                d.name as department_name, d.code as department_code,
                mt.name as manager_type_name, mt.code as manager_type_code,
                (SELECT COUNT(*) FROM users e WHERE e.manager_id = u.id AND e.role = 'employee') as employee_count,
                (SELECT COUNT(*) FROM leads l WHERE l.manager_id = u.id) as lead_count,
                (SELECT COUNT(*) FROM clients c WHERE c.manager_id = u.id) as client_count
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
            WHERE u.role = 'manager'
        ";
        $params = [];

        if ($search !== null) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.emp_or_mgr_id LIKE ?)";
            $s = "%{$search}%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if ($department !== null) {
            $sql .= " AND u.department_id = ?";
            $params[] = $department;
        }

        if ($type !== null) {
            $sql .= " AND u.manager_type_id = ?";
            $params[] = $type;
        }

        if ($status !== null) {
            $sql .= " AND u.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY u.created_at DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $managers = $stmt->fetchAll();
            foreach ($managers as &$m) {
                $m['employee_count'] = (int)$m['employee_count'];
                $m['lead_count'] = (int)$m['lead_count'];
                $m['client_count'] = (int)$m['client_count'];
            }
            Response::json(['managers' => $managers]);
        } catch (Throwable $e) {
            Response::error('Failed to fetch managers', 500);
        }
    }

    public static function show(array $params): void {
        self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT 
                u.id, u.name, u.email, u.phone, u.emp_or_mgr_id, u.role,
                u.department_id, u.manager_type_id, u.profile_image, 
                u.joining_date, u.id_type, u.id_front, u.id_back, u.status, u.created_at,
                d.name as department_name, mt.name as manager_type_name, mt.permissions_json
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
            WHERE u.id = ? AND u.role = 'manager'
        ");
        $stmt->execute([$id]);
        $manager = $stmt->fetch();
        if (!$manager) {
            Response::error('Manager not found', 404);
        }

        $permStmt = $db->prepare('SELECT * FROM user_permissions WHERE user_id = ?');
        $permStmt->execute([$id]);
        $permissions = $permStmt->fetchAll();

        $empStmt = $db->prepare("
            SELECT id, name, email, phone, emp_or_mgr_id, status, profile_image 
            FROM users 
            WHERE manager_id = ? AND role = 'employee'
        ");
        $empStmt->execute([$id]);
        $assignedEmployees = $empStmt->fetchAll();

        Response::json([
            'manager' => $manager,
            'permissions' => $permissions,
            'assignedEmployees' => $assignedEmployees
        ]);
    }

    public static function create(): void {
        $user = self::checkAdmin();
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $name = trim((string)($body['name'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $phone = isset($body['phone']) && $body['phone'] !== '' ? (string)$body['phone'] : null;
        $empOrMgrId = trim((string)($body['emp_or_mgr_id'] ?? ''));
        $password = (string)($body['password'] ?? '');
        $joiningDate = isset($body['joining_date']) && $body['joining_date'] !== '' ? (string)$body['joining_date'] : date('Y-m-d');
        $managerTypeId = isset($body['manager_type_id']) && $body['manager_type_id'] !== '' ? (string)$body['manager_type_id'] : null;
        $departmentId = isset($body['department_id']) && $body['department_id'] !== '' ? (string)$body['department_id'] : null;
        $status = (string)($body['status'] ?? 'active');
        $profileImage = isset($body['profile_image']) && $body['profile_image'] !== '' ? (string)$body['profile_image'] : null;
        $idType = isset($body['id_type']) && $body['id_type'] !== '' ? (string)$body['id_type'] : null;
        $idFront = isset($body['id_front']) && $body['id_front'] !== '' ? (string)$body['id_front'] : null;
        $idBack = isset($body['id_back']) && $body['id_back'] !== '' ? (string)$body['id_back'] : null;
        $permissions = $body['permissions'] ?? null;

        if ($name === '' || $email === '' || $password === '' || $empOrMgrId === '') {
            Response::error('Name, email, password, and Manager ID are required', 400);
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?)');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Response::error('A user with this email already exists', 400);
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE emp_or_mgr_id = ?');
        $stmt->execute([$empOrMgrId]);
        if ($stmt->fetch()) {
            Response::error('A user with this Manager ID already exists', 400);
        }

        $id = IdGenerator::generateId('mgr');
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        $stmt = $db->prepare("
            INSERT INTO users (
                id, name, email, phone, password_hash, role, manager_type_id,
                department_id, emp_or_mgr_id, profile_image, joining_date,
                id_type, id_front, id_back, status
            ) VALUES (?, ?, ?, ?, ?, 'manager', ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $name,
            strtolower($email),
            $phone,
            $passwordHash,
            $managerTypeId,
            $departmentId,
            strtoupper($empOrMgrId),
            $profileImage,
            $joiningDate,
            $idType,
            $idFront,
            $idBack,
            $status
        ]);

        if (is_array($permissions) && !empty($permissions)) {
            $permStmt = $db->prepare('
                INSERT INTO user_permissions (id, user_id, module, can_view, can_create, can_edit, can_delete, can_assign, can_verify)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($permissions as $p) {
                $permStmt->execute([
                    IdGenerator::generateId('perm'),
                    $id,
                    $p['module'] ?? '',
                    !empty($p['can_view']) ? 1 : 0,
                    !empty($p['can_create']) ? 1 : 0,
                    !empty($p['can_edit']) ? 1 : 0,
                    !empty($p['can_delete']) ? 1 : 0,
                    !empty($p['can_assign']) ? 1 : 0,
                    !empty($p['can_verify']) ? 1 : 0
                ]);
            }
        }

        AuthMiddleware::logAudit($db, $user, 'Manager Created', 'Managers', $id, [
            'name' => $name,
            'email' => $email,
            'emp_or_mgr_id' => $empOrMgrId,
            'department_id' => $departmentId,
            'manager_type_id' => $managerTypeId
        ]);

        Response::json(['message' => 'Manager created successfully', 'id' => $id], 201);
    }

    public static function update(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'manager'");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Manager not found', 404);
        }

        $email = isset($body['email']) ? trim((string)$body['email']) : null;
        if ($email !== null && strcasecmp($email, $existing['email']) !== 0) {
            $stmt = $db->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                Response::error('Email is already used by another account', 400);
            }
        }

        $empOrMgrId = isset($body['emp_or_mgr_id']) ? trim((string)$body['emp_or_mgr_id']) : null;
        if ($empOrMgrId !== null && strcasecmp($empOrMgrId, (string)$existing['emp_or_mgr_id']) !== 0) {
            $stmt = $db->prepare('SELECT id FROM users WHERE emp_or_mgr_id = ? AND id != ?');
            $stmt->execute([$empOrMgrId, $id]);
            if ($stmt->fetch()) {
                Response::error('Manager ID is already in use', 400);
            }
        }

        $name = isset($body['name']) ? trim((string)$body['name']) : $existing['name'];
        $finalEmail = $email !== null ? strtolower($email) : $existing['email'];
        $phone = array_key_exists('phone', $body) ? ($body['phone'] !== null && $body['phone'] !== '' ? (string)$body['phone'] : null) : $existing['phone'];
        $finalEmpOrMgrId = $empOrMgrId !== null ? strtoupper($empOrMgrId) : $existing['emp_or_mgr_id'];
        $joiningDate = array_key_exists('joining_date', $body) ? ($body['joining_date'] !== null && $body['joining_date'] !== '' ? (string)$body['joining_date'] : null) : $existing['joining_date'];
        $managerTypeId = array_key_exists('manager_type_id', $body) ? ($body['manager_type_id'] !== null && $body['manager_type_id'] !== '' ? (string)$body['manager_type_id'] : null) : $existing['manager_type_id'];
        $departmentId = array_key_exists('department_id', $body) ? ($body['department_id'] !== null && $body['department_id'] !== '' ? (string)$body['department_id'] : null) : $existing['department_id'];
        $status = array_key_exists('status', $body) ? (string)$body['status'] : $existing['status'];
        $profileImage = array_key_exists('profile_image', $body) ? ($body['profile_image'] !== null && $body['profile_image'] !== '' ? (string)$body['profile_image'] : null) : $existing['profile_image'];
        $idType = array_key_exists('id_type', $body) ? ($body['id_type'] !== null && $body['id_type'] !== '' ? (string)$body['id_type'] : null) : $existing['id_type'];
        $idFront = array_key_exists('id_front', $body) ? ($body['id_front'] !== null && $body['id_front'] !== '' ? (string)$body['id_front'] : null) : $existing['id_front'];
        $idBack = array_key_exists('id_back', $body) ? ($body['id_back'] !== null && $body['id_back'] !== '' ? (string)$body['id_back'] : null) : $existing['id_back'];
        $permissions = $body['permissions'] ?? null;

        $stmt = $db->prepare('
            UPDATE users SET
                name = ?,
                email = ?,
                phone = ?,
                emp_or_mgr_id = ?,
                joining_date = ?,
                manager_type_id = ?,
                department_id = ?,
                status = ?,
                profile_image = ?,
                id_type = ?,
                id_front = ?,
                id_back = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([
            $name,
            $finalEmail,
            $phone,
            $finalEmpOrMgrId,
            $joiningDate,
            $managerTypeId,
            $departmentId,
            $status,
            $profileImage,
            $idType,
            $idFront,
            $idBack,
            $id
        ]);

        if (is_array($permissions)) {
            $delStmt = $db->prepare('DELETE FROM user_permissions WHERE user_id = ?');
            $delStmt->execute([$id]);

            $permStmt = $db->prepare('
                INSERT INTO user_permissions (id, user_id, module, can_view, can_create, can_edit, can_delete, can_assign, can_verify)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($permissions as $p) {
                $permStmt->execute([
                    IdGenerator::generateId('perm'),
                    $id,
                    $p['module'] ?? '',
                    !empty($p['can_view']) ? 1 : 0,
                    !empty($p['can_create']) ? 1 : 0,
                    !empty($p['can_edit']) ? 1 : 0,
                    !empty($p['can_delete']) ? 1 : 0,
                    !empty($p['can_assign']) ? 1 : 0,
                    !empty($p['can_verify']) ? 1 : 0
                ]);
            }
        }

        AuthMiddleware::logAudit($db, $user, 'Manager Updated', 'Managers', $id, [
            'name' => $name,
            'email' => $finalEmail,
            'department_id' => $departmentId,
            'manager_type_id' => $managerTypeId
        ]);

        Response::json(['message' => 'Manager updated successfully']);
    }

    public static function toggleStatus(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT status, name FROM users WHERE id = ? AND role = 'manager'");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            Response::error('Manager not found', 404);
        }

        $newStatus = $targetUser['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $db->prepare('UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$newStatus, $id]);

        $action = $newStatus === 'active' ? 'User Activated' : 'User Deactivated';
        AuthMiddleware::logAudit($db, $user, $action, 'Managers', $id, "Manager {$targetUser['name']} status changed to {$newStatus}");

        Response::json([
            'message' => "Manager status changed to {$newStatus}",
            'status' => $newStatus
        ]);
    }

    public static function resetPassword(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $newPassword = (string)($body['new_password'] ?? '');
        if ($newPassword === '' || strlen($newPassword) < 6) {
            Response::error('Password must be at least 6 characters', 400);
        }

        $stmt = $db->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'manager'");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            Response::error('Manager not found', 404);
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt = $db->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$hash, $id]);

        AuthMiddleware::logAudit($db, $user, 'Manager Password Reset', 'Managers', $id, "Password reset performed for {$targetUser['name']}");

        Response::json(['message' => 'Password reset successfully']);
    }
}
