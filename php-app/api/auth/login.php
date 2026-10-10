<?php
// php-app/api/auth/login.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/jwt.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$emailInput = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($emailInput === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Email/Employee ID and password are required']);
    exit;
}

$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// IP Rate Limiting via database
try {
    $now = time();
    $windowSeconds = 60;
    $maxAttempts = 30;

    $stmtRate = db()->prepare('SELECT * FROM login_rate_limit WHERE ip_address = ?');
    $stmtRate->execute([$clientIp]);
    $rateRow = $stmtRate->fetch();

    if ($rateRow) {
        if ($now > $rateRow['reset_at']) {
            $stmtUp = db()->prepare('UPDATE login_rate_limit SET attempts = 1, reset_at = ? WHERE ip_address = ?');
            $stmtUp->execute([$now + $windowSeconds, $clientIp]);
        } else {
            $newAttempts = $rateRow['attempts'] + 1;
            if ($newAttempts > $maxAttempts) {
                http_response_code(429);
                echo json_encode(['error' => 'Too many login requests from this IP address. Please wait a moment and try again.']);
                exit;
            }
            $stmtUp = db()->prepare('UPDATE login_rate_limit SET attempts = ? WHERE ip_address = ?');
            $stmtUp->execute([$newAttempts, $clientIp]);
        }
    } else {
        $stmtIns = db()->prepare('INSERT INTO login_rate_limit (ip_address, attempts, reset_at) VALUES (?, 1, ?)');
        $stmtIns->execute([$clientIp, $now + $windowSeconds]);
    }
} catch (Exception $e) {
    // Graceful degrade if rate limit table error
}

$stmtUser = db()->prepare('
    SELECT * FROM users 
    WHERE LOWER(email) = LOWER(?) OR LOWER(emp_or_mgr_id) = LOWER(?)
');
$stmtUser->execute([$emailInput, $emailInput]);
$user = $stmtUser->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid email/employee ID or password']);
    exit;
}

// Admin Lockout Enforcement
if ($user['role'] === 'admin') {
    $nowDt = new DateTime();

    if (!empty($user['locked_until'])) {
        $lockExpiry = new DateTime($user['locked_until']);
        if ($lockExpiry > $nowDt) {
            log_audit($user, 'Admin Login Blocked (Account Locked)', 'Auth', $user['id'], [
                'locked_until' => $user['locked_until'],
                'client_ip'    => $clientIp
            ]);
            http_response_code(403);
            echo json_encode(['error' => 'Too many failed login attempts. Your account has been temporarily locked. Please try again after 15 minutes.']);
            exit;
        } else {
            $stmtUnlock = db()->prepare('
                UPDATE users 
                SET locked_until = NULL, failed_login_attempts = 0, updated_at = NOW() 
                WHERE id = ?
            ');
            $stmtUnlock->execute([$user['id']]);
            $user['failed_login_attempts'] = 0;
            $user['locked_until'] = null;
        }
    }

    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['error' => 'Account is deactivated. Please contact Super Administrator.']);
        exit;
    }

    $isMatch = password_verify($password, $user['password_hash']);

    if (!$isMatch) {
        $newFailed = (int)($user['failed_login_attempts'] ?? 0) + 1;
        if ($newFailed >= 5) {
            $lockUntil = (new DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');
            $stmtLock = db()->prepare('
                UPDATE users 
                SET failed_login_attempts = ?, locked_until = ?, last_failed_login_at = NOW(), updated_at = NOW() 
                WHERE id = ?
            ');
            $stmtLock->execute([$newFailed, $lockUntil, $user['id']]);

            log_audit($user, 'Admin Account Temporarily Locked', 'Auth', $user['id'], [
                'reason'       => '5 consecutive failed attempts',
                'locked_until' => $lockUntil,
                'client_ip'    => $clientIp
            ]);

            http_response_code(403);
            echo json_encode(['error' => 'Too many failed login attempts. Your account has been temporarily locked. Please try again after 15 minutes.']);
            exit;
        } else {
            $stmtFailed = db()->prepare('
                UPDATE users 
                SET failed_login_attempts = ?, last_failed_login_at = NOW(), updated_at = NOW() 
                WHERE id = ?
            ');
            $stmtFailed->execute([$newFailed, $user['id']]);

            log_audit($user, 'Failed Admin Login', 'Auth', $user['id'], [
                'failed_attempts' => $newFailed,
                'client_ip'       => $clientIp
            ]);

            http_response_code(401);
            echo json_encode(['error' => 'Invalid email or password']);
            exit;
        }
    }

    $stmtSuccess = db()->prepare('
        UPDATE users 
        SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW(), updated_at = NOW() 
        WHERE id = ?
    ');
    $stmtSuccess->execute([$user['id']]);

    log_audit($user, 'Successful Admin Login', 'Auth', $user['id'], ['client_ip' => $clientIp]);
} else {
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['error' => 'Account is deactivated. Please contact Super Administrator.']);
        exit;
    }

    $isMatch = password_verify($password, $user['password_hash']);
    if (!$isMatch) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }

    log_audit($user, 'User Login', 'Auth', $user['id'], "Logged in successfully from $clientIp");
}

$tokenPayload = [
    'id'              => $user['id'],
    'name'            => $user['name'],
    'email'           => $user['email'],
    'role'            => $user['role'],
    'emp_or_mgr_id'   => $user['emp_or_mgr_id'],
    'department_id'   => $user['department_id'],
    'manager_type_id' => $user['manager_type_id'],
];
$token = jwt_encode($tokenPayload, JWT_SECRET, 86400);

$stmtFull = db()->prepare("
    SELECT u.id, u.name, u.email, u.phone, u.role, u.emp_or_mgr_id, 
           u.profile_image, u.department_id, u.team_id, u.manager_id, 
           u.manager_type_id, u.status, u.joining_date, u.id_type,
           d.name as department_name, mt.name as manager_type_name,
           COALESCE(sep.designation, mt.name, 
               CASE 
                   WHEN u.role = 'admin' THEN 'Super Administrator' 
                   WHEN u.role = 'advocate' THEN 'Legal Advocate' 
                   WHEN u.role = 'manager' THEN 'Manager' 
                   ELSE 'Employee' 
               END
           ) as designation
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
    LEFT JOIN staff_extended_profiles sep ON u.id = sep.user_id
    WHERE u.id = ?
");
$stmtFull->execute([$user['id']]);
$fullUser = $stmtFull->fetch() ?: $user;

echo json_encode([
    'message' => 'Login successful',
    'token'   => $token,
    'user'    => $fullUser
]);
