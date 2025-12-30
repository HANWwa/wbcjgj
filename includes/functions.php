<?php
/**
 * 公共函数库
 * @神奇奶酪
 */

/**
 * 获取网站设置
 */
function getSetting($key, $default = '') {
    $db = DB::getInstance();
    $value = $db->fetchColumn(
        "SELECT setting_value FROM {prefix}site_settings WHERE setting_key = :key",
        [':key' => $key]
    );
    return $value !== false ? $value : $default;
}

/**
 * 更新网站设置
 */
function updateSetting($key, $value) {
    $db = DB::getInstance();
    $exists = $db->exists('site_settings', 'setting_key = :key', [':key' => $key]);

    if ($exists) {
        return $db->update(
            'site_settings',
            ['setting_value' => $value],
            'setting_key = :key',
            [':key' => $key]
        );
    } else {
        return $db->insert('site_settings', [
            'setting_key' => $key,
            'setting_value' => $value
        ]);
    }
}

/**
 * 获取安全设置
 */
function getSecuritySetting($key, $default = '') {
    $db = DB::getInstance();
    $value = $db->fetchColumn(
        "SELECT setting_value FROM {prefix}security_settings WHERE setting_key = :key",
        [':key' => $key]
    );
    return $value !== false ? $value : $default;
}

/**
 * 更新安全设置
 */
function updateSecuritySetting($key, $value) {
    $db = DB::getInstance();
    $exists = $db->exists('security_settings', 'setting_key = :key', [':key' => $key]);

    if ($exists) {
        return $db->update(
            'security_settings',
            ['setting_value' => $value],
            'setting_key = :key',
            [':key' => $key]
        );
    } else {
        return $db->insert('security_settings', [
            'setting_key' => $key,
            'setting_value' => $value
        ]);
    }
}

/**
 * 发送邮件
 */
function sendEmail($to, $subject, $body) {
    $smtpHost = getSecuritySetting('smtp_host');
    $smtpPort = getSecuritySetting('smtp_port');
    $smtpUsername = getSecuritySetting('smtp_username');
    $smtpPassword = getSecuritySetting('smtp_password');
    $smtpFromEmail = getSecuritySetting('smtp_from_email');
    $smtpFromName = getSecuritySetting('smtp_from_name', '微博抽奖系统');

    if (empty($smtpHost) || empty($smtpUsername) || empty($smtpPassword)) {
        return false;
    }

    require_once ROOT_PATH . '/includes/phpmailer/PHPMailer.php';
    require_once ROOT_PATH . '/includes/phpmailer/SMTP.php';
    require_once ROOT_PATH . '/includes/phpmailer/Exception.php';

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtpPort;

        $mail->setFrom($smtpFromEmail, $smtpFromName);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $e) {
        error_log("邮件发送失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 生成邮箱验证码
 */
function generateEmailCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * 发送验证码邮件
 */
function sendVerificationEmail($email) {
    $db = DB::getInstance();

    // 生成验证码
    $code = generateEmailCode();
    $expireAt = date('Y-m-d H:i:s', time() + 600); // 10分钟有效

    // 保存验证码
    $db->insert('email_verification', [
        'email' => $email,
        'code' => $code,
        'expire_at' => $expireAt,
        'is_used' => 0
    ]);

    // 发送邮件
    $subject = '邮箱验证码 - ' . getSetting('site_name', SYSTEM_NAME);
    $body = "
        <h2>邮箱验证</h2>
        <p>您的验证码是：<strong style='font-size: 24px; color: #ff6b6b;'>{$code}</strong></p>
        <p>验证码有效期为10分钟，请尽快使用。</p>
        <p>如果这不是您的操作，请忽略此邮件。</p>
        <hr>
        <p style='color: #999; font-size: 12px;'>" . COPYRIGHT . "</p>
    ";

    return sendEmail($email, $subject, $body);
}

/**
 * 验证邮箱验证码
 */
function verifyEmailCode($email, $code) {
    $db = DB::getInstance();

    $record = $db->fetchOne(
        "SELECT * FROM {prefix}email_verification
         WHERE email = :email AND code = :code AND is_used = 0 AND expire_at > NOW()
         ORDER BY id DESC LIMIT 1",
        [':email' => $email, ':code' => $code]
    );

    if ($record) {
        // 标记为已使用
        $db->update(
            'email_verification',
            ['is_used' => 1],
            'id = :id',
            [':id' => $record['id']]
        );
        return true;
    }

    return false;
}

/**
 * 生成数学验证码
 */
function generateMathCaptcha() {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $operator = rand(0, 1) ? '+' : '-';

    if ($operator === '-' && $num1 < $num2) {
        list($num1, $num2) = [$num2, $num1];
    }

    $question = "{$num1} {$operator} {$num2} = ?";
    $answer = $operator === '+' ? $num1 + $num2 : $num1 - $num2;

    $_SESSION['math_captcha'] = $answer;

    return $question;
}

/**
 * 验证数学验证码
 */
function verifyMathCaptcha($answer) {
    if (!isset($_SESSION['math_captcha'])) {
        return false;
    }

    $correct = $_SESSION['math_captcha'] == $answer;
    unset($_SESSION['math_captcha']);

    return $correct;
}

/**
 * 记录系统日志
 */
function logAction($userId, $action, $description = '', $ipAddress = null, $userAgent = null) {
    $db = DB::getInstance();

    if ($ipAddress === null) {
        $ipAddress = getClientIP();
    }

    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    return $db->insert('system_logs', [
        'user_id' => $userId,
        'action' => $action,
        'description' => $description,
        'ip_address' => $ipAddress,
        'user_agent' => substr($userAgent, 0, 255)
    ]);
}

/**
 * 上传文件
 */
function uploadFile($file, $dir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => '无效的文件'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '文件上传失败'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => '文件大小不能超过5MB'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => '不支持的文件类型'];
    }

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => str_replace(ROOT_PATH, SITE_URL, $filepath)
        ];
    }

    return ['success' => false, 'message' => '文件保存失败'];
}

