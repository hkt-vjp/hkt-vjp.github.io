<?php

namespace NetflixLoginService\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Exception;

class Logger
{
    private static $instance = null;
    private $logger;
    
    public function __construct()
    {
        if (self::$instance === null) {
            $this->initializeLogger();
            self::$instance = $this;
        }
        
        $this->logger = self::$instance->logger;
    }
    
    private function initializeLogger()
    {
        $this->logger = new MonologLogger('netflix-login-service');
        
        $logPath = $_ENV['LOG_PATH'] ?? 'storage/logs';
        $logLevel = $this->getLogLevel($_ENV['LOG_LEVEL'] ?? 'info');
        
        // Ensure log directory exists
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        try {
            // Main application log
            $appHandler = new RotatingFileHandler(
                $logPath . '/app.log',
                0, // Keep all files
                $logLevel
            );
            
            // Error log
            $errorHandler = new RotatingFileHandler(
                $logPath . '/error.log',
                0,
                MonologLogger::ERROR
            );
            
            // Security log
            $securityHandler = new RotatingFileHandler(
                $logPath . '/security.log',
                0,
                MonologLogger::WARNING
            );
            
            // Custom formatter
            $formatter = new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s'
            );
            
            $appHandler->setFormatter($formatter);
            $errorHandler->setFormatter($formatter);
            $securityHandler->setFormatter($formatter);
            
            $this->logger->pushHandler($appHandler);
            $this->logger->pushHandler($errorHandler);
            $this->logger->pushHandler($securityHandler);
            
            // Console output in debug mode
            if ($_ENV['APP_DEBUG'] === 'true') {
                $consoleHandler = new StreamHandler('php://stdout', $logLevel);
                $consoleHandler->setFormatter($formatter);
                $this->logger->pushHandler($consoleHandler);
            }
            
        } catch (Exception $e) {
            error_log("Failed to initialize logger: " . $e->getMessage());
        }
    }
    
    private function getLogLevel($level)
    {
        switch (strtolower($level)) {
            case 'debug':
                return MonologLogger::DEBUG;
            case 'info':
                return MonologLogger::INFO;
            case 'notice':
                return MonologLogger::NOTICE;
            case 'warning':
                return MonologLogger::WARNING;
            case 'error':
                return MonologLogger::ERROR;
            case 'critical':
                return MonologLogger::CRITICAL;
            case 'alert':
                return MonologLogger::ALERT;
            case 'emergency':
                return MonologLogger::EMERGENCY;
            default:
                return MonologLogger::INFO;
        }
    }
    
    public function debug($message, array $context = [])
    {
        $this->logger->debug($message, $this->addContext($context));
    }
    
    public function info($message, array $context = [])
    {
        $this->logger->info($message, $this->addContext($context));
    }
    
    public function notice($message, array $context = [])
    {
        $this->logger->notice($message, $this->addContext($context));
    }
    
    public function warning($message, array $context = [])
    {
        $this->logger->warning($message, $this->addContext($context));
    }
    
    public function error($message, array $context = [])
    {
        $this->logger->error($message, $this->addContext($context));
    }
    
    public function critical($message, array $context = [])
    {
        $this->logger->critical($message, $this->addContext($context));
    }
    
    public function alert($message, array $context = [])
    {
        $this->logger->alert($message, $this->addContext($context));
    }
    
    public function emergency($message, array $context = [])
    {
        $this->logger->emergency($message, $this->addContext($context));
    }
    
    private function addContext(array $context = [])
    {
        return array_merge($context, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ]);
    }
    
    public function logAdminAction($adminId, $action, $description, array $additionalData = [])
    {
        $this->info("Admin Action: {$action}", [
            'admin_id' => $adminId,
            'action' => $action,
            'description' => $description,
            'additional_data' => $additionalData
        ]);
        
        // Also log to database
        $this->logToDatabase($adminId, $action, $description, $additionalData);
    }
    
    public function logCodeRetrieval($orderId, $customerEmail, $success, array $details = [])
    {
        $message = $success ? "Code retrieval successful" : "Code retrieval failed";
        
        $context = [
            'order_id' => $orderId,
            'customer_email' => $customerEmail,
            'success' => $success,
            'details' => $details
        ];
        
        if ($success) {
            $this->info($message, $context);
        } else {
            $this->warning($message, $context);
        }
    }
    
    public function logSecurityEvent($event, array $details = [])
    {
        $this->warning("Security Event: {$event}", $details);
    }
    
    private function logToDatabase($adminId, $action, $description, array $additionalData = [])
    {
        try {
            $db = \NetflixLoginService\Config\Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, action, description, ip_address, user_agent, additional_data)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $adminId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                json_encode($additionalData)
            ]);
            
        } catch (Exception $e) {
            $this->error("Failed to log to database", [
                'error' => $e->getMessage(),
                'admin_id' => $adminId,
                'action' => $action
            ]);
        }
    }
}