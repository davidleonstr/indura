<?php
namespace ziphp\routers;

use ziphp\helpers\JSONResponse;

class PathRouter {
    private $routes = [];
    private $prefixDiscriminator = '/^\/api\//';

    public function __construct($prefixDiscriminator) {
        $this->prefixDiscriminator = $prefixDiscriminator;
    }

    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }

    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute($method, $path, $handler) {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function group($prefix, $callback) {
        $originalRoutes = $this->routes;
        $this->routes = [];
        
        // Execute callback to register group routes
        $callback($this);
        
        // Add prefix to all routes in the group
        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                $prefixedPath = trim($prefix, '/') . '/' . trim($route['path'], '/');
                $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $prefixedPath);
                $pattern = '#^' . $pattern . '$#';
                
                $originalRoutes[$method][] = [
                    'pattern' => $pattern,
                    'path' => $prefixedPath,
                    'handler' => $route['handler']
                ];
            }
        }
        
        $this->routes = $originalRoutes;
    }

    public function resource($resource, $controller) {
        $this->get($resource, [$controller, 'index']);
        $this->get($resource . '/{id}', [$controller, 'show']);
        $this->post($resource, [$controller, 'store']);
        $this->put($resource . '/{id}', [$controller, 'update']);
        $this->delete($resource . '/{id}', [$controller, 'destroy']);
    }

    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getPath();

        // Search for routes with patterns
        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches); // Remove the entire match
                
                // Extract parameter names from the original route
                $params = [];
                preg_match_all('/\{([^}]+)\}/', $route['path'], $paramNames);
                if (!empty($paramNames[1]) && !empty($matches)) {
                    $params = array_combine($paramNames[1], $matches);
                }
                
                $this->executeRoute($route['handler'], $params);
                return;
            }
        }

        // Route not found
        JSONResponse::notFound('Endpoint not found');
    }

    private function getPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove any API prefixes
        $path = preg_replace($this->prefixDiscriminator, '', $path);
        
        return trim($path, '/');
    }

    private function executeRoute($handler, $params = []) {
        if (is_array($handler) && count($handler) === 2) {
            // Call class method: [$controller, 'method']
            [$controller, $method] = $handler;
            call_user_func_array([$controller, $method], [$params]);
        } elseif (is_callable($handler)) {
            // Anonymous function
            call_user_func_array($handler, [$params]);
        } else {
            JSONResponse::error('Invalid route handler', 500);
        }
    }

    public function url($path, $params = []) {
        $url = $path;
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        return $url;
    }
}