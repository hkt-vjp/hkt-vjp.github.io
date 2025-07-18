<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Netflix Login Service') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= Router::asset('images/favicon.ico') ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= Router::asset('css/admin.css') ?>" rel="stylesheet">
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="Netflix Login Code Service - Admin Panel">
    <meta name="robots" content="noindex, nofollow">
    
    <style>
        :root {
            --primary-color: #e50914;
            --secondary-color: #221f1f;
            --accent-color: #f5f5f1;
            --text-primary: #333333;
            --text-secondary: #666666;
            --border-color: #e0e0e0;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-primary);
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .sidebar {
            background: var(--secondary-color);
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: var(--primary-color);
        }
        
        .main-content {
            padding: 1.5rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            border-radius: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #b8070f;
            border-color: #b8070f;
        }
        
        .alert {
            border: none;
            border-radius: 0.5rem;
        }
        
        .table {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.5rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
        }
        
        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), #b8070f);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-card .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stats-card .stats-label {
            opacity: 0.9;
            font-size: 0.875rem;
        }
    </style>
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link href="<?= $css ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div id="app">
        <?php if (isset($is_logged_in) && $is_logged_in): ?>
            <!-- Admin Layout with Sidebar -->
            <div class="container-fluid">
                <div class="row">
                    <!-- Sidebar -->
                    <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                        <div class="position-sticky pt-3">
                            <div class="text-center mb-4">
                                <h5 class="text-white">
                                    <i class="fas fa-tv text-danger me-2"></i>
                                    <?= htmlspecialchars($app_name) ?>
                                </h5>
                                <small class="text-muted">Admin Panel</small>
                            </div>
                            
                            <!-- User Info -->
                            <?php if (isset($current_user)): ?>
                                <div class="bg-dark rounded p-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div>
                                            <small class="text-white fw-bold"><?= htmlspecialchars($current_user['full_name'] ?? $current_user['username']) ?></small>
                                            <br>
                                            <small class="text-muted"><?= ucfirst($current_user['role']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Navigation Menu -->
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/dashboard">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/orders">
                                        <i class="fas fa-shopping-cart me-2"></i>
                                        Quản lý đơn hàng
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/emails">
                                        <i class="fas fa-envelope me-2"></i>
                                        Email Netflix
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/gmail">
                                        <i class="fab fa-google me-2"></i>
                                        Cài đặt Gmail
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/statistics">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Thống kê
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/logs">
                                        <i class="fas fa-list-alt me-2"></i>
                                        Lịch sử hoạt động
                                    </a>
                                </li>
                                
                                <?php if (isset($is_super_admin) && $is_super_admin): ?>
                                    <hr class="text-white-50">
                                    <li class="nav-item">
                                        <a class="nav-link" href="/admin/admins">
                                            <i class="fas fa-users-cog me-2"></i>
                                            Quản lý Admin
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="/admin/settings">
                                            <i class="fas fa-cog me-2"></i>
                                            Cài đặt hệ thống
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <hr class="text-white-50">
                                <li class="nav-item">
                                    <a class="nav-link" href="/admin/profile">
                                        <i class="fas fa-user-circle me-2"></i>
                                        Thông tin cá nhân
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/logout" onclick="return confirm('Bạn có chắc muốn đăng xuất?')">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        Đăng xuất
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                    
                    <!-- Main Content -->
                    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                        <div class="main-content">
                            <!-- Flash Messages -->
                            <?php if (isset($error) && $error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?= htmlspecialchars($error) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($success) && $success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?= htmlspecialchars($success) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($warning) && $warning): ?>
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?= htmlspecialchars($warning) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($info) && $info): ?>
                                <div class="alert alert-info alert-dismissible fade show" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?= htmlspecialchars($info) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Content -->
                            <?= $content ?? '' ?>
                        </div>
                    </main>
                </div>
            </div>
        <?php else: ?>
            <!-- Public Layout -->
            <div class="container-fluid">
                <!-- Flash Messages -->
                <?php if (isset($error) && $error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Content -->
                <?= $content ?? '' ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= Router::asset('js/admin.js') ?>"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Confirm delete actions
        $('.btn-delete').on('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa mục này?')) {
                e.preventDefault();
            }
        });
        
        // CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '<?= $csrf_token ?? '' ?>'
            }
        });
    </script>
</body>
</html>