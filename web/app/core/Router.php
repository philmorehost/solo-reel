<?php

namespace App\Core;

class Router {
    private array $routes = [];

    public function get(string $path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }

    public function post(string $path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }

    public function put(string $path, $callback) {
        $this->addRoute('PUT', $path, $callback);
    }

    public function delete(string $path, $callback) {
        $this->addRoute('DELETE', $path, $callback);
    }

    private function addRoute(string $method, string $path, $callback) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback
        ];
    }

    public function dispatch(string $uri, string $method) {
        // Strip query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        // Handle method spoofing for forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Normalize index.php out of the URL if it's there
        if (str_ends_with($uri, '/index.php')) {
            $uri = substr($uri, 0, -10);
            if ($uri === '') $uri = '/';
        }
        if ($uri !== "/" && str_ends_with($uri, "/")) {
            $uri = rtrim($uri, "/");
        }

        // Handle subfolder deployments (like /~username/)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
            if ($uri === '') $uri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $route['path']);
            $pattern = preg_replace('/\{\.\.\.([a-zA-Z0-9_]+)\}/', '(?P<$1>.*)', $pattern);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_callable($route['callback'])) {
                    return call_user_func_array($route['callback'], array_values($params));
                }

                if (is_string($route['callback'])) {
                    [$controllerName, $methodName] = explode('@', $route['callback']);

                    // Determine namespace based on path
                    $namespace = str_starts_with($route['path'], '/admin')
                        ? "\\App\\Admin\\Controllers\\"
                        : "\\App\\Controllers\\";

                    $controllerClass = $namespace . $controllerName;

                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        if (method_exists($controller, $methodName)) {
                            return call_user_func_array([$controller, $methodName], array_values($params));
                        }
                    }
                }
            }
        }

        $this->handleNotFound($uri);
    }

    private function handleNotFound($uri) {
        http_response_code(404);
        if (str_starts_with($uri, '/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not Found']);
        } else {
            // Check if 404 template exists
            $notFoundTemplate = __DIR__ . '/../../templates/pages/404.php';
            if (file_exists($notFoundTemplate)) {
                require $notFoundTemplate;
            } else {
                echo "404 Not Found";
            }
        }
    }
}
