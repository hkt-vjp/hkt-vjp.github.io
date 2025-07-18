/**
 * Netflix Login Code Service - Admin Panel JavaScript
 */

$(document).ready(function() {
    // Initialize components
    initializeAlerts();
    initializeDataTables();
    initializeForms();
    initializeModals();
    initializeTooltips();
    initializeClipboard();
    initializeCodeRetrieval();
    
    // Set up CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('input[name="csrf_token"]').val()
        }
    });
});

/**
 * Initialize alert system
 */
function initializeAlerts() {
    // Auto-hide alerts after 5 seconds
    $('.alert').each(function() {
        const $alert = $(this);
        setTimeout(() => {
            $alert.fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    });
    
    // Close button for alerts
    $('.alert .btn-close').on('click', function() {
        $(this).closest('.alert').fadeOut('fast');
    });
}

/**
 * Initialize DataTables for better table management
 */
function initializeDataTables() {
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 25,
            language: {
                "sProcessing": "Đang xử lý...",
                "sLengthMenu": "Hiển thị _MENU_ mục",
                "sZeroRecords": "Không tìm thấy dữ liệu",
                "sInfo": "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ mục",
                "sInfoEmpty": "Hiển thị 0 đến 0 trong tổng số 0 mục",
                "sInfoFiltered": "(được lọc từ _MAX_ mục)",
                "sSearch": "Tìm kiếm:",
                "oPaginate": {
                    "sFirst": "Đầu",
                    "sPrevious": "Trước",
                    "sNext": "Tiếp",
                    "sLast": "Cuối"
                }
            },
            order: [[0, 'desc']] // Sort by first column (usually ID) descending
        });
    }
}

/**
 * Initialize form enhancements
 */
function initializeForms() {
    // Form validation
    $('.needs-validation').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
    
    // Loading state for form submissions
    $('form').on('submit', function() {
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        
        if ($submitBtn.length) {
            $submitBtn.prop('disabled', true);
            $submitBtn.addClass('loading');
            
            const originalText = $submitBtn.html();
            $submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...');
            
            // Reset after 30 seconds as fallback
            setTimeout(() => {
                $submitBtn.prop('disabled', false);
                $submitBtn.removeClass('loading');
                $submitBtn.html(originalText);
            }, 30000);
        }
    });
    
    // Auto-resize textareas
    $('textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Email validation
    $('input[type="email"]').on('blur', function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid');
            $(this).siblings('.invalid-feedback').text('Email không hợp lệ');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
}

/**
 * Initialize modals
 */
function initializeModals() {
    // Delete confirmation modals
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        
        const url = $(this).attr('href') || $(this).data('url');
        const title = $(this).data('title') || 'Xóa mục này';
        const message = $(this).data('message') || 'Bạn có chắc chắn muốn xóa mục này? Hành động này không thể hoàn tác.';
        
        showConfirmModal(title, message, () => {
            if (url) {
                window.location.href = url;
            }
        });
    });
    
    // AJAX delete buttons
    $('.btn-ajax-delete').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const url = $btn.data('url');
        const title = $btn.data('title') || 'Xóa mục này';
        const message = $btn.data('message') || 'Bạn có chắc chắn muốn xóa mục này?';
        
        showConfirmModal(title, message, () => {
            ajaxDelete(url, $btn);
        });
    });
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Initialize clipboard functionality
 */
function initializeClipboard() {
    $('.btn-copy').on('click', function() {
        const text = $(this).data('copy') || $(this).siblings('input').val() || $(this).parent().find('.code-value').text();
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Đã sao chép!', 'success');
            }).catch(() => {
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    });
}

/**
 * Initialize code retrieval functionality
 */
function initializeCodeRetrieval() {
    $('#getCodeForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $result = $('#codeResult');
        
        // Show loading state
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Đang lấy mã...');
        $result.hide();
        
        $.ajax({
            url: '/api/code-retrieval',
            method: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    displayCodeResult(response.data);
                } else {
                    showAlert(response.message || 'Có lỗi xảy ra', 'danger');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Không thể kết nối đến server';
                showAlert(message, 'danger');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('<i class="fas fa-search me-2"></i>Lấy mã đăng nhập');
            }
        });
    });
}

/**
 * Display code retrieval result
 */
function displayCodeResult(data) {
    const $result = $('#codeResult');
    
    let html = '<div class="card border-success">';
    html += '<div class="card-header bg-success text-white">';
    html += '<h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Lấy mã thành công!</h5>';
    html += '</div>';
    html += '<div class="card-body">';
    
    if (data.login_code) {
        html += '<div class="code-display">';
        html += '<div class="code-value" id="loginCode">' + data.login_code + '</div>';
        html += '<div class="code-label">Mã đăng nhập Netflix</div>';
        html += '<button type="button" class="btn btn-outline-primary btn-sm mt-2 btn-copy" data-copy="' + data.login_code + '">';
        html += '<i class="fas fa-copy me-1"></i>Sao chép mã';
        html += '</button>';
        html += '</div>';
    }
    
    if (data.login_link) {
        html += '<div class="mt-3">';
        html += '<label class="form-label fw-bold">Liên kết đăng nhập:</label>';
        html += '<div class="input-group">';
        html += '<input type="text" class="form-control" value="' + data.login_link + '" readonly>';
        html += '<button type="button" class="btn btn-outline-primary btn-copy" data-copy="' + data.login_link + '">';
        html += '<i class="fas fa-copy"></i>';
        html += '</button>';
        html += '</div>';
        html += '</div>';
    }
    
    html += '<div class="row mt-3">';
    if (data.email_date) {
        html += '<div class="col-md-6">';
        html += '<small class="text-muted"><i class="fas fa-clock me-1"></i>Thời gian gửi: ' + data.email_date + '</small>';
        html += '</div>';
    }
    if (data.days_until_expiry !== undefined) {
        html += '<div class="col-md-6">';
        const daysText = data.days_until_expiry > 0 ? 
            `Còn ${data.days_until_expiry} ngày` : 
            '<span class="text-danger">Đã hết hạn</span>';
        html += '<small class="text-muted"><i class="fas fa-calendar me-1"></i>Hạn sử dụng: ' + daysText + '</small>';
        html += '</div>';
    }
    html += '</div>';
    
    if (data.days_until_expiry <= 7 && data.days_until_expiry > 0) {
        html += '<div class="alert alert-warning mt-3 mb-0">';
        html += '<i class="fas fa-exclamation-triangle me-2"></i>';
        html += 'Đơn hàng sắp hết hạn! Vui lòng liên hệ admin để gia hạn.';
        html += '</div>';
    }
    
    html += '</div></div>';
    
    $result.html(html).show();
    
    // Re-initialize clipboard for new buttons
    initializeClipboard();
}

