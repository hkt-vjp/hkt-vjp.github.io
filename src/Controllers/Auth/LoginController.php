<?php

namespace NetflixLoginService\Controllers\Auth;

use NetflixLoginService\Controllers\BaseController;
use NetflixLoginService\Config\Database;
use NetflixLoginService\Core\Session;
use NetflixLoginService\Core\Security;
use NetflixLoginService\Core\Router;
use NetflixLoginService\Utils\Logger;

class LoginController extends BaseController
{
    private $db;
    private $session;
    private $security;
    private $logger;
    
    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
        $this->session = new Session();
        $this->security = new Security();
        $this->logger = new Logger();
    }
    
    public function showLogin()
    {
        // If already logged in, redirect to admin dashboard
        if ($this->session->isLoggedIn()) {
            Router::redirect('/admin/dashboard');
        }
        
        $this->render('auth/login', [
            'title' => 'Admin Login - ' . ($_ENV['APP_NAME'] ?? 'Netflix Login Service'),
            'csrf_token' => $this->session->generateCSRFToken(),
            'error' => $this->session->flash('error'),
            'success' => $this->session->flash('success')
        ]);
    }
    
    public function login()
    {
        try {
            // Validate CSRF token
            if (!$this->security->validateCSRF()) {
                throw new \Exception('Invalid security token. Please try again.');
            }
            
            $username = $this->security->sanitizeInput($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $rememberMe = isset($_POST['remember_me']);
            
            // Input validation
            if (empty($username) || empty($password)) {
                throw new \Exception('Username and password are required.');
            }
            
            // Rate limiting check
            $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $rateLimit = $this->security->checkRateLimit($identifier, 'login');
            
            if (!$rateLimit['allowed']) {
                $this->security->logSecurityEvent('Rate limit exceeded for login', [
                    'username' => $username,
                    'ip' => $identifier,
                    'remaining_time' => $rateLimit['remaining_time']
                ]);
                
                throw new \Exception(sprintf(
                    'Too many login attempts. Please try again in %d minutes.',
                    ceil($rateLimit['remaining_time'] / 60)
                ));
            }
            
            // Get user from database
            $stmt = $this->db->prepare("
                SELECT id, username, email, password_hash, full_name, role, status, 
                       login_attempts, locked_until, permissions
                FROM admins 
                WHERE (username = ? OR email = ?) AND status != 'banned'
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->logFailedAttempt($username, 'User not found');
                throw new \Exception('Invalid username or password.');
            }
            
            // Check if account is locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $this->logFailedAttempt($username, 'Account locked');
                throw new \Exception('Account is temporarily locked. Please try again later.');
            }
            
            // Check if account is active
            if ($user['status'] !== 'active') {
                $this->logFailedAttempt($username, 'Account inactive');
                throw new \Exception('Account is not active. Please contact administrator.');
            }
            
            // Verify password
            if (!$this->security->verifyPassword($password, $user['password_hash'])) {
                $this->handleFailedLogin($user['id'], $username);
                throw new \Exception('Invalid username or password.');
            }
            
            // Successful login
            $this->handleSuccessfulLogin($user, $rememberMe);
            
            // Reset rate limiting for this IP
            $this->security->resetRateLimit($identifier, 'login');
            
            // Log successful login
            $this->logger->logAdminAction($user['id'], 'login', 'User logged in successfully', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            // Redirect to intended page or dashboard
            $intendedUrl = $this->session->get('intended_url', '/admin/dashboard');
            $this->session->remove('intended_url');
            
            Router::redirect($intendedUrl);
            
        } catch (\Exception $e) {
            $this->session->flash('error', $e->getMessage());
            Router::redirect('/login');
        }
    }
    
    public function logout()
    {
        $user = $this->session->getUser();
        
        if ($user) {
            $this->logger->logAdminAction($user['id'], 'logout', 'User logged out', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        $this->session->logout();
        $this->session->flash('success', 'You have been logged out successfully.');
        
        Router::redirect('/login');
    }
    
    private function handleFailedLogin($userId, $username)
    {
        // Increment login attempts
        $stmt = $this->db->prepare("
            UPDATE admins 
            SET login_attempts = login_attempts + 1,
                locked_until = CASE 
                    WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                    ELSE locked_until 
                END
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        
        $this->logFailedAttempt($username, 'Invalid password');
    }
    
    private function handleSuccessfulLogin($user, $rememberMe)
    {
        // Reset login attempts
        $stmt = $this->db->prepare("
            UPDATE admins 
            SET login_attempts = 0, locked_until = NULL, last_login = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        // Parse permissions if JSON
        $permissions = [];
        if ($user['permissions']) {
            $permissions = json_decode($user['permissions'], true) ?: [];
        }
        
        // Set session data
        $this->session->setUser([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'permissions' => $permissions
        ]);
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $token = $this->security->generateRandomString(64);
            // In a real application, you'd store this token securely and use it for auto-login
            setcookie(
                'remember_token',
                $token,
                time() + (30 * 24 * 60 * 60), // 30 days
                '/',
                '',
                isset($_SERVER['HTTPS']),
                true
            );
        }
    }
    
    private function logFailedAttempt($username, $reason)
    {
        $this->logger->warning("Failed login attempt", [
            'username' => $username,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        $this->security->logSecurityEvent('Failed login attempt', [
            'username' => $username,
            'reason' => $reason
        ]);
    }
}