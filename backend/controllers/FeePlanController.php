<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../helpers/IdGenerator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class FeePlanController {
    private static function checkAdmin(): array {
        $config = require __DIR__ . '/../config/config.php';
        AuthMiddleware::authenticate($config['jwt_secret']);
        return AuthMiddleware::requireAdmin();
    }

    public static function index(): void {
        self::checkAdmin();
        $db = Database::getConnection();

        try {
            $stmt = $db->query('
                SELECT 
                    fp.*,
                    (SELECT COUNT(*) FROM clients c WHERE c.fee_plan_id = fp.id) as active_clients_count,
                    (SELECT COUNT(*) FROM agreements a WHERE a.fee_plan_id = fp.id) as agreements_count
                FROM fee_plans fp
                ORDER BY fp.default_fee ASC
            ');
            $plans = $stmt->fetchAll();
            foreach ($plans as &$p) {
                $p['default_fee'] = (float)$p['default_fee'];
                $p['active_clients_count'] = (int)$p['active_clients_count'];
                $p['agreements_count'] = (int)$p['agreements_count'];
            }
            Response::json(['plans' => $plans]);
        } catch (Throwable $e) {
            Response::error('Failed to fetch fee plans', 500);
        }
    }

    public static function create(): void {
        $user = self::checkAdmin();
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $name = trim((string)($body['name'] ?? ''));
        $duration = (string)($body['duration'] ?? '');
        $status = (string)($body['status'] ?? 'active');

        if ($name === '' || $duration === '' || !array_key_exists('default_fee', $body) || $body['default_fee'] === null || $body['default_fee'] === '') {
            Response::error('Plan name, duration, and default fee are required', 400);
        }

        $defaultFee = (float)$body['default_fee'];
        $id = IdGenerator::generateId('plan');

        $stmt = $db->prepare('
            INSERT INTO fee_plans (id, name, duration, default_fee, status)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$id, $name, $duration, $defaultFee, $status]);

        AuthMiddleware::logAudit($db, $user, 'Fee Plan Created', 'Fee Plans', $id, [
            'name' => $name,
            'duration' => $duration,
            'default_fee' => $defaultFee
        ]);

        Response::json(['message' => 'Fee plan created successfully', 'id' => $id], 201);
    }

    public static function update(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $stmt = $db->prepare('SELECT * FROM fee_plans WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Fee plan not found', 404);
        }

        $name = isset($body['name']) ? trim((string)$body['name']) : $existing['name'];
        $duration = array_key_exists('duration', $body) ? (string)$body['duration'] : $existing['duration'];
        $defaultFee = array_key_exists('default_fee', $body) && $body['default_fee'] !== null ? (float)$body['default_fee'] : (float)$existing['default_fee'];
        $status = array_key_exists('status', $body) ? (string)$body['status'] : $existing['status'];

        $stmt = $db->prepare('
            UPDATE fee_plans SET
                name = ?,
                duration = ?,
                default_fee = ?,
                status = ?
            WHERE id = ?
        ');
        $stmt->execute([$name, $duration, $defaultFee, $status, $id]);

        AuthMiddleware::logAudit($db, $user, 'Fee Plan Updated', 'Fee Plans', $id, [
            'name' => $name,
            'duration' => $duration,
            'default_fee' => $defaultFee
        ]);

        Response::json(['message' => 'Fee plan updated successfully']);
    }

    public static function toggleStatus(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT * FROM fee_plans WHERE id = ?');
        $stmt->execute([$id]);
        $plan = $stmt->fetch();
        if (!$plan) {
            Response::error('Fee plan not found', 404);
        }

        $newStatus = $plan['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $db->prepare('UPDATE fee_plans SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $id]);

        AuthMiddleware::logAudit($db, $user, 'Fee Plan Updated', 'Fee Plans', $id, "Status set to {$newStatus}");

        Response::json([
            'message' => "Fee plan status changed to {$newStatus}",
            'status' => $newStatus
        ]);
    }
}
