<?php
/**
 * 用户注册页面
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
$enableRegister = getSetting('enable_register', '1');
$enableEmailVerify = getSecuritySetting('enable_email_verify', '0');
$enableMathVerify = getSecuritySetting('enable_math_verify', '1');

// 检查是否开放注册
if ($enableRegister != '1') {
    die('注册功能暂未开放');
}

// 生成数学验证码
$mathCaptcha = '';
if ($enableMathVerify == '1') {
    $mathCaptcha = generateMathCaptcha();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - <?php echo safe($siteName); ?></title>
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

        .strength-meter {
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .strength-weak { width: 33%; background: var(--error); }
        .strength-medium { width: 66%; background: var(--warning); }
        .strength-strong { width: 100%; background: var(--success); }

        .captcha-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .captcha-question {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 20px;
            border-radius: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.1);
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
                <div class="auth-logo">🎁</div>
                <h1 class="auth-title">创建账户</h1>
                <p class="auth-subtitle">加入我们，开始您的抽奖之旅</p>
            </div>

            <!-- 提示消息 -->
            <div id="errorMessage" class="alert alert-error hidden"></div>
            <div id="successMessage" class="alert alert-success hidden"></div>

            <!-- 注册表单 -->
            <form id="registerForm">
                <!-- 用户名 -->
                <div class="form-group">
                    <label class="form-label">用户名</label>
                    <div class="input-group">
                        <span class="input-icon">👤</span>
                        <input type="text" name="username" class="form-control form-control-icon"
                               placeholder="请输入用户名" required minlength="3" maxlength="20"
                               pattern="[a-zA-Z0-9_]+" title="只能包含字母、数字和下划线">
                    </div>
                    <small class="form-hint">3-20个字符，只能包含字母、数字和下划线</small>
                </div>

                <!-- 邮箱 -->
                <div class="form-group">
                    <label class="form-label">邮箱地址</label>
                    <div class="input-group">
                        <span class="input-icon">📧</span>
                        <input type="email" name="email" id="emailInput" class="form-control form-control-icon"
                               placeholder="请输入邮箱地址" required>
                    </div>
                    <small class="form-hint">用于接收验证码和找回密码</small>
                </div>

                <!-- 邮箱验证码 -->
                <?php if ($enableEmailVerify == '1'): ?>
                <div class="form-group">
                    <label class="form-label">邮箱验证码</label>
                    <div class="captcha-group">
                        <input type="text" name="email_code" class="form-control captcha-input"
                               placeholder="验证码" required maxlength="6">
                        <button type="button" id="sendCodeBtn" class="btn btn-secondary send-code-btn">
                            发送验证码
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 密码 -->
                <div class="form-group">
                    <label class="form-label">密码</label>
                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" id="passwordInput"
                               class="form-control form-control-icon"
                               placeholder="请输入密码" required minlength="6">
                        <span class="password-toggle" onclick="togglePassword('passwordInput', this)">👁️</span>
                    </div>
                    <div class="strength-meter">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <small class="form-hint" id="strengthText">至少6个字符</small>
                </div>

                <!-- 确认密码 -->
                <div class="form-group">
                    <label class="form-label">确认密码</label>
                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password_confirm" id="passwordConfirm"
                               class="form-control form-control-icon"
                               placeholder="请再次输入密码" required minlength="6">
                        <span class="password-toggle" onclick="togglePassword('passwordConfirm', this)">👁️</span>
                    </div>
                </div>

                <!-- 数学验证码 -->
                <?php if ($enableMathVerify == '1'): ?>
                <div class="form-group">
                    <label class="form-label">验证码</label>
                    <div class="captcha-group">
                        <div class="captcha-question"><?php echo $mathCaptcha; ?></div>
                        <input type="number" name="math_answer" class="form-control captcha-input"
                               placeholder="答案" required>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 提交按钮 -->
                <button type="submit" class="btn btn-primary btn-block mt-30">立即注册</button>
            </form>

            <!-- 链接 -->
            <div class="auth-links">
                <p>已有账户？<a href="login.php">立即登录</a></p>
                <a href="index.php" class="back-home">
                    <span>←</span>
                    <span>返回首页</span>
                </a>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/auth.js"></script>
    <script>
        // 邮箱验证码倒计时
        let countdown = 0;
        const sendCodeBtn = document.getElementById('sendCodeBtn');

        if (sendCodeBtn) {
            sendCodeBtn.addEventListener('click', function() {
                const email = document.getElementById('emailInput').value;

                if (!email) {
                    showError('请先输入邮箱地址');
                    return;
                }

                if (!isValidEmail(email)) {
                    showError('邮箱格式不正确');
                    return;
                }

                // 发送验证码
                ajax('ajax_auth.php', {
                    action: 'send_email_code',
                    email: email
                }, function(result) {
                    if (result.success) {
                        showSuccess('验证码已发送，请查收邮箱');
                        startCountdown();
                    } else {
                        showError(result.message);
                    }
                });
            });
        }

        function startCountdown() {
            countdown = 60;
            sendCodeBtn.disabled = true;
            const timer = setInterval(function() {
                countdown--;
                sendCodeBtn.textContent = countdown + '秒后重试';
                if (countdown <= 0) {
                    clearInterval(timer);
                    sendCodeBtn.disabled = false;
                    sendCodeBtn.textContent = '发送验证码';
                }
            }, 1000);
        }

        // 密码强度检测
        const passwordInput = document.getElementById('passwordInput');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);

                strengthBar.className = 'strength-bar';
                if (password.length === 0) {
                    strengthText.textContent = '至少6个字符';
                } else if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                    strengthText.textContent = '密码强度：弱';
                    strengthText.style.color = 'var(--error)';
                } else if (strength <= 4) {
                    strengthBar.classList.add('strength-medium');
                    strengthText.textContent = '密码强度：中';
                    strengthText.style.color = 'var(--warning)';
                } else {
                    strengthBar.classList.add('strength-strong');
                    strengthText.textContent = '密码强度：强';
                    strengthText.style.color = 'var(--success)';
                }
            });
        }
    </script>
</body>
</html>
