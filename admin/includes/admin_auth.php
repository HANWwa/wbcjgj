<?php
/**
 * 管理员认证类
 * @神奇奶酪
 */

class AdminAuth {
    private $db;
    private $sessionKey = 'admin_user_id';

    public function __construct() {
        $this->db = DB::getInstance();

        // 确保session已启动
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 管理员登录
     */
    public function login($username, $password, $remember = false) {
        // 验证输入
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => '用户名和密码不能为空'
            ];
        }

        // 查询管理员用户
        $admin = $this->db->fetchOne(
            "SELECT * FROM {prefix}users WHERE username = :username AND role = 'admin' LIMIT 1",
            [':username' => $username]
        );

        if (!$admin) {
            // 记录失败日志
            logAction(0, 'admin_login_failed', "管理员登录失败：用户名不存在 - {$username}");
            return [
                'success' => false,
                'message' => '用户名或密码错误'
            ];
        }

        // 验证密码
        if (!password_verify($password, $admin['password'])) {
            // 记录失败日志
            logAction($admin['id'], 'admin_login_failed', "管理员登录失败：密码错误");
            return [
                'success' => false,
                'message' => '用户名或密码错误'
            ];
        }

        // 检查账号状态
        if ($admin['status'] !== 'active') {
            return [
                'success' => false,
                'message' => '账号已被禁用，请联系超级管理员'
            ];
        }

        // 设置session
        $_SESSION[$this->sessionKey] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];

        // 记住我功能
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('admin_remember_token', $token, time() + 86400 * 30, '/', '', false, true);

            // 保存token到数据库
            $this->db->update(
                'users',
                ['remember_token' => $token],
                'id = :id',
                [':id' => $admin['id']]
            );
        }

        // 更新最后登录时间
        $this->db->update(
            'users',
            [
                'last_login' => date('Y-m-d H:i:s'),
                'last_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ],
            'id = :id',
            [':id' => $admin['id']]
        );

        // 记录成功日志
        logAction($admin['id'], 'admin_login', "管理员登录成功");

        return [
            'success' => true,
            'message' => '登录成功',
            'user' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'email' => $admin['email']
            ]
        ];
    }

    /**
     * 管理员退出
     */
    public function logout() {
        $userId = $this->getCurrentAdminId();

        if ($userId) {
            logAction($userId, 'admin_logout', "管理员退出登录");
        }

        // 清除session
        unset($_SESSION[$this->sessionKey]);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_role']);

        // 清除记住我cookie
        if (isset($_COOKIE['admin_remember_token'])) {
            setcookie('admin_remember_token', '', time() - 3600, '/', '', false, true);
        }

        return true;
    }

    /**
     * 检查是否已登录
     */
    public function isLoggedIn() {
        // 检查session
        if (isset($_SESSION[$this->sessionKey]) && $_SESSION[$this->sessionKey] > 0) {
            return true;
        }

        // 检查记住我cookie
        if (isset($_COOKIE['admin_remember_token'])) {
            $token = $_COOKIE['admin_remember_token'];
            $admin = $this->db->fetchOne(
                "SELECT * FROM {prefix}users WHERE remember_token = :token AND role = 'admin' LIMIT 1",
                [':token' => $token]
            );

            if ($admin) {
                $_SESSION[$this->sessionKey] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                return true;
            }
        }

        return false;
    }

    /**
     * 要求登录（未登录则跳转）
     */
    public function requireLogin($redirect = true) {
        if (!$this->isLoggedIn()) {
            if ($redirect) {
                header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
                exit;
            }
            return false;
        }
        return true;
    }

    /**
     * 获取当前管理员ID
     */
    public function getCurrentAdminId() {
        return $_SESSION[$this->sessionKey] ?? 0;
    }

    /**
     * 获取当前管理员信息
     */
    public function getCurrentAdmin() {
        $adminId = $this->getCurrentAdminId();
        if (!$adminId) {
            return null;
        }

        return $this->db->fetchOne(
            "SELECT id, username, email, role, vip_expire, status, created_at, last_login
             FROM {prefix}users WHERE id = :id AND role = 'admin' LIMIT 1",
            [':id' => $adminId]
        );
    }

    /**
     * 检查权限
     */
    public function checkPermission($permission) {
        $admin = $this->getCurrentAdmin();
        if (!$admin) {
            return false;
        }

        // 超级管理员拥有所有权限
        if ($admin['role'] === 'admin') {
            return true;
        }

        // 这里可以扩展更细粒度的权限控制
        return false;
    }

    /**
     * 修改管理员密码
     */
    public function changePassword($adminId, $oldPassword, $newPassword) {
        // 获取管理员信息
        $admin = $this->db->fetchOne(
            "SELECT * FROM {prefix}users WHERE id = :id AND role = 'admin' LIMIT 1",
            [':id' => $adminId]
        );

        if (!$admin) {
            return [
                'success' => false,
                'message' => '管理员不存在'
            ];
        }

        // 验证旧密码
        if (!password_verify($oldPassword, $admin['password'])) {
            return [
                'success' => false,
                'message' => '原密码错误'
            ];
        }

        // 验证新密码强度
        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'message' => '新密码长度不能少于6位'
            ];
        }

        // 更新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update(
            'users',
            ['password' => $hashedPassword],
            'id = :id',
            [':id' => $adminId]
        );

        logAction($adminId, 'admin_change_password', "管理员修改密码");

        return [
            'success' => true,
            'message' => '密码修改成功'
        ];
    }

    /**
     * 重置用户密码（管理员操作）
     */
    public function resetUserPassword($userId, $newPassword) {
        $adminId = $this->getCurrentAdminId();

        if (!$adminId) {
            return [
                'success' => false,
                'message' => '未登录'
            ];
        }

        // 验证新密码
        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'message' => '密码长度不能少于6位'
            ];
        }

        // 更新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $result = $this->db->update(
            'users',
            ['password' => $hashedPassword],
            'id = :id',
            [':id' => $userId]
        );

        if ($result) {
            logAction($adminId, 'admin_reset_password', "管理员重置用户密码，用户ID: {$userId}");
            return [
                'success' => true,
                'message' => '密码重置成功'
            ];
        }

        return [
            'success' => false,
            'message' => '密码重置失败'
        ];
    }
}
