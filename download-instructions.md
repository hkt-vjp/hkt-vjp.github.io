# 📥 Hướng dẫn Download Netflix Login Code Service

## 🗂️ Danh sách Files cần tạo:

### **Root Files:**
1. `composer.json` - Quản lý dependencies
2. `.env.example` - File cấu hình mẫu  
3. `README.md` - Hướng dẫn sử dụng
4. `install.php` - Trình cài đặt

### **Database:**
5. `database/schema.sql` - Database schema

### **Public Files:**
6. `public/index.php` - Entry point
7. `public/.htaccess` - Apache configuration
8. `public/assets/css/admin.css` - CSS styles
9. `public/assets/js/admin.js` - JavaScript

### **Source Code:**
10. `src/Config/Database.php` - Database connection
11. `src/Core/Application.php` - Main application
12. `src/Core/Router.php` - URL routing
13. `src/Core/Session.php` - Session management
14. `src/Core/Security.php` - Security utilities
15. `src/Controllers/BaseController.php` - Base controller
16. `src/Controllers/Auth/LoginController.php` - Login controller
17. `src/Controllers/Admin/DashboardController.php` - Dashboard
18. `src/Middleware/AuthMiddleware.php` - Authentication
19. `src/Middleware/AdminMiddleware.php` - Admin authorization
20. `src/Middleware/SuperAdminMiddleware.php` - Super admin auth
21. `src/Services/GmailService.php` - Gmail API service
22. `src/Utils/Logger.php` - Logging utility
23. `src/Views/layouts/main.php` - Main layout
24. `src/Views/auth/login.php` - Login page

## 📋 Các bước thực hiện:

### **Bước 1: Tạo thư mục gốc**
```bash
mkdir netflix-login-service
cd netflix-login-service
```

### **Bước 2: Tạo cấu trúc thư mục**
```bash
mkdir -p database
mkdir -p public/assets/css
mkdir -p public/assets/js
mkdir -p src/Config
mkdir -p src/Core
mkdir -p src/Controllers/Auth
mkdir -p src/Controllers/Admin
mkdir -p src/Middleware
mkdir -p src/Services
mkdir -p src/Utils
mkdir -p src/Views/layouts
mkdir -p src/Views/auth
mkdir -p storage/logs
mkdir -p storage/cache
mkdir -p storage/backups
```

### **Bước 3: Copy nội dung từng file**

#### **1. composer.json**
```json
{
    "name": "netflix-login-code-service/admin-panel",
    "description": "Netflix Login Code Service - Admin Panel for managing Netflix login codes with Gmail API integration",
    "type": "project",
    "require": {
        "php": ">=7.4",
        "google/apiclient": "^2.12",
        "vlucas/phpdotenv": "^5.4",
        "firebase/php-jwt": "^6.3",
        "phpmailer/phpmailer": "^6.6",
        "ramsey/uuid": "^4.2",
        "monolog/monolog": "^2.8"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5"
    },
    "autoload": {
        "psr-4": {
            "NetflixLoginService\\": "src/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "php -r \"copy('.env.example', '.env');\""
        ]
    }
}
```

#### **2. .env.example**
```env
# Netflix Login Code Service Configuration

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=netflix_login_service
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Application Settings
APP_NAME="Netflix Login Code Service"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=Asia/Ho_Chi_Minh

# Security
JWT_SECRET=your_jwt_secret_key_here_change_this
SESSION_SECRET=your_session_secret_key_here_change_this
ENCRYPTION_KEY=your_encryption_key_here_32_chars

# Gmail API Configuration
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/admin/gmail-callback

# Rate Limiting
RATE_LIMIT_MAX_ATTEMPTS=5
RATE_LIMIT_DECAY_MINUTES=15

# Email Settings (for notifications)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Netflix Login Service"

# Logging
LOG_LEVEL=info
LOG_PATH=storage/logs

# File Upload
MAX_FILE_SIZE=2097152
ALLOWED_FILE_TYPES=jpg,jpeg,png,gif

# Admin Settings
DEFAULT_SUPER_ADMIN_EMAIL=admin@yourdomain.com
DEFAULT_SUPER_ADMIN_PASSWORD=admin123456
SESSION_LIFETIME=7200

# Backup Settings
BACKUP_PATH=storage/backups
AUTO_BACKUP_ENABLED=true
AUTO_BACKUP_INTERVAL=daily
```

### **Bước 4: Cài đặt**
```bash
# Cài đặt Composer dependencies
composer install

# Copy file cấu hình
cp .env.example .env

# Chỉnh sửa .env với thông tin của bạn
nano .env

# Chạy installer
php -S localhost:8000 -t public
# Sau đó truy cập: http://localhost:8000/install.php
```

## 💡 **Tips:**

1. **Sử dụng IDE/Editor tốt:** Visual Studio Code, PHPStorm
2. **Git tracking:** Khởi tạo git repo để theo dõi changes
3. **Backup:** Backup code thường xuyên
4. **Testing:** Test trên localhost trước khi deploy

## 🔗 **Links hữu ích:**

- [Composer Download](https://getcomposer.org/download/)
- [Google Cloud Console](https://console.cloud.google.com/)
- [Gmail API Documentation](https://developers.google.com/gmail/api)

## ⚠️ **Lưu ý:**

- Đảm bảo PHP >= 7.4
- Cần MySQL database
- Cần cấu hình Gmail API
- Chmod 755/777 cho thư mục storage/
- Sử dụng HTTPS trong production

---

**Happy Coding! 🚀**