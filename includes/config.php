<?php
/**
 * 核心配置文件
 * @神奇奶酪
 */

// 防止直接访问
if (!defined('IN_INSTALL') && !defined('IN_SYSTEM')) {
    define('IN_SYSTEM', true);
}

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 会话设置
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// 项目根目录
define('ROOT_PATH', dirname(__DIR__));

// 配置文件路径
define('CONFIG_FILE', ROOT_PATH . '/config_db.php');

// 数据库配置（如果配置文件存在则加载）
if (file_exists(CONFIG_FILE)) {
    require_once CONFIG_FILE;
} else {
    // 默认配置（用于安装过程）
    if (!defined('DB_HOST')) define('DB_HOST', '');
    if (!defined('DB_NAME')) define('DB_NAME', '');
    if (!defined('DB_USER')) define('DB_USER', '');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('DB_PREFIX')) define('DB_PREFIX', 'wb_');
}

// 系统常量定义
define('SITE_URL', getSiteUrl());
define('ADMIN_DIR', 'admin');
define('UPLOAD_DIR', ROOT_PATH . '/uploads');
define('AVATAR_DIR', UPLOAD_DIR . '/avatars');
define('QRCODE_DIR', UPLOAD_DIR . '/qrcodes');

// 加密密钥（用于密码加密等）
define('SECURITY_SALT', 'wb_lottery_magic_cheese_2024');

// 分页设置
define('ITEMS_PER_PAGE', 20);

// 会员默认有效期（天）
define('VIP_DEFAULT_DAYS', 365);

// 版权信息
define('COPYRIGHT', '@神奇奶酪');
define('SYSTEM_NAME', '微博抽奖系统');
define('VERSION', '1.0.0');

/**
 * 获取网站URL
 */
function getSiteUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = str_replace('\\', '/', dirname($script));
    $path = ($path === '/') ? '' : $path;
    return $protocol . $host . $path;
}

/**
 * 自动加载类
 */
spl_autoload_register(function ($class) {
    $file = ROOT_PATH . '/includes/' . strtolower($class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * 调试函数
 */
function debug($data, $die = false) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}

/**
 * JSON响应
 */
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 重定向
 */
function redirect($url, $time = 0) {
    if ($time === 0) {
        header("Location: $url");
        exit;
    } else {
        header("Refresh: $time; url=$url");
    }
}

/**
 * 安全过滤
 */
function safe($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 获取客户端IP
 */
function getClientIP() {
    $ip = '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return $ip;
}

/**
 * 生成随机字符串
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * 生成订单号
 */
function generateOrderNo() {
    return 'WB' . date('YmdHis') . rand(1000, 9999);
}

/**
 * 格式化时间
 */
function formatTime($time) {
    if (is_string($time)) {
        $time = strtotime($time);
    }
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . '天前';
    } else {
        return date('Y-m-d H:i', $time);
    }
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * 检查是否已安装
 */
function isInstalled() {
    return file_exists(CONFIG_FILE);
}

/**
 * 检查是否为POST请求
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * 获取POST数据
 */
function post($key, $default = '') {
    return $_POST[$key] ?? $default;
}

/**
 * 获取GET数据
 */
function get($key, $default = '') {
    return $_GET[$key] ?? $default;
}
