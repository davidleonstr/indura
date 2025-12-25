<?php
namespace indura\router;

use Exception;

/**
 * Views
 * 
 * Router class for handling view-based routes with layout support.
 * Manages routing for traditional server-rendered pages, supporting dynamic parameters,
 * layouts, view rendering, and 404 error handling. Provides utilities for URL generation
 * and redirects.
 */
class Views {
    /**
     * Collection of registered view routes
     * 
     * @var array
     */
    private $routes = [];
    
    /**
     * Path to the views directory
     * 
     * @var string
     */
    private $viewsPath;
    
    /**
     * Path to the layouts directory
     * 
     * @var string
     */
    private $layoutsPath;
    
    /**
     * Default layout to use for views
     * 
     * @var string
     */
    private $defaultLayout = 'main';
    
    /**
     * View to display for 404 errors
     * 
     * @var string
     */
    private $notFoundView = '404';
    
    /**
     * Constructor
     * 
     * Initializes the view router with paths to views and layouts directories.
     * 
     * @param string $viewsPath Path to the views directory
     * @param string $layoutsPath Path to the layouts directory
     */
    public function __construct($viewsPath, $layoutsPath) {
        $this->viewsPath = rtrim($viewsPath, '/') . '/';
        $this->layoutsPath = rtrim($layoutsPath, '/') . '/';
    }
    
    /**
     * Registers a GET route for a view
     * 
     * @param string $path Route path with optional dynamic parameters in {param} format
     * @param string $view View name (without .php extension)
     * @param array $data Data to pass to the view
     * @param string|null $layout Layout name to use (null uses default layout)
     * @return void
     */
    public function get($path, $view, $data = [], $layout = null) {
        $this->addRoute('GET', $path, $view, $data, $layout);
    }

    /**
     * Registers a POST route for a view
     * 
     * @param string $path Route path with optional dynamic parameters in {param} format
     * @param string $view View name (without .php extension)
     * @param array $data Data to pass to the view
     * @param string|null $layout Layout name to use (null uses default layout)
     * @return void
     */
    public function post($path, $view, $data = [], $layout = null) {
        $this->addRoute('POST', $path, $view, $data, $layout);
    }
    
    /**
     * Adds a route to the routes collection
     * 
     * Converts route path with {param} syntax to a regular expression pattern
     * for matching and parameter extraction.
     * 
     * @param string $method HTTP method (GET, POST)
     * @param string $path Route path with optional dynamic parameters
     * @param string $view View name
     * @param array $data Data to pass to the view
     * @param string|null $layout Layout name
     * @return void
     */
    private function addRoute($method, $path, $view, $data, $layout) {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'path' => $path,
            'view' => $view,
            'data' => $data,
            'layout' => $layout ?: $this->defaultLayout
        ];
    }
    
    /**
     * Sets the default layout for all views
     * 
     * @param string $layout Layout name (without .php extension)
     * @return void
     */
    public function setDefaultLayout($layout) {
        $this->defaultLayout = $layout;
    }
    
    /**
     * Sets the view to display for 404 errors
     * 
     * @param string $view View name (without .php extension)
     * @return void
     */
    public function setNotFoundView($view) {
        $this->notFoundView = $view;
    }

    /**
     * Renders a view with optional layout
     * 
     * Loads the view file, extracts data as variables, captures the output,
     * and wraps it in a layout if specified. The layout file should include
     * the $content variable to display the view content.
     * 
     * @param string $view View name (without .php extension)
     * @param array $data Associative array of data to pass to the view
     * @param string|null $layout Layout name (null uses default, 'none' for no layout)
     * @return void Outputs the rendered view
     * @throws Exception If the view file is not found
     */
    public function render($view, $data = [], $layout = null) {
        $viewFile = $this->viewsPath . $view . '.php';
        $layout = $layout ?: $this->defaultLayout;
        
        if (!file_exists($viewFile)) {
            throw new Exception("View not found: $view");
        }
        
        // Extract data as variables
        extract($data);
        
        // Capture the content of the view
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        // If there is a layout, use it
        if ($layout && $layout !== 'none') {
            $layoutFile = $this->layoutsPath . $layout . '.php';
            if (file_exists($layoutFile)) {
                include $layoutFile;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }
    
    /**
     * Dispatches the current request to the matching view route
     * 
     * Matches the current HTTP method and URI against registered routes,
     * extracts dynamic parameters, merges them with route data, and renders
     * the corresponding view. Displays 404 view if no matching route is found.
     * 
     * @return void Renders view or 404 page
     */
    public function run() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = strtok($_SERVER['REQUEST_URI'], '?');
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && preg_match($route['pattern'], $requestUri, $matches)) {
                array_shift($matches);
                
                // Combine route data with captured parameters
                $data = $route['data'];
                if (!empty($matches)) {
                    // Extract parameter names from the original route
                    preg_match_all('/\{([^}]+)\}/', $route['path'], $paramNames);
                    if (!empty($paramNames[1])) {
                        $params = array_combine($paramNames[1], $matches);
                        $data = array_merge($data, $params);
                    }
                }
                $this->render($route['view'], $data, $route['layout']);
                return;
            }
        }
        
        // Page not found
        http_response_code(404);
        try {
            $this->render($this->notFoundView, ['error' => '404 - Page not found']);
        } catch (Exception $e) {
            echo '<h1>404 - Page not found</h1>';
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

    /**
     * Redirects to a specified URL
     * 
     * Sets the HTTP status code and Location header, then terminates execution.
     * 
     * @param string $url Target URL for redirection
     * @param int $statusCode HTTP status code (default: 302 for temporary redirect)
     * @return void Sends redirect header and exits
     */
    public function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header("Location: $url");
        exit();
    }

    /**
     * Retrieves a request parameter from GET or POST data
     * 
     * Static utility method for accessing request parameters.
     * 
     * @param string $param Parameter name
     * @return mixed|null Parameter value or null if not found
     */
    public static function param($param) {
        return $_REQUEST[$param] ?? null;
    }
}