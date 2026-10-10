<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Jwt.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RateLimiter.php';

class AuthController {
    private static function getClientIp(): string {
        return RateLimiter::getClientIp();
    }

    public static function login(): void {
        $db = Database::getConnection();
        $config = require __DIR__ . '/../config/config.php';
        $clientIp = self::getClientIp();

        // 1. IP Rate Limiting Check (30 attempts per minute)
        try {
            $stmt = $db->prepare('SELECT attempts, last_attempt FROM login_rate_limit WHERE ip_address = ?');
            $stmt->execute([$clientIp]);
            $ipRecord = $stmt->fetch();

            if ($ipRecord) {
                $lastAttemptTime = strtotime($ipRecord['last_attempt']);
                $now = time();
                if (($now - $lastAttemptTime) < 60) {
                    if ((int)$ipRecord['attempts'] >= 30) {
                        Response::error('Too many login requests from this IP address. Please wait a moment and try again.', 429);
                    }
                    $up = $db->prepare('UPDATE login_rate_limit SET attempts = attempts + 1, last_attempt = NOW() WHERE ip_address = ?');
                    $up->execute([$clientIp]);
                } else {
                    $up = $db->prepare('UPDATE login_rate_limit SET attempts = 1, last_attempt = NOW() WHERE ip_address = ?');
                    $up->execute([$clientIp]);
                }
            } else {
                $ins = $db->prepare('INSERT INTO login_rate_limit (ip_address, attempts, last_attempt) VALUES (?, 1, NOW())');
                $ins->execute([$clientIp]);
            }
        } catch (Throwable $e) {
            error_log('Rate limiting check error: ' . $e->getMessage());
        }

        $body = Router::getJsonBody();
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::error('Email/Employee ID and password are required', 400);
        }

        // Find user by email or emp_or_mgr_id and evaluate lock status using MySQL clock
        $stmt = $db->prepare('
            SELECT u.*, (u.locked_until IS NOT NULL AND u.locked_until > NOW()) as is_locked
            FROM users u 
            WHERE LOWER(u.email) = LOWER(?) OR LOWER(u.emp_or_mgr_id) = LOWER(?)
        ');
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('Invalid email/employee ID or password', 401);
        }

        // 2. Super Admin Lockout Enforcement
        if ($user['role'] === 'admin') {
            if (!empty($user['locked_until'])) {
                if (!empty($user['is_locked'])) {
                    AuthMiddleware::logAudit(
                        $db,
                        $user,
                        'Admin Login Blocked (Account Locked)',
                        'Auth',
                        $user['id'],
                        ['locked_until' => $user['locked_until'], 'client_ip' => $clientIp]
                    );
                    Response::error('Too many failed login attempts. Your account has been temporarily locked. Please try again after 15 minutes.', 403);
                } else {
                    // Lock expired -> automatically unlock account
                    $unlock = $db->prepare('UPDATE users SET locked_until = NULL, failed_login_attempts = 0, updated_at = NOW() WHERE id = ?');
                    $unlock->execute([$user['id']]);
                    $user['failed_login_attempts'] = 0;
                    $user['locked_until'] = null;
                }
            }

            if ($user['status'] !== 'active') {
                Response::error('Account is deactivated. Please contact Super Administrator.', 403);
            }

            $isMatch = password_verify($password, $user['password_hash']);
            if (!$isMatch) {
                $newFailedCount = (int)($user['failed_login_attempts'] ?? 0) + 1;

                if ($newFailedCount >= 5) {
                    $lockStmt = $db->prepare('UPDATE users SET failed_login_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE), last_failed_login_at = NOW(), updated_at = NOW() WHERE id = ?');
                    $lockStmt->execute([$newFailedCount, $user['id']]);

                    AuthMiddleware::logAudit(
                        $db,
                        $user,
                        'Admin Account Temporarily Locked',
                        'Auth',
                        $user['id'],
                        ['reason' => '5 consecutive failed attempts', 'client_ip' => $clientIp]
                    );

                    Response::error('Too many failed login attempts. Your account has been temporarily locked. Please try again after 15 minutes.', 403);
                } else {
                    $recStmt = $db->prepare('UPDATE users SET failed_login_attempts = ?, last_failed_login_at = NOW(), updated_at = NOW() WHERE id = ?');
                    $recStmt->execute([$newFailedCount, $user['id']]);

                    AuthMiddleware::logAudit(
                        $db,
                        $user,
                        'Failed Admin Login',
                        'Auth',
                        $user['id'],
                        ['failed_attempts' => $newFailedCount, 'client_ip' => $clientIp]
                    );

                    Response::error('Invalid email or password', 401);
                }
            }

            // Successful Admin Login -> reset failed attempts and clear lock
            $resetStmt = $db->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW(), updated_at = NOW() WHERE id = ?');
            $resetStmt->execute([$user['id']]);

            AuthMiddleware::logAudit(
                $db,
                $user,
                'Successful Admin Login',
                'Auth',
                $user['id'],
                ['client_ip' => $clientIp]
            );
        } else {
            // Non-admin users
            if ($user['status'] !== 'active') {
                Response::error('Account is deactivated. Please contact Super Administrator.', 403);
            }

            $isMatch = password_verify($password, $user['password_hash']);
            if (!$isMatch) {
                Response::error('Invalid email or password', 401);
            }

            AuthMiddleware::logAudit(
                $db,
                $user,
                'User Login',
                'Auth',
                $user['id'],
                "Logged in successfully from {$clientIp}"
            );
        }

