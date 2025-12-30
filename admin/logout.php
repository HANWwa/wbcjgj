<?php
/**
 * 管理员退出登录
 * @神奇奶酪
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$adminAuth = new AdminAuth();
$adminAuth->logout();

redirect('login.php');