/**
 * 删除文件
 */
function deleteFile($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * 生成缩略图
 */
function createThumbnail($source, $destination, $width, $height) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }

    list($srcWidth, $srcHeight, $type) = $imageInfo;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $srcImage = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    $dstImage = imagecreatetruecolor($width, $height);
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);

    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dstImage, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($dstImage, $destination);
            break;
        case IMAGETYPE_GIF:
            imagegif($dstImage, $destination);
            break;
    }

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    return true;
}

/**
 * 验证邮箱格式
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 验证URL格式
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * 清理HTML标签
 */
function cleanHtml($html) {
    return strip_tags($html);
}

/**
 * 截取字符串
 */
function subString($str, $length, $suffix = '...') {
    if (mb_strlen($str, 'utf-8') <= $length) {
        return $str;
    }
    return mb_substr($str, 0, $length, 'utf-8') . $suffix;
}

/**
 * 分页函数
 */
function pagination($total, $page, $perPage, $url) {
    $totalPages = ceil($total / $perPage);

    if ($totalPages <= 1) {
        return '';
    }

    $html = '<div class="pagination">';

    // 上一页
    if ($page > 1) {
        $prevUrl = str_replace('{page}', $page - 1, $url);
        $html .= '<a href="' . $prevUrl . '" class="page-link">&laquo; 上一页</a>';
    }

    // 页码
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);

    if ($start > 1) {
        $html .= '<a href="' . str_replace('{page}', 1, $url) . '" class="page-link">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $pageUrl = str_replace('{page}', $i, $url);
        $active = $i === $page ? ' active' : '';
        $html .= '<a href="' . $pageUrl . '" class="page-link' . $active . '">' . $i . '</a>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
        $html .= '<a href="' . str_replace('{page}', $totalPages, $url) . '" class="page-link">' . $totalPages . '</a>';
    }

    // 下一页
    if ($page < $totalPages) {
        $nextUrl = str_replace('{page}', $page + 1, $url);
        $html .= '<a href="' . $nextUrl . '" class="page-link">下一页 &raquo;</a>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * 检查VIP状态
 */
function isVip($userId) {
    $db = DB::getInstance();
    $user = $db->fetchOne(
        "SELECT is_vip, vip_expire FROM {prefix}users WHERE id = :id",
        [':id' => $userId]
    );

    if (!$user || !$user['is_vip']) {
        return false;
    }

    // 检查是否过期
    if ($user['vip_expire'] && strtotime($user['vip_expire']) < time()) {
        // 过期，更新状态
        $db->update(
            'users',
            ['is_vip' => 0],
            'id = :id',
            [':id' => $userId]
        );
        return false;
    }

    return true;
}

/**
 * 格式化金额
 */
function formatMoney($amount) {
    return '¥' . number_format($amount, 2);
}
