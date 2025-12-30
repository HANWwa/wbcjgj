<?php
/**
 * 管理后台登录页面
 * @神奇奶酪
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin_auth.php';

// 检查是否已安装
if (!isInstalled()) {
    redirect('../install/index.php');
    exit;
}

// 如果已登录，跳转到后台首页
$adminAuth = new AdminAuth();
if ($adminAuth->isLoggedIn()) {
    redirect('index.php');
    exit;
}

// 处理登录请求
if (isPost()) {
    $username = post('username');
    $password = post('password');
    $remember = post('remember') === '1';

    $result = $adminAuth->login($username, $password, $remember);

    if ($result['success']) {
        $redirect = get('redirect', 'index.php');
        redirect($redirect);
        exit;
    } else {
        $error = $result['message'];
    }
}

$pageTitle = '管理员登录';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo getSetting('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: var(--dark-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .login-title {
            font-size: 32px;
            font-weight: bold;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: var(--text-gray);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-light);
            font-weight: 500;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            opacity: 0.5;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text-light);
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.08);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            color: var(--text-gray);
            cursor: pointer;
            font-weight: normal;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--gradient-2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 87, 108, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 40px 25px;
            }

            .login-title {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">🔐</div>
                <h1 class="login-title">管理后台</h1>
                <p class="login-subtitle">请使用管理员账号登录</p>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo safe($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">管理员账号</label>
                    <div class="input-group">
                        <span class="input-icon">👤</span>
                        <input type="text" id="username" name="username" class="form-control"
                               placeholder="请输入管理员用户名" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">登录密码</label>
                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="请输入密码" required>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">记住我（30天内自动登录）</label>
                </div>

                <button type="submit" class="btn-login">
                    🚀 立即登录
                </button>
            </form>

            <div class="login-footer">
                <a href="../index.php" class="back-link">← 返回前台首页</a>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px; color: var(--text-gray); font-size: 13px;">
            &copy; 2024 <?php echo getSetting('site_name'); ?>. <?php echo COPYRIGHT; ?>
        </div>
    </div>
</body>
</html>
