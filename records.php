<?php
/**
 * 抽奖记录页面
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

// 获取网站设置
$siteName = getSetting('site_name', SYSTEM_NAME);
$enableVip = getSetting('enable_vip', '0');

// 分页参数
$page = max(1, (int)get('page', 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 获取抽奖记录
$db = DB::getInstance();
$total = $db->count('lottery_records', 'user_id = :user_id', [':user_id' => $currentUser['id']]);
$records = $db->fetchAll(
    "SELECT * FROM {prefix}lottery_records
     WHERE user_id = :user_id
     ORDER BY id DESC
     LIMIT {$perPage} OFFSET {$offset}",
    [':user_id' => $currentUser['id']]
);

$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>抽奖记录 - <?php echo safe($siteName); ?></title>
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

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            font-weight: bold;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .page-desc {
            color: var(--text-gray);
        }

        .record-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
        }

        .record-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .record-id {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-light);
        }

        .record-time {
            color: var(--text-gray);
            font-size: 14px;
        }

        .record-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .record-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .record-label {
            color: var(--text-gray);
            font-size: 13px;
        }

        .record-value {
            color: var(--text-light);
            font-size: 15px;
            font-weight: 500;
        }

        .record-url {
            color: var(--primary-color);
            text-decoration: none;
            word-break: break-all;
        }

        .record-url:hover {
            text-decoration: underline;
        }

        .record-footer {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-completed {
            background: rgba(81, 207, 102, 0.2);
            color: var(--success);
        }

        .status-processing {
            background: rgba(79, 172, 254, 0.2);
            color: #4facfe;
        }

        .status-pending {
            background: rgba(255, 212, 59, 0.2);
            color: var(--warning);
        }

        .status-failed {
            background: rgba(255, 107, 107, 0.2);
            color: var(--error);
        }

        .verify-code {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }

        .winners-btn {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            background: var(--gradient-2);
            color: var(--text-light);
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .winners-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-text {
            font-size: 18px;
            color: var(--text-gray);
            margin-bottom: 25px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            max-width: 800px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-title {
            font-size: 24px;
            font-weight: bold;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .modal-close {
            font-size: 28px;
            cursor: pointer;
            color: var(--text-gray);
            transition: all 0.3s;
        }

        .modal-close:hover {
            color: var(--primary-color);
            transform: rotate(90deg);
        }

        .winner-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .winner-rank {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--text-light);
        }

        .winner-info {
            flex: 1;
        }

        .winner-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .winner-uid {
            font-size: 13px;
            color: var(--text-gray);
        }

        @media (max-width: 1024px) {
            .page-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .record-body {
                grid-template-columns: 1fr;
            }

            .record-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                <li><a href="<?php echo SITE_URL; ?>/profile.php">个人中心</a></li>
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
            <div class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="<?php echo SITE_URL; ?>/profile.php">
                        <span>📊</span>
                        <span>个人中心</span>
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/records.php" class="active">
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
            <!-- 页面头部 -->
            <div class="page-header">
                <h1 class="page-title">📝 抽奖记录</h1>
                <p class="page-desc">查看您的所有抽奖历史记录和中奖名单</p>
            </div>

            <?php if (empty($records)): ?>
                <!-- 空状态 -->
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <div class="empty-text">暂无抽奖记录</div>
                    <a href="<?php echo SITE_URL; ?>/lottery.php" class="btn btn-primary">
                        立即开始抽奖
                    </a>
                </div>
            <?php else: ?>
                <!-- 抽奖记录列表 -->
                <?php foreach ($records as $record): ?>
                <div class="record-card">
                    <div class="record-header">
                        <div class="record-id">抽奖 #<?php echo $record['id']; ?></div>
                        <div class="record-time"><?php echo formatTime($record['created_at']); ?></div>
                    </div>

                    <div class="record-body">
                        <div class="record-item">
                            <div class="record-label">微博链接</div>
                            <div class="record-value">
                                <a href="<?php echo safe($record['weibo_url']); ?>" target="_blank" class="record-url">
                                    查看微博
                                </a>
                            </div>
                        </div>

                        <div class="record-item">
                            <div class="record-label">抽奖类型</div>
                            <div class="record-value">
                                <?php
                                $typeMap = [
                                    'like' => '点赞',
                                    'comment' => '评论',
                                    'repost' => '转发',
                                    'mixed' => '混合'
                                ];
                                echo $typeMap[$record['lottery_type']] ?? $record['lottery_type'];
                                ?>
                            </div>
                        </div>

                        <div class="record-item">
                            <div class="record-label">抽奖模式</div>
                            <div class="record-value">
                                <?php echo $record['mode'] === 'vip' ? '💎 VIP模式' : '🆓 免费模式'; ?>
                            </div>
                        </div>

                        <div class="record-item">
                            <div class="record-label">中奖人数</div>
                            <div class="record-value"><?php echo $record['winner_count']; ?> 人</div>
                        </div>

                        <div class="record-item">
                            <div class="record-label">参与人数</div>
                            <div class="record-value"><?php echo $record['total_participants']; ?> 人</div>
                        </div>

                        <div class="record-item">
                            <div class="record-label">完成时间</div>
                            <div class="record-value">
                                <?php echo $record['completed_at'] ? formatTime($record['completed_at']) : '-'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="record-footer">
                        <?php
                        $statusMap = [
                            'pending' => '<span class="status-badge status-pending">等待中</span>',
                            'processing' => '<span class="status-badge status-processing">处理中</span>',
                            'completed' => '<span class="status-badge status-completed">已完成</span>',
                            'failed' => '<span class="status-badge status-failed">失败</span>'
                        ];
                        echo $statusMap[$record['status']] ?? '';
                        ?>

                        <?php if ($record['verify_code']): ?>
                            <span class="verify-code">验证码: <?php echo safe($record['verify_code']); ?></span>
                        <?php endif; ?>

                        <?php if ($record['status'] === 'completed'): ?>
                            <button class="winners-btn" onclick="showWinners(<?php echo $record['id']; ?>)">
                                查看中奖名单
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-link">上一页</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-link">下一页</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- 中奖名单弹窗 -->
    <div id="winnersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">🎉 中奖名单</h3>
                <span class="modal-close" onclick="closeWinners()">&times;</span>
            </div>
            <div id="winnersList"></div>
        </div>
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
        // 显示中奖名单
        function showWinners(lotteryId) {
            ajax('ajax_records.php', {
                action: 'get_winners',
                lottery_id: lotteryId
            }, function(result) {
                if (result.success && result.data.winners) {
                    const winners = result.data.winners;
                    let html = '';

                    winners.forEach(function(winner, index) {
                        html += `
                            <div class="winner-item">
                                <div class="winner-rank">${winner.rank}</div>
                                <div class="winner-info">
                                    <div class="winner-name">${winner.weibo_name}</div>
                                    <div class="winner-uid">UID: ${winner.weibo_uid}</div>
                                </div>
                            </div>
                        `;
                    });

                    document.getElementById('winnersList').innerHTML = html;
                    document.getElementById('winnersModal').classList.add('show');
                } else {
                    showError('获取中奖名单失败');
                }
            });
        }

        // 关闭弹窗
        function closeWinners() {
            document.getElementById('winnersModal').classList.remove('show');
        }

        // 点击背景关闭弹窗
        document.getElementById('winnersModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeWinners();
            }
        });
    </script>
</body>
</html>
