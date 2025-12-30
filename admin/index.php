<?php
/**
 * 管理后台首页 - 仪表盘
 * @神奇奶酪
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin_auth.php';

// 检查是否已安装
if (!isInstalled()) {
    redirect('../install/index.php');
    exit;
}

// 检查登录状态
$adminAuth = new AdminAuth();
$adminAuth->requireLogin();

$admin = $adminAuth->getCurrentAdmin();
$db = DB::getInstance();

// 获取统计数据
$stats = [
    'total_users' => $db->fetchColumn("SELECT COUNT(*) FROM {prefix}users WHERE role = 'user'"),
    'total_vip' => $db->fetchColumn("SELECT COUNT(*) FROM {prefix}users WHERE role = 'user' AND vip_expire > NOW()"),
    'total_lottery' => $db->fetchColumn("SELECT COUNT(*) FROM {prefix}lottery_records"),
    'today_lottery' => $db->fetchColumn("SELECT COUNT(*) FROM {prefix}lottery_records WHERE DATE(created_at) = CURDATE()"),
    'total_transactions' => $db->fetchColumn("SELECT COUNT(*) FROM {prefix}transactions WHERE status = 'completed'"),
    'total_revenue' => $db->fetchColumn("SELECT IFNULL(SUM(amount), 0) FROM {prefix}transactions WHERE status = 'completed'") ?: 0,
];

// 获取最近7天的抽奖统计
$lotteryTrend = $db->fetchAll("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM {prefix}lottery_records
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

// 获取最近用户
$recentUsers = $db->fetchAll("
    SELECT id, username, email, vip_expire, created_at
    FROM {prefix}users
    WHERE role = 'user'
    ORDER BY created_at DESC
    LIMIT 10
");

// 获取最近抽奖记录
$recentLotteries = $db->fetchAll("
    SELECT lr.*, u.username
    FROM {prefix}lottery_records lr
    LEFT JOIN {prefix}users u ON lr.user_id = u.id
    ORDER BY lr.created_at DESC
    LIMIT 10
");

// 获取系统信息
$systemInfo = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'db_size' => $db->fetchColumn("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE table_schema = DATABASE()") . ' MB',
];

$pageTitle = '仪表盘';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 管理后台</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-body">
    <!-- 侧边栏 -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🎰</div>
            <h2 class="sidebar-title"><?php echo getSetting('site_name'); ?></h2>
            <p class="sidebar-subtitle">管理后台</p>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item active">
                <span class="nav-icon">📊</span>
                <span class="nav-text">仪表盘</span>
            </a>
            <a href="users.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span class="nav-text">用户管理</span>
            </a>
            <a href="lottery_records.php" class="nav-item">
                <span class="nav-icon">🎲</span>
                <span class="nav-text">抽奖记录</span>
            </a>
            <a href="settings.php" class="nav-item">
                <span class="nav-icon">⚙️</span>
                <span class="nav-text">系统设置</span>
            </a>
            <a href="api_settings.php" class="nav-item">
                <span class="nav-icon">🔑</span>
                <span class="nav-text">API设置</span>
            </a>
            <a href="payment_settings.php" class="nav-item">
                <span class="nav-icon">💳</span>
                <span class="nav-text">支付设置</span>
            </a>
            <a href="logs.php" class="nav-item">
                <span class="nav-icon">📝</span>
                <span class="nav-text">系统日志</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="../index.php" class="sidebar-link" target="_blank">
                <span>🏠</span> 前台首页
            </a>
            <a href="logout.php" class="sidebar-link">
                <span>🚪</span> 退出登录
            </a>
        </div>
    </aside>

    <!-- 主要内容区 -->
    <main class="admin-main">
        <!-- 顶部栏 -->
        <header class="admin-header">
            <h1 class="page-title">📊 <?php echo $pageTitle; ?></h1>
            <div class="header-actions">
                <div class="admin-info">
                    <span class="admin-avatar">👤</span>
                    <span class="admin-name"><?php echo safe($admin['username']); ?></span>
                </div>
            </div>
        </header>

        <!-- 内容区 -->
        <div class="admin-content">
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">总用户数</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">⭐</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_vip']); ?></div>
                        <div class="stat-label">VIP用户</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">🎲</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_lottery']); ?></div>
                        <div class="stat-label">总抽奖次数</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">📈</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['today_lottery']); ?></div>
                        <div class="stat-label">今日抽奖</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">💰</div>
                    <div class="stat-content">
                        <div class="stat-value">¥<?php echo number_format($stats['total_revenue'], 2); ?></div>
                        <div class="stat-label">总收入</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">💳</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_transactions']); ?></div>
                        <div class="stat-label">成功交易</div>
                    </div>
                </div>
            </div>

            <!-- 图表和列表 -->
            <div class="content-grid">
                <!-- 最近用户 -->
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">👥 最近注册用户</h3>
                        <a href="users.php" class="card-link">查看全部 →</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>用户名</th>
                                        <th>邮箱</th>
                                        <th>状态</th>
                                        <th>注册时间</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td><?php echo safe($user['username']); ?></td>
                                        <td><?php echo safe($user['email']); ?></td>
                                        <td>
                                            <?php if ($user['vip_expire'] && strtotime($user['vip_expire']) > time()): ?>
                                                <span class="badge badge-vip">VIP</span>
                                            <?php else: ?>
                                                <span class="badge badge-normal">普通</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 最近抽奖 -->
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">🎲 最近抽奖记录</h3>
                        <a href="lottery_records.php" class="card-link">查看全部 →</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>用户</th>
                                        <th>类型</th>
                                        <th>中奖人数</th>
                                        <th>状态</th>
                                        <th>时间</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLotteries as $lottery): ?>
                                    <tr>
                                        <td><?php echo safe($lottery['username']); ?></td>
                                        <td>
                                            <?php
                                            $typeNames = [
                                                'like' => '点赞',
                                                'comment' => '评论',
                                                'repost' => '转发',
                                                'mixed' => '混合'
                                            ];
                                            echo $typeNames[$lottery['lottery_type']] ?? $lottery['lottery_type'];
                                            ?>
                                        </td>
                                        <td><?php echo $lottery['winner_count']; ?>人</td>
                                        <td>
                                            <?php if ($lottery['status'] === 'completed'): ?>
                                                <span class="badge badge-success">已完成</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">处理中</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($lottery['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 系统信息 -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">💻 系统信息</h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">PHP版本</span>
                            <span class="info-value"><?php echo $systemInfo['php_version']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">服务器软件</span>
                            <span class="info-value"><?php echo $systemInfo['server_software']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">数据库大小</span>
                            <span class="info-value"><?php echo $systemInfo['db_size']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">当前时间</span>
                            <span class="info-value"><?php echo date('Y-m-d H:i:s'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/common.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
