<?php
// php-app/api/admin/teams.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_admin($authUser);

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: List Teams ─────────────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $stmt = db()->query("
            SELECT t.*,
                   d.name as department_name,
                   u.name as manager_name,
                   (SELECT COUNT(*) FROM users e WHERE e.team_id = t.id) as member_count
            FROM teams t
            LEFT JOIN departments d ON t.department_id = d.id
            LEFT JOIN users u ON t.manager_id = u.id
            ORDER BY t.name ASC
        ");
        echo json_encode(['teams' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch teams']);
        exit;
    }
}

// ── POST: Create Team ───────────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = trim($input['name'] ?? '');
    $code = trim(strtoupper($input['code'] ?? ''));

    if ($name === '' || $code === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Team name and code are required']);
        exit;
    }

    $id = 'team-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $stmt = db()->prepare('
        INSERT INTO teams (id, name, code, department_id, manager_id, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $id,
        $name,
        $code,
        $input['department_id'] ?? null,
        $input['manager_id'] ?? null,
        $input['status'] ?? 'active'
    ]);

    log_audit($authUser, 'Team Created', 'Teams', $id, ['name' => $name, 'code' => $code]);

    http_response_code(201);
    echo json_encode(['message' => 'Team created successfully', 'id' => $id]);
    exit;
}

// ── PUT: Update Team ────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $_GET['id'] ?? $input['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Team ID required']);
        exit;
    }

    $stmt = db()->prepare('
        UPDATE teams SET
            name = COALESCE(?, name),
            department_id = COALESCE(?, department_id),
            manager_id = COALESCE(?, manager_id),
            status = COALESCE(?, status),
            updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([
        $input['name'] ?? null,
        $input['department_id'] ?? null,
        $input['manager_id'] ?? null,
        $input['status'] ?? null,
        $id
    ]);

    log_audit($authUser, 'Team Updated', 'Teams', $id);

    echo json_encode(['message' => 'Team updated successfully']);
    exit;
}

// ── DELETE: Delete Team ─────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Team ID required']);
        exit;
    }

    $stmt = db()->prepare('DELETE FROM teams WHERE id = ?');
    $stmt->execute([$id]);

    log_audit($authUser, 'Team Deleted', 'Teams', $id);

    echo json_encode(['message' => 'Team deleted successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
