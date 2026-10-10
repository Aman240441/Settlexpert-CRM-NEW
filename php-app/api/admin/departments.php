<?php
// php-app/api/admin/departments.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_admin($authUser);

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: List Departments ───────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $stmt = db()->query("
            SELECT d.*,
                   u.name as head_manager_name,
                   (SELECT COUNT(*) FROM users m WHERE m.department_id = d.id AND m.role = 'manager') as manager_count,
                   (SELECT COUNT(*) FROM users e WHERE e.department_id = d.id AND e.role = 'employee') as employee_count,
                   (SELECT COUNT(*) FROM teams t WHERE t.department_id = d.id) as team_count
            FROM departments d
            LEFT JOIN users u ON d.head_manager_id = u.id
            ORDER BY d.name ASC
        ");
        echo json_encode(['departments' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch departments']);
        exit;
    }
}

// ── POST: Create Department ─────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = trim($input['name'] ?? '');
    $code = trim(strtoupper($input['code'] ?? ''));

    if ($name === '' || $code === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Department name and code are required']);
        exit;
    }

    $dup = db()->prepare('SELECT id FROM departments WHERE LOWER(name) = LOWER(?) OR LOWER(code) = LOWER(?)');
    $dup->execute([$name, $code]);
    if ($dup->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Department name or code already exists']);
        exit;
    }

    $id = 'dept-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $stmt = db()->prepare('
        INSERT INTO departments (id, name, code, description, head_manager_id, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $id,
        $name,
        $code,
        $input['description'] ?? null,
        $input['head_manager_id'] ?? null,
        $input['status'] ?? 'active'
    ]);

    log_audit($authUser, 'Department Created', 'Departments', $id, ['name' => $name, 'code' => $code]);

    http_response_code(201);
    echo json_encode(['message' => 'Department created successfully', 'id' => $id]);
    exit;
}

// ── PUT: Update Department ──────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $_GET['id'] ?? $input['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Department ID required']);
        exit;
    }

    $stmt = db()->prepare('
        UPDATE departments SET
            name = COALESCE(?, name),
            description = COALESCE(?, description),
            head_manager_id = COALESCE(?, head_manager_id),
            status = COALESCE(?, status),
            updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([
        $input['name'] ?? null,
        $input['description'] ?? null,
        $input['head_manager_id'] ?? null,
        $input['status'] ?? null,
        $id
    ]);

    log_audit($authUser, 'Department Updated', 'Departments', $id, ['name' => $input['name'] ?? null]);

    echo json_encode(['message' => 'Department updated successfully']);
    exit;
}

// ── DELETE: Delete Department ───────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Department ID required']);
        exit;
    }

    $chk = db()->prepare('SELECT COUNT(*) as c FROM users WHERE department_id = ?');
    $chk->execute([$id]);
    if ((int)$chk->fetch()['c'] > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete department with active users attached']);
        exit;
    }

    $stmt = db()->prepare('DELETE FROM departments WHERE id = ?');
    $stmt->execute([$id]);

    log_audit($authUser, 'Department Deleted', 'Departments', $id);

    echo json_encode(['message' => 'Department deleted successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
