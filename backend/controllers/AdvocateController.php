<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../helpers/IdGenerator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AdvocateController {
    private static function checkAdmin(): array {
        $config = require __DIR__ . '/../config/config.php';
        AuthMiddleware::authenticate($config['jwt_secret']);
        return AuthMiddleware::requireAdmin();
    }

    private static function checkEmployeeOrAdmin(): array {
        $config = require __DIR__ . '/../config/config.php';
        AuthMiddleware::authenticate($config['jwt_secret']);
        return AuthMiddleware::requireEmployeeOrAdmin();
    }

    public static function index(): void {
        self::checkEmployeeOrAdmin();
        $db = Database::getConnection();

        $search = isset($_GET['search']) && $_GET['search'] !== '' && $_GET['search'] !== 'undefined' && $_GET['search'] !== 'null' ? trim((string)$_GET['search']) : null;
        $status = isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== 'undefined' && $_GET['status'] !== 'null' ? trim((string)$_GET['status']) : null;

        $sql = "
            SELECT a.*, 
                (SELECT COUNT(*) FROM clients c WHERE c.advocate_id = a.id) as assigned_clients_count
            FROM advocates a
            WHERE 1=1
        ";
        $params = [];

        if ($search !== null) {
            $sql .= " AND (a.name LIKE ? OR a.advocate_id LIKE ? OR a.email LIKE ? OR a.mobile LIKE ? OR a.registration_number LIKE ? OR a.specialization LIKE ?)";
            $s = "%{$search}%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if ($status !== null) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.created_at DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $advocates = $stmt->fetchAll();
            foreach ($advocates as &$adv) {
                $adv['assigned_clients_count'] = (int)$adv['assigned_clients_count'];
            }
            Response::json(['advocates' => $advocates]);
        } catch (Throwable $e) {
            Response::error('Failed to fetch advocates', 500);
        }
    }

    public static function show(array $params): void {
        self::checkEmployeeOrAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT * FROM advocates WHERE id = ?');
        $stmt->execute([$id]);
        $advocate = $stmt->fetch();
        if (!$advocate) {
            Response::error('Advocate not found', 404);
        }

        $cStmt = $db->prepare('SELECT id, client_number, name, phone, status, total_debt FROM clients WHERE advocate_id = ?');
        $cStmt->execute([$id]);
        $assignedClients = $cStmt->fetchAll();

        Response::json([
            'advocate' => $advocate,
            'assignedClients' => $assignedClients
        ]);
    }

    public static function create(): void {
        $user = self::checkAdmin();
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $name = trim((string)($body['name'] ?? ''));
        $advocateId = trim((string)($body['advocate_id'] ?? ''));
        $mobile = trim((string)($body['mobile'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $regNumber = trim((string)($body['registration_number'] ?? ''));
        $specialization = trim((string)($body['specialization'] ?? ''));
        $address = isset($body['address']) && $body['address'] !== '' ? (string)$body['address'] : null;
        $profileImage = isset($body['profile_image']) && $body['profile_image'] !== '' ? (string)$body['profile_image'] : null;
        $status = (string)($body['status'] ?? 'active');
        $notes = isset($body['notes']) && $body['notes'] !== '' ? (string)$body['notes'] : null;

        if ($name === '' || $advocateId === '' || $mobile === '' || $email === '' || $regNumber === '' || $specialization === '') {
            Response::error('Name, Advocate ID, mobile, email, registration number, and specialization are required', 400);
        }

        $stmt = $db->prepare('SELECT id FROM advocates WHERE LOWER(email) = LOWER(?)');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Response::error('An advocate with this email already exists', 400);
        }

        $stmt = $db->prepare('SELECT id FROM advocates WHERE advocate_id = ?');
        $stmt->execute([$advocateId]);
        if ($stmt->fetch()) {
            Response::error('An advocate with this Advocate ID already exists', 400);
        }

        $stmt = $db->prepare('SELECT id FROM advocates WHERE registration_number = ?');
        $stmt->execute([$regNumber]);
        if ($stmt->fetch()) {
            Response::error('An advocate with this Bar Registration Number already exists', 400);
        }

        $id = IdGenerator::generateId('adv');

        $stmt = $db->prepare("
            INSERT INTO advocates (
                id, name, advocate_id, mobile, email, registration_number,
                specialization, address, profile_image, status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $name,
            strtoupper($advocateId),
            $mobile,
            strtolower($email),
            strtoupper($regNumber),
            $specialization,
            $address,
            $profileImage,
            $status,
            $notes
        ]);

        AuthMiddleware::logAudit($db, $user, 'Advocate Created', 'Advocates', $id, [
            'name' => $name,
            'advocate_id' => $advocateId,
            'registration_number' => $regNumber,
            'specialization' => $specialization
        ]);

        Response::json(['message' => 'Advocate created successfully', 'id' => $id], 201);
    }

    public static function update(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $stmt = $db->prepare('SELECT * FROM advocates WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Response::error('Advocate not found', 404);
        }

        $email = isset($body['email']) ? trim((string)$body['email']) : null;
        if ($email !== null && strcasecmp($email, $existing['email']) !== 0) {
            $stmt = $db->prepare('SELECT id FROM advocates WHERE LOWER(email) = LOWER(?) AND id != ?');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                Response::error('Email already in use', 400);
            }
        }

        $advocateId = isset($body['advocate_id']) ? trim((string)$body['advocate_id']) : null;
        if ($advocateId !== null && strcasecmp($advocateId, (string)$existing['advocate_id']) !== 0) {
            $stmt = $db->prepare('SELECT id FROM advocates WHERE advocate_id = ? AND id != ?');
            $stmt->execute([$advocateId, $id]);
            if ($stmt->fetch()) {
                Response::error('Advocate ID already in use', 400);
            }
        }

        $regNumber = isset($body['registration_number']) ? trim((string)$body['registration_number']) : null;
        if ($regNumber !== null && strcasecmp($regNumber, (string)$existing['registration_number']) !== 0) {
            $stmt = $db->prepare('SELECT id FROM advocates WHERE registration_number = ? AND id != ?');
            $stmt->execute([$regNumber, $id]);
            if ($stmt->fetch()) {
                Response::error('Bar Registration Number already in use', 400);
            }
        }

        $name = isset($body['name']) ? trim((string)$body['name']) : $existing['name'];
        $finalAdvocateId = $advocateId !== null ? strtoupper($advocateId) : $existing['advocate_id'];
        $mobile = array_key_exists('mobile', $body) ? trim((string)$body['mobile']) : $existing['mobile'];
        $finalEmail = $email !== null ? strtolower($email) : $existing['email'];
        $finalRegNumber = $regNumber !== null ? strtoupper($regNumber) : $existing['registration_number'];
        $specialization = array_key_exists('specialization', $body) ? trim((string)$body['specialization']) : $existing['specialization'];
        $address = array_key_exists('address', $body) ? ($body['address'] !== null && $body['address'] !== '' ? (string)$body['address'] : null) : $existing['address'];
        $profileImage = array_key_exists('profile_image', $body) ? ($body['profile_image'] !== null && $body['profile_image'] !== '' ? (string)$body['profile_image'] : null) : $existing['profile_image'];
        $status = array_key_exists('status', $body) ? (string)$body['status'] : $existing['status'];
        $notes = array_key_exists('notes', $body) ? ($body['notes'] !== null && $body['notes'] !== '' ? (string)$body['notes'] : null) : $existing['notes'];

        $stmt = $db->prepare('
            UPDATE advocates SET
                name = ?,
                advocate_id = ?,
                mobile = ?,
                email = ?,
                registration_number = ?,
                specialization = ?,
                address = ?,
                profile_image = ?,
                status = ?,
                notes = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $name,
            $finalAdvocateId,
            $mobile,
            $finalEmail,
            $finalRegNumber,
            $specialization,
            $address,
            $profileImage,
            $status,
            $notes,
            $id
        ]);

        AuthMiddleware::logAudit($db, $user, 'Advocate Updated', 'Advocates', $id, [
            'name' => $name,
            'advocate_id' => $finalAdvocateId
        ]);

        Response::json(['message' => 'Advocate updated successfully']);
    }

    public static function toggleStatus(array $params): void {
        $user = self::checkAdmin();
        $id = $params['id'] ?? '';
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT status, name FROM advocates WHERE id = ?');
        $stmt->execute([$id]);
        $adv = $stmt->fetch();
        if (!$adv) {
            Response::error('Advocate not found', 404);
        }

        $newStatus = $adv['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $db->prepare('UPDATE advocates SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $id]);

        $action = $newStatus === 'active' ? 'User Activated' : 'User Deactivated';
        AuthMiddleware::logAudit($db, $user, $action, 'Advocates', $id, "Advocate {$adv['name']} status changed to {$newStatus}");

        Response::json([
            'message' => "Advocate status updated to {$newStatus}",
            'status' => $newStatus
        ]);
    }
}
