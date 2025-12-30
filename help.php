<?php
/**
 * 使用帮助页面
 * @神奇奶酪
 */

require_once __DIR__ . '/includes/config.php';

// 检查是否已安装
if (!isInstalled()) {
    redirect('install/index.php');
    exit;
}

// 获取网站设置
$siteName = getSetting('site_name', SYSTEM_NAME);

// 获取当前用户信息
$auth = new Auth();
$isLoggedIn = $auth->isLoggedIn();
$isAdmin = $isLoggedIn ? $auth->isAdmin() : false;
$enableVip = getSetting('enable_vip', '0');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>使用帮助 - <?php echo safe($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        .help-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .help-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .help-title {
            font-size: 42px;
            font-weight: bold;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .help-subtitle {
            font-size: 18px;
            color: var(--text-gray);
        }

        .help-nav {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .help-nav-list {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            list-style: none;
        }

        .help-nav-list li a {
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s;
            display: block;
        }

        .help-nav-list li a:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }

        .help-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .help-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-2);
        }

        .section-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-icon {
            font-size: 32px;
        }

        .help-content h3 {
            font-size: 20px;
            color: var(--primary-color);
            margin: 25px 0 15px;
        }

        .help-content p {
            color: var(--text-gray);
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .help-content ul, .help-content ol {
            color: var(--text-gray);
            line-height: 1.8;
            margin-bottom: 15px;
            padding-left: 25px;
        }

        .help-content li {
            margin-bottom: 10px;
        }

        .step-list {
            counter-reset: step-counter;
            list-style: none;
            padding-left: 0;
        }

        .step-list li {
            counter-increment: step-counter;
            position: relative;
            padding-left: 50px;
            margin-bottom: 20px;
        }

        .step-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 35px;
            height: 35px;
            background: var(--gradient-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--text-light);
        }

        .highlight-box {
            background: rgba(255, 107, 107, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .warning-box {
            background: rgba(255, 212, 59, 0.1);
            border-left: 4px solid var(--warning);
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .faq-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .faq-question {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .faq-answer {
            color: var(--text-gray);
            line-height: 1.8;
        }

        @media (max-width: 768px) {
            .help-title {
                font-size: 32px;
            }

            .section-title {
                font-size: 24px;
            }

            .help-section {
                padding: 25px 20px;
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
                <?php if ($isLoggedIn): ?>
                    <li><a href="<?php echo SITE_URL; ?>/lottery.php">开始抽奖</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/profile.php">个人中心</a></li>
                    <?php if ($enableVip == '1'): ?>
                        <li><a href="<?php echo SITE_URL; ?>/member.php">会员中心</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li><a href="<?php echo SITE_URL; ?>/help.php" class="active">使用帮助</a></li>
                <?php if ($isLoggedIn): ?>
                    <?php if ($isAdmin): ?>
                        <li><a href="<?php echo SITE_URL; ?>/admin/index.php" style="color: var(--accent-color);">后台管理</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php">退出登录</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/login.php">登录</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary" style="padding: 8px 20px;">注册</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- 帮助内容 -->
    <div class="help-container">
        <!-- 头部 -->
        <div class="help-header">
            <h1 class="help-title">📚 使用帮助</h1>
            <p class="help-subtitle">快速了解如何使用微博抽奖系统</p>
        </div>

        <!-- 快速导航 -->
        <div class="help-nav">
            <ul class="help-nav-list">
                <li><a href="#getting-started">快速开始</a></li>
                <li><a href="#vip-lottery">会员抽奖</a></li>
                <li><a href="#free-lottery">免费抽奖</a></li>
                <li><a href="#faq">常见问题</a></li>
                <li><a href="#contact">联系我们</a></li>
            </ul>
        </div>

        <!-- 快速开始 -->
        <div id="getting-started" class="help-section">
            <h2 class="section-title">
                <span class="section-icon">🚀</span>
                <span>快速开始</span>
            </h2>
            <div class="help-content">
                <p>欢迎使用微博抽奖系统！以下是使用本系统的基本步骤：</p>

                <ol class="step-list">
                    <li>
                        <strong>注册账号</strong>
                        <p>点击右上角"注册"按钮，填写用户名、邮箱和密码完成注册。</p>
                    </li>
                    <li>
                        <strong>登录系统</strong>
                        <p>使用注册的账号登录系统，即可开始使用抽奖功能。</p>
                    </li>
                    <li>
                        <strong>选择抽奖模式</strong>
                        <p>VIP会员可直接使用API抽奖，普通用户使用免费版需手动配置。</p>
                    </li>
                    <li>
                        <strong>开始抽奖</strong>
                        <p>输入微博链接，设置抽奖条件，点击开始抽奖即可。</p>
                    </li>
                </ol>

                <div class="highlight-box">
                    <strong>💡 提示：</strong>建议先开通VIP会员，享受更便捷的抽奖体验！
                </div>
            </div>
        </div>

        <!-- VIP会员抽奖 -->
        <div id="vip-lottery" class="help-section">
            <h2 class="section-title">
                <span class="section-icon">💎</span>
                <span>VIP会员抽奖</span>
            </h2>
            <div class="help-content">
                <h3>VIP会员优势</h3>
                <ul>
                    <li>⚡ 无需手动配置，系统自动调用微博API</li>
                    <li>🚀 一键解析微博链接，快速完成抽奖</li>
                    <li>📊 支持点赞、评论、转发多维度筛选</li>
                    <li>🎯 交叉验证，确保中奖用户真实有效</li>
                    <li>💬 专属客服支持，优先响应</li>
                </ul>

                <h3>使用步骤</h3>
                <ol class="step-list">
                    <li>
                        <strong>开通VIP会员</strong>
                        <p>进入"会员中心"，选择支付方式完成支付。</p>
                    </li>
                    <li>
                        <strong>进入抽奖页面</strong>
                        <p>点击"开始抽奖"进入VIP抽奖页面。</p>
                    </li>
                    <li>
                        <strong>输入微博链接</strong>
                        <p>粘贴需要抽奖的微博博文链接。</p>
                    </li>
                    <li>
                        <strong>设置抽奖条件</strong>
                        <p>选择点赞、评论或转发，设置中奖人数。</p>
                    </li>
                    <li>
                        <strong>开始抽奖</strong>
                        <p>点击"开始抽奖"按钮，系统自动完成抽奖。</p>
                    </li>
                    <li>
                        <strong>查看结果</strong>
                        <p>抽奖完成后显示中奖名单和验证码。</p>
                    </li>
                </ol>
            </div>
        </div>

        <!-- 免费版抽奖 -->
        <div id="free-lottery" class="help-section">
            <h2 class="section-title">
                <span class="section-icon">🆓</span>
                <span>免费版抽奖</span>
            </h2>
            <div class="help-content">
                <h3>免费版说明</h3>
                <p>免费版需要用户自行获取Cookie和API信息，适合有一定技术基础的用户使用。</p>

                <h3>获取Cookie和API</h3>
                <ol class="step-list">
                    <li>
                        <strong>登录微博</strong>
                        <p>在浏览器中登录微博账号。</p>
                    </li>
                    <li>
                        <strong>打开开发者工具</strong>
                        <p>按F12或右键选择"检查"打开开发者工具。</p>
                    </li>
                    <li>
                        <strong>切换到Network标签</strong>
                        <p>在开发者工具中切换到"Network"（网络）标签。</p>
                    </li>
                    <li>
                        <strong>刷新页面</strong>
                        <p>刷新微博页面，在Network中找到请求。</p>
                    </li>
                    <li>
                        <strong>复制Cookie</strong>
                        <p>在请求头中找到Cookie字段并复制。</p>
                    </li>
                </ol>

                <div class="warning-box">
                    <strong>⚠️ 注意：</strong>Cookie信息非常重要，请勿泄露给他人！
                </div>

                <h3>使用免费版抽奖</h3>
                <ol class="step-list">
                    <li>
                        <strong>进入免费抽奖页面</strong>
                        <p>点击"免费抽奖"进入配置页面。</p>
                    </li>
                    <li>
                        <strong>填写Cookie信息</strong>
                        <p>将获取的Cookie粘贴到对应输入框。</p>
                    </li>
                    <li>
                        <strong>输入微博链接</strong>
                        <p>粘贴需要抽奖的微博链接。</p>
                    </li>
                    <li>
                        <strong>设置抽奖条件</strong>
                        <p>选择筛选条件和中奖人数。</p>
                    </li>
                    <li>
                        <strong>开始抽奖</strong>
                        <p>点击"开始抽奖"完成抽奖流程。</p>
                    </li>
                </ol>
            </div>
        </div>

        <!-- 常见问题 -->
        <div id="faq" class="help-section">
            <h2 class="section-title">
                <span class="section-icon">❓</span>
                <span>常见问题</span>
            </h2>
            <div class="help-content">
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Q:</span>
                        <span>抽奖是否公平公正？</span>
                    </div>
                    <div class="faq-answer">
                        A: 本系统采用随机算法，确保每次抽奖都是公平公正的。每次抽奖都会生成唯一验证码，可供随时查询验证。
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Q:</span>
                        <span>VIP会员如何开通？</span>
                    </div>
                    <div class="faq-answer">
                        A: 进入"会员中心"，选择支付方式完成支付即可开通。支持扫码支付、支付宝、微信等多种支付方式。
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Q:</span>
                        <span>免费版和VIP版有什么区别？</span>
                    </div>
                    <div class="faq-answer">
                        A: VIP版无需手动配置Cookie，系统自动调用API，使用更便捷。免费版需要用户自行获取Cookie信息。
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Q:</span>
                        <span>如何查看历史抽奖记录？</span>
                    </div>
                    <div class="faq-answer">
                        A: 登录后进入"个人中心"-"抽奖记录"即可查看所有历史抽奖记录和中奖名单。
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Q:</span>
                        <span>忘记密码怎么办？</span>
                    </div>
                    <div class="faq-answer">
                        A: 在登录页面点击"忘记密码"，通过注册邮箱接收验证码即可重置密码。
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Q:</span>
                        <span>系统是否保存我的微博账号信息？</span>
                    </div>
                    <div class="faq-answer">
                        A: VIP版不保存任何账号信息，所有操作通过加密API完成。免费版Cookie仅临时使用，不会存储。
                    </div>
                </div>
            </div>
        </div>

        <!-- 联系我们 -->
        <div id="contact" class="help-section">
            <h2 class="section-title">
                <span class="section-icon">📞</span>
                <span>联系我们</span>
            </h2>
            <div class="help-content">
                <p>如果您在使用过程中遇到任何问题，欢迎通过以下方式联系我们：</p>

                <div class="highlight-box">
                    <p><strong>📧 邮箱：</strong> support@example.com</p>
                    <p><strong>💬 在线客服：</strong> 工作日 9:00-18:00</p>
                    <p><strong>🌐 官方网站：</strong> <?php echo SITE_URL; ?></p>
                </div>

                <p style="margin-top: 20px;">我们会尽快回复您的问题！</p>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="<?php echo SITE_URL; ?>/index.php">首页</a>
                    <a href="<?php echo SITE_URL; ?>/help.php">使用帮助</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo SITE_URL; ?>/profile.php">个人中心</a>
                    <?php endif; ?>
                </div>
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo safe($siteName); ?> - <?php echo COPYRIGHT; ?></p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
