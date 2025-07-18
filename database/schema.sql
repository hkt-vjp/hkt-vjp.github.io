-- Netflix Login Code Service Database Schema
-- Created: $(date)
-- Version: 1.0

CREATE DATABASE IF NOT EXISTS netflix_login_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE netflix_login_service;

-- Admins table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('superadmin', 'admin') DEFAULT 'admin',
    avatar VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    permissions JSON NULL, -- For fine-grained permissions
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Netflix emails table (for receiving login codes)
CREATE TABLE netflix_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    display_name VARCHAR(100) NULL,
    gmail_refresh_token TEXT NULL,
    gmail_access_token TEXT NULL,
    token_expires_at TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'error') DEFAULT 'active',
    last_sync TIMESTAMP NULL,
    usage_count INT DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin_email (admin_id, email),
    INDEX idx_admin_id (admin_id),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    order_code VARCHAR(50) UNIQUE NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    netflix_email_id INT NOT NULL,
    status ENUM('active', 'expired', 'pending', 'suspended') DEFAULT 'active',
    expires_at TIMESTAMP NOT NULL,
    usage_count INT DEFAULT 0,
    max_usage INT DEFAULT 999, -- Maximum times this order can be used
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (netflix_email_id) REFERENCES netflix_emails(id) ON DELETE RESTRICT,
    INDEX idx_admin_id (admin_id),
    INDEX idx_order_code (order_code),
    INDEX idx_customer_email (customer_email),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
);

-- Code retrieval logs
CREATE TABLE code_retrievals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    netflix_email_id INT NOT NULL,
    success BOOLEAN DEFAULT TRUE,
    login_code VARCHAR(20) NULL,
    login_link TEXT NULL,
    email_subject VARCHAR(255) NULL,
    email_date TIMESTAMP NULL,
    error_message TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    retrieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (netflix_email_id) REFERENCES netflix_emails(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_customer_email (customer_email),
    INDEX idx_retrieved_at (retrieved_at),
    INDEX idx_success (success)
);

-- Admin activity logs
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    additional_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- System settings
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    description TEXT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
);

-- Sessions table
CREATE TABLE admin_sessions (
    id VARCHAR(128) PRIMARY KEY,
    admin_id INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_last_activity (last_activity)
);

-- Rate limiting table
CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(100) NOT NULL, -- IP address or user ID
    type VARCHAR(50) NOT NULL, -- login, code_retrieval, etc.
    attempts INT DEFAULT 1,
    reset_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_type (identifier, type),
    INDEX idx_reset_time (reset_time)
);

-- Insert default super admin
INSERT INTO admins (username, email, password_hash, full_name, role, status) 
VALUES (
    'superadmin', 
    'admin@yourdomain.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123456
    'Super Administrator', 
    'superadmin', 
    'active'
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'Netflix Login Code Service', 'Website name displayed in header'),
('maintenance_mode', '0', 'Enable/disable maintenance mode'),
('max_login_attempts', '5', 'Maximum login attempts before lockout'),
('lockout_duration', '15', 'Lockout duration in minutes'),
('code_expiry_warning_days', '7', 'Days before expiry to show warning'),
('default_order_duration', '30', 'Default order duration in days'),
('gmail_api_enabled', '1', 'Enable Gmail API integration'),
('auto_refresh_tokens', '1', 'Automatically refresh Gmail tokens');

-- Create indexes for better performance
CREATE INDEX idx_orders_customer_code ON orders(customer_email, order_code);
CREATE INDEX idx_code_retrievals_date ON code_retrievals(retrieved_at DESC);
CREATE INDEX idx_admin_logs_date ON admin_logs(created_at DESC);

-- Create views for common queries
CREATE VIEW active_orders AS
SELECT 
    o.*,
    a.username as admin_username,
    ne.email as netflix_email,
    DATEDIFF(o.expires_at, NOW()) as days_until_expiry
FROM orders o
JOIN admins a ON o.admin_id = a.id
JOIN netflix_emails ne ON o.netflix_email_id = ne.id
WHERE o.status = 'active' AND o.expires_at > NOW();

CREATE VIEW admin_statistics AS
SELECT 
    a.id,
    a.username,
    a.full_name,
    COUNT(DISTINCT o.id) as total_orders,
    COUNT(DISTINCT ne.id) as total_emails,
    COUNT(DISTINCT cr.id) as total_retrievals,
    MAX(cr.retrieved_at) as last_code_retrieval
FROM admins a
LEFT JOIN orders o ON a.id = o.admin_id
LEFT JOIN netflix_emails ne ON a.id = ne.admin_id
LEFT JOIN code_retrievals cr ON o.id = cr.order_id
WHERE a.status = 'active'
GROUP BY a.id, a.username, a.full_name;