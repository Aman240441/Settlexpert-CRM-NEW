<?php
// php-app/api/admin/employees.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_admin($authUser);

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: List Employees ─────────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $stmt = db()->query("
            SELECT u.id, u.name, u.email, u.phone, u.emp_or_mgr_id, u.status, u.joining_date,
                   u.department_id, u.manager_id, u.team_id,
                   d.name as department_name,
                   m.name as manager_name,
                   t.name as team_name,
                   (SELECT COUNT(*) FROM leads l WHERE l.employee_id = u.id) as leads_count,
                   (SELECT COUNT(*) FROM clients c WHERE c.employee_id = u.id) as clients_count
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN users m ON u.manager_id = m.id
            LEFT JOIN teams t ON u.team_id = t.id
            WHERE u.role = 'employee'
            ORDER BY u.name ASC
        ");
        echo json_encode(['employees' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch employees']);
        exit;
    }
}

// ── POST: Create Employee ───────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? 'SettleExpert@2026';

    if ($name === '' || $email === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Name and email are required']);
        exit;
    }

    $chk = db()->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?)');
    $chk->execute([$email]);
    if ($chk->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    $id = 'emp-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $empCode = trim($input['emp_or_mgr_id'] ?? ('EMP-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5))));
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

    $stmt = db()->prepare('
        INSERT INTO users (
            id, name, email, phone, password_hash, role,
            department_id, manager_id, team_id, emp_or_mgr_id, status, joining_date
        ) VALUES (
            ?, ?, ?, ?, ?, "employee",
            ?, ?, ?, ?, ?, ?
        )
    ');
    $stmt->execute([
        $id,
        $name,
        $email,
        $input['phone'] ?? null,
        $hash,
        $input['department_id'] ?? null,
        $input['manager_id'] ?? null,
        $input['team_id'] ?? null,
        $empCode,
        $input['status'] ?? 'active',
        $input['joining_date'] ?? date('Y-m-d')
    ]);

    log_audit($authUser, 'Employee Created', 'Employees', $id, ['name' => $name, 'email' => $email]);

    http_response_code(201);
    echo json_encode(['message' => 'Employee created successfully', 'id' => $id]);
    exit;
}

// ── PUT: Update Employee ────────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $_GET['id'] ?? $input['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Employee ID required']);
        exit;
    }

    $passwordHash = null;
    if (!empty($input['password'])) {
        $passwordHash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 10]);
    }

    $stmt = db()->prepare('
        UPDATE users SET
            name = COALESCE(?, name),
            phone = COALESCE(?, phone),
            department_id = COALESCE(?, department_id),
            manager_id = COALESCE(?, manager_id),
            team_id = COALESCE(?, team_id),
            status = COALESCE(?, status),
            password_hash = COALESCE(?, password_hash),
            updated_at = NOW()
        WHERE id = ? AND role = "employee"
    ');
    $stmt->execute([
        $input['name'] ?? null,
        $input['phone'] ?? null,
        $input['department_id'] ?? null,
        $input['manager_id'] ?? null,
        $input['team_id'] ?? null,
        $input['status'] ?? null,
        $passwordHash,
        $id
    ]);

    log_audit($authUser, 'Employee Updated', 'Employees', $id);

    echo json_encode(['message' => 'Employee updated successfully']);
    exit;
}

// ── DELETE: Deactivate Employee ─────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Employee ID required']);
        exit;
    }

    $stmt = db()->prepare('UPDATE users SET status = "inactive", updated_at = NOW() WHERE id = ? AND role = "employee"');
    $stmt->execute([$id]);

    log_audit($authUser, 'Employee Deactivated', 'Employees', $id);

    echo json_encode(['message' => 'Employee deactivated successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
