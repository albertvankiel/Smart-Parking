<?php

namespace App\Infrastructure;

/**
 * Router for mapping paths and methods to controllers.
 */
class Router
{
    /**
     * @var array<int, array{path: string, method: string, controller: array<int, string>}>
     */
    private array $routes = [];

    public function __construct(
        private ServiceContainer $serviceContainer,
    ) {
    }

    public function add(string $path, string $method, array $controller): void
    {
        $path = $this->normalizePath($path);

        $this->routes[] = [
            'path'   => $path,
            'method' => $method,
            'controller' => $controller
        ];
    }

    public function dispatch(string $path): void
    {
        $path = $this->normalizePath($path);
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['path'] === $path && $route['method'] === $method) {
                $controller = $this->serviceContainer->get($route['controller'][0]);
                $controller->{$route['controller'][1]}();
                return;
            }
        }

        throw new \Exception("Route does not exist");
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        $path = "/{$path}";
        return preg_replace('#[/]{2,}#', '/', $path);
    }
}
