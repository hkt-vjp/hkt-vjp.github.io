<?php

namespace NetflixLoginService\Core;

use NetflixLoginService\Config\Database;
use NetflixLoginService\Utils\Logger;
use Exception;

class Security
{
    private $logger;
    private $db;
    
    public function __construct()
    {
        $this->logger = new Logger();
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function applySecurityHeaders()
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self';";
        header("Content-Security-Policy: {$csp}");
        
        // HTTPS enforcement in production
        if ($_ENV['APP_ENV'] === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    public function checkMaintenanceMode()
    {
        $maintenanceMode = $this->getSystemSetting('maintenance_mode', '0');
        
        if ($maintenanceMode === '1') {
            // Allow super admin to bypass maintenance mode
            $session = new Session();
            if (!$session->isSuperAdmin()) {
                http_response_code(503);
                include __DIR__ . '/../Views/maintenance.php';
                exit;
            }
        }
    }
    
    public function checkRateLimit($identifier, $type, $maxAttempts = null, $decayMinutes = null)
    {
        $maxAttempts = $maxAttempts ?? (int)($_ENV['RATE_LIMIT_MAX_ATTEMPTS'] ?? 5);
        $decayMinutes = $decayMinutes ?? (int)($_ENV['RATE_LIMIT_DECAY_MINUTES'] ?? 15);
        
        try {
            // Clean old rate limit records
            $this->cleanOldRateLimits($decayMinutes);
            
            // Check current attempts
            $stmt = $this->db->prepare("
                SELECT attempts, reset_time 
                FROM rate_limits 
                WHERE identifier = ? AND type = ? 
                AND reset_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([$identifier, $type, $decayMinutes]);
            $rateLimit = $stmt->fetch();
            
            if ($rateLimit) {
                if ($rateLimit['attempts'] >= $maxAttempts) {
                    $resetTime = strtotime($rateLimit['reset_time']) + ($decayMinutes * 60);
                    $remainingTime = max(0, $resetTime - time());
                    
                    $this->logger->warning("Rate limit exceeded", [
                        'identifier' => $identifier,
                        'type' => $type,
                        'attempts' => $rateLimit['attempts'],
                        'remaining_time' => $remainingTime
                    ]);
                    
                    return [
                        'allowed' => false,
                        'remaining_time' => $remainingTime,
                        'attempts' => $rateLimit['attempts']
                    ];
                }
                
                // Increment attempts
                $updateStmt = $this->db->prepare("
                    UPDATE rate_limits 
                    SET attempts = attempts + 1, reset_time = NOW() 
                    WHERE identifier = ? AND type = ?
                ");
                $updateStmt->execute([$identifier, $type]);
                
                return [
                    'allowed' => true,
                    'attempts' => $rateLimit['attempts'] + 1,
                    'remaining' => $maxAttempts - ($rateLimit['attempts'] + 1)
                ];
            } else {
                // First attempt
                $insertStmt = $this->db->prepare("
                    INSERT INTO rate_limits (identifier, type, attempts, reset_time) 
                    VALUES (?, ?, 1, NOW())
                ");
                $insertStmt->execute([$identifier, $type]);
                
                return [
                    'allowed' => true,
                    'attempts' => 1,
                    'remaining' => $maxAttempts - 1
                ];
            }
            
        } catch (Exception $e) {
            $this->logger->error("Rate limit check failed", ['error' => $e->getMessage()]);
            // Allow on error to prevent system lockout
            return ['allowed' => true, 'attempts' => 0, 'remaining' => $maxAttempts];
        }
    }
    
    private function cleanOldRateLimits($decayMinutes)
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM rate_limits 
                WHERE reset_time < DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([$decayMinutes]);
        } catch (Exception $e) {
            $this->logger->error("Failed to clean old rate limits", ['error' => $e->getMessage()]);
        }
    }
    
    public function resetRateLimit($identifier, $type)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE identifier = ? AND type = ?");
            $stmt->execute([$identifier, $type]);
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to reset rate limit", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function validateCSRF($token = null)
    {
        $token = $token ?? ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null);
        
        if (!$token) {
            return false;
        }
        
        $session = new Session();
        return $session->validateCSRFToken($token);
    }
    
    public function sanitizeInput($input, $type = 'string')
    {
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    public function validateInput($input, $type, $options = [])
    {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
            
            case 'int':
                $min = $options['min'] ?? null;
                $max = $options['max'] ?? null;
                $flags = 0;
                $filterOptions = [];
                
                if ($min !== null) {
                    $filterOptions['min_range'] = $min;
                }
                if ($max !== null) {
                    $filterOptions['max_range'] = $max;
                }
                
                if (!empty($filterOptions)) {
                    return filter_var($input, FILTER_VALIDATE_INT, [
                        'options' => $filterOptions
                    ]) !== false;
                }
                
                return filter_var($input, FILTER_VALIDATE_INT) !== false;
            
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) !== false;
            
            case 'required':
                return !empty(trim($input));
            
            case 'min_length':
                return strlen($input) >= ($options['length'] ?? 0);
            
            case 'max_length':
                return strlen($input) <= ($options['length'] ?? PHP_INT_MAX);
            
            case 'regex':
                return preg_match($options['pattern'], $input) === 1;
            
            default:
                return true;
        }
    }
    
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    public function generateRandomString($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    public function encryptData($data, $key = null)
    {
        $key = $key ?? $_ENV['ENCRYPTION_KEY'];
        if (!$key) {
            throw new Exception("Encryption key not configured");
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    public function decryptData($encryptedData, $key = null)
    {
        $key = $key ?? $_ENV['ENCRYPTION_KEY'];
        if (!$key) {
            throw new Exception("Encryption key not configured");
        }
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    private function getSystemSetting($key, $default = null)
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            return $result ? $result['setting_value'] : $default;
        } catch (Exception $e) {
            $this->logger->error("Failed to get system setting", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }
    
    public function logSecurityEvent($event, $details = [])
    {
        $this->logger->warning("Security Event: {$event}", array_merge($details, [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }
}