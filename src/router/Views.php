<?php
namespace indura\routers;

use Exception;

class Views {
    private $routes = [];
    private $viewsPath;
    private $layoutsPath;
    private $defaultLayout = 'main';
    private $notFoundView = '404';
    
    public function __construct($viewsPath, $layoutsPath) {
        $this->viewsPath = rtrim($viewsPath, '/') . '/';
        $this->layoutsPath = rtrim($layoutsPath, '/') . '/';
    }
    
    public function get($path, $view, $data = [], $layout = null) {
        $this->addRoute('GET', $path, $view, $data, $layout);
    }

    public function post($path, $view, $data = [], $layout = null) {
        $this->addRoute('POST', $path, $view, $data, $layout);
    }
    
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
    
    public function setDefaultLayout($layout) {
        $this->defaultLayout = $layout;
    }
    
    public function setNotFoundView($view) {
        $this->notFoundView = $view;
    }

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
    
    public function url($path, $params = []) {
        $url = $path;
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        return $url;
    }

    public function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header("Location: $url");
        exit();
    }

    public static function param($param) {
        return $_REQUEST[$param] ?? null;
    }
}