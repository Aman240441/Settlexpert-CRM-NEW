<?php
// php-app/api/admin/fee_plans.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_admin($authUser);

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: List Fee Plans ─────────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $stmt = db()->query('SELECT * FROM fee_plans ORDER BY default_fee ASC');
        echo json_encode(['feePlans' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch fee plans']);
        exit;
    }
}

// ── POST: Create Fee Plan ───────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = trim($input['name'] ?? '');
    $duration = trim($input['duration'] ?? '12 Months');
    $defaultFee = (float)($input['default_fee'] ?? 0);

    if ($name === '' || $defaultFee <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and valid default_fee required']);
        exit;
    }

    $id = 'fp-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $stmt = db()->prepare('
        INSERT INTO fee_plans (id, name, duration, default_fee, status)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $id,
        $name,
        $duration,
        $defaultFee,
        $input['status'] ?? 'active'
    ]);

    log_audit($authUser, 'Fee Plan Created', 'FeePlans', $id, ['name' => $name, 'fee' => $defaultFee]);

    http_response_code(201);
    echo json_encode(['message' => 'Fee plan created successfully', 'id' => $id]);
    exit;
}

// ── PUT: Update Fee Plan ────────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $_GET['id'] ?? $input['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Fee Plan ID required']);
        exit;
    }

    $stmt = db()->prepare('
        UPDATE fee_plans SET
            name = COALESCE(?, name),
            duration = COALESCE(?, duration),
            default_fee = COALESCE(?, default_fee),
            status = COALESCE(?, status)
        WHERE id = ?
    ');
    $stmt->execute([
        $input['name'] ?? null,
        $input['duration'] ?? null,
        isset($input['default_fee']) ? (float)$input['default_fee'] : null,
        $input['status'] ?? null,
        $id
    ]);

    log_audit($authUser, 'Fee Plan Updated', 'FeePlans', $id);

    echo json_encode(['message' => 'Fee plan updated successfully']);
    exit;
}

// ── DELETE: Delete Fee Plan ─────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Fee Plan ID required']);
        exit;
    }

    $stmt = db()->prepare('DELETE FROM fee_plans WHERE id = ?');
    $stmt->execute([$id]);

    log_audit($authUser, 'Fee Plan Deleted', 'FeePlans', $id);

    echo json_encode(['message' => 'Fee plan deleted successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
