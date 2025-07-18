# Netflix Login Code Service

Hệ thống quản lý và phân phối mã đăng nhập Netflix tự động, bảo mật và chuyên nghiệp.

## 📋 Mô tả

Netflix Login Code Service là một hệ thống web được thiết kế để giúp các admin quản lý và phân phối mã đăng nhập Netflix một cách tự động. Hệ thống tích hợp với Gmail API để tự động lấy mã OTP và liên kết đăng nhập từ email Netflix, giúp khách hàng dễ dàng truy cập tài khoản mà không cần truy cập email thật.

## ✨ Tính năng chính

### 🔐 Admin Panel Đa cấp
- Đăng nhập bảo mật với session management
- Phân quyền: Super Admin và Admin thường
- Mỗi admin quản lý email và đơn hàng của riêng mình
- Thay đổi mật khẩu và quản lý profile

### 📧 Quản lý Email Netflix
- Thêm/sửa/xóa email Netflix để nhận mã
- Tích hợp Gmail API với OAuth 2.0
- Tự động refresh token
- Theo dõi trạng thái kết nối

### 🛒 Quản lý Đơn hàng
- Tạo/sửa/xóa đơn hàng cho khách
- Hạn sử dụng và trạng thái đơn hàng
- Ghi chú và theo dõi đơn hàng
- Gán email nhận mã cho từng đơn hàng

### 🔑 Dịch vụ Lấy mã Đăng nhập
- Khách nhập order code + email
- Kiểm tra tính hợp lệ của đơn hàng
- Tự động truy xuất email mới nhất từ Gmail
- Hiển thị mã OTP và liên kết đăng nhập
- Thông báo chi tiết về thời hạn sử dụng

### 📊 Thống kê & Lịch sử
- Dashboard với thống kê tổng quan
- Log chi tiết từng truy cập
- Báo cáo hoạt động theo thời gian
- Cảnh báo bất thường

### 🔒 Bảo mật
- Mã hóa password với bcrypt
- Session bảo mật với database storage
- CSRF protection
- Rate limiting chống brute-force
- Logging và audit trail
- Headers bảo mật (CSP, XSS Protection, etc.)

## 🛠️ Công nghệ sử dụng

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, jQuery, Font Awesome
- **API**: Gmail API (Google Client Library)
- **Security**: OAuth 2.0, JWT, bcrypt
- **Logging**: Monolog
- **Environment**: Dotenv

## 📦 Cài đặt

### Yêu cầu hệ thống
- PHP 7.4 hoặc cao hơn
- MySQL 5.7 hoặc cao hơn
- Apache/Nginx với mod_rewrite
- Composer
- OpenSSL extension
- PDO MySQL extension
- cURL extension

### 1. Tải về và cài đặt

```bash
# Clone repository
git clone https://github.com/your-repo/netflix-login-service.git
cd netflix-login-service

# Cài đặt dependencies
composer install

# Copy và cấu hình environment
cp .env.example .env
```

### 2. Cấu hình Database

Tạo database MySQL và import schema:

```bash
mysql -u root -p
CREATE DATABASE netflix_login_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

mysql -u root -p netflix_login_service < database/schema.sql
```

### 3. Cấu hình .env

Chỉnh sửa file `.env`:

```bash
# Database
DB_HOST=localhost
DB_NAME=netflix_login_service
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Application
APP_URL=https://yourdomain.com
APP_ENV=production
APP_DEBUG=false

# Security (Tạo key ngẫu nhiên 32 ký tự)
JWT_SECRET=your_jwt_secret_key_here_change_this
SESSION_SECRET=your_session_secret_key_here_change_this
ENCRYPTION_KEY=your_encryption_key_here_32_chars

# Gmail API (Từ Google Cloud Console)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/admin/gmail-callback
```

### 4. Cấu hình Gmail API

1. Truy cập [Google Cloud Console](https://console.cloud.google.com/)
2. Tạo project mới hoặc chọn project có sẵn
3. Enable Gmail API
4. Tạo OAuth 2.0 credentials
5. Thêm redirect URI: `https://yourdomain.com/admin/gmail-callback`
6. Copy Client ID và Client Secret vào file `.env`

### 5. Cấu hình Web Server

#### Apache
Đảm bảo file `.htaccess` trong thư mục `public/` được sử dụng.

#### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/netflix-login-service/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(ht|env|git) {
        deny all;
    }
}
```

### 6. Phân quyền thư mục

```bash
chmod -R 755 /path/to/netflix-login-service
chmod -R 777 /path/to/netflix-login-service/storage
```

## 🚀 Sử dụng

### Đăng nhập Admin

1. Truy cập: `https://yourdomain.com/login`
2. Đăng nhập với tài khoản mặc định:
   - Username: `superadmin`
   - Password: `admin123456` (Thay đổi ngay sau khi đăng nhập)

