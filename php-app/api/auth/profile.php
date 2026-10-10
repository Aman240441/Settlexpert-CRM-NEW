<?php
// php-app/api/auth/profile.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/jwt.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$authUser = authenticate_token();

$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$authUser['id']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $name = $input['name'] ?? $user['name'];
    $phone = $input['phone'] ?? $user['phone'];
    $profileImage = $input['profile_image'] ?? $user['profile_image'];
    $passwordHash = $user['password_hash'];

    if (!empty($input['new_password'])) {
        $currentPassword = $input['current_password'] ?? '';
        if ($currentPassword === '' || !password_verify($currentPassword, $user['password_hash'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Current password does not match']);
            exit;
        }
        $passwordHash = password_hash($input['new_password'], PASSWORD_BCRYPT, ['cost' => 10]);
    }

    $stmtUp = db()->prepare('
        UPDATE users 
        SET name = ?, phone = ?, profile_image = ?, password_hash = ?, updated_at = NOW() 
        WHERE id = ?
    ');
    $stmtUp->execute([$name, $phone, $profileImage, $passwordHash, $authUser['id']]);

    log_audit($authUser, 'Profile Updated', 'Profile', $authUser['id'], 'Admin updated profile details');

    echo json_encode(['message' => 'Profile updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update profile']);
}
