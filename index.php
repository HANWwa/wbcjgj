<?php
/**
 * 网站首页
 * @神奇奶酪
 */

require_once __DIR__ . '/includes/config.php';

// 检查是否已安装
if (!isInstalled()) {
    redirect('install/index.php');
    exit;
}

// 初始化认证
$auth = new Auth();
$currentUser = $auth->getCurrentUser();
$isLoggedIn = $auth->isLoggedIn();
$isAdmin = $auth->isAdmin();
$isVip = $auth->isVip();

// 获取网站设置
$siteName = getSetting('site_name', SYSTEM_NAME);
$siteDescription = getSetting('site_description', '专业的微博抽奖工具平台');
$enableVip = getSetting('enable_vip', '0');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo safe($siteName); ?> - 专业微博抽奖工具</title>
    <meta name="description" content="<?php echo safe($siteDescription); ?>">
    <meta name="keywords" content="<?php echo safe(getSetting('site_keywords', '微博抽奖,抽奖工具,微博营销')); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        /* 首页特殊样式 */
        .hero-section {
            text-align: center;
            padding: 80px 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
            border-radius: 20px;
            margin: 40px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 56px;
            font-weight: bold;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            animation: fadeInDown 0.8s ease-out;
        }

        .hero-subtitle {
            font-size: 24px;
            color: var(--text-gray);
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease-out;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeIn 1s ease-out;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin: 60px 0;
        }

        .feature-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-2);
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-light);
        }

        .feature-desc {
            color: var(--text-gray);
            line-height: 1.8;
        }

        .stats-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            margin: 60px 0;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 42px;
            font-weight: bold;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--text-gray);
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 36px;
            }

            .hero-subtitle {
                font-size: 18px;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .features-grid {
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
                <li><a href="<?php echo SITE_URL; ?>/index.php" class="active">首页</a></li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="<?php echo SITE_URL; ?>/lottery.php">开始抽奖</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/profile.php">个人中心</a></li>
                    <?php if ($enableVip == '1'): ?>
                        <li><a href="<?php echo SITE_URL; ?>/member.php">
                            会员中心
                            <?php if ($isVip): ?>
                                <span class="badge badge-vip">VIP</span>
                            <?php endif; ?>
                        </a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo SITE_URL; ?>/help.php">使用帮助</a></li>
                    <?php if ($isAdmin): ?>
                        <li><a href="<?php echo SITE_URL; ?>/admin/index.php" style="color: var(--accent-color);">后台管理</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php">退出登录</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/help.php">使用帮助</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/login.php">登录</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary" style="padding: 8px 20px;">注册</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- 主要内容 -->
    <div class="container">
        <!-- 英雄区域 -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">🎁 微博抽奖系统</h1>
                <p class="hero-subtitle">公平、透明、专业的微博抽奖工具</p>
                <div class="hero-buttons">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo SITE_URL; ?>/lottery.php" class="btn btn-primary">立即开始抽奖</a>
                        <a href="<?php echo SITE_URL; ?>/profile.php" class="btn btn-secondary">个人中心</a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary">免费注册</a>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-secondary">立即登录</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 功能特色 -->
        <section class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 class="feature-title">快速抽奖</h3>
                <p class="feature-desc">一键导入微博链接，自动解析博文数据，智能筛选有效参与用户，快速完成抽奖。</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3 class="feature-title">精准筛选</h3>
                <p class="feature-desc">支持点赞、评论、转发多维度筛选，交叉验证确保中奖用户真实有效。</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3 class="feature-title">公平公正</h3>
                <p class="feature-desc">采用随机算法保证抽奖公平性，生成唯一验证码可供随时查询验证。</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">数据统计</h3>
                <p class="feature-desc">完整记录每次抽奖数据，支持查看历史记录和中奖名单，数据可追溯。</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💎</div>
                <h3 class="feature-title">会员专享</h3>
                <p class="feature-desc">VIP会员享受API直连服务，无需手动配置，更便捷的抽奖体验。</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h3 class="feature-title">精美界面</h3>
                <p class="feature-desc">现代化设计风格，响应式布局，完美适配电脑和移动设备。</p>
            </div>
        </section>

        <!-- 统计数据 -->
        <?php
        $db = DB::getInstance();
        $totalUsers = $db->count('users');
        $totalLotteries = $db->count('lottery_records');
        $totalWinners = $db->count('lottery_winners');
        ?>
        <section class="stats-section">
            <h2 class="text-center" style="font-size: 32px; margin-bottom: 10px;">平台数据</h2>
            <p class="text-center" style="color: var(--text-gray);">越来越多的用户选择我们</p>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
                    <div class="stat-label">注册用户</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($totalLotteries); ?></div>
                    <div class="stat-label">抽奖次数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($totalWinners); ?></div>
                    <div class="stat-label">产生中奖</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">公平公正</div>
                </div>
            </div>
        </section>
    </div>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="<?php echo SITE_URL; ?>/index.php">首页</a>
                    <a href="<?php echo SITE_URL; ?>/help.php">使用帮助</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo SITE_URL; ?>/profile.php">个人中心</a>
                    <?php endif; ?>
                </div>
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo safe($siteName); ?> - <?php echo COPYRIGHT; ?></p>
                    <?php if (getSetting('icp_number')): ?>
                        <p><?php echo safe(getSetting('icp_number')); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
