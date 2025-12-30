<?php
/**
 * 抽奖记录管理页面
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

// 分页参数
$page = max(1, (int)get('page', 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 搜索参数
$search = get('search', '');
$lotteryType = get('lottery_type', '');
$mode = get('mode', '');

// 构建查询条件
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (lr.weibo_url LIKE :search OR lr.verify_code LIKE :search OR u.username LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($lotteryType) {
    $where .= " AND lr.lottery_type = :lottery_type";
    $params[':lottery_type'] = $lotteryType;
}

if ($mode) {
    $where .= " AND lr.mode = :mode";
    $params[':mode'] = $mode;
}

// 获取总数
$total = $db->fetchColumn(
    "SELECT COUNT(*) FROM {prefix}lottery_records lr LEFT JOIN {prefix}users u ON lr.user_id = u.id WHERE {$where}",
    $params
);

// 获取抽奖记录
$records = $db->fetchAll(
    "SELECT lr.*, u.username
     FROM {prefix}lottery_records lr
     LEFT JOIN {prefix}users u ON lr.user_id = u.id
     WHERE {$where}
     ORDER BY lr.id DESC
     LIMIT {$offset}, {$perPage}",
    $params
);

// 分页
$totalPages = ceil($total / $perPage);

$pageTitle = '抽奖记录';
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
            <a href="index.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span class="nav-text">仪表盘</span>
            </a>
            <a href="users.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span class="nav-text">用户管理</span>
            </a>
            <a href="lottery_records.php" class="nav-item active">
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
            <h1 class="page-title">🎲 <?php echo $pageTitle; ?></h1>
            <div class="header-actions">
                <div class="admin-info">
                    <span class="admin-avatar">👤</span>
                    <span class="admin-name"><?php echo safe($admin['username']); ?></span>
                </div>
            </div>
        </header>

        <!-- 内容区 -->
        <div class="admin-content">
            <!-- 搜索和筛选 -->
            <div class="content-card">
                <div class="card-body">
                    <form method="GET" action="" class="filter-form">
                        <div class="filter-grid">
                            <div class="form-group">
                                <input type="text" name="search" class="form-control"
                                       placeholder="搜索用户名、链接或验证码" value="<?php echo safe($search); ?>">
                            </div>

                            <div class="form-group">
                                <select name="lottery_type" class="form-control">
                                    <option value="">全部类型</option>
                                    <option value="like" <?php echo $lotteryType === 'like' ? 'selected' : ''; ?>>点赞抽奖</option>
                                    <option value="comment" <?php echo $lotteryType === 'comment' ? 'selected' : ''; ?>>评论抽奖</option>
                                    <option value="repost" <?php echo $lotteryType === 'repost' ? 'selected' : ''; ?>>转发抽奖</option>
                                    <option value="mixed" <?php echo $lotteryType === 'mixed' ? 'selected' : ''; ?>>混合抽奖</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <select name="mode" class="form-control">
                                    <option value="">全部模式</option>
                                    <option value="vip" <?php echo $mode === 'vip' ? 'selected' : ''; ?>>VIP抽奖</option>
                                    <option value="free" <?php echo $mode === 'free' ? 'selected' : ''; ?>>免费抽奖</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">🔍 搜索</button>
                                <a href="lottery_records.php" class="btn btn-secondary">清空</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 抽奖记录列表 -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">抽奖记录（共 <?php echo number_format($total); ?> 条）</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>用户</th>
                                    <th>类型</th>
                                    <th>模式</th>
                                    <th>中奖/参与</th>
                                    <th>验证码</th>
                                    <th>状态</th>
                                    <th>时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo $record['id']; ?></td>
                                    <td><?php echo safe($record['username']); ?></td>
                                    <td>
                                        <?php
                                        $typeNames = [
                                            'like' => '❤️ 点赞',
                                            'comment' => '💬 评论',
                                            'repost' => '🔁 转发',
                                            'mixed' => '🎯 混合'
                                        ];
                                        echo $typeNames[$record['lottery_type']] ?? $record['lottery_type'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($record['mode'] === 'vip'): ?>
                                            <span class="badge badge-vip">VIP</span>
                                        <?php else: ?>
                                            <span class="badge badge-normal">免费</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $record['winner_count']; ?></strong>
                                        /
                                        <?php echo $record['total_participants']; ?>
                                    </td>
                                    <td>
                                        <code style="background: rgba(255,255,255,0.1); padding: 3px 8px; border-radius: 4px;">
                                            <?php echo $record['verify_code']; ?>
                                        </code>
                                    </td>
                                    <td>
                                        <?php if ($record['status'] === 'completed'): ?>
                                            <span class="badge badge-success">已完成</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">处理中</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($record['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action" onclick="viewWinners(<?php echo $record['id']; ?>)" title="查看中奖名单">
                                                👁️
                                            </button>
                                            <button class="btn-action" onclick="deleteLottery(<?php echo $record['id']; ?>)" title="删除">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        $queryParams = $_GET;

                        // 上一页
                        if ($page > 1):
                            $queryParams['page'] = $page - 1;
                        ?>
                        <a href="?<?php echo http_build_query($queryParams); ?>" class="page-link">← 上一页</a>
                        <?php endif; ?>

                        <!-- 页码 -->
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        for ($i = $startPage; $i <= $endPage; $i++):
                            $queryParams['page'] = $i;
                        ?>
                        <a href="?<?php echo http_build_query($queryParams); ?>"
                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <!-- 下一页 -->
                        <?php
                        if ($page < $totalPages):
                            $queryParams['page'] = $page + 1;
                        ?>
                        <a href="?<?php echo http_build_query($queryParams); ?>" class="page-link">下一页 →</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- 中奖名单模态框 -->
    <div id="winnersModal" class="modal hidden">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>🏆 中奖名单</h3>
                <button class="modal-close" onclick="closeWinnersModal()">×</button>
            </div>
            <div class="modal-body">
                <div id="winnersContent">
                    <!-- 动态加载 -->
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/common.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        // 查看中奖名单
        function viewWinners(lotteryId) {
            ajax('ajax_admin.php', {
                action: 'get_lottery_winners',
                lottery_id: lotteryId
            }, function(result) {
                if (result.success) {
                    let html = '<div class="table-responsive"><table class="data-table">';
                    html += '<thead><tr><th>排名</th><th>用户名</th><th>微博UID</th></tr></thead>';
                    html += '<tbody>';

                    result.data.forEach(function(winner) {
                        html += '<tr>';
                        html += '<td><strong>' + winner.rank + '</strong></td>';
                        html += '<td>@' + winner.weibo_name + '</td>';
                        html += '<td>' + winner.weibo_uid + '</td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div>';

                    document.getElementById('winnersContent').innerHTML = html;
                    document.getElementById('winnersModal').classList.remove('hidden');
                } else {
                    showError(result.message);
                }
            });
        }

        // 关闭中奖名单模态框
        function closeWinnersModal() {
            document.getElementById('winnersModal').classList.add('hidden');
        }

        // 删除抽奖记录
        function deleteLottery(lotteryId) {
            if (!confirm('确定要删除该抽奖记录吗？此操作不可恢复！')) return;

            ajax('ajax_admin.php', {
                action: 'delete_lottery',
                lottery_id: lotteryId
            }, function(result) {
                if (result.success) {
                    showSuccess(result.message);
                    location.reload();
                } else {
                    showError(result.message);
                }
            });
        }
    </script>
</body>
</html>
