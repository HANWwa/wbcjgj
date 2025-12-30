<?php
/**
 * 个人中心页面
 * @神奇奶酪
 */

require_once __DIR__ . '/includes/config.php';

// 检查是否已安装
if (!isInstalled()) {
    redirect('install/index.php');
    exit;
}

// 检查登录状态
$auth = new Auth();
$auth->requireLogin();

// 获取当前用户信息
$currentUser = $auth->getCurrentUser();
$isAdmin = $auth->isAdmin();
$isVip = $auth->isVip();

// 获取用户统计信息
$userClass = new User();
$stats = $userClass->getUserStats($currentUser['id']);

// 获取网站设置
$siteName = getSetting('site_name', SYSTEM_NAME);
$enableVip = getSetting('enable_vip', '0');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心 - <?php echo safe($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        .page-container {
            display: flex;
            gap: 30px;
            padding: 40px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .sidebar {
            width: 280px;
            flex-shrink: 0;
        }

        .main-content {
            flex: 1;
            min-width: 0;
        }

        .user-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .user-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-2);
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--gradient-2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
        }

        .user-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .user-email {
            color: var(--text-gray);
            margin-bottom: 15px;
        }

        .user-badges {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--text-gray);
            font-size: 14px;
        }

        .section-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .section-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-2);
        }

        .section-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title-icon {
            font-size: 24px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-gray);
        }

        .info-value {
            font-weight: 600;
            color: var(--text-light);
        }

        @media (max-width: 1024px) {
            .page-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container">
            <a href="<?php echo SITE_URL; ?>/index.php" class="navbar-brand">
                <?php echo safe($siteName); ?>
            </a>
            <ul class="navbar-menu">
                <li><a href="<?php echo SITE_URL; ?>/index.php">首页</a></li>
                <li><a href="<?php echo SITE_URL; ?>/lottery.php">开始抽奖</a></li>
                <li><a href="<?php echo SITE_URL; ?>/profile.php" class="active">个人中心</a></li>
                <?php if ($enableVip == '1'): ?>
                    <li><a href="<?php echo SITE_URL; ?>/member.php">会员中心</a></li>
                <?php endif; ?>
                <li><a href="<?php echo SITE_URL; ?>/help.php">使用帮助</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="<?php echo SITE_URL; ?>/admin/index.php" style="color: var(--accent-color);">后台管理</a></li>
                <?php endif; ?>
                <li><a href="<?php echo SITE_URL; ?>/logout.php">退出登录</a></li>
            </ul>
        </div>
    </nav>

    <!-- 主要内容 -->
    <div class="page-container">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <!-- 用户卡片 -->
            <div class="user-card">
                <div class="user-avatar">👤</div>
                <div class="user-name"><?php echo safe($currentUser['username']); ?></div>
                <div class="user-email"><?php echo safe($currentUser['email']); ?></div>
                <div class="user-badges">
                    <?php if ($isAdmin): ?>
                        <span class="badge badge-admin">管理员</span>
                    <?php endif; ?>
                    <?php if ($isVip): ?>
                        <span class="badge badge-vip">VIP会员</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 侧边栏菜单 -->
            <div class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="<?php echo SITE_URL; ?>/profile.php" class="active">
                        <span>📊</span>
                        <span>个人中心</span>
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/records.php">
                        <span>📝</span>
                        <span>抽奖记录</span>
                    </a></li>
                    <?php if ($enableVip == '1'): ?>
                        <li><a href="<?php echo SITE_URL; ?>/member.php">
                            <span>💎</span>
                            <span>会员中心</span>
                        </a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo SITE_URL; ?>/help.php">
                        <span>❓</span>
                        <span>使用帮助</span>
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php">
                        <span>🚪</span>
                        <span>退出登录</span>
                    </a></li>
                </ul>
            </div>
        </aside>

        <!-- 主内容区域 -->
        <main class="main-content">
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-value"><?php echo $stats['lottery_count']; ?></div>
                    <div class="stat-label">抽奖次数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎁</div>
                    <div class="stat-value"><?php echo $stats['winner_count']; ?></div>
                    <div class="stat-label">中奖人数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-value"><?php echo $stats['last_lottery'] ? formatTime($stats['last_lottery']) : '暂无'; ?></div>
                    <div class="stat-label">最近抽奖</div>
                </div>
            </div>

            <!-- 提示消息 -->
            <div id="errorMessage" class="alert alert-error hidden"></div>
            <div id="successMessage" class="alert alert-success hidden"></div>

            <!-- 账户信息 -->
            <div class="section-card">
                <h2 class="section-title">
                    <span class="section-title-icon">👤</span>
                    <span>账户信息</span>
                </h2>

                <div class="info-item">
                    <span class="info-label">用户名</span>
                    <span class="info-value"><?php echo safe($currentUser['username']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">邮箱</span>
                    <span class="info-value"><?php echo safe($currentUser['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">账户类型</span>
                    <span class="info-value">
                        <?php if ($isAdmin): ?>
                            <span class="badge badge-admin">管理员</span>
                        <?php else: ?>
                            普通用户
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">会员状态</span>
                    <span class="info-value">
                        <?php if ($isVip): ?>
                            <span class="badge badge-vip">VIP会员</span>
                            <?php if ($currentUser['vip_expire']): ?>
                                <small style="color: var(--text-gray); display: block; margin-top: 5px;">
                                    到期时间: <?php echo date('Y-m-d H:i', strtotime($currentUser['vip_expire'])); ?>
                                </small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-warning">普通用户</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">注册时间</span>
                    <span class="info-value"><?php echo date('Y-m-d H:i', strtotime($currentUser['created_at'])); ?></span>
                </div>
            </div>

            <!-- 修改邮箱 -->
            <div class="section-card">
                <h2 class="section-title">
                    <span class="section-title-icon">📧</span>
                    <span>修改邮箱</span>
                </h2>

                <form id="updateEmailForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">新邮箱地址</label>
                            <input type="email" name="new_email" class="form-control"
                                   placeholder="请输入新邮箱" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">当前密码</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="请输入当前密码验证" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">更新邮箱</button>
                </form>
            </div>

            <!-- 修改密码 -->
            <div class="section-card">
                <h2 class="section-title">
                    <span class="section-title-icon">🔒</span>
                    <span>修改密码</span>
                </h2>

                <form id="changePasswordForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">当前密码</label>
                            <input type="password" name="old_password" class="form-control"
                                   placeholder="请输入当前密码" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">新密码</label>
                            <input type="password" name="new_password" class="form-control"
                                   placeholder="请输入新密码" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label">确认新密码</label>
                            <input type="password" name="confirm_password" class="form-control"
                                   placeholder="请再次输入新密码" required minlength="6">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">修改密码</button>
                </form>
            </div>
        </main>
    </div>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo safe($siteName); ?> - <?php echo COPYRIGHT; ?></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="<?php echo SITE_URL; ?>/assets/js/auth.js"></script>
    <script>
        // 修改邮箱
        document.getElementById('updateEmailForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            ajax('ajax_profile.php', {
                action: 'update_email',
                new_email: formData.get('new_email'),
                password: formData.get('password')
            }, function(result) {
                if (result.success) {
                    showSuccess(result.message);
                    document.getElementById('updateEmailForm').reset();
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showError(result.message);
                }
            });
        });

        // 修改密码
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');

            if (newPassword !== confirmPassword) {
                showError('两次输入的新密码不一致');
                return;
            }

            ajax('ajax_profile.php', {
                action: 'change_password',
                old_password: formData.get('old_password'),
                new_password: newPassword
            }, function(result) {
                if (result.success) {
                    showSuccess(result.message);
                    document.getElementById('changePasswordForm').reset();
                } else {
                    showError(result.message);
                }
            });
        });
    </script>
</body>
</html>
