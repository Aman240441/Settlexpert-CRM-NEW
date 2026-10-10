<?php
declare(strict_types=1);

// Load environment variables from .env if present
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $val;
                putenv("$key=$val");
            }
        }
    }
}

$jwtSecret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? '');
if (empty($jwtSecret)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Configuration error: JWT_SECRET is required.']);
    exit;
}

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1'),
        'port' => (int)(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 3306)),
        'name' => getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'settl_expert'),
        'user' => getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root'),
        'pass' => getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ''),
        'charset' => 'utf8mb4'
    ],
    'jwt_secret' => $jwtSecret,
    'jwt_expiry' => (int)(getenv('JWT_EXPIRY') ?: ($_ENV['JWT_EXPIRY'] ?? 86400)), // 24 hours
    'allowed_origins' => explode(',', getenv('ALLOWED_ORIGINS') ?: ($_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:5173,http://localhost:5000,http://127.0.0.1:5173,http://localhost:3000'))
];
