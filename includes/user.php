<?php
/**
 * 用户类
 * @神奇奶酪
 */

class User {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance();
    }

    /**
     * 根据ID获取用户
     */
    public function getUserById($userId) {
        return $this->db->fetchOne(
            "SELECT * FROM {prefix}users WHERE id = :id",
            [':id' => $userId]
        );
    }

    /**
     * 根据用户名获取用户
     */
    public function getUserByUsername($username) {
        return $this->db->fetchOne(
            "SELECT * FROM {prefix}users WHERE username = :username",
            [':username' => $username]
        );
    }

    /**
     * 根据邮箱获取用户
     */
    public function getUserByEmail($email) {
        return $this->db->fetchOne(
            "SELECT * FROM {prefix}users WHERE email = :email",
            [':email' => $email]
        );
    }

    /**
     * 获取所有用户列表
     */
    public function getAllUsers($page = 1, $perPage = ITEMS_PER_PAGE, $search = '', $role = '') {
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];

        if (!empty($search)) {
            $where .= " AND (username LIKE :search OR email LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($role)) {
            $where .= " AND role = :role";
            $params[':role'] = $role;
        }

        // 获取总数
        $total = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {prefix}users WHERE {$where}",
            $params
        );

        // 获取数据
        $users = $this->db->fetchAll(
            "SELECT * FROM {prefix}users WHERE {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * 更新用户信息
     */
    public function updateUser($userId, $data) {
        return $this->db->update(
            'users',
            $data,
            'id = :id',
            [':id' => $userId]
        );
    }

    /**
     * 删除用户
     */
    public function deleteUser($userId) {
        // 不能删除管理员
        $user = $this->getUserById($userId);
        if ($user && $user['role'] === 'admin') {
            return ['success' => false, 'message' => '不能删除管理员账户'];
        }

        $deleted = $this->db->delete('users', 'id = :id', [':id' => $userId]);

        if ($deleted) {
            logAction(0, 'delete_user', "删除用户ID: {$userId}");
            return ['success' => true, 'message' => '用户删除成功'];
        }

        return ['success' => false, 'message' => '用户删除失败'];
    }

    /**
     * 封禁/解封用户
     */
    public function toggleUserStatus($userId) {
        $user = $this->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }

        // 不能封禁管理员
        if ($user['role'] === 'admin') {
            return ['success' => false, 'message' => '不能封禁管理员账户'];
        }

        $newStatus = $user['status'] == 1 ? 0 : 1;
        $updated = $this->updateUser($userId, ['status' => $newStatus]);

        if ($updated !== false) {
            $action = $newStatus == 1 ? '解封' : '封禁';
            logAction(0, 'toggle_user_status', "{$action}用户ID: {$userId}");
            return ['success' => true, 'message' => $action . '成功'];
        }

        return ['success' => false, 'message' => '操作失败'];
    }

    /**
     * 开通VIP
     */
    public function activateVip($userId, $days = null) {
        if ($days === null) {
            $days = getSetting('vip_duration', VIP_DEFAULT_DAYS);
        }

        $user = $this->getUserById($userId);
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }

        // 计算过期时间
        if ($user['is_vip'] && $user['vip_expire'] && strtotime($user['vip_expire']) > time()) {
            // 如果已经是VIP且未过期，在原有基础上延长
            $expireTime = date('Y-m-d H:i:s', strtotime($user['vip_expire']) + ($days * 86400));
        } else {
            // 否则从现在开始计算
            $expireTime = date('Y-m-d H:i:s', time() + ($days * 86400));
        }

        $updated = $this->updateUser($userId, [
            'is_vip' => 1,
            'vip_expire' => $expireTime
        ]);

        if ($updated !== false) {
            logAction($userId, 'activate_vip', "开通VIP，有效期至: {$expireTime}");
            return [
                'success' => true,
                'message' => 'VIP开通成功',
                'expire_time' => $expireTime
            ];
        }

        return ['success' => false, 'message' => 'VIP开通失败'];
    }

    /**
     * 取消VIP
     */
    public function deactivateVip($userId) {
        $updated = $this->updateUser($userId, [
            'is_vip' => 0,
            'vip_expire' => null
        ]);

        if ($updated !== false) {
            logAction($userId, 'deactivate_vip', '取消VIP');
            return ['success' => true, 'message' => 'VIP已取消'];
        }

        return ['success' => false, 'message' => '操作失败'];
    }

    /**
     * 获取用户统计信息
     */
    public function getUserStats($userId) {
        // 抽奖总次数
        $lotteryCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {prefix}lottery_records WHERE user_id = :id",
            [':id' => $userId]
        );

        // 中奖总人数
        $winnerCount = $this->db->fetchColumn(
            "SELECT SUM(winner_count) FROM {prefix}lottery_records WHERE user_id = :id AND status = 'completed'",
            [':id' => $userId]
        );

        // 最近抽奖时间
        $lastLottery = $this->db->fetchColumn(
            "SELECT MAX(created_at) FROM {prefix}lottery_records WHERE user_id = :id",
            [':id' => $userId]
        );

        return [
            'lottery_count' => $lotteryCount ?: 0,
            'winner_count' => $winnerCount ?: 0,
            'last_lottery' => $lastLottery
        ];
    }

    /**
     * 修改用户密码（管理员操作）
     */
    public function adminChangePassword($userId, $newPassword) {
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => '密码长度至少6个字符'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->updateUser($userId, ['password' => $hashedPassword]);

        if ($updated !== false) {
            logAction(0, 'admin_change_password', "管理员修改用户密码，用户ID: {$userId}");
            return ['success' => true, 'message' => '密码修改成功'];
        }

        return ['success' => false, 'message' => '密码修改失败'];
    }

    /**
     * 修改用户邮箱（管理员操作）
     */
    public function adminChangeEmail($userId, $newEmail) {
        if (!isValidEmail($newEmail)) {
            return ['success' => false, 'message' => '邮箱格式不正确'];
        }

        // 检查邮箱是否已存在
        $exists = $this->db->fetchOne(
            "SELECT id FROM {prefix}users WHERE email = :email AND id != :id",
            [':email' => $newEmail, ':id' => $userId]
        );

        if ($exists) {
            return ['success' => false, 'message' => '邮箱已被使用'];
        }

        $updated = $this->updateUser($userId, ['email' => $newEmail]);

        if ($updated !== false) {
            logAction(0, 'admin_change_email', "管理员修改用户邮箱，用户ID: {$userId}");
            return ['success' => true, 'message' => '邮箱修改成功'];
        }

        return ['success' => false, 'message' => '邮箱修改失败'];
    }

    /**
     * 获取系统统计信息
     */
    public function getSystemStats() {
        // 总用户数
        $totalUsers = $this->db->count('users');

        // VIP用户数
        $vipUsers = $this->db->count('users', 'is_vip = 1');

        // 今日新增用户
        $todayUsers = $this->db->count(
            'users',
            'DATE(created_at) = CURDATE()'
        );

        // 总抽奖次数
        $totalLotteries = $this->db->count('lottery_records');

        // 今日抽奖次数
        $todayLotteries = $this->db->count(
            'lottery_records',
            'DATE(created_at) = CURDATE()'
        );

        // 总中奖人数
        $totalWinners = $this->db->count('lottery_winners');

        // 总交易金额
        $totalRevenue = $this->db->fetchColumn(
            "SELECT SUM(amount) FROM {prefix}transactions WHERE status = 'paid'"
        );

        // 今日交易金额
        $todayRevenue = $this->db->fetchColumn(
            "SELECT SUM(amount) FROM {prefix}transactions WHERE status = 'paid' AND DATE(created_at) = CURDATE()"
        );

        return [
            'total_users' => $totalUsers ?: 0,
            'vip_users' => $vipUsers ?: 0,
            'today_users' => $todayUsers ?: 0,
            'total_lotteries' => $totalLotteries ?: 0,
            'today_lotteries' => $todayLotteries ?: 0,
            'total_winners' => $totalWinners ?: 0,
            'total_revenue' => $totalRevenue ?: 0,
            'today_revenue' => $todayRevenue ?: 0
        ];
    }
}
