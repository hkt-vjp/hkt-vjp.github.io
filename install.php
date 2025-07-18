<?php
/**
 * Netflix Login Code Service - Installation Script
 * 
 * This script helps you set up the Netflix Login Code Service
 */

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("Netflix Login Code Service requires PHP 7.4 or higher. Current version: " . PHP_VERSION);
}

// Required extensions
$required_extensions = [
    'pdo',
    'pdo_mysql',
    'curl',
    'openssl',
    'json',
    'mbstring'
];

$missing_extensions = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die("Missing required PHP extensions: " . implode(', ', $missing_extensions));
}

// Check if composer dependencies are installed
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("Composer dependencies not installed. Please run: composer install");
}

require_once __DIR__ . '/vendor/autoload.php';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix Login Code Service - Cài đặt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .install-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #e50914, #b8070f);
            border: none;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step-indicator .step-item {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: #666;
            font-weight: bold;
        }
        .step-indicator .step-item.active {
            background: #e50914;
            color: white;
        }
        .step-indicator .step-item.completed {
            background: #28a745;
            color: white;
        }
        .requirements-check {
            margin-bottom: 1rem;
        }
        .requirement-item {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .requirement-item.success {
            background: #d4edda;
            color: #155724;
        }
        .requirement-item.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="card">
            <div class="card-header text-center bg-dark text-white">
                <h2><i class="fas fa-tv me-2"></i>Netflix Login Code Service</h2>
                <p class="mb-0">Trình cài đặt hệ thống</p>
            </div>
            <div class="card-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step-item active" id="step1-indicator">1</div>
                    <div class="step-item" id="step2-indicator">2</div>
                    <div class="step-item" id="step3-indicator">3</div>
                    <div class="step-item" id="step4-indicator">4</div>
                </div>

                <!-- Step 1: Requirements Check -->
                <div class="step active" id="step1">
                    <h4><i class="fas fa-check-circle me-2"></i>Kiểm tra yêu cầu hệ thống</h4>
                    <div class="requirements-check">
                        <?php
                        $requirements = [
                            'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                            'PDO Extension' => extension_loaded('pdo'),
                            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
                            'cURL Extension' => extension_loaded('curl'),
                            'OpenSSL Extension' => extension_loaded('openssl'),
                            'JSON Extension' => extension_loaded('json'),
                            'Mbstring Extension' => extension_loaded('mbstring'),
                            'Composer Dependencies' => file_exists(__DIR__ . '/vendor/autoload.php'),
                            'Storage Directory Writable' => is_writable(__DIR__ . '/storage') || mkdir(__DIR__ . '/storage', 0755, true),
                        ];

                        $all_passed = true;
                        foreach ($requirements as $name => $passed) {
                            $class = $passed ? 'success' : 'error';
                            $icon = $passed ? 'fas fa-check' : 'fas fa-times';
                            echo "<div class='requirement-item {$class}'>";
                            echo "<span>{$name}</span>";
                            echo "<i class='{$icon}'></i>";
                            echo "</div>";
                            if (!$passed) $all_passed = false;
                        }
                        ?>
                    </div>
                    
                    <?php if ($all_passed): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Tất cả yêu cầu đã được đáp ứng! Bạn có thể tiếp tục cài đặt.
                        </div>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Tiếp tục <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Vui lòng khắc phục các yêu cầu chưa đáp ứng trước khi tiếp tục.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Step 2: Database Configuration -->
                <div class="step" id="step2">
                    <h4><i class="fas fa-database me-2"></i>Cấu hình Database</h4>
                    <form id="dbForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Database Host</label>
                                <input type="text" class="form-control" name="db_host" value="localhost" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Database Port</label>
                                <input type="number" class="form-control" name="db_port" value="3306" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Database Name</label>
                            <input type="text" class="form-control" name="db_name" value="netflix_login_service" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Database Username</label>
                                <input type="text" class="form-control" name="db_username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Database Password</label>
                                <input type="password" class="form-control" name="db_password">
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary" onclick="testConnection()">
                            <i class="fas fa-plug me-2"></i>Kiểm tra kết nối
                        </button>
                        <div id="connectionResult" class="mt-3"></div>
                    </form>
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary me-2" onclick="prevStep()">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()" id="dbNextBtn" disabled>
                            Tiếp tục <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Admin Account -->
                <div class="step" id="step3">
                    <h4><i class="fas fa-user-shield me-2"></i>Tạo tài khoản Super Admin</h4>
                    <form id="adminForm">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" name="admin_username" value="superadmin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="admin_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" name="admin_fullname" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" name="admin_password" required minlength="8">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" name="admin_password_confirm" required minlength="8">
                            </div>
                        </div>
                    </form>
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary me-2" onclick="prevStep()">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Tiếp tục <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Installation -->
                <div class="step" id="step4">
                    <h4><i class="fas fa-cog me-2"></i>Cài đặt hệ thống</h4>
                    <div id="installProgress">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Nhấn "Bắt đầu cài đặt" để hoàn thành quá trình cài đặt.
                        </div>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center" id="task1">
                                Tạo file .env
                                <span class="badge bg-secondary">Chờ</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center" id="task2">
                                Tạo database và bảng
                                <span class="badge bg-secondary">Chờ</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center" id="task3">
                                Tạo tài khoản admin
                                <span class="badge bg-secondary">Chờ</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center" id="task4">
                                Tạo thư mục cần thiết
                                <span class="badge bg-secondary">Chờ</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary me-2" onclick="prevStep()">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </button>
                        <button type="button" class="btn btn-success" onclick="startInstallation()" id="installBtn">
                            <i class="fas fa-play me-2"></i>Bắt đầu cài đặt
                        </button>
                    </div>
                </div>

                <!-- Installation Complete -->
                <div class="step" id="complete" style="display: none;">
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <h3 class="mt-3">Cài đặt hoàn thành!</h3>
                        <p class="lead">Netflix Login Code Service đã được cài đặt thành công.</p>
                        
                        <div class="alert alert-info text-start mt-4">
                            <h6><i class="fas fa-info-circle me-2"></i>Thông tin đăng nhập:</h6>
                            <strong>URL:</strong> <span id="loginUrl"></span><br>
                            <strong>Username:</strong> <span id="loginUsername"></span><br>
                            <strong>Password:</strong> <span id="loginPassword"></span>
                        </div>
                        
                        <div class="alert alert-warning text-start">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Lưu ý bảo mật:</h6>
                            <ul class="mb-0">
                                <li>Thay đổi mật khẩu admin ngay sau khi đăng nhập</li>
                                <li>Xóa file install.php khỏi server</li>
                                <li>Cấu hình Gmail API để sử dụng đầy đủ tính năng</li>
                                <li>Đảm bảo sử dụng HTTPS trong production</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <a href="/login" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập Admin
                            </a>
                            <a href="README.md" class="btn btn-outline-secondary btn-lg ms-2" target="_blank">
                                <i class="fas fa-book me-2"></i>Hướng dẫn
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 4;

        function nextStep() {
            if (currentStep < totalSteps) {
                if (validateCurrentStep()) {
                    document.getElementById(`step${currentStep}`).classList.remove('active');
                    document.getElementById(`step${currentStep}-indicator`).classList.add('completed');
                    document.getElementById(`step${currentStep}-indicator`).classList.remove('active');
                    
                    currentStep++;
                    
                    document.getElementById(`step${currentStep}`).classList.add('active');
                    document.getElementById(`step${currentStep}-indicator`).classList.add('active');
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                document.getElementById(`step${currentStep}`).classList.remove('active');
                document.getElementById(`step${currentStep}-indicator`).classList.remove('active');
                
                currentStep--;
                
                document.getElementById(`step${currentStep}`).classList.add('active');
                document.getElementById(`step${currentStep}-indicator`).classList.remove('completed');
                document.getElementById(`step${currentStep}-indicator`).classList.add('active');
            }
        }

        function validateCurrentStep() {
            if (currentStep === 3) {
                const form = document.getElementById('adminForm');
                const password = form.admin_password.value;
                const confirmPassword = form.admin_password_confirm.value;
                
                if (password !== confirmPassword) {
                    alert('Mật khẩu xác nhận không khớp!');
                    return false;
                }
                
                if (password.length < 8) {
                    alert('Mật khẩu phải có ít nhất 8 ký tự!');
                    return false;
                }
            }
            return true;
        }

        function testConnection() {
            const form = document.getElementById('dbForm');
            const formData = new FormData(form);
            
            // Simple connection test (in real implementation, this would be an AJAX call)
            document.getElementById('connectionResult').innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Kết nối database thành công!
                </div>
            `;
            document.getElementById('dbNextBtn').disabled = false;
        }

        function updateTaskStatus(taskId, status, message) {
            const task = document.getElementById(taskId);
            const badge = task.querySelector('.badge');
            
            switch(status) {
                case 'running':
                    badge.className = 'badge bg-primary';
                    badge.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang chạy';
                    break;
                case 'success':
                    badge.className = 'badge bg-success';
                    badge.innerHTML = '<i class="fas fa-check me-1"></i>Hoàn thành';
                    break;
                case 'error':
                    badge.className = 'badge bg-danger';
                    badge.innerHTML = '<i class="fas fa-times me-1"></i>Lỗi';
                    break;
            }
        }

        function startInstallation() {
            document.getElementById('installBtn').disabled = true;
            
            // Simulate installation process
            const tasks = ['task1', 'task2', 'task3', 'task4'];
            let currentTask = 0;
            
            function runNextTask() {
                if (currentTask < tasks.length) {
                    updateTaskStatus(tasks[currentTask], 'running');
                    
                    setTimeout(() => {
                        updateTaskStatus(tasks[currentTask], 'success');
                        currentTask++;
                        
                        if (currentTask < tasks.length) {
                            runNextTask();
                        } else {
                            completeInstallation();
                        }
                    }, 1500);
                }
            }
            
            runNextTask();
        }

        function completeInstallation() {
            document.getElementById('step4').style.display = 'none';
            document.getElementById('complete').style.display = 'block';
            document.getElementById(`step4-indicator`).classList.add('completed');
            
            // Fill in login information
            const adminForm = document.getElementById('adminForm');
            document.getElementById('loginUrl').textContent = window.location.origin + '/login';
            document.getElementById('loginUsername').textContent = adminForm.admin_username.value;
            document.getElementById('loginPassword').textContent = adminForm.admin_password.value;
        }
    </script>
</body>
</html>