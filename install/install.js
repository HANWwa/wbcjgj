/**
 * 安装向导JavaScript
 * @神奇奶酪
 */

// 显示加载动画
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('hidden');
    }
}

// 隐藏加载动画
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('hidden');
    }
}

// 显示错误消息
function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
        // 滚动到顶部
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// 隐藏错误消息
function hideError() {
    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        errorDiv.classList.add('hidden');
    }
}

// 显示成功消息
function showSuccess(message) {
    const successDiv = document.getElementById('successMessage');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.classList.remove('hidden');
        // 滚动到顶部
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// 隐藏成功消息
function hideSuccess() {
    const successDiv = document.getElementById('successMessage');
    if (successDiv) {
        successDiv.classList.add('hidden');
    }
}

// AJAX请求封装
function ajax(url, data, callback) {
    showLoading();
    hideError();
    hideSuccess();

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        callback(result);
    })
    .catch(error => {
        hideLoading();
        showError('网络请求失败，请重试');
        console.error('Error:', error);
    });
}

// 数据库配置表单处理
const dbConfigForm = document.getElementById('dbConfigForm');
if (dbConfigForm) {
    dbConfigForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            action: 'test_db',
            db_host: formData.get('db_host'),
            db_port: formData.get('db_port'),
            db_name: formData.get('db_name'),
            db_user: formData.get('db_user'),
            db_pass: formData.get('db_pass'),
            db_prefix: formData.get('db_prefix')
        };

        ajax('process.php', data, function(result) {
            if (result.success) {
                // 测试成功，安装数据库
                installDatabase();
            } else {
                showError(result.message);
            }
        });
    });
}

// 安装数据库
function installDatabase() {
    const data = {
        action: 'install_db'
    };

    ajax('process.php', data, function(result) {
        if (result.success) {
            showSuccess('数据库安装成功，正在跳转...');
            setTimeout(function() {
                window.location.href = '?step=3';
            }, 1500);
        } else {
            showError(result.message);
        }
    });
}

// 管理员配置表单处理
const adminConfigForm = document.getElementById('adminConfigForm');
if (adminConfigForm) {
    adminConfigForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const username = formData.get('admin_username');
        const email = formData.get('admin_email');
        const password = formData.get('admin_password');
        const passwordConfirm = formData.get('admin_password_confirm');

        // 前端验证
        if (username.length < 3 || username.length > 20) {
            showError('用户名长度必须在3-20个字符之间');
            return;
        }

        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            showError('用户名只能包含字母、数字和下划线');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('邮箱格式不正确');
            return;
        }

        if (password.length < 6) {
            showError('密码长度至少6个字符');
            return;
        }

        if (password !== passwordConfirm) {
            showError('两次输入的密码不一致');
            return;
        }

        const data = {
            action: 'create_admin',
            admin_username: username,
            admin_email: email,
            admin_password: password,
            admin_password_confirm: passwordConfirm
        };

        ajax('process.php', data, function(result) {
            if (result.success) {
                showSuccess('管理员账户创建成功，正在跳转...');
                setTimeout(function() {
                    window.location.href = '?step=4';
                }, 1500);
            } else {
                showError(result.message);
            }
        });
    });
}

// 页面加载完成后的处理
document.addEventListener('DOMContentLoaded', function() {
    // 添加输入框焦点效果
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(function(control) {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // 自动隐藏提示消息
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (!alert.classList.contains('hidden')) {
                // 5秒后自动隐藏
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.3s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.classList.add('hidden');
                        alert.style.opacity = '1';
                    }, 300);
                }, 5000);
            }
        });
    }, 100);
});

// 密码强度检测
function checkPasswordStrength(password) {
    let strength = 0;

    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;

    return strength;
}

// 显示密码强度
const passwordInput = document.querySelector('input[name="admin_password"]');
if (passwordInput) {
    passwordInput.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        let strengthText = '';
        let strengthClass = '';

        if (this.value.length === 0) {
            strengthText = '';
        } else if (strength <= 2) {
            strengthText = '弱';
            strengthClass = 'weak';
        } else if (strength <= 4) {
            strengthText = '中';
            strengthClass = 'medium';
        } else {
            strengthText = '强';
            strengthClass = 'strong';
        }

        // 查找或创建强度提示元素
        let strengthIndicator = this.parentElement.querySelector('.password-strength');
        if (!strengthIndicator && strengthText) {
            strengthIndicator = document.createElement('small');
            strengthIndicator.className = 'password-strength form-hint';
            this.parentElement.appendChild(strengthIndicator);
        }

        if (strengthIndicator) {
            if (strengthText) {
                strengthIndicator.textContent = '密码强度：' + strengthText;
                strengthIndicator.className = 'password-strength form-hint ' + strengthClass;
                strengthIndicator.style.display = 'block';
            } else {
                strengthIndicator.style.display = 'none';
            }
        }
    });
}

// 表单验证样式
const forms = document.querySelectorAll('form');
forms.forEach(function(form) {
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let hasError = false;

        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                field.style.borderColor = 'var(--error)';
                hasError = true;
            } else {
                field.style.borderColor = '';
            }
        });

        if (hasError) {
            e.preventDefault();
            showError('请填写所有必填字段');
        }
    });
});

// 输入框实时验证
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('form-control')) {
        if (e.target.hasAttribute('required') && !e.target.value.trim()) {
            e.target.style.borderColor = 'var(--error)';
        } else {
            e.target.style.borderColor = '';
        }
    }
});

// 防止重复提交
let isSubmitting = false;

document.addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }

    const form = e.target;
    if (form.tagName === 'FORM') {
        isSubmitting = true;
        setTimeout(function() {
            isSubmitting = false;
        }, 3000);
    }
});
