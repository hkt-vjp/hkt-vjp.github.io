<?php

namespace NetflixLoginService\Controllers\Admin;

use NetflixLoginService\Controllers\BaseController;
use NetflixLoginService\Config\Database;
use NetflixLoginService\Core\Session;
use NetflixLoginService\Utils\Logger;

class DashboardController extends BaseController
{
    private $db;
    private $logger;
    
    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }
    
    public function index()
    {
        $user = $this->getCurrentUser();
        
        // Get statistics
        $stats = $this->getDashboardStats($user);
        
        // Get recent activities
        $recentOrders = $this->getRecentOrders($user);
        $recentRetrievals = $this->getRecentRetrievals($user);
        $recentLogs = $this->getRecentLogs($user);
        
        // Get alerts
        $alerts = $this->getSystemAlerts($user);
        
        $this->render('admin/dashboard', [
            'title' => 'Dashboard - Admin Panel',
            'stats' => $stats,
            'recent_orders' => $recentOrders,
            'recent_retrievals' => $recentRetrievals,
            'recent_logs' => $recentLogs,
            'alerts' => $alerts
        ]);
    }
    
    private function getDashboardStats($user)
    {
        $stats = [];
        
        try {
            if ($user['role'] === 'superadmin') {
                // Super admin sees all stats
                $stmt = $this->db->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM orders) as total_orders,
                        (SELECT COUNT(*) FROM orders WHERE status = 'active') as active_orders,
                        (SELECT COUNT(*) FROM orders WHERE expires_at < NOW()) as expired_orders,
                        (SELECT COUNT(*) FROM netflix_emails) as total_emails,
                        (SELECT COUNT(*) FROM netflix_emails WHERE status = 'active') as active_emails,
                        (SELECT COUNT(*) FROM admins WHERE status = 'active') as total_admins,
                        (SELECT COUNT(*) FROM code_retrievals WHERE DATE(retrieved_at) = CURDATE()) as today_retrievals,
                        (SELECT COUNT(*) FROM code_retrievals WHERE success = 1 AND DATE(retrieved_at) = CURDATE()) as today_successful
                ");
                $stmt->execute();
            } else {
                // Regular admin sees only their stats
                $stmt = $this->db->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM orders WHERE admin_id = ?) as total_orders,
                        (SELECT COUNT(*) FROM orders WHERE admin_id = ? AND status = 'active') as active_orders,
                        (SELECT COUNT(*) FROM orders WHERE admin_id = ? AND expires_at < NOW()) as expired_orders,
                        (SELECT COUNT(*) FROM netflix_emails WHERE admin_id = ?) as total_emails,
                        (SELECT COUNT(*) FROM netflix_emails WHERE admin_id = ? AND status = 'active') as active_emails,
                        (SELECT COUNT(*) FROM code_retrievals cr 
                         JOIN orders o ON cr.order_id = o.id 
                         WHERE o.admin_id = ? AND DATE(cr.retrieved_at) = CURDATE()) as today_retrievals,
                        (SELECT COUNT(*) FROM code_retrievals cr 
                         JOIN orders o ON cr.order_id = o.id 
                         WHERE o.admin_id = ? AND cr.success = 1 AND DATE(cr.retrieved_at) = CURDATE()) as today_successful
                ");
                $stmt->execute([
                    $user['id'], $user['id'], $user['id'], $user['id'], 
                    $user['id'], $user['id'], $user['id']
                ]);
            }
            
            $stats = $stmt->fetch();
            
            // Calculate success rate
            if ($stats['today_retrievals'] > 0) {
                $stats['success_rate'] = round(($stats['today_successful'] / $stats['today_retrievals']) * 100, 1);
            } else {
                $stats['success_rate'] = 0;
            }
            
            // Get additional stats
            $stats['expiring_soon'] = $this->getExpiringSoonCount($user);
            $stats['gmail_errors'] = $this->getGmailErrorsCount($user);
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to get dashboard stats", [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            // Return default stats on error
            $stats = [
                'total_orders' => 0,
                'active_orders' => 0,
                'expired_orders' => 0,
                'total_emails' => 0,
                'active_emails' => 0,
                'today_retrievals' => 0,
                'today_successful' => 0,
                'success_rate' => 0,
                'expiring_soon' => 0,
                'gmail_errors' => 0
            ];
            
            if ($user['role'] === 'superadmin') {
                $stats['total_admins'] = 0;
            }
        }
        
        return $stats;
    }
    
    private function getRecentOrders($user)
    {
        try {
            if ($user['role'] === 'superadmin') {
                $stmt = $this->db->prepare("
                    SELECT o.*, a.username as admin_username, ne.email as netflix_email
                    FROM orders o
                    JOIN admins a ON o.admin_id = a.id
                    JOIN netflix_emails ne ON o.netflix_email_id = ne.id
                    ORDER BY o.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("
                    SELECT o.*, ne.email as netflix_email
                    FROM orders o
                    JOIN netflix_emails ne ON o.netflix_email_id = ne.id
                    WHERE o.admin_id = ?
                    ORDER BY o.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute([$user['id']]);
            }
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get recent orders", [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    private function getRecentRetrievals($user)
    {
        try {
            if ($user['role'] === 'superadmin') {
                $stmt = $this->db->prepare("
                    SELECT cr.*, o.order_code, o.customer_email, ne.email as netflix_email, a.username as admin_username
                    FROM code_retrievals cr
                    JOIN orders o ON cr.order_id = o.id
                    JOIN netflix_emails ne ON cr.netflix_email_id = ne.id
                    JOIN admins a ON o.admin_id = a.id
                    ORDER BY cr.retrieved_at DESC
                    LIMIT 15
                ");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("
                    SELECT cr.*, o.order_code, o.customer_email, ne.email as netflix_email
                    FROM code_retrievals cr
                    JOIN orders o ON cr.order_id = o.id
                    JOIN netflix_emails ne ON cr.netflix_email_id = ne.id
                    WHERE o.admin_id = ?
                    ORDER BY cr.retrieved_at DESC
                    LIMIT 15
                ");
                $stmt->execute([$user['id']]);
            }
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get recent retrievals", [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    private function getRecentLogs($user)
    {
        try {
            if ($user['role'] === 'superadmin') {
                $stmt = $this->db->prepare("
                    SELECT al.*, a.username
                    FROM admin_logs al
                    LEFT JOIN admins a ON al.admin_id = a.id
                    ORDER BY al.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("
                    SELECT al.*, a.username
                    FROM admin_logs al
                    LEFT JOIN admins a ON al.admin_id = a.id
                    WHERE al.admin_id = ?
                    ORDER BY al.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute([$user['id']]);
            }
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get recent logs", [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    private function getSystemAlerts($user)
    {
        $alerts = [];
        
        try {
            // Check for expiring orders
            $expiringSoon = $this->getExpiringSoonCount($user);
            if ($expiringSoon > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-exclamation-triangle',
                    'title' => 'Đơn hàng sắp hết hạn',
                    'message' => "Có {$expiringSoon} đơn hàng sẽ hết hạn trong 7 ngày tới.",
                    'action' => '/admin/orders?status=expiring'
                ];
            }
            
            // Check for Gmail connection errors
            $gmailErrors = $this->getGmailErrorsCount($user);
            if ($gmailErrors > 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'icon' => 'fas fa-exclamation-circle',
                    'title' => 'Lỗi kết nối Gmail',
                    'message' => "Có {$gmailErrors} email gặp lỗi kết nối Gmail.",
                    'action' => '/admin/emails?status=error'
                ];
            }
            
            // Check for recent failed retrievals
            $failedToday = $this->getFailedRetrievalsToday($user);
            if ($failedToday > 5) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-exclamation-triangle',
                    'title' => 'Nhiều lỗi lấy mã',
                    'message' => "Có {$failedToday} lần lấy mã thất bại hôm nay.",
                    'action' => '/admin/logs/retrievals?status=failed'
                ];
            }
            
            // Super admin specific alerts
            if ($user['role'] === 'superadmin') {
                // Check for inactive admins
                $inactiveAdmins = $this->getInactiveAdminsCount();
                if ($inactiveAdmins > 0) {
                    $alerts[] = [
                        'type' => 'info',
                        'icon' => 'fas fa-users',
                        'title' => 'Admin không hoạt động',
                        'message' => "Có {$inactiveAdmins} admin chưa đăng nhập trong 30 ngày.",
                        'action' => '/admin/admins?status=inactive'
                    ];
                }
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to get system alerts", [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
        
        return $alerts;
    }
    
    private function getExpiringSoonCount($user)
    {
        try {
            if ($user['role'] === 'superadmin') {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count
                    FROM orders
                    WHERE status = 'active' 
                    AND expires_at > NOW() 
                    AND expires_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                ");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count
                    FROM orders
                    WHERE admin_id = ? 
                    AND status = 'active' 
                    AND expires_at > NOW() 
                    AND expires_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                ");
                $stmt->execute([$user['id']]);
            }
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getGmailErrorsCount($user)
    {
        try {
            if ($user['role'] === 'superadmin') {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count
                    FROM netflix_emails
                    WHERE status = 'error'
                ");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count
                    FROM netflix_emails
                    WHERE admin_id = ? AND status = 'error'
                ");
                $stmt->execute([$user['id']]);
            }
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getFailedRetrievalsToday($user)
    {
        try {
            if ($user['role'] === 'superadmin') {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count
                    FROM code_retrievals
                    WHERE success = 0 AND DATE(retrieved_at) = CURDATE()
                ");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count
                    FROM code_retrievals cr
                    JOIN orders o ON cr.order_id = o.id
                    WHERE o.admin_id = ? AND cr.success = 0 AND DATE(cr.retrieved_at) = CURDATE()
                ");
                $stmt->execute([$user['id']]);
            }
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getInactiveAdminsCount()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM admins
                WHERE status = 'active' 
                AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY))
                AND role != 'superadmin'
            ");
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}