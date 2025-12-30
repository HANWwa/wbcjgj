<?php
/**
 * 抽奖记录AJAX处理
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
    case 'get_winners':
        handleGetWinners($userId);
        break;
    default:
        jsonResponse(false, '无效的操作');
}

/**
 * 获取中奖名单
 */
function handleGetWinners($userId) {
    $lotteryId = (int)post('lottery_id');

    if (!$lotteryId) {
        jsonResponse(false, '无效的抽奖ID');
    }

    $db = DB::getInstance();

    // 验证抽奖记录是否属于当前用户
    $lottery = $db->fetchOne(
        "SELECT * FROM {prefix}lottery_records WHERE id = :id AND user_id = :user_id",
        [':id' => $lotteryId, ':user_id' => $userId]
    );

    if (!$lottery) {
        jsonResponse(false, '抽奖记录不存在');
    }

    // 获取中奖名单
    $winners = $db->fetchAll(
        "SELECT * FROM {prefix}lottery_winners WHERE lottery_id = :lottery_id ORDER BY rank ASC",
        [':lottery_id' => $lotteryId]
    );

    jsonResponse(true, '获取成功', ['winners' => $winners]);
}
