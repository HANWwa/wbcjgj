<?php
/**
 * 会员相关AJAX处理
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
    case 'get_qrcode':
        handleGetQrcode();
        break;
    case 'create_order':
        handleCreateOrder($userId);
        break;
    default:
        jsonResponse(false, '无效的操作');
}

/**
 * 获取收款二维码
 */
function handleGetQrcode() {
    $db = DB::getInstance();

    $qrcodeSettings = $db->fetchOne(
        "SELECT * FROM {prefix}payment_settings WHERE payment_type = 'qrcode' AND is_enabled = 1"
    );

    if (!$qrcodeSettings || empty($qrcodeSettings['qrcode_image'])) {
        jsonResponse(false, '二维码未配置');
    }

    jsonResponse(true, '获取成功', [
        'qrcode_url' => SITE_URL . '/' . $qrcodeSettings['qrcode_image']
    ]);
}

/**
 * 创建订单
 */
function handleCreateOrder($userId) {
    $paymentMethod = post('payment_method');

    if (empty($paymentMethod)) {
        jsonResponse(false, '请选择支付方式');
    }

    $db = DB::getInstance();

    // 获取VIP价格和时长
    $vipPrice = getSetting('vip_price', '99');
    $vipDuration = getSetting('vip_duration', '365');

    // 生成订单号
    $orderNo = generateOrderNo();

    // 创建订单
    $orderId = $db->insert('transactions', [
        'user_id' => $userId,
        'order_no' => $orderNo,
        'payment_type' => $paymentMethod,
        'amount' => $vipPrice,
        'product_type' => 'vip',
        'product_name' => "VIP会员 {$vipDuration}天",
        'status' => 'pending'
    ]);

    if (!$orderId) {
        jsonResponse(false, '订单创建失败');
    }

    // 记录日志
    logAction($userId, 'create_order', "创建订单: {$orderNo}");

    // 根据支付方式返回不同的数据
    $responseData = [
        'order_no' => $orderNo,
        'order_id' => $orderId
    ];

    // 如果是在线支付，这里应该调用支付接口获取支付链接
    if ($paymentMethod === 'alipay' || $paymentMethod === 'wechat') {
        // TODO: 调用支付接口
        // $responseData['pay_url'] = '支付链接';
        jsonResponse(false, '在线支付功能暂未开放，请使用扫码支付');
    }

    jsonResponse(true, '订单创建成功', $responseData);
}
