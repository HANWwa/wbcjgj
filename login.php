<?php
/**
 * 用户登录页面
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

// 获取跳转地址
$redirect = get('redirect', '');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户登录 - <?php echo safe($siteName); ?></title>
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
            max-width: 480px;
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .checkbox-group label {
            color: var(--text-gray);
            font-size: 14px;
            cursor: pointer;
            user-select: none;
        }

        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .forgot-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
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

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- 加载动画 -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div>
            <div class="loader"></div>
            <div class="loading-text">正在登录，请稍候...</div>
        </div>
    </div>

    <div class="auth-container">
        <div class="auth-box">
            <!-- 头部 -->
            <div class="auth-header">
                <div class="auth-logo">🎁</div>
                <h1 class="auth-title">欢迎回来</h1>
                <p class="auth-subtitle">登录您的账户继续使用</p>
            </div>

            <!-- 提示消息 -->
            <div id="errorMessage" class="alert alert-error hidden"></div>
            <div id="successMessage" class="alert alert-success hidden"></div>

            <!-- 登录表单 -->
            <form id="loginForm">
                <input type="hidden" name="redirect" value="<?php echo safe($redirect); ?>">

                <!-- 用户名/邮箱 -->
                <div class="form-group">
                    <label class="form-label">用户名或邮箱</label>
                    <div class="input-group">
                        <span class="input-icon">👤</span>
                        <input type="text" name="username" class="form-control form-control-icon"
                               placeholder="请输入用户名或邮箱" required autofocus>
                    </div>
                </div>

                <!-- 密码 -->
                <div class="form-group">
                    <label class="form-label">密码</label>
                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" id="passwordInput"
                               class="form-control form-control-icon"
                               placeholder="请输入密码" required>
                        <span class="password-toggle" onclick="togglePassword('passwordInput', this)">👁️</span>
                    </div>
                </div>

                <!-- 选项 -->
                <div class="form-options">
                    <div class="checkbox-group">
                        <input type="checkbox" name="remember" id="rememberMe" value="1">
                        <label for="rememberMe">记住我</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-link">忘记密码？</a>
                </div>

                <!-- 提交按钮 -->
                <button type="submit" class="btn btn-primary btn-block">立即登录</button>
            </form>

            <!-- 链接 -->
            <div class="auth-links">
                <p>还没有账户？<a href="register.php">立即注册</a></p>
                <a href="index.php" class="back-home">
                    <span>←</span>
                    <span>返回首页</span>
                </a>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/auth.js"></script>
</body>
</html>
