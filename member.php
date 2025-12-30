<?php
/**
 * 会员中心页面
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

// 检查是否开启会员功能
$enableVip = getSetting('enable_vip', '0');
if ($enableVip != '1') {
    die('会员功能未开启');
}

// 获取当前用户信息
$currentUser = $auth->getCurrentUser();
$isAdmin = $auth->isAdmin();
$isVip = $auth->isVip();

// 获取会员设置
$vipPrice = getSetting('vip_price', '99');
$vipDuration = getSetting('vip_duration', '365');

// 获取网站设置
$siteName = getSetting('site_name', SYSTEM_NAME);

// 获取支付配置
$db = DB::getInstance();
$paymentQrcode = $db->fetchOne("SELECT * FROM {prefix}payment_settings WHERE payment_type = 'qrcode'");
$paymentAlipay = $db->fetchOne("SELECT * FROM {prefix}payment_settings WHERE payment_type = 'alipay'");
$paymentWechat = $db->fetchOne("SELECT * FROM {prefix}payment_settings WHERE payment_type = 'wechat'");

// 获取交易记录
$transactions = $db->fetchAll(
    "SELECT * FROM {prefix}transactions WHERE user_id = :user_id ORDER BY id DESC LIMIT 10",
    [':user_id' => $currentUser['id']]
);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会员中心 - <?php echo safe($siteName); ?></title>
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

        .vip-banner {
            background: var(--gradient-2);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .vip-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
        }

        .vip-banner-content {
            position: relative;
            z-index: 1;
        }

        .vip-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .vip-title {
            font-size: 32px;
            font-weight: bold;
            color: var(--text-light);
            margin-bottom: 15px;
        }

        .vip-desc {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 25px;
        }

        .vip-status {
            display: inline-block;
            padding: 10px 30px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            font-size: 16px;
            color: var(--text-light);
            backdrop-filter: blur(10px);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .feature-item {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .feature-icon {
            font-size: 36px;
            flex-shrink: 0;
        }

        .feature-content {
            flex: 1;
        }

        .feature-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-light);
        }

        .feature-desc {
            font-size: 13px;
            color: var(--text-gray);
        }

        .section-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .section-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-2);
        }

        .section-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .price-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            border: 2px solid var(--primary-color);
            margin-bottom: 25px;
        }

        .price-amount {
            font-size: 48px;
            font-weight: bold;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .price-duration {
            color: var(--text-gray);
            margin-bottom: 20px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .payment-method {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method:hover {
            border-color: var(--primary-color);
            background: rgba(255, 107, 107, 0.1);
        }

        .payment-method.active {
            border-color: var(--primary-color);
            background: rgba(255, 107, 107, 0.1);
        }

        .payment-method.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .payment-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .payment-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-light);
        }

        .qrcode-container {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            margin-top: 20px;
        }

        .qrcode-img {
            max-width: 300px;
            width: 100%;
            height: auto;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .qrcode-tips {
            color: var(--text-gray);
            font-size: 14px;
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
            .features-grid {
                grid-template-columns: 1fr;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }

            .vip-title {
                font-size: 24px;
            }

            .price-amount {
                font-size: 36px;
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
                <li><a href="<?php echo SITE_URL; ?>/member.php" class="active">会员中心</a></li>
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
                    <li><a href="<?php echo SITE_URL; ?>/records.php">
                        <span>📝</span>
                        <span>抽奖记录</span>
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/member.php" class="active">
                        <span>💎</span>
                        <span>会员中心</span>
                    </a></li>
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
            <!-- VIP横幅 -->
            <div class="vip-banner">
                <div class="vip-banner-content">
                    <div class="vip-icon">💎</div>
                    <h1 class="vip-title">VIP会员专享</h1>
                    <p class="vip-desc">解锁更多高级功能，享受更优质的服务</p>
                    <?php if ($isVip): ?>
                        <div class="vip-status">
                            ✅ 您已是VIP会员
                            <?php if ($currentUser['vip_expire']): ?>
                                · 到期时间: <?php echo date('Y-m-d', strtotime($currentUser['vip_expire'])); ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="vip-status">⭐ 开通VIP解锁全部功能</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- VIP权益 -->
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-content">
                        <div class="feature-title">API直连服务</div>
                        <div class="feature-desc">无需手动配置，自动调用接口</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🚀</div>
                    <div class="feature-content">
                        <div class="feature-title">快速抽奖</div>
                        <div class="feature-desc">一键解析，极速完成抽奖</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <div class="feature-content">
                        <div class="feature-title">详细统计</div>
                        <div class="feature-desc">完整的数据分析和报表</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🎯</div>
                    <div class="feature-content">
                        <div class="feature-title">高级筛选</div>
                        <div class="feature-desc">更多筛选条件和组合</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🔒</div>
                    <div class="feature-content">
                        <div class="feature-title">数据安全</div>
                        <div class="feature-desc">加密存储，隐私保护</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💬</div>
                    <div class="feature-content">
                        <div class="feature-title">优先支持</div>
                        <div class="feature-desc">专属客服，优先响应</div>
                    </div>
                </div>
            </div>

            <!-- 提示消息 -->
            <div id="errorMessage" class="alert alert-error hidden"></div>
            <div id="successMessage" class="alert alert-success hidden"></div>

            <?php if (!$isVip): ?>
            <!-- 购买会员 -->
            <div class="section-card">
                <h2 class="section-title">
                    <span>💳</span>
                    <span>开通会员</span>
                </h2>

                <!-- 价格卡片 -->
                <div class="price-card">
                    <div class="price-amount">¥<?php echo safe($vipPrice); ?></div>
                    <div class="price-duration"><?php echo safe($vipDuration); ?>天会员</div>
                    <p style="color: var(--text-gray);">开通后即可享受所有VIP权益</p>
                </div>

                <!-- 支付方式 -->
                <h3 style="margin-bottom: 15px;">选择支付方式</h3>
                <div class="payment-methods">
                    <?php if ($paymentQrcode && $paymentQrcode['is_enabled']): ?>
                    <div class="payment-method" data-method="qrcode">
                        <div class="payment-icon">📱</div>
                        <div class="payment-name">扫码支付</div>
                    </div>
                    <?php endif; ?>

                    <?php if ($paymentAlipay && $paymentAlipay['is_enabled']): ?>
                    <div class="payment-method" data-method="alipay">
                        <div class="payment-icon">💳</div>
                        <div class="payment-name">支付宝</div>
                    </div>
                    <?php endif; ?>

                    <?php if ($paymentWechat && $paymentWechat['is_enabled']): ?>
                    <div class="payment-method" data-method="wechat">
                        <div class="payment-icon">💚</div>
                        <div class="payment-name">微信支付</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 二维码支付容器 -->
                <div id="qrcodeContainer" class="qrcode-container hidden">
                    <h4 style="margin-bottom: 15px;">请扫码支付</h4>
                    <img id="qrcodeImg" class="qrcode-img" alt="支付二维码">
                    <div class="qrcode-tips">
                        <p>请使用微信或支付宝扫描上方二维码完成支付</p>
                        <p>支付金额：<strong style="color: var(--primary-color);">¥<?php echo safe($vipPrice); ?></strong></p>
                        <p style="color: var(--warning); margin-top: 10px;">⚠️ 支付成功后请联系客服开通会员</p>
                    </div>
                </div>

                <button id="buyVipBtn" class="btn btn-primary btn-block mt-30 hidden">确认购买</button>
            </div>
            <?php endif; ?>

            <!-- 交易记录 -->
            <?php if (!empty($transactions)): ?>
            <div class="section-card">
                <h2 class="section-title">
                    <span>📋</span>
                    <span>交易记录</span>
                </h2>

                <div class="table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>订单号</th>
                                <th>商品</th>
                                <th>金额</th>
                                <th>状态</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td><?php echo safe($trans['order_no']); ?></td>
                                <td><?php echo safe($trans['product_name']); ?></td>
                                <td>¥<?php echo safe($trans['amount']); ?></td>
                                <td>
                                    <?php
                                    $statusMap = [
                                        'pending' => '<span class="badge badge-warning">待支付</span>',
                                        'paid' => '<span class="badge badge-success">已支付</span>',
                                        'cancelled' => '<span class="badge badge-error">已取消</span>',
                                        'refunded' => '<span class="badge">已退款</span>'
                                    ];
                                    echo $statusMap[$trans['status']] ?? $trans['status'];
                                    ?>
                                </td>
                                <td><?php echo formatTime($trans['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
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
        let selectedPayment = null;

        // 支付方式选择
        document.querySelectorAll('.payment-method').forEach(function(method) {
            if (!method.classList.contains('disabled')) {
                method.addEventListener('click', function() {
                    // 移除其他选中状态
                    document.querySelectorAll('.payment-method').forEach(function(m) {
                        m.classList.remove('active');
                    });

                    // 添加选中状态
                    this.classList.add('active');
                    selectedPayment = this.dataset.method;

                    // 显示购买按钮
                    document.getElementById('buyVipBtn').classList.remove('hidden');

                    // 如果是二维码支付，显示二维码
                    if (selectedPayment === 'qrcode') {
                        showQrcode();
                    } else {
                        document.getElementById('qrcodeContainer').classList.add('hidden');
                    }
                });
            }
        });

        // 显示二维码
        function showQrcode() {
            // 这里需要从后台获取二维码
            ajax('ajax_member.php', {
                action: 'get_qrcode'
            }, function(result) {
                if (result.success && result.data.qrcode_url) {
                    document.getElementById('qrcodeImg').src = result.data.qrcode_url;
                    document.getElementById('qrcodeContainer').classList.remove('hidden');
                }
            });
        }

        // 购买VIP
        document.getElementById('buyVipBtn')?.addEventListener('click', function() {
            if (!selectedPayment) {
                showError('请选择支付方式');
                return;
            }

            ajax('ajax_member.php', {
                action: 'create_order',
                payment_method: selectedPayment
            }, function(result) {
                if (result.success) {
                    if (selectedPayment === 'qrcode') {
                        showSuccess('订单创建成功，请扫码支付');
                    } else if (selectedPayment === 'alipay' || selectedPayment === 'wechat') {
                        // 跳转到支付页面
                        if (result.data.pay_url) {
                            window.location.href = result.data.pay_url;
                        } else {
                            showError('支付链接获取失败');
                        }
                    }
                } else {
                    showError(result.message);
                }
            });
        });
    </script>
</body>
</html>
