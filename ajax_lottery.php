<?php
/**
 * 抽奖AJAX处理
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
    case 'parse_weibo':
        handleParseWeibo();
        break;
    case 'start_lottery':
        handleStartLottery($userId, $auth);
        break;
    case 'verify_lottery':
        handleVerifyLottery();
        break;
    default:
        jsonResponse(false, '无效的操作');
}

/**
 * 解析微博链接
 */
function handleParseWeibo() {
    $weiboUrl = post('weibo_url');

    if (empty($weiboUrl)) {
        jsonResponse(false, '请输入微博链接');
    }

    // 创建微博API实例
    $weiboApi = new WeiboAPI();

    // 解析链接
    $weiboId = $weiboApi->parseWeiboUrl($weiboUrl);
    if (!$weiboId) {
        jsonResponse(false, '无法解析微博链接，请检查链接格式');
    }

    // 获取微博信息
    $weiboInfo = $weiboApi->getWeiboInfo($weiboId);
    if (!$weiboInfo['success']) {
        jsonResponse(false, '获取微博信息失败，请检查API配置');
    }

    // 返回微博信息
    jsonResponse(true, '解析成功', [
        'weibo_id' => $weiboId,
        'like_count' => $weiboInfo['data']['attitudes_count'] ?? 0,
        'comment_count' => $weiboInfo['data']['comments_count'] ?? 0,
        'repost_count' => $weiboInfo['data']['reposts_count'] ?? 0,
        'text' => $weiboInfo['data']['text'] ?? ''
    ]);
}

/**
 * 开始抽奖
 */
function handleStartLottery($userId, $auth) {
    $weiboUrl = post('weibo_url');
    $lotteryType = post('lottery_type');
    $winnerCount = (int)post('winner_count');

    // 验证参数
    if (empty($weiboUrl)) {
        jsonResponse(false, '请输入微博链接');
    }

    if (!in_array($lotteryType, ['like', 'comment', 'repost', 'mixed'])) {
        jsonResponse(false, '无效的抽奖类型');
    }

    if ($winnerCount < 1 || $winnerCount > 100) {
        jsonResponse(false, '中奖人数必须在1-100之间');
    }

    // 检查VIP状态
    $isVip = $auth->isVip();
    $mode = $isVip ? 'vip' : 'free';

    // 创建抽奖引擎实例
    $lottery = new LotteryEngine();

    // 执行抽奖
    $result = $lottery->executeLottery($userId, $weiboUrl, $lotteryType, $winnerCount, $mode);

    if ($result['success']) {
        jsonResponse(true, '抽奖成功', $result['data']);
    } else {
        jsonResponse(false, $result['message']);
    }
}

/**
 * 验证抽奖
 */
function handleVerifyLottery() {
    $verifyCode = post('verify_code');

    if (empty($verifyCode)) {
        jsonResponse(false, '请输入验证码');
    }

    // 创建抽奖引擎实例
    $lottery = new LotteryEngine();

    // 验证
    $result = $lottery->verifyLottery($verifyCode);

    if ($result['success']) {
        jsonResponse(true, '验证成功', $result['data']);
    } else {
        jsonResponse(false, $result['message']);
    }
}
