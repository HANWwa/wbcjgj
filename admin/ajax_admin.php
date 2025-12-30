<?php
/**
 * 管理后台AJAX处理
 * @神奇奶酪
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin_auth.php';

// 检查是否已安装
if (!isInstalled()) {
    jsonResponse(false, '系统未安装');
}

// 检查登录状态
$adminAuth = new AdminAuth();
if (!$adminAuth->isLoggedIn()) {
    jsonResponse(false, '请先登录');
}

// 检查请求方法
if (!isPost()) {
    jsonResponse(false, '无效的请求方法');
}

$action = post('action');
$adminId = $adminAuth->getCurrentAdminId();
$db = DB::getInstance();

// 根据不同的操作执行相应的功能
switch ($action) {
    case 'get_user':
        handleGetUser();
        break;
    case 'update_user':
        handleUpdateUser($adminId);
        break;
    case 'ban_user':
        handleBanUser($adminId);
        break;
    case 'unban_user':
        handleUnbanUser($adminId);
        break;
    case 'delete_user':
        handleDeleteUser($adminId);
        break;
    case 'get_lottery_winners':
        handleGetLotteryWinners();
        break;
    case 'delete_lottery':
        handleDeleteLottery($adminId);
        break;
    default:
        jsonResponse(false, '无效的操作');
}

/**
 * 获取用户信息
 */
function handleGetUser() {
    global $db;

    $userId = (int)post('user_id');

    if (!$userId) {
        jsonResponse(false, '用户ID不能为空');
    }

    $user = $db->fetchOne(
        "SELECT * FROM {prefix}users WHERE id = :id LIMIT 1",
        [':id' => $userId]
    );

    if (!$user) {
        jsonResponse(false, '用户不存在');
    }

    jsonResponse(true, '获取成功', $user);
}

/**
 * 更新用户信息
 */
function handleUpdateUser($adminId) {
    global $db;

    $userId = (int)post('user_id');
    $email = post('email');
    $vipExpire = post('vip_expire');
    $password = post('password');

    if (!$userId) {
        jsonResponse(false, '用户ID不能为空');
    }

    // 验证邮箱
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, '邮箱格式不正确');
    }

    // 构建更新数据
    $updateData = [];

    if (!empty($email)) {
        // 检查邮箱是否已被使用
        $existingUser = $db->fetchOne(
            "SELECT id FROM {prefix}users WHERE email = :email AND id != :id LIMIT 1",
            [':email' => $email, ':id' => $userId]
        );

        if ($existingUser) {
            jsonResponse(false, '该邮箱已被其他用户使用');
        }

        $updateData['email'] = $email;
    }

    if (!empty($vipExpire)) {
        $updateData['vip_expire'] = $vipExpire . ' 23:59:59';
    }

    if (!empty($password)) {
        if (strlen($password) < 6) {
            jsonResponse(false, '密码长度不能少于6位');
        }
        $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if (empty($updateData)) {
        jsonResponse(false, '没有需要更新的内容');
    }

    // 更新用户
    $result = $db->update('users', $updateData, 'id = :id', [':id' => $userId]);

    if ($result) {
        logAction($adminId, 'admin_update_user', "管理员更新用户信息，用户ID: {$userId}");
        jsonResponse(true, '更新成功');
    } else {
        jsonResponse(false, '更新失败');
    }
}

/**
 * 禁用用户
 */
function handleBanUser($adminId) {
    global $db;

    $userId = (int)post('user_id');

    if (!$userId) {
        jsonResponse(false, '用户ID不能为空');
    }

    $result = $db->update(
        'users',
        ['status' => 'banned'],
        'id = :id',
        [':id' => $userId]
    );

    if ($result) {
        logAction($adminId, 'admin_ban_user', "管理员禁用用户，用户ID: {$userId}");
        jsonResponse(true, '用户已禁用');
    } else {
        jsonResponse(false, '操作失败');
    }
}

/**
 * 启用用户
 */
function handleUnbanUser($adminId) {
    global $db;

    $userId = (int)post('user_id');

    if (!$userId) {
        jsonResponse(false, '用户ID不能为空');
    }

    $result = $db->update(
        'users',
        ['status' => 'active'],
        'id = :id',
        [':id' => $userId]
    );

    if ($result) {
        logAction($adminId, 'admin_unban_user', "管理员启用用户，用户ID: {$userId}");
        jsonResponse(true, '用户已启用');
    } else {
        jsonResponse(false, '操作失败');
    }
}

/**
 * 删除用户
 */
function handleDeleteUser($adminId) {
    global $db;

    $userId = (int)post('user_id');

    if (!$userId) {
        jsonResponse(false, '用户ID不能为空');
    }

    // 检查用户是否存在
    $user = $db->fetchOne(
        "SELECT * FROM {prefix}users WHERE id = :id LIMIT 1",
        [':id' => $userId]
    );

    if (!$user) {
        jsonResponse(false, '用户不存在');
    }

    // 防止删除管理员
    if ($user['role'] === 'admin') {
        jsonResponse(false, '不能删除管理员账号');
    }

    // 删除用户的抽奖记录中奖名单
    $db->query(
        "DELETE lw FROM {prefix}lottery_winners lw
         INNER JOIN {prefix}lottery_records lr ON lw.lottery_id = lr.id
         WHERE lr.user_id = :user_id",
        [':user_id' => $userId]
    );

    // 删除用户的抽奖记录
    $db->delete('lottery_records', 'user_id = :user_id', [':user_id' => $userId]);

    // 删除用户的交易记录
    $db->delete('transactions', 'user_id = :user_id', [':user_id' => $userId]);

    // 删除用户
    $result = $db->delete('users', 'id = :id', [':id' => $userId]);

    if ($result) {
        logAction($adminId, 'admin_delete_user', "管理员删除用户，用户ID: {$userId}，用户名: {$user['username']}");
        jsonResponse(true, '用户已删除');
    } else {
        jsonResponse(false, '删除失败');
    }
}

/**
 * 获取抽奖中奖名单
 */
function handleGetLotteryWinners() {
    global $db;

    $lotteryId = (int)post('lottery_id');

    if (!$lotteryId) {
        jsonResponse(false, '抽奖ID不能为空');
    }

    $winners = $db->fetchAll(
        "SELECT * FROM {prefix}lottery_winners WHERE lottery_id = :lottery_id ORDER BY rank ASC",
        [':lottery_id' => $lotteryId]
    );

    jsonResponse(true, '获取成功', $winners);
}

/**
 * 删除抽奖记录
 */
function handleDeleteLottery($adminId) {
    global $db;

    $lotteryId = (int)post('lottery_id');

    if (!$lotteryId) {
        jsonResponse(false, '抽奖ID不能为空');
    }

    // 删除中奖名单
    $db->delete('lottery_winners', 'lottery_id = :lottery_id', [':lottery_id' => $lotteryId]);

    // 删除抽奖记录
    $result = $db->delete('lottery_records', 'id = :id', [':id' => $lotteryId]);

    if ($result) {
        logAction($adminId, 'admin_delete_lottery', "管理员删除抽奖记录，ID: {$lotteryId}");
        jsonResponse(true, '删除成功');
    } else {
        jsonResponse(false, '删除失败');
    }
}
