<?php
// php-app/api/admin/manager_types.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_admin($authUser);

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: List Manager Types ─────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $stmt = db()->query("
            SELECT mt.*,
                   (SELECT COUNT(*) FROM users u WHERE u.manager_type_id = mt.id) as manager_count
            FROM manager_types mt
            ORDER BY mt.name ASC
        ");
        echo json_encode(['managerTypes' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch manager types']);
        exit;
    }
}

// ── POST: Create Manager Type ───────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = trim($input['name'] ?? '');
    $code = trim(strtoupper($input['code'] ?? ''));

    if ($name === '' || $code === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Name and code are required']);
        exit;
    }

    $id = 'mt-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $perms = is_array($input['permissions_json'] ?? null) ? json_encode($input['permissions_json']) : ($input['permissions_json'] ?? '{}');

    $stmt = db()->prepare('
        INSERT INTO manager_types (id, name, code, description, permissions_json, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $id,
        $name,
        $code,
        $input['description'] ?? null,
        $perms,
        $input['status'] ?? 'active'
    ]);

    log_audit($authUser, 'Manager Type Created', 'ManagerTypes', $id, ['name' => $name, 'code' => $code]);

    http_response_code(201);
    echo json_encode(['message' => 'Manager type created successfully', 'id' => $id]);
    exit;
}

// ── PUT: Update Manager Type ────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $_GET['id'] ?? $input['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Manager Type ID required']);
        exit;
    }

    $perms = isset($input['permissions_json']) 
        ? (is_array($input['permissions_json']) ? json_encode($input['permissions_json']) : $input['permissions_json'])
        : null;

    $stmt = db()->prepare('
        UPDATE manager_types SET
            name = COALESCE(?, name),
            description = COALESCE(?, description),
            permissions_json = COALESCE(?, permissions_json),
            status = COALESCE(?, status),
            updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([
        $input['name'] ?? null,
        $input['description'] ?? null,
        $perms,
        $input['status'] ?? null,
        $id
    ]);

    log_audit($authUser, 'Manager Type Updated', 'ManagerTypes', $id);

    echo json_encode(['message' => 'Manager type updated successfully']);
    exit;
}

// ── DELETE: Delete Manager Type ─────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Manager Type ID required']);
        exit;
    }

    $stmt = db()->prepare('DELETE FROM manager_types WHERE id = ?');
    $stmt->execute([$id]);

    log_audit($authUser, 'Manager Type Deleted', 'ManagerTypes', $id);

    echo json_encode(['message' => 'Manager type deleted successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
