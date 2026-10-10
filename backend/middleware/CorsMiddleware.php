<?php
declare(strict_types=1);

class CorsMiddleware {
    public static function handle(array $allowedOrigins): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origin && (in_array($origin, $allowedOrigins, true) || in_array('*', $allowedOrigins, true))) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
        } elseif (!empty($allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$allowedOrigins[0]}");
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
