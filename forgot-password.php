<?php
/**
 * 忘记密码页面
 * @神奇奶酪
 */

require_once __DIR__ . '/includes/config.php';

// 检查是否已安装
if (!isInstalled()) {
    redirect('install/index.php');
    exit;
}

// 如果已登录，跳转到首页
$auth = new Auth();
if ($auth->isLoggedIn()) {
    redirect('index.php');
    exit;
}

// 获取网站设置
$siteName = getSetting('site_name', SYSTEM_NAME);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>找回密码 - <?php echo safe($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .auth-box {
            max-width: 500px;
            width: 100%;
            background: var(--card-bg);
            border-radius: 25px;
            padding: 50px 40px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        .auth-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient-2);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .auth-logo {
            font-size: 56px;
            margin-bottom: 15px;
        }

        .auth-title {
            font-size: 32px;
            font-weight: bold;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .auth-subtitle {
            color: var(--text-gray);
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: var(--text-gray);
            z-index: 1;
        }

        .form-control-icon {
            padding-left: 50px;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 20px;
            color: var(--text-gray);
            z-index: 1;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .captcha-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .captcha-input {
            width: 120px;
        }

        .send-code-btn {
            min-width: 120px;
            white-space: nowrap;
        }

        .send-code-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .auth-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s;
        }

        .auth-links a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            color: var(--text-gray);
            text-decoration: none;
            transition: all 0.3s;
        }

        .back-home:hover {
            color: var(--primary-color);
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }

        .step-dot.active {
            background: var(--primary-color);
            width: 30px;
            border-radius: 5px;
        }

        @media (max-width: 576px) {
            .auth-box {
                padding: 40px 25px;
            }

            .auth-title {
                font-size: 26px;
            }

            .captcha-group {
                flex-direction: column;
            }

            .captcha-input {
                width: 100%;
            }

            .send-code-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- 加载动画 -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div>
            <div class="loader"></div>
            <div class="loading-text">正在处理，请稍候...</div>
        </div>
    </div>

    <div class="auth-container">
        <div class="auth-box">
            <!-- 头部 -->
            <div class="auth-header">
                <div class="auth-logo">🔑</div>
                <h1 class="auth-title">找回密码</h1>
                <p class="auth-subtitle">通过邮箱验证重置您的密码</p>
            </div>

            <!-- 步骤指示器 -->
            <div class="step-indicator">
                <div class="step-dot active" id="step1Dot"></div>
                <div class="step-dot" id="step2Dot"></div>
            </div>

            <!-- 提示消息 -->
            <div id="errorMessage" class="alert alert-error hidden"></div>
            <div id="successMessage" class="alert alert-success hidden"></div>

            <!-- 步骤1：发送验证码 -->
            <div id="step1" class="reset-step">
                <form id="sendCodeForm">
                    <div class="form-group">
                        <label class="form-label">邮箱地址</label>
                        <div class="input-group">
                            <span class="input-icon">📧</span>
                            <input type="email" name="email" id="emailInput" class="form-control form-control-icon"
                                   placeholder="请输入注册邮箱" required autofocus>
                        </div>
                        <small class="form-hint">请输入您注册时使用的邮箱地址</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">发送验证码</button>
                </form>
            </div>

            <!-- 步骤2：重置密码 -->
            <div id="step2" class="reset-step hidden">
                <form id="resetPasswordForm">
                    <input type="hidden" name="email" id="resetEmail">

                    <div class="form-group">
                        <label class="form-label">验证码</label>
                        <div class="captcha-group">
                            <input type="text" name="code" class="form-control captcha-input"
                                   placeholder="验证码" required maxlength="6">
                            <button type="button" id="resendCodeBtn" class="btn btn-secondary send-code-btn">
                                重新发送
                            </button>
                        </div>
                        <small class="form-hint">验证码已发送至您的邮箱，有效期10分钟</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">新密码</label>
                        <div class="input-group">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="new_password" id="newPasswordInput"
                                   class="form-control form-control-icon"
                                   placeholder="请输入新密码" required minlength="6">
                            <span class="password-toggle" onclick="togglePassword('newPasswordInput', this)">👁️</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">确认新密码</label>
                        <div class="input-group">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="confirm_password" id="confirmPasswordInput"
                                   class="form-control form-control-icon"
                                   placeholder="请再次输入新密码" required minlength="6">
                            <span class="password-toggle" onclick="togglePassword('confirmPasswordInput', this)">👁️</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">重置密码</button>
                </form>
            </div>

            <!-- 链接 -->
            <div class="auth-links">
                <p>想起密码了？<a href="login.php">立即登录</a></p>
                <a href="index.php" class="back-home">
                    <span>←</span>
                    <span>返回首页</span>
                </a>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/auth.js"></script>
    <script>
        let countdown = 0;

        // 发送验证码表单
        document.getElementById('sendCodeForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const email = document.getElementById('emailInput').value;

            ajax('ajax_auth.php', {
                action: 'send_reset_code',
                email: email
            }, function(result) {
                if (result.success) {
                    showSuccess('验证码已发送，请查收邮箱');
                    document.getElementById('resetEmail').value = email;

                    // 切换到步骤2
                    setTimeout(function() {
                        document.getElementById('step1').classList.add('hidden');
                        document.getElementById('step2').classList.remove('hidden');
                        document.getElementById('step1Dot').classList.remove('active');
                        document.getElementById('step2Dot').classList.add('active');
                        startCountdown();
                    }, 1500);
                } else {
                    showError(result.message);
                }
            });
        });

        // 重置密码表单
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');

            if (newPassword !== confirmPassword) {
                showError('两次输入的密码不一致');
                return;
            }

            ajax('ajax_auth.php', {
                action: 'reset_password',
                email: formData.get('email'),
                code: formData.get('code'),
                new_password: newPassword
            }, function(result) {
                if (result.success) {
                    showSuccess('密码重置成功，正在跳转到登录页...');
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showError(result.message);
                }
            });
        });

        // 重新发送验证码
        const resendBtn = document.getElementById('resendCodeBtn');
        if (resendBtn) {
            resendBtn.addEventListener('click', function() {
                const email = document.getElementById('resetEmail').value;

                ajax('ajax_auth.php', {
                    action: 'send_reset_code',
                    email: email
                }, function(result) {
                    if (result.success) {
                        showSuccess('验证码已重新发送');
                        startCountdown();
                    } else {
                        showError(result.message);
                    }
                });
            });
        }

        // 倒计时
        function startCountdown() {
            countdown = 60;
            resendBtn.disabled = true;
            const timer = setInterval(function() {
                countdown--;
                resendBtn.textContent = countdown + '秒后重试';
                if (countdown <= 0) {
                    clearInterval(timer);
                    resendBtn.disabled = false;
                    resendBtn.textContent = '重新发送';
                }
            }, 1000);
        }
    </script>
</body>
</html>
