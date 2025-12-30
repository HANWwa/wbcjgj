<?php
/**
 * 用户管理页面
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
$status = get('status', '');
$vipFilter = get('vip', '');

// 构建查询条件
$where = "role = 'user'";
$params = [];

if ($search) {
    $where .= " AND (username LIKE :search OR email LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($status) {
    $where .= " AND status = :status";
    $params[':status'] = $status;
}

if ($vipFilter === 'yes') {
    $where .= " AND vip_expire > NOW()";
} elseif ($vipFilter === 'no') {
    $where .= " AND (vip_expire IS NULL OR vip_expire <= NOW())";
}

// 获取总数
$total = $db->fetchColumn(
    "SELECT COUNT(*) FROM {prefix}users WHERE {$where}",
    $params
);

// 获取用户列表
$users = $db->fetchAll(
    "SELECT * FROM {prefix}users WHERE {$where} ORDER BY id DESC LIMIT {$offset}, {$perPage}",
    $params
);

// 分页
$totalPages = ceil($total / $perPage);

$pageTitle = '用户管理';
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
            <a href="users.php" class="nav-item active">
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
            <h1 class="page-title">👥 <?php echo $pageTitle; ?></h1>
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
                                       placeholder="搜索用户名或邮箱" value="<?php echo safe($search); ?>">
                            </div>

                            <div class="form-group">
                                <select name="status" class="form-control">
                                    <option value="">全部状态</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>正常</option>
                                    <option value="banned" <?php echo $status === 'banned' ? 'selected' : ''; ?>>已禁用</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <select name="vip" class="form-control">
                                    <option value="">全部用户</option>
                                    <option value="yes" <?php echo $vipFilter === 'yes' ? 'selected' : ''; ?>>VIP用户</option>
                                    <option value="no" <?php echo $vipFilter === 'no' ? 'selected' : ''; ?>>普通用户</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">🔍 搜索</button>
                                <a href="users.php" class="btn btn-secondary">清空</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 用户列表 -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">用户列表（共 <?php echo number_format($total); ?> 人）</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>用户名</th>
                                    <th>邮箱</th>
                                    <th>VIP状态</th>
                                    <th>账号状态</th>
                                    <th>注册时间</th>
                                    <th>最后登录</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo safe($user['username']); ?></td>
                                    <td><?php echo safe($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['vip_expire'] && strtotime($user['vip_expire']) > time()): ?>
                                            <span class="badge badge-vip">VIP</span>
                                            <small style="display: block; color: var(--text-gray); margin-top: 5px;">
                                                至 <?php echo date('Y-m-d', strtotime($user['vip_expire'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge badge-normal">普通</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="badge badge-success">正常</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">已禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <?php echo date('Y-m-d H:i', strtotime($user['last_login'])); ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-gray);">从未登录</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action" onclick="editUser(<?php echo $user['id']; ?>)" title="编辑">
                                                ✏️
                                            </button>
                                            <?php if ($user['status'] === 'active'): ?>
                                            <button class="btn-action" onclick="banUser(<?php echo $user['id']; ?>)" title="禁用">
                                                🚫
                                            </button>
                                            <?php else: ?>
                                            <button class="btn-action" onclick="unbanUser(<?php echo $user['id']; ?>)" title="启用">
                                                ✅
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn-action" onclick="deleteUser(<?php echo $user['id']; ?>)" title="删除">
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

    <!-- 编辑用户模态框 -->
    <div id="editUserModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3>编辑用户</h3>
                <button class="modal-close" onclick="closeEditModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="user_id">

                    <div class="form-group">
                        <label>用户名</label>
                        <input type="text" id="editUsername" name="username" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label>邮箱</label>
                        <input type="email" id="editEmail" name="email" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>VIP到期时间</label>
                        <input type="date" id="editVipExpire" name="vip_expire" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>重置密码（留空则不修改）</label>
                        <input type="password" id="editPassword" name="password" class="form-control"
                               placeholder="输入新密码">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">取消</button>
                        <button type="button" class="btn btn-primary" onclick="saveUser()">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/common.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        // 编辑用户
        function editUser(userId) {
            ajax('ajax_admin.php', {
                action: 'get_user',
                user_id: userId
            }, function(result) {
                if (result.success) {
                    document.getElementById('editUserId').value = result.data.id;
                    document.getElementById('editUsername').value = result.data.username;
                    document.getElementById('editEmail').value = result.data.email;
                    document.getElementById('editVipExpire').value = result.data.vip_expire ? result.data.vip_expire.split(' ')[0] : '';
                    document.getElementById('editPassword').value = '';
                    document.getElementById('editUserModal').classList.remove('hidden');
                } else {
                    showError(result.message);
                }
            });
        }

        // 保存用户
        function saveUser() {
            const formData = {
                action: 'update_user',
                user_id: document.getElementById('editUserId').value,
                email: document.getElementById('editEmail').value,
                vip_expire: document.getElementById('editVipExpire').value,
                password: document.getElementById('editPassword').value
            };

            ajax('ajax_admin.php', formData, function(result) {
                if (result.success) {
                    showSuccess(result.message);
                    closeEditModal();
                    location.reload();
                } else {
                    showError(result.message);
                }
            });
        }

        // 关闭编辑模态框
        function closeEditModal() {
            document.getElementById('editUserModal').classList.add('hidden');
        }

        // 禁用用户
        function banUser(userId) {
            if (!confirm('确定要禁用该用户吗？')) return;

            ajax('ajax_admin.php', {
                action: 'ban_user',
                user_id: userId
            }, function(result) {
                if (result.success) {
                    showSuccess(result.message);
                    location.reload();
                } else {
                    showError(result.message);
                }
            });
        }

        // 启用用户
        function unbanUser(userId) {
            if (!confirm('确定要启用该用户吗？')) return;

            ajax('ajax_admin.php', {
                action: 'unban_user',
                user_id: userId
            }, function(result) {
                if (result.success) {
                    showSuccess(result.message);
                    location.reload();
                } else {
                    showError(result.message);
                }
            });
        }

        // 删除用户
        function deleteUser(userId) {
            if (!confirm('确定要删除该用户吗？此操作不可恢复！')) return;

            ajax('ajax_admin.php', {
                action: 'delete_user',
                user_id: userId
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
