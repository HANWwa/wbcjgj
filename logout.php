<?php
/**
 * 退出登录
 * @神奇奶酪
 */

require_once __DIR__ . '/includes/config.php';

// 检查是否已安装
if (!isInstalled()) {
    redirect('install/index.php');
    exit;
}

// 执行退出登录
$auth = new Auth();
$auth->logout();

// 跳转到首页
redirect('index.php');
