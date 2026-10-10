<?php
// php-app/api/admin/managers.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_admin($authUser);

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: List Managers ──────────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $stmt = db()->query("
            SELECT u.id, u.name, u.email, u.phone, u.emp_or_mgr_id, u.status, u.joining_date,
                   u.department_id, u.manager_type_id,
                   d.name as department_name, mt.name as manager_type_name,
                   (SELECT COUNT(*) FROM users e WHERE e.manager_id = u.id AND e.role = 'employee') as employee_count,
                   (SELECT COUNT(*) FROM leads l WHERE l.manager_id = u.id) as lead_count,
                   (SELECT COUNT(*) FROM clients c WHERE c.manager_id = u.id) as client_count
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
            WHERE u.role = 'manager'
            ORDER BY u.name ASC
        ");
        echo json_encode(['managers' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch managers']);
        exit;
    }
}

// ── POST: Create Manager ────────────────────────────────────────────────────
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

    $id = 'mgr-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $empOrMgrId = trim($input['emp_or_mgr_id'] ?? ('MGR-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5))));
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

    $stmt = db()->prepare('
        INSERT INTO users (
            id, name, email, phone, password_hash, role,
            department_id, manager_type_id, emp_or_mgr_id, status, joining_date
        ) VALUES (
            ?, ?, ?, ?, ?, "manager",
            ?, ?, ?, ?, ?
        )
    ');
    $stmt->execute([
        $id,
        $name,
        $email,
        $input['phone'] ?? null,
        $hash,
        $input['department_id'] ?? null,
        $input['manager_type_id'] ?? null,
        $empOrMgrId,
        $input['status'] ?? 'active',
        $input['joining_date'] ?? date('Y-m-d')
    ]);

    log_audit($authUser, 'Manager Created', 'Managers', $id, ['name' => $name, 'email' => $email]);

    http_response_code(201);
    echo json_encode(['message' => 'Manager created successfully', 'id' => $id]);
    exit;
}

// ── PUT: Update Manager ─────────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $_GET['id'] ?? $input['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Manager ID required']);
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
            manager_type_id = COALESCE(?, manager_type_id),
            status = COALESCE(?, status),
            password_hash = COALESCE(?, password_hash),
            updated_at = NOW()
        WHERE id = ? AND role = "manager"
    ');
    $stmt->execute([
        $input['name'] ?? null,
        $input['phone'] ?? null,
        $input['department_id'] ?? null,
        $input['manager_type_id'] ?? null,
        $input['status'] ?? null,
        $passwordHash,
        $id
    ]);

    log_audit($authUser, 'Manager Updated', 'Managers', $id);

    echo json_encode(['message' => 'Manager updated successfully']);
    exit;
}

// ── DELETE: Delete Manager ──────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Manager ID required']);
        exit;
    }

    $stmt = db()->prepare('UPDATE users SET status = "inactive", updated_at = NOW() WHERE id = ? AND role = "manager"');
    $stmt->execute([$id]);

    log_audit($authUser, 'Manager Deactivated', 'Managers', $id);

    echo json_encode(['message' => 'Manager deactivated successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