### Cấu hình Gmail

1. Vào **Admin Panel** > **Cài đặt Gmail**
2. Thêm email Netflix cần quản lý
3. Nhấn **"Kết nối Gmail"** để authorize OAuth
4. Hoàn thành quá trình xác thực

### Tạo đơn hàng

1. Vào **Quản lý đơn hàng** > **Tạo mới**
2. Điền thông tin:
   - Mã đơn hàng (unique)
   - Email khách hàng
   - Chọn email Netflix để nhận mã
   - Thời hạn sử dụng
3. Lưu đơn hàng

### Khách hàng lấy mã

1. Khách truy cập: `https://yourdomain.com/get-code`
2. Nhập mã đơn hàng và email
3. Hệ thống tự động lấy mã từ Gmail
4. Hiển thị mã OTP và liên kết đăng nhập

## 🔧 Cấu hình nâng cao

### Cron Job cho Auto-refresh Token

Thêm vào crontab để tự động refresh Gmail token:

```bash
# Refresh Gmail tokens mỗi 30 phút
*/30 * * * * /usr/bin/php /path/to/netflix-login-service/scripts/refresh-tokens.php

# Cleanup logs cũ mỗi ngày
0 2 * * * /usr/bin/php /path/to/netflix-login-service/scripts/cleanup-logs.php
```

### Backup Database

```bash
# Backup daily
0 1 * * * mysqldump -u root -p netflix_login_service > /backup/netflix_$(date +\%Y\%m\%d).sql
```

### SSL Certificate

Cài đặt SSL certificate cho production:

```bash
# Sử dụng Let's Encrypt
certbot --apache -d yourdomain.com
```

## 📚 API Documentation

### Lấy mã đăng nhập (Public API)

```http
POST /api/code-retrieval
Content-Type: application/json

{
    "order_code": "ORDER123",
    "customer_email": "customer@example.com"
}
```

Response:
```json
{
    "success": true,
    "data": {
        "login_code": "123456",
        "login_link": "https://account.netflix.com/verify?token=...",
        "email_date": "2024-01-01 10:30:00",
        "expires_at": "2024-01-31 23:59:59",
        "days_until_expiry": 7
    }
}
```

### Kiểm tra trạng thái đơn hàng

```http
GET /api/order-status/{order_code}
```

## 🔐 Bảo mật

### Best Practices
- Thay đổi mật khẩu admin mặc định ngay lập tức
- Sử dụng HTTPS trong production
- Backup database thường xuyên
- Monitor logs để phát hiện hoạt động bất thường
- Cập nhật dependencies định kỳ

### Rate Limiting
- Login: 5 attempts / 15 minutes
- Code retrieval: 10 requests / hour per IP
- API calls: 100 requests / hour per IP

## 📊 Monitoring

### Logs Location
- Application logs: `storage/logs/app.log`
- Error logs: `storage/logs/error.log`
- Security logs: `storage/logs/security.log`

### Database Monitoring
- Check connection pool
- Monitor slow queries
- Track storage usage

## 🛠️ Troubleshooting

### Lỗi thường gặp

1. **Gmail API Error**
   - Kiểm tra credentials trong `.env`
   - Verify redirect URI trong Google Console
   - Check token expiration

2. **Database Connection Error**
   - Verify database credentials
   - Check MySQL service status
   - Ensure database exists

3. **Permission Denied**
   - Check file permissions (755/777)
   - Verify web server user ownership
   - Check SELinux/AppArmor settings

### Debug Mode

Bật debug mode để troubleshoot:

```bash
# .env
APP_DEBUG=true
LOG_LEVEL=debug
```

## 🤝 Đóng góp

1. Fork repository
2. Tạo feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📝 Changelog

### v1.0.0 (2024-01-01)
- Initial release
- Admin panel với authentication
- Gmail API integration
- Order management
- Code retrieval system
- Comprehensive logging
- Security features

## 📄 License

Dự án này sử dụng license MIT. Xem file `LICENSE` để biết thêm chi tiết.

## 📞 Hỗ trợ

- Email: support@yourdomain.com
- Documentation: https://docs.yourdomain.com
- Issues: https://github.com/your-repo/netflix-login-service/issues

## ⚠️ Lưu ý quan trọng

- Hệ thống này chỉ dành cho mục đích quản lý tài khoản hợp pháp
- Tuân thủ các điều khoản sử dụng của Netflix
- Đảm bảo quyền riêng tư và bảo mật thông tin khách hàng
- Sử dụng có trách nhiệm và đúng mục đích

---

**© 2024 Netflix Login Code Service. All rights reserved.**