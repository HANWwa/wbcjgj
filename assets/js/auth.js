/**
 * 认证相关JavaScript
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
    hideSuccess();
    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // 5秒后自动隐藏
        setTimeout(function() {
            errorDiv.style.transition = 'opacity 0.3s';
            errorDiv.style.opacity = '0';
            setTimeout(function() {
                errorDiv.classList.add('hidden');
                errorDiv.style.opacity = '1';
            }, 300);
        }, 5000);
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
    hideError();
    const successDiv = document.getElementById('successMessage');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // 5秒后自动隐藏
        setTimeout(function() {
            successDiv.style.transition = 'opacity 0.3s';
            successDiv.style.opacity = '0';
            setTimeout(function() {
                successDiv.classList.add('hidden');
                successDiv.style.opacity = '1';
            }, 300);
        }, 5000);
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

// 邮箱验证
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

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

// 切换密码可见性
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input) {
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = '🙈';
        } else {
            input.type = 'password';
            icon.textContent = '👁️';
        }
    }
}

// 注册表单处理
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const password = formData.get('password');
        const passwordConfirm = formData.get('password_confirm');

        // 前端验证
        if (password !== passwordConfirm) {
            showError('两次输入的密码不一致');
            return;
        }

        // 构建数据
        const data = {
            action: 'register',
            username: formData.get('username'),
            email: formData.get('email'),
            password: password,
            password_confirm: passwordConfirm,
            email_code: formData.get('email_code') || '',
            math_answer: formData.get('math_answer') || ''
        };

        // 提交注册
        ajax('ajax_auth.php', data, function(result) {
            if (result.success) {
                showSuccess(result.message);
                setTimeout(function() {
                    window.location.href = result.data.redirect || 'index.php';
                }, 1500);
            } else {
                showError(result.message);
            }
        });
    });
}

// 登录表单处理
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        const data = {
            action: 'login',
            username: formData.get('username'),
            password: formData.get('password'),
            remember: formData.get('remember') || '0',
            redirect: formData.get('redirect') || ''
        };

        // 提交登录
        ajax('ajax_auth.php', data, function(result) {
            if (result.success) {
                showSuccess(result.message);
                setTimeout(function() {
                    window.location.href = result.data.redirect || 'index.php';
                }, 1000);
            } else {
                showError(result.message);
            }
        });
    });
}

// 页面加载完成后的处理
document.addEventListener('DOMContentLoaded', function() {
    // 输入框焦点效果
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(function(control) {
        control.addEventListener('focus', function() {
            this.style.borderColor = 'var(--primary-color)';
            this.style.boxShadow = '0 0 0 3px rgba(255, 107, 107, 0.1)';
        });

        control.addEventListener('blur', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
    });

    // 实时表单验证
    const requiredInputs = document.querySelectorAll('input[required]');
    requiredInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.style.borderColor = 'var(--error)';
            } else {
                this.style.borderColor = '';
            }
        });

        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = '';
            }
        });
    });

    // 邮箱格式验证
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.style.borderColor = 'var(--error)';
                showError('邮箱格式不正确');
            }
        });
    });

    // 密码确认验证
    const passwordConfirm = document.querySelector('input[name="password_confirm"]');
    const passwordInput = document.querySelector('input[name="password"]');

    if (passwordConfirm && passwordInput) {
        passwordConfirm.addEventListener('input', function() {
            if (this.value && this.value !== passwordInput.value) {
                this.style.borderColor = 'var(--error)';
            } else {
                this.style.borderColor = '';
            }
        });
    }

    // 用户名格式验证
    const usernameInput = document.querySelector('input[name="username"]');
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            const value = this.value;
            if (value && !/^[a-zA-Z0-9_]+$/.test(value)) {
                this.style.borderColor = 'var(--error)';
            } else {
                this.style.borderColor = '';
            }
        });
    }

    // 自动聚焦第一个输入框
    const firstInput = document.querySelector('input[autofocus]');
    if (firstInput) {
        firstInput.focus();
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

// 回车键提交表单
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
        const form = e.target.closest('form');
        if (form && !e.target.hasAttribute('data-no-submit')) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.click();
            }
        }
    }
});

// 输入框动画效果
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        // 添加输入动画
        input.addEventListener('focus', function() {
            const inputGroup = this.closest('.input-group');
            if (inputGroup) {
                const icon = inputGroup.querySelector('.input-icon');
                if (icon) {
                    icon.style.transform = 'translateY(-50%) scale(1.1)';
                    icon.style.color = 'var(--primary-color)';
                }
            }
        });

        input.addEventListener('blur', function() {
            const inputGroup = this.closest('.input-group');
            if (inputGroup) {
                const icon = inputGroup.querySelector('.input-icon');
                if (icon) {
                    icon.style.transform = 'translateY(-50%) scale(1)';
                    icon.style.color = 'var(--text-gray)';
                }
            }
        });
    });
});
