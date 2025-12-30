<?php
/**
 * 免费抽奖页面
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

$user = $auth->getCurrentUser();
$isVip = $auth->isVip();

// 页面标题
$pageTitle = '免费抽奖';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo getSetting('site_name'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/lottery.css">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                🎰 <?php echo getSetting('site_name'); ?>
            </a>
            <div class="nav-menu">
                <a href="index.php">首页</a>
                <a href="lottery.php">VIP抽奖</a>
                <a href="lottery_free.php" class="active">免费抽奖</a>
                <a href="records.php">抽奖记录</a>
                <a href="help.php">帮助中心</a>
                <a href="profile.php" class="user-info">
                    👤 <?php echo safe($user['username']); ?>
                    <?php if ($isVip): ?>
                        <span class="vip-badge">VIP</span>
                    <?php endif; ?>
                </a>
                <a href="logout.php" class="btn-logout">退出</a>
            </div>
        </div>
    </nav>

    <!-- 主要内容 -->
    <div class="lottery-container">
        <!-- VIP提示 -->
        <?php if (!$isVip): ?>
        <div class="info-banner" style="margin-bottom: 30px;">
            <div class="banner-icon">💡</div>
            <div class="banner-content">
                <div class="banner-title">升级VIP获得更多功能</div>
                <div class="banner-text">
                    VIP用户可使用高级抽奖功能，无需手动配置Cookie，支持更大规模抽奖，更快速便捷！
                    <a href="member.php" class="btn btn-sm btn-primary" style="margin-left: 15px;">立即升级</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 抽奖卡片 -->
        <div class="lottery-card">
            <div class="lottery-header">
                <h1 class="lottery-title">🎁 免费抽奖</h1>
                <p class="lottery-subtitle">适合小规模抽奖活动</p>
            </div>

            <!-- 步骤指示器 -->
            <div class="lottery-steps">
                <div class="step-item active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">输入链接</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">配置Cookie</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">设置参数</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">开始抽奖</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="5">
                    <div class="step-number">5</div>
                    <div class="step-label">查看结果</div>
                </div>
            </div>

            <!-- 步骤1: 输入微博链接 -->
            <div id="step1" class="lottery-section">
                <h3 class="section-title">
                    <span class="section-icon">🔗</span>
                    第一步：输入微博链接
                </h3>

                <div class="form-group">
                    <label for="weiboUrl">微博链接</label>
                    <input type="text" id="weiboUrl" class="form-control"
                           placeholder="请输入微博链接，例如：https://weibo.com/...">
                    <small class="form-text">支持PC端和移动端链接</small>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-primary btn-block" onclick="goToStep(2)">
                        下一步 →
                    </button>
                </div>
            </div>

            <!-- 步骤2: 配置Cookie -->
            <div id="step2" class="lottery-section hidden">
                <h3 class="section-title">
                    <span class="section-icon">🍪</span>
                    第二步：配置Cookie
                </h3>

                <!-- Cookie说明 -->
                <div class="warning-box" style="margin-bottom: 25px;">
                    <div class="warning-icon">⚠️</div>
                    <div class="warning-content">
                        <div class="warning-title">安全提示</div>
                        <div class="warning-text">
                            Cookie包含您的登录凭证，请妥善保管，不要泄露给他人。建议使用小号Cookie进行抽奖操作。
                        </div>
                    </div>
                </div>

                <!-- Cookie教程 -->
                <div class="info-card" style="margin-bottom: 25px;">
                    <h4 style="margin-bottom: 15px; color: var(--text-light);">📖 如何获取Cookie？</h4>
                    <ol style="line-height: 2; color: var(--text-gray); margin-left: 20px;">
                        <li>打开浏览器，访问 <a href="https://weibo.com" target="_blank" style="color: var(--primary-color);">https://weibo.com</a> 并登录</li>
                        <li>按F12打开开发者工具，切换到"网络"(Network)标签</li>
                        <li>刷新页面，在请求列表中找到"weibo.com"的请求</li>
                        <li>点击该请求，在右侧"请求头"(Headers)中找到"Cookie"</li>
                        <li>复制完整的Cookie值，粘贴到下方输入框中</li>
                    </ol>
                </div>

                <div class="form-group">
                    <label for="weiboCookie">微博Cookie</label>
                    <textarea id="weiboCookie" class="form-control" rows="5"
                              placeholder="请粘贴从浏览器获取的完整Cookie值"></textarea>
                    <small class="form-text">Cookie将仅用于本次抽奖，不会保存到服务器</small>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                        ← 上一步
                    </button>
                    <button type="button" class="btn btn-primary" onclick="validateCookie()">
                        验证并继续 →
                    </button>
                </div>
            </div>

            <!-- 步骤3: 设置抽奖参数 -->
            <div id="step3" class="lottery-section hidden">
                <h3 class="section-title">
                    <span class="section-icon">⚙️</span>
                    第三步：设置抽奖参数
                </h3>

                <!-- 抽奖类型 -->
                <div style="margin-bottom: 30px;">
                    <label class="form-label">抽奖类型</label>
                    <div class="lottery-type-grid">
                        <div class="type-card" data-type="like">
                            <div class="type-icon">❤️</div>
                            <div class="type-name">点赞抽奖</div>
                            <div class="type-desc">从点赞用户中抽取</div>
                        </div>
                        <div class="type-card" data-type="comment">
                            <div class="type-icon">💬</div>
                            <div class="type-name">评论抽奖</div>
                            <div class="type-desc">从评论用户中抽取</div>
                        </div>
                        <div class="type-card" data-type="repost">
                            <div class="type-icon">🔁</div>
                            <div class="type-name">转发抽奖</div>
                            <div class="type-desc">从转发用户中抽取</div>
                        </div>
                        <div class="type-card" data-type="mixed">
                            <div class="type-icon">🎯</div>
                            <div class="type-name">混合抽奖</div>
                            <div class="type-desc">同时点赞评论转发</div>
                        </div>
                    </div>
                </div>

                <!-- 中奖人数 -->
                <div class="form-group">
                    <label for="winnerCount">中奖人数</label>
                    <input type="number" id="winnerCount" class="form-control"
                           value="1" min="1" max="50">
                    <small class="form-text">免费版最多支持50人</small>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                        ← 上一步
                    </button>
                    <button type="button" class="btn btn-primary" onclick="goToStep(4)">
                        下一步 →
                    </button>
                </div>
            </div>

            <!-- 步骤4: 开始抽奖 -->
            <div id="step4" class="lottery-section hidden">
                <h3 class="section-title">
                    <span class="section-icon">🎲</span>
                    第四步：确认并开始抽奖
                </h3>

                <!-- 抽奖信息确认 -->
                <div class="result-info">
                    <div class="result-item">
                        <span class="result-label">微博链接</span>
                        <span class="result-value" id="confirmWeiboUrl">-</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">抽奖类型</span>
                        <span class="result-value" id="confirmLotteryType">-</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">中奖人数</span>
                        <span class="result-value" id="confirmWinnerCount">-</span>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(3)">
                        ← 上一步
                    </button>
                    <button type="button" class="btn btn-success btn-lg" onclick="startFreeLottery()">
                        🎰 开始抽奖
                    </button>
                </div>
            </div>

            <!-- 步骤5: 抽奖中 -->
            <div id="step5" class="lottery-section hidden">
                <div class="lottery-animation">
                    <div class="lottery-wheel">
                        <div class="wheel-inner">
                            <div class="wheel-icon">🎰</div>
                        </div>
                    </div>
                    <p class="lottery-text">正在抽取幸运用户...</p>
                    <div class="lottery-progress">
                        <div class="progress-bar"></div>
                    </div>
                </div>
            </div>

            <!-- 步骤6: 抽奖结果 -->
            <div id="step6" class="lottery-section hidden">
                <h3 class="section-title">
                    <span class="section-icon">🎉</span>
                    抽奖完成
                </h3>

                <!-- 抽奖信息 -->
                <div class="result-info">
                    <div class="result-item">
                        <span class="result-label">验证码</span>
                        <span class="result-value verify-code" id="verifyCode">-</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">参与人数</span>
                        <span class="result-value" id="totalParticipants">-</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">中奖人数</span>
                        <span class="result-value" id="totalWinners">-</span>
                    </div>
                </div>

                <!-- 中奖名单 -->
                <h4 class="section-title" style="margin-top: 30px;">
                    <span class="section-icon">🏆</span>
                    中奖名单
                </h4>
                <div id="winnersList" class="winners-list">
                    <!-- 动态生成 -->
                </div>

                <!-- 公告文本 -->
                <div class="announcement-box">
                    <div class="announcement-title">📢 中奖公告（可复制发布）</div>
                    <textarea id="announcement" class="announcement-text" readonly></textarea>
                    <button type="button" class="btn btn-primary" onclick="copyAnnouncement()">
                        📋 复制公告
                    </button>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='records.php'">
                        查看历史记录
                    </button>
                    <button type="button" class="btn btn-primary" onclick="resetLottery()">
                        再次抽奖
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 底部 -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 <?php echo getSetting('site_name'); ?>. All rights reserved. <?php echo COPYRIGHT; ?></p>
        </div>
    </footer>

    <script src="assets/js/common.js"></script>
    <script src="assets/js/lottery_free.js"></script>
</body>
</html>
