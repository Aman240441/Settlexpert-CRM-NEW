<?php
declare(strict_types=1);

class Router {
    private static array $routes = [];

    public static function get(string $path, callable|array $handler): void {
        self::add('GET', $path, $handler);
    }

    public static function post(string $path, callable|array $handler): void {
        self::add('POST', $path, $handler);
    }

    public static function put(string $path, callable|array $handler): void {
        self::add('PUT', $path, $handler);
    }

    public static function patch(string $path, callable|array $handler): void {
        self::add('PATCH', $path, $handler);
    }

    public static function delete(string $path, callable|array $handler): void {
        self::add('DELETE', $path, $handler);
    }

    private static function add(string $method, string $path, callable|array $handler): void {
        $paramNames = [];
        $pattern = preg_replace_callback('/:([a-zA-Z0-9_]+)/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $path);

        self::$routes[$method][] = [
            'path' => $path,
            'regex' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'paramNames' => $paramNames
        ];
    }

    public static function getJsonBody(): array {
        $raw = file_get_contents('php://input');
        if (!$raw) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function dispatch(string $method, string $uri): void {
        $routes = self::$routes[$method] ?? [];
        foreach ($routes as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                $params = [];
                foreach ($route['paramNames'] as $name) {
                    $params[$name] = $matches[$name] ?? '';
                }

                $handler = $route['handler'];
                if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
                    [$class, $action] = $handler;
                    (new $class())->$action($params);
                    return;
                }
                $handler($params);
                return;
            }
        }

        Response::error("Route {$method} {$uri} not found", 404);
    }
}
