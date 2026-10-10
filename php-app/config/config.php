<?php
// php-app/config/config.php

function load_env($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1], " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_ENV)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Check php-app/.env first, then root .env
$phpAppEnv = dirname(__DIR__) . '/.env';
$rootEnv = dirname(__DIR__, 2) . '/.env';

if (file_exists($phpAppEnv)) {
    load_env($phpAppEnv);
} elseif (file_exists($rootEnv)) {
    load_env($rootEnv);
}

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'settlexpert_crm');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'settlexpert_crm_jwt_super_secret_production_key_2026_secure');
