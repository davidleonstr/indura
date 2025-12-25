<?php
namespace indura\router;

use indura\json\Response;

/**
 * Paths
 * 
 * HTTP router class for defining and handling application routes.
 * Supports RESTful routing with dynamic parameters, route groups, resource routes,
 * and automatic parameter extraction. Handles GET, POST, PUT, and DELETE methods.
 */
class Paths {
    /**
     * Collection of registered routes organized by HTTP method
     * 
     * @var array
     */
    private $routes = [];
    
    /**
     * Regular expression pattern to identify and remove API prefixes from paths
     * 
     * @var string
     */
    private $prefixDiscriminator = '/^\/api\//';

    /**
     * Constructor
     * 
     * Initializes the router with a custom prefix discriminator pattern.
     * 
     * @param string $prefixDiscriminator Regular expression to match and remove path prefixes
     */
    public function __construct($prefixDiscriminator) {
        $this->prefixDiscriminator = $prefixDiscriminator;
    }

    /**
     * Registers a GET route
     * 
     * @param string $path Route path with optional dynamic parameters in {param} format
     * @param callable|array $handler Callback function or array [controller, method] to handle the route
     * @return void
     */
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Registers a POST route
     * 
     * @param string $path Route path with optional dynamic parameters in {param} format
     * @param callable|array $handler Callback function or array [controller, method] to handle the route
     * @return void
     */
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Registers a PUT route
     * 
     * @param string $path Route path with optional dynamic parameters in {param} format
     * @param callable|array $handler Callback function or array [controller, method] to handle the route
     * @return void
     */
    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Registers a DELETE route
     * 
     * @param string $path Route path with optional dynamic parameters in {param} format
     * @param callable|array $handler Callback function or array [controller, method] to handle the route
     * @return void
     */
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Adds a route to the routes collection
     * 
     * Converts route path with {param} syntax to a regular expression pattern
     * for matching and parameter extraction.
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $path Route path with optional dynamic parameters
     * @param callable|array $handler Route handler
     * @return void
     */
    private function addRoute($method, $path, $handler) {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Creates a route group with a common prefix
     * 
     * Allows organizing related routes under a shared path prefix.
     * Routes defined in the callback are automatically prefixed.
     * 
     * @param string $prefix Common path prefix for all routes in the group
     * @param callable $callback Function that defines routes within the group
     * @return void
     */
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

    /**
     * Registers a RESTful resource with standard CRUD routes
     * 
     * Automatically creates five standard routes:
     * - GET /resource -> index
     * - GET /resource/{id} -> show
     * - POST /resource -> store
     * - PUT /resource/{id} -> update
     * - DELETE /resource/{id} -> destroy
     * 
     * @param string $resource Resource name/path
     * @param object $controller Controller instance that handles the resource
     * @return void
     */
    public function resource($resource, $controller) {
        $this->get($resource, [$controller, 'index']);
        $this->get($resource . '/{id}', [$controller, 'show']);
        $this->post($resource, [$controller, 'store']);
        $this->put($resource . '/{id}', [$controller, 'update']);
        $this->delete($resource . '/{id}', [$controller, 'destroy']);
    }

    /**
     * Dispatches the current request to the matching route
     * 
     * Matches the current HTTP method and URI against registered routes,
     * extracts dynamic parameters, and executes the corresponding handler.
     * Returns a 404 response if no matching route is found.
     * 
     * @return void Executes route handler or sends error response
     */
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
        Response::notFound('Endpoint not found');
    }

    /**
     * Extracts and normalizes the request path
     * 
     * Removes query strings and API prefixes from the request URI.
     * 
     * @return string Normalized request path
     */
    private function getPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove any API prefixes
        $path = preg_replace($this->prefixDiscriminator, '', $path);
        
        return trim($path, '/');
    }

    /**
     * Executes a route handler with extracted parameters
     * 
     * Supports both controller method arrays and anonymous functions as handlers.
     * Parameters are passed to the handler as an associative array.
     * 
     * @param callable|array $handler Route handler (callback or [controller, method])
     * @param array $params Extracted route parameters
     * @return void Executes handler or sends error response
     */
    private function executeRoute($handler, $params = []) {
        if (is_array($handler) && count($handler) === 2) {
            // Call class method: [$controller, 'method']
            [$controller, $method] = $handler;
            call_user_func_array([$controller, $method], [$params]);
        } elseif (is_callable($handler)) {
            // Anonymous function
            call_user_func_array($handler, [$params]);
        } else {
            Response::error('Invalid route handler', 500);
        }
    }

    /**
     * Generates a URL by replacing route parameters with actual values
     * 
     * Useful for creating URLs dynamically based on route definitions.
     * 
     * @param string $path Route path template with {param} placeholders
     * @param array $params Associative array of parameter names and values
     * @return string Generated URL with parameters replaced
     */
    public function url($path, $params = []) {
        $url = $path;
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        return $url;
    }
}