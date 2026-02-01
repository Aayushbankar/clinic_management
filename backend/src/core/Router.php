<?php
declare(strict_types=1);

final class Router
{
    /**
     * @var array<string, array<int, array{pattern:string, regex:string, handler:callable}>>
     */
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $compiled = $this->compile($path);
        $this->routes[$method][] = [
            'pattern' => $path,
            'regex' => $compiled,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $r) {
            if (preg_match($r['regex'], $path, $m) !== 1) {
                continue;
            }
            $params = [];
            foreach ($m as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = $v;
                }
            }

            $handler = $r['handler'];
            $handler($params);
            return;
        }

        Response::error('Route not found', 404, ['method' => $method, 'path' => $path]);
    }

    private function compile(string $pathPattern): string
    {
        // Convert /resource/{id} into a named-capture regex.
        $escaped = preg_quote($pathPattern, '~');
        $regex = preg_replace('~\\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\\}~', '(?P<$1>[^/]+)', $escaped);
        if (!is_string($regex)) {
            $regex = $escaped;
        }
        return '~^' . $regex . '$~';
    }
}

