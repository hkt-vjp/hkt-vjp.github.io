<?php ob_start(); ?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <!-- Left Side - Branding -->
        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-dark">
            <div class="text-center text-white">
                <i class="fas fa-tv display-1 text-danger mb-4"></i>
                <h1 class="h2 mb-3">Netflix Login Code Service</h1>
                <p class="lead">Quản lý mã đăng nhập Netflix một cách chuyên nghiệp</p>
                <div class="mt-5">
                    <div class="row text-center">
                        <div class="col-4">
                            <i class="fas fa-shield-alt fa-2x text-primary mb-2"></i>
                            <p class="small">Bảo mật cao</p>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-bolt fa-2x text-warning mb-2"></i>
                            <p class="small">Tự động hóa</p>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                            <p class="small">Thống kê chi tiết</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="col-lg-6 d-flex align-items-center justify-content-center">
            <div class="w-100" style="max-width: 400px;">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                            <h3 class="card-title mb-1">Đăng nhập Admin</h3>
                            <p class="text-muted">Vui lòng đăng nhập để tiếp tục</p>
                        </div>
                        
                        <form method="POST" action="/login" id="loginForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    Tên đăng nhập hoặc Email
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Nhập tên đăng nhập hoặc email"
                                       required 
                                       autofocus
                                       autocomplete="username">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>
                                    Mật khẩu
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Nhập mật khẩu"
                                           required
                                           autocomplete="current-password">
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            id="togglePassword"
                                            title="Hiện/ẩn mật khẩu">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="remember_me" 
                                       name="remember_me"
                                       value="1">
                                <label class="form-check-label" for="remember_me">
                                    Ghi nhớ đăng nhập
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Đăng nhập
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Hệ thống được bảo vệ bởi các biện pháp bảo mật tiên tiến
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="text-center mt-4">
                    <small class="text-muted">
                        Netflix Login Code Service v1.0<br>
                        © 2024 - Bảo mật và tin cậy
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .card {
        border-radius: 15px;
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
    }
    
    .form-control:focus {
        border-color: #e50914;
        box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #e50914, #b8070f);
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(229, 9, 20, 0.4);
    }
    
    .form-control {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px 16px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        transform: translateY(-1px);
    }
    
    .input-group .btn {
        border-radius: 0 8px 8px 0;
        border: 2px solid #e9ecef;
        border-left: none;
    }
    
    .bg-dark {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
    }
    
    @media (max-width: 991.98px) {
        .container-fluid {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    }
    
    /* Loading animation */
    .btn-primary.loading {
        pointer-events: none;
    }
    
    .btn-primary.loading i {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    
    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        if (type === 'password') {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    });
    
    // Form submission with loading state
    loginForm.addEventListener('submit', function() {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        
        const icon = submitBtn.querySelector('i');
        icon.classList.remove('fa-sign-in-alt');
        icon.classList.add('fa-spinner');
        
        submitBtn.innerHTML = '<i class="fas fa-spinner me-2"></i>Đang đăng nhập...';
    });
    
    // Enter key support
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loginForm.submit();
        }
    });
});
</script>

<?php $content = ob_get_clean(); ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Netflix Login Service') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body>
    <!-- Flash Messages -->
    <?php if (isset($error) && $error): ?>
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 11;">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success) && $success): ?>
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 11;">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <?= $content ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>