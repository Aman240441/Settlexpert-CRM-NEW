<?php
declare(strict_types=1);

class Response {
    public static function json(mixed $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $statusCode = 400, mixed $details = null): void {
        $body = ['error' => $message];
        if ($details !== null) {
            $body['details'] = $details;
        }
        self::json($body, $statusCode);
    }
}
