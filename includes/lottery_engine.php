<?php
/**
 * 抽奖引擎类
 * @神奇奶酪
 */

class LotteryEngine {
    private $db;
    private $weiboApi;

    public function __construct() {
        $this->db = DB::getInstance();
        $this->weiboApi = new WeiboAPI();
    }

    /**
     * 执行抽奖
     */
    public function executeLottery($userId, $weiboUrl, $lotteryType, $winnerCount, $mode = 'vip') {
        try {
            // 1. 解析微博链接
            $weiboId = $this->weiboApi->parseWeiboUrl($weiboUrl);
            if (!$weiboId) {
                return [
                    'success' => false,
                    'message' => '无法解析微博链接，请检查链接格式'
                ];
            }

            // 2. 获取微博信息
            $weiboInfo = $this->weiboApi->getWeiboInfo($weiboId);
            if (!$weiboInfo['success']) {
                return [
                    'success' => false,
                    'message' => '获取微博信息失败，请检查链接是否正确'
                ];
            }

            // 3. 根据类型获取参与用户
            $participants = $this->getParticipants($weiboId, $lotteryType);
            if (empty($participants)) {
                return [
                    'success' => false,
                    'message' => '未找到参与用户'
                ];
            }

            // 4. 检查中奖人数
            if ($winnerCount > count($participants)) {
                return [
                    'success' => false,
                    'message' => '中奖人数不能超过参与人数'
                ];
            }

            // 5. 生成验证码
            $verifyCode = $this->generateVerifyCode();

            // 6. 创建抽奖记录
            $lotteryId = $this->db->insert('lottery_records', [
                'user_id' => $userId,
                'weibo_url' => $weiboUrl,
                'weibo_id' => $weiboId,
                'lottery_type' => $lotteryType,
                'winner_count' => $winnerCount,
                'total_participants' => count($participants),
                'mode' => $mode,
                'verify_code' => $verifyCode,
                'status' => 'processing'
            ]);

            if (!$lotteryId) {
                return [
                    'success' => false,
                    'message' => '创建抽奖记录失败'
                ];
            }

            // 7. 执行抽奖算法
            $winners = $this->drawWinners($participants, $winnerCount);

            // 8. 保存中奖名单
            foreach ($winners as $index => $winner) {
                $this->db->insert('lottery_winners', [
                    'lottery_id' => $lotteryId,
                    'weibo_uid' => $winner['uid'],
                    'weibo_name' => $winner['name'],
                    'weibo_screen_name' => $winner['name'],
                    'rank' => $index + 1
                ]);
            }

            // 9. 更新抽奖记录状态
            $this->db->update(
                'lottery_records',
                [
                    'status' => 'completed',
                    'completed_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                [':id' => $lotteryId]
            );

            // 10. 记录日志
            logAction($userId, 'lottery', "完成抽奖，ID: {$lotteryId}");

            // 11. 生成中奖话术
            $announcement = $this->generateAnnouncement($winners, $verifyCode);

            return [
                'success' => true,
                'data' => [
                    'lottery_id' => $lotteryId,
                    'verify_code' => $verifyCode,
                    'total_participants' => count($participants),
                    'winners' => $winners,
                    'announcement' => $announcement
                ]
            ];

        } catch (Exception $e) {
            error_log("抽奖失败: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '抽奖失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取参与用户
     */
    private function getParticipants($weiboId, $lotteryType) {
        $participants = [];

        switch ($lotteryType) {
            case 'like':
                // 获取点赞用户
                $result = $this->weiboApi->getLikeUsers($weiboId, 1000);
                if ($result['success']) {
                    $participants = $result['data'];
                }
                break;

            case 'comment':
                // 获取评论用户
                $result = $this->weiboApi->getCommentUsers($weiboId, 1000);
                if ($result['success']) {
                    $participants = $result['data'];
                }
                break;

            case 'repost':
                // 获取转发用户
                $result = $this->weiboApi->getRepostUsers($weiboId, 1000);
                if ($result['success']) {
                    $participants = $result['data'];
                }
                break;

            case 'mixed':
                // 混合模式：同时点赞、评论、转发的用户
                $likeResult = $this->weiboApi->getLikeUsers($weiboId, 1000);
                $commentResult = $this->weiboApi->getCommentUsers($weiboId, 1000);
                $repostResult = $this->weiboApi->getRepostUsers($weiboId, 1000);

                // 找出同时满足条件的用户
                $likeUids = [];
                if ($likeResult['success']) {
                    foreach ($likeResult['data'] as $user) {
                        $likeUids[$user['uid']] = $user;
                    }
                }

                $commentUids = [];
                if ($commentResult['success']) {
                    foreach ($commentResult['data'] as $user) {
                        $commentUids[$user['uid']] = $user;
                    }
                }

                $repostUids = [];
                if ($repostResult['success']) {
                    foreach ($repostResult['data'] as $user) {
                        $repostUids[$user['uid']] = $user;
                    }
                }

                // 取交集
                $validUids = array_intersect_key($likeUids, $commentUids, $repostUids);
                $participants = array_values($validUids);
                break;
        }

        return $participants;
    }

    /**
     * 抽取中奖用户（随机算法）
     */
    private function drawWinners($participants, $winnerCount) {
        if (empty($participants) || $winnerCount <= 0) {
            return [];
        }

        $winners = [];
        $participantsCopy = $participants;

        // 随机抽取
        for ($i = 0; $i < $winnerCount && !empty($participantsCopy); $i++) {
            $randomIndex = array_rand($participantsCopy);
            $winners[] = $participantsCopy[$randomIndex];
            unset($participantsCopy[$randomIndex]);
            $participantsCopy = array_values($participantsCopy);  // 重新索引
        }

        return $winners;
    }

    /**
     * 生成验证码
     */
    private function generateVerifyCode() {
        return strtoupper(substr(md5(time() . rand(1000, 9999)), 0, 8));
    }

    /**
     * 生成中奖公告
     */
    private function generateAnnouncement($winners, $verifyCode) {
        $announcement = "🎉 恭喜以下用户中奖！\n\n";

        foreach ($winners as $index => $winner) {
            $rank = $index + 1;
            $announcement .= "🏆 第{$rank}名：@{$winner['name']}\n";
        }

        $announcement .= "\n验证码：{$verifyCode}\n";
        $announcement .= "请中奖用户私信联系领奖！\n\n";
        $announcement .= "本次抽奖由@神奇奶酪微博抽奖系统提供技术支持";

        return $announcement;
    }

    /**
     * 查询抽奖记录
     */
    public function getLotteryRecord($lotteryId, $userId = null) {
        $where = 'id = :id';
        $params = [':id' => $lotteryId];

        if ($userId !== null) {
            $where .= ' AND user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        $record = $this->db->fetchOne(
            "SELECT * FROM {prefix}lottery_records WHERE {$where}",
            $params
        );

        if (!$record) {
            return [
                'success' => false,
                'message' => '抽奖记录不存在'
            ];
        }

        // 获取中奖名单
        $winners = $this->db->fetchAll(
            "SELECT * FROM {prefix}lottery_winners WHERE lottery_id = :lottery_id ORDER BY rank ASC",
            [':lottery_id' => $lotteryId]
        );

        return [
            'success' => true,
            'data' => [
                'record' => $record,
                'winners' => $winners
            ]
        ];
    }

    /**
     * 验证抽奖验证码
     */
    public function verifyLottery($verifyCode) {
        $record = $this->db->fetchOne(
            "SELECT * FROM {prefix}lottery_records WHERE verify_code = :code",
            [':code' => $verifyCode]
        );

        if (!$record) {
            return [
                'success' => false,
                'message' => '验证码不存在'
            ];
        }

        // 获取中奖名单
        $winners = $this->db->fetchAll(
            "SELECT * FROM {prefix}lottery_winners WHERE lottery_id = :lottery_id ORDER BY rank ASC",
            [':lottery_id' => $record['id']]
        );

        return [
            'success' => true,
            'data' => [
                'record' => $record,
                'winners' => $winners
            ]
        ];
    }

    /**
     * 删除抽奖记录
     */
    public function deleteLottery($lotteryId, $userId) {
        // 验证权限
        $record = $this->db->fetchOne(
            "SELECT * FROM {prefix}lottery_records WHERE id = :id AND user_id = :user_id",
            [':id' => $lotteryId, ':user_id' => $userId]
        );

        if (!$record) {
            return [
                'success' => false,
                'message' => '抽奖记录不存在或无权删除'
            ];
        }

        // 删除中奖名单
        $this->db->delete('lottery_winners', 'lottery_id = :id', [':id' => $lotteryId]);

        // 删除抽奖记录
        $this->db->delete('lottery_records', 'id = :id', [':id' => $lotteryId]);

        logAction($userId, 'delete_lottery', "删除抽奖记录，ID: {$lotteryId}");

        return [
            'success' => true,
            'message' => '删除成功'
        ];
    }
}