        // Generate JWT token
        $payload = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'emp_or_mgr_id' => $user['emp_or_mgr_id'],
            'department_id' => $user['department_id'],
            'manager_type_id' => $user['manager_type_id']
        ];
        $token = Jwt::encode($payload, $config['jwt_secret'], $config['jwt_expiry']);

        $fullUser = self::fetchFullUser($db, $user['id']);

        Response::json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $fullUser
        ]);
    }

    public static function me(): void {
        $config = require __DIR__ . '/../config/config.php';
        $user = AuthMiddleware::authenticate($config['jwt_secret']);

        $db = Database::getConnection();
        $fullUser = self::fetchFullUser($db, $user['id']);

        if (!$fullUser) {
            Response::error('User not found', 404);
        }

        Response::json(['user' => $fullUser]);
    }

    public static function updateProfile(): void {
        $config = require __DIR__ . '/../config/config.php';
        $currentUser = AuthMiddleware::authenticate($config['jwt_secret']);

        $db = Database::getConnection();
        $body = Router::getJsonBody();

        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('User not found', 404);
        }

        $name = isset($body['name']) && trim((string)$body['name']) !== '' ? trim((string)$body['name']) : $user['name'];
        $phone = array_key_exists('phone', $body) ? (string)$body['phone'] : $user['phone'];
        $profileImage = array_key_exists('profile_image', $body) ? (string)$body['profile_image'] : $user['profile_image'];

        $passwordHash = $user['password_hash'];
        if (!empty($body['new_password'])) {
            $currentPassword = (string)($body['current_password'] ?? '');
            if ($currentPassword === '' || !password_verify($currentPassword, $user['password_hash'])) {
                Response::error('Current password does not match', 400);
            }
            $passwordHash = password_hash((string)$body['new_password'], PASSWORD_BCRYPT, ['cost' => 10]);
        }

        $upStmt = $db->prepare('
            UPDATE users 
            SET name = ?, phone = ?, profile_image = ?, password_hash = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $upStmt->execute([$name, $phone, $profileImage, $passwordHash, $user['id']]);

        AuthMiddleware::logAudit($db, $currentUser, 'Profile Updated', 'Profile', $user['id'], 'Admin updated profile details');

        Response::json(['message' => 'Profile updated successfully']);
    }

    private static function fetchFullUser(PDO $db, string $userId): ?array {
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.role, u.emp_or_mgr_id, 
                   u.profile_image, u.department_id, u.team_id, u.manager_id, 
                   u.manager_type_id, u.status, u.joining_date, u.id_type,
                   d.name as department_name, mt.name as manager_type_name,
                   COALESCE(sep.designation, mt.name, CASE WHEN u.role = 'admin' THEN 'Super Administrator' WHEN u.role = 'advocate' THEN 'Legal Advocate' WHEN u.role = 'manager' THEN 'Manager' ELSE 'Employee' END) as designation
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
            LEFT JOIN staff_extended_profiles sep ON u.id = sep.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $fullUser = $stmt->fetch();

        return $fullUser ?: null;
    }
}
