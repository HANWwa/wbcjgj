<?php
/**
 * 认证相关AJAX处理
 * @神奇奶酪
 */

require_once __DIR__ . '/includes/config.php';

// 检查是否已安装
if (!isInstalled()) {
    jsonResponse(false, '系统未安装');
}

// 检查请求方法
if (!isPost()) {
    jsonResponse(false, '无效的请求方法');
}

$action = post('action');

// 根据不同的操作执行相应的功能
switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'send_email_code':
        handleSendEmailCode();
        break;
    case 'send_reset_code':
        handleSendResetCode();
        break;
    case 'reset_password':
        handleResetPassword();
        break;
    default:
        jsonResponse(false, '无效的操作');
}

/**
 * 处理用户注册
 */
function handleRegister() {
    $username = post('username');
    $email = post('email');
    $password = post('password');
    $passwordConfirm = post('password_confirm');
    $emailCode = post('email_code');
    $mathAnswer = post('math_answer');

    // 验证两次密码
    if ($password !== $passwordConfirm) {
        jsonResponse(false, '两次输入的密码不一致');
    }

    // 验证数学验证码
    if (getSecuritySetting('enable_math_verify') == '1') {
        if (!verifyMathCaptcha($mathAnswer)) {
            jsonResponse(false, '验证码错误');
        }
    }

    // 执行注册
    $auth = new Auth();
    $result = $auth->register($username, $email, $password, $emailCode);

    if ($result['success']) {
        // 注册成功，自动登录
        $loginResult = $auth->login($username, $password);
        if ($loginResult['success']) {
            jsonResponse(true, '注册成功！正在跳转...', ['redirect' => 'index.php']);
        } else {
            jsonResponse(true, '注册成功！请登录', ['redirect' => 'login.php']);
        }
    } else {
        jsonResponse(false, $result['message']);
    }
}

/**
 * 处理用户登录
 */
function handleLogin() {
    $username = post('username');
    $password = post('password');
    $remember = post('remember') == '1';
    $redirect = post('redirect', 'index.php');

    // 执行登录
    $auth = new Auth();
    $result = $auth->login($username, $password, $remember);

    if ($result['success']) {
        // 确保跳转地址安全
        if (empty($redirect) || strpos($redirect, 'http') !== false) {
            $redirect = 'index.php';
        }
        jsonResponse(true, '登录成功！正在跳转...', ['redirect' => $redirect]);
    } else {
        jsonResponse(false, $result['message']);
    }
}

/**
 * 发送邮箱验证码（注册用）
 */
function handleSendEmailCode() {
    $email = post('email');

    if (empty($email)) {
        jsonResponse(false, '请输入邮箱地址');
    }

    if (!isValidEmail($email)) {
        jsonResponse(false, '邮箱格式不正确');
    }

    // 检查邮箱是否已被注册
    $db = DB::getInstance();
    if ($db->exists('users', 'email = :email', [':email' => $email])) {
        jsonResponse(false, '该邮箱已被注册');
    }

    // 发送验证码
    if (sendVerificationEmail($email)) {
        jsonResponse(true, '验证码已发送，请查收邮箱');
    } else {
        jsonResponse(false, '验证码发送失败，请检查邮件配置');
    }
}

/**
 * 发送重置密码验证码
 */
function handleSendResetCode() {
    $email = post('email');

    if (empty($email)) {
        jsonResponse(false, '请输入邮箱地址');
    }

    if (!isValidEmail($email)) {
        jsonResponse(false, '邮箱格式不正确');
    }

    // 检查邮箱是否已注册
    $db = DB::getInstance();
    if (!$db->exists('users', 'email = :email', [':email' => $email])) {
        jsonResponse(false, '该邮箱未注册');
    }

    // 发送验证码
    if (sendVerificationEmail($email)) {
        jsonResponse(true, '验证码已发送，请查收邮箱');
    } else {
        jsonResponse(false, '验证码发送失败，请检查邮件配置');
    }
}

/**
 * 重置密码
 */
function handleResetPassword() {
    $email = post('email');
    $code = post('code');
    $newPassword = post('new_password');

    if (empty($email) || empty($code) || empty($newPassword)) {
        jsonResponse(false, '请填写完整信息');
    }

    // 执行重置密码
    $auth = new Auth();
    $result = $auth->resetPassword($email, $code, $newPassword);

    if ($result['success']) {
        jsonResponse(true, '密码重置成功！');
    } else {
        jsonResponse(false, $result['message']);
    }
}