/**
 * Show confirmation modal
 */
function showConfirmModal(title, message, onConfirm) {
    const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-danger" id="confirmBtn">Xác nhận</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal
    $('#confirmModal').remove();
    
    // Add new modal
    $('body').append(modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    
    $('#confirmBtn').on('click', function() {
        modal.hide();
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    });
    
    modal.show();
}

/**
 * AJAX delete function
 */
function ajaxDelete(url, $btn) {
    $.ajax({
        url: url,
        method: 'DELETE',
        success: function(response) {
            if (response.success) {
                showToast('Xóa thành công!', 'success');
                
                // Remove row from table or reload page
                const $row = $btn.closest('tr');
                if ($row.length) {
                    $row.fadeOut(() => $row.remove());
                } else {
                    location.reload();
                }
            } else {
                showAlert(response.message || 'Có lỗi xảy ra', 'danger');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Không thể xóa mục này';
            showAlert(message, 'danger');
        }
    });
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('.main-content').prepend(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        $('.alert').first().fadeOut();
    }, 5000);
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    // Create toast container if it doesn't exist
    if (!$('#toastContainer').length) {
        $('body').append('<div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 11;"></div>');
    }
    
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${getAlertIcon(type)} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    $('#toastContainer').append(toastHtml);
    
    const toast = new bootstrap.Toast($('#toastContainer .toast').last()[0]);
    toast.show();
}

/**
 * Get icon for alert type
 */
function getAlertIcon(type) {
    const icons = {
        success: 'check-circle',
        danger: 'exclamation-triangle',
        warning: 'exclamation-circle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Fallback copy to clipboard
 */
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showToast('Đã sao chép!', 'success');
        } else {
            showToast('Không thể sao chép', 'danger');
        }
    } catch (err) {
        showToast('Không thể sao chép', 'danger');
    } finally {
        document.body.removeChild(textArea);
    }
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Check if element is in viewport
 */
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

/**
 * Smooth scroll to element
 */
function scrollToElement(selector, offset = 0) {
    const element = document.querySelector(selector);
    if (element) {
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
}

/**
 * Auto-refresh page data
 */
function setupAutoRefresh(interval = 30000) {
    setInterval(() => {
        // Only refresh if page is visible
        if (!document.hidden) {
            // Refresh specific content instead of full page
            refreshDashboardStats();
        }
    }, interval);
}

/**
 * Refresh dashboard statistics
 */
function refreshDashboardStats() {
    $.ajax({
        url: '/admin/api/stats',
        method: 'GET',
        success: function(data) {
            // Update stats cards
            Object.keys(data).forEach(key => {
                $(`.stats-${key}`).text(data[key]);
            });
        },
        error: function() {
            // Silently fail for auto-refresh
        }
    });
}

/**
 * Initialize page based on current URL
 */
function initializePage() {
    const path = window.location.pathname;
    
    if (path.includes('/admin/dashboard')) {
        setupAutoRefresh();
    }
    
    if (path.includes('/admin/orders')) {
        initializeOrdersPage();
    }
    
    if (path.includes('/admin/emails')) {
        initializeEmailsPage();
    }
}

/**
 * Initialize orders page specific functionality
 */
function initializeOrdersPage() {
    // Order status filter
    $('#statusFilter').on('change', function() {
        const status = $(this).val();
        if (status) {
            window.location.href = `/admin/orders?status=${status}`;
        } else {
            window.location.href = '/admin/orders';
        }
    });
    
    // Bulk actions
    $('#selectAll').on('change', function() {
        $('.order-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    $('.order-checkbox').on('change', function() {
        const total = $('.order-checkbox').length;
        const checked = $('.order-checkbox:checked').length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
    });
}

/**
 * Initialize emails page specific functionality
 */
function initializeEmailsPage() {
    // Gmail connection status check
    $('.btn-check-connection').on('click', function() {
        const $btn = $(this);
        const emailId = $btn.data('email-id');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: `/admin/gmail/check/${emailId}`,
            method: 'GET',
            success: function(response) {
                if (response.connected) {
                    $btn.removeClass('btn-warning').addClass('btn-success');
                    $btn.html('<i class="fas fa-check"></i> Đã kết nối');
                } else {
                    $btn.removeClass('btn-success').addClass('btn-danger');
                    $btn.html('<i class="fas fa-times"></i> Lỗi kết nối');
                }
            },
            error: function() {
                $btn.removeClass('btn-success').addClass('btn-danger');
                $btn.html('<i class="fas fa-times"></i> Lỗi');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
}

// Initialize page-specific functionality
initializePage();