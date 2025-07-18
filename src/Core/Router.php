<?php

namespace NetflixLoginService\Core;

use NetflixLoginService\Middleware\AuthMiddleware;
use NetflixLoginService\Middleware\AdminMiddleware;
use NetflixLoginService\Middleware\SuperAdminMiddleware;
use Exception;

class Router
{
    private $routes = [];
    private $groupPrefix = '';
    private $groupMiddleware = [];
    
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }
    
    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }
    
    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    public function group($prefix, $callback)
    {
        $oldPrefix = $this->groupPrefix;
        $oldMiddleware = $this->groupMiddleware;
        
        $this->groupPrefix = $oldPrefix . $prefix;
        
        // Add middleware for admin routes
        if (strpos($prefix, '/admin') === 0) {
            $this->groupMiddleware = array_merge($this->groupMiddleware, [
                AuthMiddleware::class,
                AdminMiddleware::class
            ]);
        }
        
        $callback($this);
        
        $this->groupPrefix = $oldPrefix;
        $this->groupMiddleware = $oldMiddleware;
    }
    
    private function addRoute($method, $path, $handler)
    {
        $fullPath = $this->groupPrefix . $path;
        
        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $this->groupMiddleware
        ];
    }
    
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Handle method override for forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path, $params)) {
                
                // Apply middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    $result = $middleware->handle();
                    if ($result === false) {
                        return; // Middleware blocked the request
                    }
                }
                
                return $this->callHandler($route['handler'], $params);
            }
        }
        
        // No route found
        $this->handle404();
    }
    
    private function matchPath($routePath, $requestPath, &$params = [])
    {
        $params = [];
        
        // Convert route path to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $requestPath, $matches)) {
            // Extract parameter names
            preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
            
            // Map parameter values to names
            for ($i = 1; $i < count($matches); $i++) {
                $paramName = $paramNames[1][$i - 1];
                $params[$paramName] = $matches[$i];
            }
            
            return true;
        }
        
        return false;
    }
    
    private function callHandler($handler, $params = [])
    {
        if (is_string($handler)) {
            list($controllerName, $methodName) = explode('@', $handler);
            
            // Add namespace prefix for controllers
            if (strpos($controllerName, '\\') === false) {
                $controllerName = 'NetflixLoginService\\Controllers\\' . $controllerName;
            }
            
            if (!class_exists($controllerName)) {
                throw new Exception("Controller {$controllerName} not found");
            }
            
            $controller = new $controllerName();
            
            if (!method_exists($controller, $methodName)) {
                throw new Exception("Method {$methodName} not found in controller {$controllerName}");
            }
            
            return call_user_func_array([$controller, $methodName], $params);
        }
        
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        throw new Exception("Invalid route handler");
    }
    
    private function handle404()
    {
        http_response_code(404);
        
        if (file_exists(__DIR__ . '/../Views/errors/404.php')) {
            include __DIR__ . '/../Views/errors/404.php';
        } else {
            echo "<h1>404 - Page Not Found</h1>";
            echo "<p>The requested page could not be found.</p>";
        }
    }
    
    public static function redirect($url, $status = 302)
    {
        header("Location: {$url}", true, $status);
        exit;
    }
    
    public static function url($path = '')
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
    
    public static function asset($path)
    {
        return self::url('assets/' . ltrim($path, '/'));
    }
}