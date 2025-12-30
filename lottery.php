<?php
/**
 * 会员版抽奖页面
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
$isVip = $auth->isVip();

// 如果不是VIP，跳转到免费版或会员中心
if (!$isVip) {
    $enableVip = getSetting('enable_vip', '0');
    if ($enableVip == '1') {
        redirect('member.php');
    } else {
        redirect('lottery_free.php');
    }
    exit;
}

// 获取网站设置
$siteName = getSetting('site_name', SYSTEM_NAME);
$isAdmin = $auth->isAdmin();
$enableVip = getSetting('enable_vip', '0');

// 检查API配置
$db = DB::getInstance();
$apiConfig = $db->fetchOne("SELECT * FROM {prefix}api_keys WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$apiConfigured = !empty($apiConfig) && !empty($apiConfig['app_key']) && !empty($apiConfig['app_secret']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP抽奖 - <?php echo safe($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/lottery.css">
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
                <li><a href="<?php echo SITE_URL; ?>/lottery.php" class="active">开始抽奖</a></li>
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
    <div class="lottery-container">
        <!-- VIP标识 -->
        <div class="vip-badge-float">
            <span class="badge badge-vip">💎 VIP专享</span>
        </div>

        <!-- 抽奖卡片 -->
        <div class="lottery-card">
            <div class="lottery-header">
                <h1 class="lottery-title">🎁 微博抽奖</h1>
                <p class="lottery-subtitle">VIP专享 · 快速便捷 · 公平公正</p>
            </div>

            <!-- 提示消息 -->
            <div id="errorMessage" class="alert alert-error hidden"></div>
            <div id="successMessage" class="alert alert-success hidden"></div>

            <?php if (!$apiConfigured): ?>
            <!-- API未配置提示 -->
            <div class="alert alert-warning">
                <strong>⚠️ 提示：</strong>管理员尚未配置微博API密钥，VIP抽奖功能暂时无法使用。
                <?php if ($isAdmin): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/api_settings.php" style="color: var(--primary-color); text-decoration: underline;">立即配置</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 抽奖步骤 -->
            <div class="lottery-steps">
                <div class="step-item active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">输入链接</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">设置条件</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">开始抽奖</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">查看结果</div>
                </div>
            </div>

            <!-- 步骤1：输入微博链接 -->
            <div class="lottery-section" id="step1">
                <div class="section-title">
                    <span class="section-icon">🔗</span>
                    <span>输入微博链接</span>
                </div>

                <div class="form-group">
                    <label class="form-label">微博博文链接</label>
                    <input type="text" id="weiboUrl" class="form-control"
                           placeholder="请粘贴微博博文链接，例如：https://weibo.com/...">
                    <small class="form-hint">支持微博PC端和移动端链接</small>
                </div>

                <button class="btn btn-primary btn-block" onclick="parseWeibo()" <?php echo !$apiConfigured ? 'disabled' : ''; ?>>
                    解析微博
                </button>
            </div>

            <!-- 步骤2：微博信息和设置条件 -->
            <div class="lottery-section hidden" id="step2">
                <div class="section-title">
                    <span class="section-icon">📊</span>
                    <span>微博信息</span>
                </div>

                <!-- 微博信息卡片 -->
                <div class="weibo-info-card">
                    <div class="weibo-stats">
                        <div class="stat-item">
                            <div class="stat-icon">👍</div>
                            <div class="stat-content">
                                <div class="stat-value" id="likeCount">-</div>
                                <div class="stat-label">点赞</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">💬</div>
                            <div class="stat-content">
                                <div class="stat-value" id="commentCount">-</div>
                                <div class="stat-label">评论</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">🔄</div>
                            <div class="stat-content">
                                <div class="stat-value" id="repostCount">-</div>
                                <div class="stat-label">转发</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-title" style="margin-top: 30px;">
                    <span class="section-icon">⚙️</span>
                    <span>抽奖设置</span>
                </div>

                <!-- 抽奖类型 -->
                <div class="form-group">
                    <label class="form-label">选择抽奖类型</label>
                    <div class="lottery-type-grid">
                        <div class="type-card" data-type="like">
                            <div class="type-icon">👍</div>
                            <div class="type-name">点赞</div>
                            <div class="type-desc">从点赞用户中抽取</div>
                        </div>
                        <div class="type-card" data-type="comment">
                            <div class="type-icon">💬</div>
                            <div class="type-name">评论</div>
                            <div class="type-desc">从评论用户中抽取</div>
                        </div>
                        <div class="type-card" data-type="repost">
                            <div class="type-icon">🔄</div>
                            <div class="type-name">转发</div>
                            <div class="type-desc">从转发用户中抽取</div>
                        </div>
                        <div class="type-card" data-type="mixed">
                            <div class="type-icon">🎯</div>
                            <div class="type-name">混合</div>
                            <div class="type-desc">多条件交叉验证</div>
                        </div>
                    </div>
                </div>

                <!-- 中奖人数 -->
                <div class="form-group">
                    <label class="form-label">中奖人数</label>
                    <input type="number" id="winnerCount" class="form-control"
                           value="1" min="1" max="100" placeholder="请输入中奖人数">
                    <small class="form-hint">建议不超过参与人数的20%</small>
                </div>

                <div class="button-group">
                    <button class="btn btn-outline" onclick="backToStep1()">上一步</button>
                    <button class="btn btn-primary" onclick="startLottery()">开始抽奖</button>
                </div>
            </div>

            <!-- 步骤3：抽奖中 -->
            <div class="lottery-section hidden" id="step3">
                <div class="lottery-animation">
                    <div class="lottery-wheel">
                        <div class="wheel-inner">
                            <div class="wheel-icon">🎁</div>
                        </div>
                    </div>
                    <div class="lottery-text">正在抽奖中...</div>
                    <div class="lottery-progress">
                        <div class="progress-bar"></div>
                    </div>
                </div>
            </div>

            <!-- 步骤4：抽奖结果 -->
            <div class="lottery-section hidden" id="step4">
                <div class="section-title">
                    <span class="section-icon">🎉</span>
                    <span>抽奖结果</span>
                </div>

                <!-- 结果信息 -->
                <div class="result-info">
                    <div class="result-item">
                        <span class="result-label">验证码：</span>
                        <span class="result-value verify-code" id="verifyCode">-</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">参与人数：</span>
                        <span class="result-value" id="totalParticipants">-</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">中奖人数：</span>
                        <span class="result-value" id="totalWinners">-</span>
                    </div>
                </div>

                <!-- 中奖名单 -->
                <div class="winners-list" id="winnersList"></div>

                <!-- 生成话术 -->
                <div class="announcement-box">
                    <div class="announcement-title">📢 中奖公告</div>
                    <textarea id="announcement" class="announcement-text" readonly></textarea>
                    <button class="btn btn-secondary btn-block" onclick="copyAnnouncement()">
                        复制公告
                    </button>
                </div>

                <div class="button-group">
                    <button class="btn btn-outline" onclick="resetLottery()">重新抽奖</button>
                    <a href="<?php echo SITE_URL; ?>/records.php" class="btn btn-primary">查看记录</a>
                </div>
            </div>
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
    <script src="<?php echo SITE_URL; ?>/assets/js/lottery.js"></script>
</body>
</html>
