<?php

namespace NetflixLoginService\Core;

use NetflixLoginService\Config\Database;
use NetflixLoginService\Core\Router;
use NetflixLoginService\Core\Session;
use NetflixLoginService\Core\Security;
use NetflixLoginService\Utils\Logger;
use Dotenv\Dotenv;
use Exception;

class Application
{
    private $router;
    private $session;
    private $security;
    private $logger;
    
    public function __construct()
    {
        $this->loadEnvironment();
        $this->initializeComponents();
        $this->setupErrorHandling();
        $this->registerRoutes();
    }
    
    private function loadEnvironment()
    {
        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        } catch (Exception $e) {
            error_log("Failed to load environment file: " . $e->getMessage());
            throw new Exception("Configuration file not found. Please copy .env.example to .env and configure it.");
        }
    }
    
    private function initializeComponents()
    {
        // Initialize session management
        $this->session = new Session();
        
        // Initialize security layer
        $this->security = new Security();
        
        // Initialize logger
        $this->logger = new Logger();
        
        // Initialize router
        $this->router = new Router();
        
        // Test database connection
        Database::getInstance();
    }
    
    private function setupErrorHandling()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', $_ENV['APP_DEBUG'] === 'true' ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', __DIR__ . '/../../storage/logs/error.log');
        
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    private function registerRoutes()
    {
        // Public routes
        $this->router->get('/', 'HomeController@index');
        $this->router->get('/login', 'Auth\LoginController@showLogin');
        $this->router->post('/login', 'Auth\LoginController@login');
        $this->router->get('/logout', 'Auth\LoginController@logout');
        $this->router->get('/get-code', 'PublicController@showCodeForm');
        $this->router->post('/get-code', 'PublicController@getCode');
        
        // Admin routes (protected)
        $this->router->group('/admin', function($router) {
            $router->get('/', 'Admin\DashboardController@index');
            $router->get('/dashboard', 'Admin\DashboardController@index');
            
            // Profile management
            $router->get('/profile', 'Admin\ProfileController@show');
            $router->post('/profile', 'Admin\ProfileController@update');
            $router->post('/profile/password', 'Admin\ProfileController@changePassword');
            
            // Orders management
            $router->get('/orders', 'Admin\OrderController@index');
            $router->get('/orders/create', 'Admin\OrderController@create');
            $router->post('/orders', 'Admin\OrderController@store');
            $router->get('/orders/{id}', 'Admin\OrderController@show');
            $router->get('/orders/{id}/edit', 'Admin\OrderController@edit');
            $router->post('/orders/{id}', 'Admin\OrderController@update');
            $router->delete('/orders/{id}', 'Admin\OrderController@delete');
            
            // Netflix emails management
            $router->get('/emails', 'Admin\EmailController@index');
            $router->get('/emails/create', 'Admin\EmailController@create');
            $router->post('/emails', 'Admin\EmailController@store');
            $router->get('/emails/{id}/edit', 'Admin\EmailController@edit');
            $router->post('/emails/{id}', 'Admin\EmailController@update');
            $router->delete('/emails/{id}', 'Admin\EmailController@delete');
            
            // Gmail setup
            $router->get('/gmail', 'Admin\GmailController@index');
            $router->get('/gmail/auth/{email_id}', 'Admin\GmailController@authenticate');
            $router->get('/gmail-callback', 'Admin\GmailController@callback');
            $router->post('/gmail/refresh/{email_id}', 'Admin\GmailController@refreshToken');
            $router->delete('/gmail/disconnect/{email_id}', 'Admin\GmailController@disconnect');
            
            // Statistics and logs
            $router->get('/statistics', 'Admin\StatisticsController@index');
            $router->get('/logs', 'Admin\LogController@index');
            $router->get('/logs/retrievals', 'Admin\LogController@retrievals');
            $router->get('/logs/admin', 'Admin\LogController@adminLogs');
            
            // Settings
            $router->get('/settings', 'Admin\SettingsController@index');
            $router->post('/settings', 'Admin\SettingsController@update');
            
            // Super admin only routes
            $router->get('/admins', 'Admin\AdminController@index');
            $router->get('/admins/create', 'Admin\AdminController@create');
            $router->post('/admins', 'Admin\AdminController@store');
            $router->get('/admins/{id}/edit', 'Admin\AdminController@edit');
            $router->post('/admins/{id}', 'Admin\AdminController@update');
            $router->post('/admins/{id}/permissions', 'Admin\AdminController@updatePermissions');
            $router->delete('/admins/{id}', 'Admin\AdminController@delete');
        });
        
        // API routes
        $this->router->group('/api', function($router) {
            $router->post('/code-retrieval', 'Api\CodeRetrievalController@retrieve');
            $router->get('/order-status/{code}', 'Api\OrderController@status');
        });
    }
    
    public function run()
    {
        try {
            // Apply security middleware
            $this->security->applySecurityHeaders();
            $this->security->checkMaintenanceMode();
            
            // Handle the request
            $this->router->dispatch();
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    public function handleError($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $this->logger->error("PHP Error: {$message}", [
            'severity' => $severity,
            'file' => $file,
            'line' => $line
        ]);
        
        if ($_ENV['APP_DEBUG'] === 'true') {
            echo "<strong>Error:</strong> {$message} in <strong>{$file}</strong> on line <strong>{$line}</strong><br>";
        }
        
        return true;
    }
    
    public function handleException($exception)
    {
        $this->logger->error("Uncaught Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        if ($_ENV['APP_DEBUG'] === 'true') {
            echo "<h1>Uncaught Exception</h1>";
            echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
            echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
            echo "<pre>" . $exception->getTraceAsString() . "</pre>";
        } else {
            http_response_code(500);
            include __DIR__ . '/../Views/errors/500.php';
        }
    }
    
    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->logger->critical("Fatal Error: {$error['message']}", [
                'file' => $error['file'],
                'line' => $error['line']
            ]);
        }
    }
}