<?php
/**
 * 安装向导
 * @神奇奶酪
 */

define('IN_INSTALL', true);
require_once dirname(__DIR__) . '/includes/config.php';

// 检查是否已安装
if (isInstalled()) {
    redirect('../index.php');
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(4, $step)); // 限制步骤在1-4之间
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 微博抽奖系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="install.css">
</head>
<body>
    <!-- 加载动画 -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div>
            <div class="loader"></div>
            <div class="loading-text">正在处理，请稍候...</div>
        </div>
    </div>

    <div class="install-container">
        <div class="install-header">
            <h1>🎁 微博抽奖系统</h1>
            <p>欢迎使用专业的微博抽奖工具平台</p>
        </div>

        <!-- 步骤指示器 -->
        <div class="steps-indicator">
            <div class="step-item <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-label">环境检测</div>
            </div>
            <div class="step-line <?php echo $step > 1 ? 'active' : ''; ?>"></div>
            <div class="step-item <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">数据库配置</div>
            </div>
            <div class="step-line <?php echo $step > 2 ? 'active' : ''; ?>"></div>
            <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-label">管理员设置</div>
            </div>
            <div class="step-line <?php echo $step > 3 ? 'active' : ''; ?>"></div>
            <div class="step-item <?php echo $step >= 4 ? 'active' : ''; ?>">
                <div class="step-number">4</div>
                <div class="step-label">完成安装</div>
            </div>
        </div>

        <!-- 安装内容 -->
        <div class="install-content">
            <?php if ($step === 1): ?>
                <!-- 步骤1: 环境检测 -->
                <div class="install-step">
                    <h2>环境检测</h2>
                    <p class="step-desc">正在检测服务器环境是否满足安装要求...</p>

                    <div class="check-list">
                        <?php
                        $checks = [
                            'PHP版本 >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                            'PDO扩展' => extension_loaded('pdo'),
                            'PDO MySQL扩展' => extension_loaded('pdo_mysql'),
                            'MBString扩展' => extension_loaded('mbstring'),
                            'JSON扩展' => extension_loaded('json'),
                            'GD扩展' => extension_loaded('gd'),
                            'uploads目录可写' => is_writable(dirname(__DIR__) . '/uploads'),
                            'includes目录可写' => is_writable(dirname(__DIR__) . '/includes')
                        ];

                        $allPassed = true;
                        foreach ($checks as $label => $passed) {
                            if (!$passed) $allPassed = false;
                            $icon = $passed ? '✅' : '❌';
                            $status = $passed ? 'passed' : 'failed';
                            echo "<div class='check-item check-{$status}'>";
                            echo "<span class='check-icon'>{$icon}</span>";
                            echo "<span class='check-label'>{$label}</span>";
                            echo "<span class='check-status'>" . ($passed ? '通过' : '失败') . "</span>";
                            echo "</div>";
                        }
                        ?>
                    </div>

                    <?php if ($allPassed): ?>
                        <div class="alert alert-success mt-30">
                            <strong>恭喜！</strong> 您的服务器环境完全满足安装要求。
                        </div>
                        <div class="step-actions">
                            <a href="?step=2" class="btn btn-primary btn-block">下一步：配置数据库</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error mt-30">
                            <strong>警告！</strong> 您的服务器环境存在问题，请先解决上述标记为失败的项目。
                        </div>
                        <div class="step-actions">
                            <a href="?step=1" class="btn btn-outline btn-block">重新检测</a>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($step === 2): ?>
                <!-- 步骤2: 数据库配置 -->
                <div class="install-step">
                    <h2>数据库配置</h2>
                    <p class="step-desc">请填写您的数据库连接信息</p>

                    <div id="errorMessage" class="alert alert-error hidden"></div>
                    <div id="successMessage" class="alert alert-success hidden"></div>

                    <form id="dbConfigForm" class="install-form">
                        <div class="form-group">
                            <label class="form-label">数据库主机</label>
                            <input type="text" name="db_host" class="form-control" value="localhost" required>
                            <small class="form-hint">通常为 localhost 或 127.0.0.1</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">数据库端口</label>
                            <input type="number" name="db_port" class="form-control" value="3306" required>
                            <small class="form-hint">MySQL默认端口为 3306</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">数据库名称</label>
                            <input type="text" name="db_name" class="form-control" placeholder="weibo_lottery" required>
                            <small class="form-hint">请确保该数据库已创建</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">数据库用户名</label>
                            <input type="text" name="db_user" class="form-control" placeholder="root" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">数据库密码</label>
                            <input type="password" name="db_pass" class="form-control" placeholder="请输入数据库密码">
                        </div>

                        <div class="form-group">
                            <label class="form-label">表前缀</label>
                            <input type="text" name="db_prefix" class="form-control" value="wb_" required>
                            <small class="form-hint">建议保持默认 wb_</small>
                        </div>

                        <div class="step-actions">
                            <a href="?step=1" class="btn btn-outline">上一步</a>
                            <button type="submit" class="btn btn-primary">测试连接并继续</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($step === 3): ?>
                <!-- 步骤3: 管理员设置 -->
                <div class="install-step">
                    <h2>创建管理员账户</h2>
                    <p class="step-desc">设置系统管理员账户信息</p>

                    <div id="errorMessage" class="alert alert-error hidden"></div>
                    <div id="successMessage" class="alert alert-success hidden"></div>

                    <form id="adminConfigForm" class="install-form">
                        <div class="form-group">
                            <label class="form-label">管理员用户名</label>
                            <input type="text" name="admin_username" class="form-control" placeholder="admin" required minlength="3" maxlength="20">
                            <small class="form-hint">3-20个字符，只能包含字母、数字和下划线</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">管理员邮箱</label>
                            <input type="email" name="admin_email" class="form-control" placeholder="admin@example.com" required>
                            <small class="form-hint">用于接收系统通知和密码找回</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">管理员密码</label>
                            <input type="password" name="admin_password" class="form-control" placeholder="请输入密码" required minlength="6">
                            <small class="form-hint">至少6个字符，建议使用字母+数字+符号组合</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">确认密码</label>
                            <input type="password" name="admin_password_confirm" class="form-control" placeholder="请再次输入密码" required minlength="6">
                        </div>

                        <div class="step-actions">
                            <button type="button" class="btn btn-outline" onclick="window.location.href='?step=2'">上一步</button>
                            <button type="submit" class="btn btn-primary">创建管理员并完成安装</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($step === 4): ?>
                <!-- 步骤4: 安装完成 -->
                <div class="install-step">
                    <div class="success-icon">🎉</div>
                    <h2>安装完成！</h2>
                    <p class="step-desc">恭喜您，微博抽奖系统已成功安装！</p>

                    <div class="install-info">
                        <div class="info-item">
                            <div class="info-label">系统名称</div>
                            <div class="info-value">微博抽奖系统</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">系统版本</div>
                            <div class="info-value">v1.0.0</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">版权信息</div>
                            <div class="info-value">@神奇奶酪</div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-30">
                        <strong>温馨提示：</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>请删除或重命名 install 目录以增强安全性</li>
                            <li>建议在后台完成微博API配置后再使用抽奖功能</li>
                            <li>定期备份数据库以防数据丢失</li>
                        </ul>
                    </div>

                    <div class="step-actions">
                        <a href="../index.php" class="btn btn-primary btn-block">进入网站首页</a>
                        <a href="../admin/index.php" class="btn btn-secondary btn-block mt-10">进入后台管理</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="install-footer">
            <p>&copy; 2024 微博抽奖系统 - <?php echo COPYRIGHT; ?></p>
        </div>
    </div>

    <script src="install.js"></script>
</body>
</html>
