<?php
/**
 * 认证类
 * @神奇奶酪
 */

class Auth {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance();
    }

    /**
     * 用户注册
     */
    public function register($username, $email, $password, $emailCode = null) {
        // 验证用户名
        if (strlen($username) < 3 || strlen($username) > 20) {
            return ['success' => false, 'message' => '用户名长度必须在3-20个字符之间'];
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'message' => '用户名只能包含字母、数字和下划线'];
        }

        // 验证邮箱
        if (!isValidEmail($email)) {
            return ['success' => false, 'message' => '邮箱格式不正确'];
        }

        // 验证密码
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => '密码长度至少6个字符'];
        }

        // 检查邮箱验证
        if (getSecuritySetting('enable_email_verify') == '1') {
            if (!$emailCode) {
                return ['success' => false, 'message' => '请输入邮箱验证码'];
            }
            if (!verifyEmailCode($email, $emailCode)) {
                return ['success' => false, 'message' => '验证码错误或已过期'];
            }
        }

        // 检查用户名是否已存在
        if ($this->db->exists('users', 'username = :username', [':username' => $username])) {
            return ['success' => false, 'message' => '用户名已存在'];
        }

        // 检查邮箱是否已存在
        if ($this->db->exists('users', 'email = :email', [':email' => $email])) {
            return ['success' => false, 'message' => '邮箱已被注册'];
        }

        // 加密密码
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 插入用户
        $userId = $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => 'user',
            'is_vip' => 0,
            'status' => 1
        ]);

        if ($userId) {
            logAction($userId, 'register', '用户注册');
            return ['success' => true, 'message' => '注册成功', 'user_id' => $userId];
        }

        return ['success' => false, 'message' => '注册失败，请重试'];
    }

    /**
     * 用户登录
     */
    public function login($username, $password, $remember = false) {
        // 查询用户
        $user = $this->db->fetchOne(
            "SELECT * FROM {prefix}users WHERE username = :username OR email = :username",
            [':username' => $username]
        );

        if (!$user) {
            return ['success' => false, 'message' => '用户名或密码错误'];
        }

        // 检查账户状态
        if ($user['status'] != 1) {
            return ['success' => false, 'message' => '账户已被封禁'];
        }

        // 验证密码
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => '用户名或密码错误'];
        }

        // 设置会话
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['is_vip'] = $user['is_vip'];

        // 记住我功能
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 86400 * 30, '/');
            // TODO: 将token存储到数据库
        }

        // 记录登录日志
        logAction($user['id'], 'login', '用户登录');

        return ['success' => true, 'message' => '登录成功', 'user' => $user];
    }

    /**
     * 用户登出
     */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? 0;

        if ($userId) {
            logAction($userId, 'logout', '用户登出');
        }

        // 清除会话
        session_unset();
        session_destroy();

        // 清除cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }

        return ['success' => true, 'message' => '已退出登录'];
    }

    /**
     * 检查是否已登录
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    /**
     * 获取当前用户ID
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? 0;
    }

    /**
     * 获取当前用户信息
     */
    public function getCurrentUser() {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return null;
        }

        return $this->db->fetchOne(
            "SELECT * FROM {prefix}users WHERE id = :id",
            [':id' => $userId]
        );
    }

    /**
     * 检查是否为管理员
     */
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * 检查是否为VIP
     */
    public function isVip() {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return false;
        }
        return isVip($userId);
    }

    /**
     * 要求登录
     */
    public function requireLogin($redirect = true) {
        if (!$this->isLoggedIn()) {
            if ($redirect) {
                redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            }
            return false;
        }
        return true;
    }

    /**
     * 要求管理员权限
     */
    public function requireAdmin($redirect = true) {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            if ($redirect) {
                redirect(SITE_URL . '/index.php');
            }
            return false;
        }
        return true;
    }

    /**
     * 要求VIP权限
     */
    public function requireVip($redirect = true) {
        if (!$this->isLoggedIn() || !$this->isVip()) {
            if ($redirect) {
                redirect(SITE_URL . '/member.php');
            }
            return false;
        }
        return true;
    }

    /**
     * 修改密码
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->db->fetchOne(
            "SELECT password FROM {prefix}users WHERE id = :id",
            [':id' => $userId]
        );

        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }

        // 验证旧密码
        if (!password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => '原密码错误'];
        }

        // 验证新密码
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => '新密码长度至少6个字符'];
        }

        // 更新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->db->update(
            'users',
            ['password' => $hashedPassword],
            'id = :id',
            [':id' => $userId]
        );

        if ($updated) {
            logAction($userId, 'change_password', '修改密码');
            return ['success' => true, 'message' => '密码修改成功'];
        }

        return ['success' => false, 'message' => '密码修改失败'];
    }

    /**
     * 重置密码（忘记密码）
     */
    public function resetPassword($email, $code, $newPassword) {
        // 验证邮箱验证码
        if (!verifyEmailCode($email, $code)) {
            return ['success' => false, 'message' => '验证码错误或已过期'];
        }

        // 查询用户
        $user = $this->db->fetchOne(
            "SELECT id FROM {prefix}users WHERE email = :email",
            [':email' => $email]
        );

        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }

        // 验证新密码
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => '密码长度至少6个字符'];
        }

        // 更新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->db->update(
            'users',
            ['password' => $hashedPassword],
            'id = :id',
            [':id' => $user['id']]
        );

        if ($updated) {
            logAction($user['id'], 'reset_password', '重置密码');
            return ['success' => true, 'message' => '密码重置成功'];
        }

        return ['success' => false, 'message' => '密码重置失败'];
    }

    /**
     * 更新用户信息
     */
    public function updateProfile($userId, $data) {
        $allowedFields = ['email', 'avatar'];
        $updateData = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateData[$key] = $value;
            }
        }

        if (empty($updateData)) {
            return ['success' => false, 'message' => '没有可更新的数据'];
        }

        // 如果更新邮箱，检查是否已存在
        if (isset($updateData['email'])) {
            if (!isValidEmail($updateData['email'])) {
                return ['success' => false, 'message' => '邮箱格式不正确'];
            }

            $exists = $this->db->fetchOne(
                "SELECT id FROM {prefix}users WHERE email = :email AND id != :id",
                [':email' => $updateData['email'], ':id' => $userId]
            );

            if ($exists) {
                return ['success' => false, 'message' => '邮箱已被使用'];
            }
        }

        $updated = $this->db->update(
            'users',
            $updateData,
            'id = :id',
            [':id' => $userId]
        );

        if ($updated !== false) {
            logAction($userId, 'update_profile', '更新个人信息');
            return ['success' => true, 'message' => '信息更新成功'];
        }

        return ['success' => false, 'message' => '信息更新失败'];
    }
}
