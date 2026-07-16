<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal HTTP router. Supports `{param}` path placeholders and dispatches to
 * a `[ControllerClass, 'method']` handler.
 */
final class Router
{
    /** @var array<string, array<string, array{0:class-string,1:string}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->path();

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route) . '$#';
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter(
                    $matches,
                    'is_string',
                    ARRAY_FILTER_USE_KEY
                );
                [$class, $action] = $handler;
                echo (new $class())->$action($request, $params);
                return;
            }
        }

        http_response_code(404);
        echo View::render('errors/404', ['title' => 'Not found'], 'layouts/app');
    }
}
