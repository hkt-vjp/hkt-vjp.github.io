<?php

namespace NetflixLoginService\Core;

use NetflixLoginService\Config\Database;
use NetflixLoginService\Utils\Logger;
use Exception;

class Session
{
    private $logger;
    private $sessionLifetime;
    
    public function __construct()
    {
        $this->logger = new Logger();
        $this->sessionLifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 7200); // 2 hours default
        
        $this->configure();
        $this->start();
    }
    
    private function configure()
    {
        // Configure session settings
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', $this->sessionLifetime);
        
        // Set custom session handler to use database
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc']
        );
        
        register_shutdown_function('session_write_close');
    }
    
    private function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['last_regeneration'])) {
                $this->regenerateId();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                $this->regenerateId();
            }
        }
    }
    
    public function regenerateId()
    {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }
    
    public function remove($key)
    {
        unset($_SESSION[$key]);
    }
    
    public function clear()
    {
        $_SESSION = [];
    }
    
    public function destroy()
    {
        session_destroy();
        $_SESSION = [];
    }
    
    public function flash($key, $value = null)
    {
        if ($value === null) {
            // Get flash message
            $message = $this->get("flash_{$key}");
            $this->remove("flash_{$key}");
            return $message;
        } else {
            // Set flash message
            $this->set("flash_{$key}", $value);
        }
    }
    
    public function setUser($user)
    {
        $this->set('user_id', $user['id']);
        $this->set('user_username', $user['username']);
        $this->set('user_role', $user['role']);
        $this->set('user_permissions', $user['permissions'] ?? []);
        $this->set('login_time', time());
        
        // Update last login in database
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
        } catch (Exception $e) {
            $this->logger->error("Failed to update last login time", ['error' => $e->getMessage()]);
        }
    }
    
    public function getUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $this->get('user_id'),
            'username' => $this->get('user_username'),
            'role' => $this->get('user_role'),
            'permissions' => $this->get('user_permissions', []),
            'login_time' => $this->get('login_time')
        ];
    }
    
    public function isLoggedIn()
    {
        return $this->has('user_id') && $this->has('login_time');
    }
    
    public function isSuperAdmin()
    {
        return $this->isLoggedIn() && $this->get('user_role') === 'superadmin';
    }
    
    public function hasPermission($permission)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        $permissions = $this->get('user_permissions', []);
        return in_array($permission, $permissions);
    }
    
    public function logout()
    {
        // Log the logout
        $user = $this->getUser();
        if ($user) {
            $this->logger->info("User logged out", [
                'user_id' => $user['id'],
                'username' => $user['username']
            ]);
        }
        
        $this->clear();
        $this->destroy();
    }
    
    public function generateCSRFToken()
    {
        if (!$this->has('csrf_token')) {
            $this->set('csrf_token', bin2hex(random_bytes(32)));
        }
        return $this->get('csrf_token');
    }
    
    public function validateCSRFToken($token)
    {
        $sessionToken = $this->get('csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }
    
    // Custom session handlers for database storage
    public function open($save_path, $session_name)
    {
        return true;
    }
    
    public function close()
    {
        return true;
    }
    
    public function read($session_id)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT admin_id, ip_address, user_agent, last_activity 
                FROM admin_sessions 
                WHERE id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$session_id, $this->sessionLifetime]);
            $session = $stmt->fetch();
            
            if ($session) {
                // Verify IP and user agent for security
                $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
                $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
                
                if ($session['ip_address'] !== $currentIP || $session['user_agent'] !== $currentUA) {
                    $this->logger->warning("Session security mismatch", [
                        'session_id' => $session_id,
                        'stored_ip' => $session['ip_address'],
                        'current_ip' => $currentIP
                    ]);
                    return '';
                }
                
                // Update last activity
                $updateStmt = $db->prepare("UPDATE admin_sessions SET last_activity = NOW() WHERE id = ?");
                $updateStmt->execute([$session_id]);
                
                // Return session data (we'll store it in $_SESSION)
                return serialize([
                    'user_id' => $session['admin_id'],
                    'last_activity' => time()
                ]);
            }
            
            return '';
        } catch (Exception $e) {
            $this->logger->error("Session read error", ['error' => $e->getMessage()]);
            return '';
        }
    }
    
    public function write($session_id, $session_data)
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get user ID from session data
            $data = unserialize($session_data);
            $userId = $data['user_id'] ?? null;
            
            if (!$userId) {
                return true; // No user logged in, don't store session
            }
            
            $stmt = $db->prepare("
                INSERT INTO admin_sessions (id, admin_id, ip_address, user_agent, last_activity) 
                VALUES (?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                    admin_id = VALUES(admin_id),
                    ip_address = VALUES(ip_address),
                    user_agent = VALUES(user_agent),
                    last_activity = NOW()
            ");
            
            return $stmt->execute([
                $session_id,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            $this->logger->error("Session write error", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function destroy($session_id)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM admin_sessions WHERE id = ?");
            return $stmt->execute([$session_id]);
        } catch (Exception $e) {
            $this->logger->error("Session destroy error", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function gc($maxlifetime)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM admin_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)");
            return $stmt->execute([$maxlifetime]);
        } catch (Exception $e) {
            $this->logger->error("Session garbage collection error", ['error' => $e->getMessage()]);
            return false;
        }
    }
}