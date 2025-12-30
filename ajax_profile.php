<?php
/**
 * 个人资料AJAX处理
 * @神奇奶酪
 */

require_once __DIR__ . '/includes/config.php';

// 检查是否已安装
if (!isInstalled()) {
    jsonResponse(false, '系统未安装');
}

// 检查登录状态
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(false, '请先登录');
}

// 检查请求方法
if (!isPost()) {
    jsonResponse(false, '无效的请求方法');
}

$action = post('action');
$userId = $auth->getCurrentUserId();

// 根据不同的操作执行相应的功能
switch ($action) {
    case 'update_email':
        handleUpdateEmail($userId);
        break;
    case 'change_password':
        handleChangePassword($userId);
        break;
    default:
        jsonResponse(false, '无效的操作');
}

/**
 * 更新邮箱
 */
function handleUpdateEmail($userId) {
    $newEmail = post('new_email');
    $password = post('password');

    if (empty($newEmail) || empty($password)) {
        jsonResponse(false, '请填写完整信息');
    }

    if (!isValidEmail($newEmail)) {
        jsonResponse(false, '邮箱格式不正确');
    }

    $db = DB::getInstance();

    // 验证密码
    $user = $db->fetchOne(
        "SELECT password FROM {prefix}users WHERE id = :id",
        [':id' => $userId]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(false, '密码错误');
    }

    // 检查邮箱是否已被使用
    $exists = $db->fetchOne(
        "SELECT id FROM {prefix}users WHERE email = :email AND id != :id",
        [':email' => $newEmail, ':id' => $userId]
    );

    if ($exists) {
        jsonResponse(false, '该邮箱已被使用');
    }

    // 更新邮箱
    $updated = $db->update(
        'users',
        ['email' => $newEmail],
        'id = :id',
        [':id' => $userId]
    );

    if ($updated !== false) {
        logAction($userId, 'update_email', '更新邮箱');
        jsonResponse(true, '邮箱更新成功');
    } else {
        jsonResponse(false, '邮箱更新失败');
    }
}

/**
 * 修改密码
 */
function handleChangePassword($userId) {
    $oldPassword = post('old_password');
    $newPassword = post('new_password');

    if (empty($oldPassword) || empty($newPassword)) {
        jsonResponse(false, '请填写完整信息');
    }

    if (strlen($newPassword) < 6) {
        jsonResponse(false, '新密码长度至少6个字符');
    }

    $auth = new Auth();
    $result = $auth->changePassword($userId, $oldPassword, $newPassword);

    if ($result['success']) {
        jsonResponse(true, $result['message']);
    } else {
        jsonResponse(false, $result['message']);
    }
}
