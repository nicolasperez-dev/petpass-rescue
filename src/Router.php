<?php
declare(strict_types=1);

final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, callable|array $handler, array $middleware): void
    {
        $pattern = trim($pattern, '/');
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = compact('method', 'pattern', 'regex', 'handler', 'middleware');
    }

    public function dispatch(string $method, string $path): void
    {
        $path = trim(rawurldecode($path), '/');
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match('#^' . $route['regex'] . '$#', $path, $matches)) {
                continue;
            }
            foreach ($route['middleware'] as $middleware) {
                $middleware();
            }
            $params = array_filter($matches, static fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);
            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $action] = $handler;
                (new $class())->$action(...array_values($params));
                return;
            }
            $handler(...array_values($params));
            return;
        }

        http_response_code(404);
        view('public/404', ['title' => 'Página não encontrada']);
    }
}
