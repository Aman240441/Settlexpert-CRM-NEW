<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../helpers/IdGenerator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class EmployeeController {
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
        $manager = isset($_GET['manager']) && $_GET['manager'] !== '' && $_GET['manager'] !== 'undefined' && $_GET['manager'] !== 'null' ? trim((string)$_GET['manager']) : null;
        $status = isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== 'undefined' && $_GET['status'] !== 'null' ? trim((string)$_GET['status']) : null;
        $team = isset($_GET['team']) && $_GET['team'] !== '' && $_GET['team'] !== 'undefined' && $_GET['team'] !== 'null' ? trim((string)$_GET['team']) : null;

        $sql = "
            SELECT 
                u.id, u.name, u.email, u.phone, u.emp_or_mgr_id, u.role,
                u.department_id, u.manager_id, u.team_id, u.profile_image, 
                u.joining_date, u.id_type, u.id_front, u.id_back, u.status, u.created_at,
                d.name as department_name, d.code as department_code,
                m.name as manager_name, m.emp_or_mgr_id as manager_code,
                t.name as team_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN users m ON u.manager_id = m.id
            LEFT JOIN teams t ON u.team_id = t.id
            WHERE u.role = 'employee'
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

        if ($manager !== null) {
            $sql .= " AND u.manager_id = ?";
            $params[] = $manager;
        }

        if ($team !== null) {
            $sql .= " AND u.team_id = ?";
            $params[] = $team;
        }

        if ($status !== null) {
            $sql .= " AND u.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY u.created_at DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $employees = $stmt->fetchAll();
            Response::json(['employees' => $employees]);
        } catch (Throwable $e) {
            Response::error('Failed to fetch employees', 500);
        }
    }

    public static function show(array $params): void {
        self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT 
                u.id, u.name, u.email, u.phone, u.emp_or_mgr_id, u.role,
                u.department_id, u.manager_id, u.team_id, u.profile_image, 
                u.joining_date, u.id_type, u.id_front, u.id_back, u.status, u.created_at,
                d.name as department_name,
                m.name as manager_name, m.emp_or_mgr_id as manager_code,
                t.name as team_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN users m ON u.manager_id = m.id
            LEFT JOIN teams t ON u.team_id = t.id
            WHERE u.id = ? AND u.role = 'employee'
        ");
        $stmt->execute([$id]);
        $employee = $stmt->fetch();
        if (!$employee) {
            Response::error('Employee not found', 404);
        }

        Response::json(['employee' => $employee]);
    }

    public static function create(): void {
        $user = self::checkAdmin();
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $name = trim((string)($body['name'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $phone = isset($body['phone']) && $body['phone'] !== '' ? (string)$body['phone'] : null;
        $empOrMgrId = trim((string)($body['emp_or_mgr_id'] ?? ''));
        $password = (string)($body['password'] ?? 'Employee@123456');
        $joiningDate = isset($body['joining_date']) && $body['joining_date'] !== '' ? (string)$body['joining_date'] : date('Y-m-d');
        $departmentId = isset($body['department_id']) && $body['department_id'] !== '' ? (string)$body['department_id'] : null;
        $managerId = isset($body['manager_id']) && $body['manager_id'] !== '' ? (string)$body['manager_id'] : null;
        $teamId = isset($body['team_id']) && $body['team_id'] !== '' ? (string)$body['team_id'] : null;
        $status = (string)($body['status'] ?? 'active');
        $profileImage = isset($body['profile_image']) && $body['profile_image'] !== '' ? (string)$body['profile_image'] : null;
        $idType = isset($body['id_type']) && $body['id_type'] !== '' ? (string)$body['id_type'] : null;
        $idFront = isset($body['id_front']) && $body['id_front'] !== '' ? (string)$body['id_front'] : null;
        $idBack = isset($body['id_back']) && $body['id_back'] !== '' ? (string)$body['id_back'] : null;

        if ($name === '' || $email === '' || $empOrMgrId === '') {
            Response::error('Name, email, and Employee ID are required', 400);
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?)');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Response::error('A user with this email already exists', 400);
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE emp_or_mgr_id = ?');
        $stmt->execute([$empOrMgrId]);
        if ($stmt->fetch()) {
            Response::error('A user with this Employee ID already exists', 400);
        }

        $id = IdGenerator::generateId('emp');
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        $stmt = $db->prepare("
            INSERT INTO users (
                id, name, email, phone, password_hash, role,
                department_id, manager_id, team_id, emp_or_mgr_id,
                profile_image, joining_date, id_type, id_front, id_back, status
            ) VALUES (?, ?, ?, ?, ?, 'employee', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $name,
            strtolower($email),
            $phone,
            $passwordHash,
            $departmentId,
            $managerId,
            $teamId,
            strtoupper($empOrMgrId),
            $profileImage,
            $joiningDate,
            $idType,
            $idFront,
            $idBack,
            $status
        ]);

        AuthMiddleware::logAudit($db, $user, 'Employee Created', 'Employees', $id, [
            'name' => $name,
            'email' => $email,
            'emp_or_mgr_id' => $empOrMgrId,
            'department_id' => $departmentId,
            'manager_id' => $managerId
        ]);

        Response::json(['message' => 'Employee created successfully', 'id' => $id], 201);
    }

    public static function update(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'employee'");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Employee not found', 404);
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
                Response::error('Employee ID is already in use', 400);
            }
        }

        $name = isset($body['name']) ? trim((string)$body['name']) : $existing['name'];
        $finalEmail = $email !== null ? strtolower($email) : $existing['email'];
        $phone = array_key_exists('phone', $body) ? ($body['phone'] !== null && $body['phone'] !== '' ? (string)$body['phone'] : null) : $existing['phone'];
        $finalEmpOrMgrId = $empOrMgrId !== null ? strtoupper($empOrMgrId) : $existing['emp_or_mgr_id'];
        $joiningDate = array_key_exists('joining_date', $body) ? ($body['joining_date'] !== null && $body['joining_date'] !== '' ? (string)$body['joining_date'] : null) : $existing['joining_date'];
        $departmentId = array_key_exists('department_id', $body) ? ($body['department_id'] !== null && $body['department_id'] !== '' ? (string)$body['department_id'] : null) : $existing['department_id'];
        $managerId = array_key_exists('manager_id', $body) ? ($body['manager_id'] !== null && $body['manager_id'] !== '' ? (string)$body['manager_id'] : null) : $existing['manager_id'];
        $teamId = array_key_exists('team_id', $body) ? ($body['team_id'] !== null && $body['team_id'] !== '' ? (string)$body['team_id'] : null) : $existing['team_id'];
        $status = array_key_exists('status', $body) ? (string)$body['status'] : $existing['status'];
        $profileImage = array_key_exists('profile_image', $body) ? ($body['profile_image'] !== null && $body['profile_image'] !== '' ? (string)$body['profile_image'] : null) : $existing['profile_image'];
        $idType = array_key_exists('id_type', $body) ? ($body['id_type'] !== null && $body['id_type'] !== '' ? (string)$body['id_type'] : null) : $existing['id_type'];
        $idFront = array_key_exists('id_front', $body) ? ($body['id_front'] !== null && $body['id_front'] !== '' ? (string)$body['id_front'] : null) : $existing['id_front'];
        $idBack = array_key_exists('id_back', $body) ? ($body['id_back'] !== null && $body['id_back'] !== '' ? (string)$body['id_back'] : null) : $existing['id_back'];

        $stmt = $db->prepare('
            UPDATE users SET
                name = ?,
                email = ?,
                phone = ?,
                emp_or_mgr_id = ?,
                joining_date = ?,
                department_id = ?,
                manager_id = ?,
                team_id = ?,
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
            $departmentId,
            $managerId,
            $teamId,
            $status,
            $profileImage,
            $idType,
            $idFront,
            $idBack,
            $id
        ]);

        AuthMiddleware::logAudit($db, $user, 'Employee Updated', 'Employees', $id, [
            'name' => $name,
            'email' => $finalEmail,
            'department_id' => $departmentId,
            'manager_id' => $managerId
        ]);

        Response::json(['message' => 'Employee updated successfully']);
    }

    public static function assignManager(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();
        $managerId = isset($body['manager_id']) && $body['manager_id'] !== '' ? (string)$body['manager_id'] : null;

        $stmt = $db->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'employee'");
        $stmt->execute([$id]);
        $employee = $stmt->fetch();
        if (!$employee) {
            Response::error('Employee not found', 404);
        }

        $managerName = 'Unassigned';
        if ($managerId !== null) {
            $mStmt = $db->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'manager'");
            $mStmt->execute([$managerId]);
            $mgr = $mStmt->fetch();
            if (!$mgr) {
                Response::error('Invalid Manager selected', 400);
            }
            $managerName = $mgr['name'];
        }

        $upStmt = $db->prepare('UPDATE users SET manager_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $upStmt->execute([$managerId, $id]);

        AuthMiddleware::logAudit($db, $user, 'Employee Assigned', 'Employees', $id, "Assigned employee {$employee['name']} to manager {$managerName}");

        Response::json(['message' => "Employee assigned to {$managerName} successfully"]);
    }

    public static function transferManager(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $newManagerId = (string)($body['new_manager_id'] ?? '');
        $newDeptId = isset($body['new_department_id']) && $body['new_department_id'] !== '' ? (string)$body['new_department_id'] : null;
        $newTeamId = isset($body['new_team_id']) && $body['new_team_id'] !== '' ? (string)$body['new_team_id'] : null;
        $transferReason = isset($body['transfer_reason']) && $body['transfer_reason'] !== '' ? (string)$body['transfer_reason'] : 'Administrative transfer';

        $stmt = $db->prepare("SELECT id, name, manager_id FROM users WHERE id = ? AND role = 'employee'");
        $stmt->execute([$id]);
        $employee = $stmt->fetch();
        if (!$employee) {
            Response::error('Employee not found', 404);
        }

        $mStmt = $db->prepare("SELECT id, name, department_id FROM users WHERE id = ? AND role = 'manager'");
        $mStmt->execute([$newManagerId]);
        $newMgr = $mStmt->fetch();
        if (!$newMgr) {
            Response::error('Target manager not found', 400);
        }

        $deptId = $newDeptId !== null ? $newDeptId : $newMgr['department_id'];

        $upStmt = $db->prepare('
            UPDATE users 
            SET manager_id = ?, department_id = ?, team_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $upStmt->execute([$newManagerId, $deptId, $newTeamId, $id]);

        AuthMiddleware::logAudit($db, $user, 'Employee Transferred', 'Employees', $id, [
            'employee' => $employee['name'],
            'new_manager' => $newMgr['name'],
            'reason' => $transferReason
        ]);

        Response::json(['message' => "Employee successfully transferred to {$newMgr['name']}"]);
    }

    public static function toggleStatus(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT status, name FROM users WHERE id = ? AND role = 'employee'");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            Response::error('Employee not found', 404);
        }

        $newStatus = $targetUser['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $db->prepare('UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$newStatus, $id]);

        $action = $newStatus === 'active' ? 'User Activated' : 'User Deactivated';
        AuthMiddleware::logAudit($db, $user, $action, 'Employees', $id, "Employee {$targetUser['name']} status changed to {$newStatus}");

        Response::json([
            'message' => "Employee status changed to {$newStatus}",
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

        $stmt = $db->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'employee'");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            Response::error('Employee not found', 404);
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt = $db->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$hash, $id]);

        AuthMiddleware::logAudit($db, $user, 'Employee Password Reset', 'Employees', $id, "Password reset for employee {$targetUser['name']}");

        Response::json(['message' => 'Password reset successfully']);
    }
}
