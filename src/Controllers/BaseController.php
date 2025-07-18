<?php

namespace NetflixLoginService\Controllers;

use NetflixLoginService\Core\Session;
use NetflixLoginService\Core\Router;

class BaseController
{
    protected $session;
    protected $viewPath;
    
    public function __construct()
    {
        $this->session = new Session();
        $this->viewPath = __DIR__ . '/../Views';
    }
    
    protected function render($template, $data = [])
    {
        // Add common data available to all views
        $data = array_merge([
            'current_user' => $this->session->getUser(),
            'is_logged_in' => $this->session->isLoggedIn(),
            'is_super_admin' => $this->session->isSuperAdmin(),
            'csrf_token' => $this->session->generateCSRFToken(),
            'app_name' => $_ENV['APP_NAME'] ?? 'Netflix Login Service',
            'app_url' => $_ENV['APP_URL'] ?? 'http://localhost',
        ], $data);
        
        // Extract variables for the template
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the template
        $templateFile = $this->viewPath . '/' . $template . '.php';
        
        if (!file_exists($templateFile)) {
            throw new \Exception("Template not found: {$template}");
        }
        
        include $templateFile;
        
        // Get the content and clean the buffer
        $content = ob_get_clean();
        
        // Output the content
        echo $content;
    }
    
    protected function renderJson($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    protected function redirect($url, $statusCode = 302)
    {
        Router::redirect($url, $statusCode);
    }
    
    protected function getCurrentUser()
    {
        return $this->session->getUser();
    }
    
    protected function requireAuth()
    {
        if (!$this->session->isLoggedIn()) {
            $this->redirect('/login');
        }
    }
    
    protected function requireSuperAdmin()
    {
        $this->requireAuth();
        
        if (!$this->session->isSuperAdmin()) {
            http_response_code(403);
            $this->render('errors/403', [
                'title' => '403 - Access Forbidden',
                'message' => 'You need super administrator privileges to access this page.'
            ]);
            exit;
        }
    }
    
    protected function validateCsrf()
    {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        
        if (!$this->session->validateCSRFToken($token)) {
            throw new \Exception('Invalid security token. Please refresh the page and try again.');
        }
    }
    
    protected function setFlash($type, $message)
    {
        $this->session->flash($type, $message);
    }
    
    protected function getFlash($type)
    {
        return $this->session->flash($type);
    }
}