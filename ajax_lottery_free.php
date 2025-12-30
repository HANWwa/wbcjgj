<?php
/**
 * 免费抽奖AJAX处理
 * @神奇奶酪
 */

require_once __DIR__ . '/includes/config.php';

// 检查是否已安装
if (!isInstalled()) {
    jsonResponse(false, '系统未安装');
}

// 检查登录状态
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(false, '请先登录');
}

// 检查请求方法
if (!isPost()) {
    jsonResponse(false, '无效的请求方法');
}

$action = post('action');
$userId = $auth->getCurrentUserId();

// 根据不同的操作执行相应的功能
switch ($action) {
    case 'validate_cookie':
        handleValidateCookie();
        break;
    case 'parse_weibo_free':
        handleParseWeiboFree();
        break;
    case 'start_lottery_free':
        handleStartLotteryFree($userId, $auth);
        break;
    default:
        jsonResponse(false, '无效的操作');
}

/**
 * 验证Cookie
 */
function handleValidateCookie() {
    $cookie = post('cookie');

    if (empty($cookie)) {
        jsonResponse(false, '请输入Cookie');
    }

    // 基本Cookie格式验证
    if (!strpos($cookie, 'SUB=') && !strpos($cookie, 'SUBP=')) {
        jsonResponse(false, 'Cookie格式不正确，请确保包含完整的Cookie信息');
    }

    // 尝试验证Cookie是否有效
    $isValid = testWeiboCookie($cookie);

    if ($isValid) {
        jsonResponse(true, 'Cookie验证成功', ['valid' => true]);
    } else {
        jsonResponse(false, 'Cookie验证失败，请检查Cookie是否正确或已过期');
    }
}

/**
 * 解析微博链接（免费版）
 */
function handleParseWeiboFree() {
    $weiboUrl = post('weibo_url');
    $cookie = post('cookie');

    if (empty($weiboUrl)) {
        jsonResponse(false, '请输入微博链接');
    }

    if (empty($cookie)) {
        jsonResponse(false, '请先配置Cookie');
    }

    // 创建免费版微博API实例
    $weiboApi = new WeiboAPIFree($cookie);

    // 解析链接
    $weiboId = $weiboApi->parseWeiboUrl($weiboUrl);
    if (!$weiboId) {
        jsonResponse(false, '无法解析微博链接，请检查链接格式');
    }

    // 获取微博信息
    $weiboInfo = $weiboApi->getWeiboInfo($weiboId);
    if (!$weiboInfo['success']) {
        jsonResponse(false, '获取微博信息失败：' . ($weiboInfo['message'] ?? '请检查Cookie是否有效'));
    }

    // 返回微博信息
    jsonResponse(true, '解析成功', [
        'weibo_id' => $weiboId,
        'like_count' => $weiboInfo['data']['attitudes_count'] ?? 0,
        'comment_count' => $weiboInfo['data']['comments_count'] ?? 0,
        'repost_count' => $weiboInfo['data']['reposts_count'] ?? 0,
        'text' => $weiboInfo['data']['text'] ?? ''
    ]);
}

/**
 * 开始抽奖（免费版）
 */
function handleStartLotteryFree($userId, $auth) {
    $weiboUrl = post('weibo_url');
    $cookie = post('cookie');
    $lotteryType = post('lottery_type');
    $winnerCount = (int)post('winner_count');

    // 验证参数
    if (empty($weiboUrl)) {
        jsonResponse(false, '请输入微博链接');
    }

    if (empty($cookie)) {
        jsonResponse(false, '请先配置Cookie');
    }

    if (!in_array($lotteryType, ['like', 'comment', 'repost', 'mixed'])) {
        jsonResponse(false, '无效的抽奖类型');
    }

    // 免费版限制
    if ($winnerCount < 1 || $winnerCount > 50) {
        jsonResponse(false, '中奖人数必须在1-50之间');
    }

    // 创建免费版抽奖引擎实例
    $lottery = new LotteryEngineFree($cookie);

    // 执行抽奖
    $result = $lottery->executeLottery($userId, $weiboUrl, $lotteryType, $winnerCount, 'free');

    if ($result['success']) {
        jsonResponse(true, '抽奖成功', $result['data']);
    } else {
        jsonResponse(false, $result['message']);
    }
}

/**
 * 测试Cookie是否有效
 */
function testWeiboCookie($cookie) {
    try {
        // 简单测试：请求微博首页
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://weibo.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 检查是否成功获取页面且包含登录信息
        return $httpCode == 200 && !empty($response) && strpos($response, '$CONFIG') !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 免费版微博API类
 */
class WeiboAPIFree {
    private $cookie;
    private $headers = [];

    public function __construct($cookie) {
        $this->cookie = $cookie;
        $this->headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer: https://weibo.com',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        ];
    }

    /**
     * 解析微博链接
     */
    public function parseWeiboUrl($url) {
        $weiboApi = new WeiboAPI();
        return $weiboApi->parseWeiboUrl($url);
    }

    /**
     * 获取微博详情
     */
    public function getWeiboInfo($weiboId) {
        $url = "https://weibo.com/ajax/statuses/show?id={$weiboId}";
        $response = $this->request($url);

        if ($response && !isset($response['error'])) {
            return [
                'success' => true,
                'data' => [
                    'id' => $response['id'] ?? $weiboId,
                    'text' => $response['text_raw'] ?? $response['text'] ?? '',
                    'user' => $response['user'] ?? [],
                    'attitudes_count' => $response['attitudes_count'] ?? 0,
                    'comments_count' => $response['comments_count'] ?? 0,
                    'reposts_count' => $response['reposts_count'] ?? 0,
                    'created_at' => $response['created_at'] ?? '',
                ]
            ];
        }

        return [
            'success' => false,
            'message' => '获取微博信息失败'
        ];
    }

    /**
     * 获取点赞用户列表
     */
    public function getLikeUsers($weiboId, $count = 200) {
        $users = [];
        $page = 1;
        $limit = min($count, 200);  // 免费版最多200

        while (count($users) < $limit && $page <= 10) {
            $url = "https://weibo.com/ajax/statuses/repostTimeline?id={$weiboId}&page={$page}&count=20";
            $response = $this->request($url);

            if (!$response || isset($response['error']) || empty($response['data'])) {
                break;
            }

            foreach ($response['data'] as $item) {
                if (isset($item['user'])) {
                    $users[] = [
                        'uid' => $item['user']['id'],
                        'name' => $item['user']['screen_name'],
                        'avatar' => $item['user']['profile_image_url'] ?? '',
                    ];

                    if (count($users) >= $limit) {
                        break 2;
                    }
                }
            }

            $page++;
        }

        return [
            'success' => true,
            'data' => $users,
            'total' => count($users)
        ];
    }

    /**
     * 获取评论用户列表
     */
    public function getCommentUsers($weiboId, $count = 200) {
        $users = [];
        $uidMap = [];
        $page = 1;
        $limit = min($count, 200);

        while (count($users) < $limit && $page <= 10) {
            $url = "https://weibo.com/ajax/statuses/buildComments?is_reload=1&id={$weiboId}&is_show_bulletin=2&is_mix=0&count=20&page={$page}";
            $response = $this->request($url);

            if (!$response || isset($response['error']) || empty($response['data'])) {
                break;
            }

            foreach ($response['data'] as $comment) {
                if (isset($comment['user'])) {
                    $uid = $comment['user']['id'];
                    if (!isset($uidMap[$uid])) {
                        $users[] = [
                            'uid' => $uid,
                            'name' => $comment['user']['screen_name'],
                            'avatar' => $comment['user']['profile_image_url'] ?? '',
                        ];
                        $uidMap[$uid] = true;

                        if (count($users) >= $limit) {
                            break 2;
                        }
                    }
                }
            }

            $page++;
        }

        return [
            'success' => true,
            'data' => $users,
            'total' => count($users)
        ];
    }

    /**
     * 获取转发用户列表
     */
    public function getRepostUsers($weiboId, $count = 200) {
        $users = [];
        $uidMap = [];
        $page = 1;
        $limit = min($count, 200);

        while (count($users) < $limit && $page <= 10) {
            $url = "https://weibo.com/ajax/statuses/repostTimeline?id={$weiboId}&page={$page}&count=20";
            $response = $this->request($url);

            if (!$response || isset($response['error']) || empty($response['data'])) {
                break;
            }

            foreach ($response['data'] as $repost) {
                if (isset($repost['user'])) {
                    $uid = $repost['user']['id'];
                    if (!isset($uidMap[$uid])) {
                        $users[] = [
                            'uid' => $uid,
                            'name' => $repost['user']['screen_name'],
                            'avatar' => $repost['user']['profile_image_url'] ?? '',
                        ];
                        $uidMap[$uid] = true;

                        if (count($users) >= $limit) {
                            break 2;
                        }
                    }
                }
            }

            $page++;
        }

        return [
            'success' => true,
            'data' => $users,
            'total' => count($users)
        ];
    }

    /**
     * 发送HTTP请求
     */
    private function request($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("免费版微博API请求失败: {$error}");
            return false;
        }

        if ($httpCode != 200) {
            error_log("免费版微博API返回错误状态码: {$httpCode}");
            return false;
        }

        return json_decode($response, true);
    }
}

/**
 * 免费版抽奖引擎类
 */
class LotteryEngineFree {
    private $db;
    private $weiboApi;

    public function __construct($cookie) {
        $this->db = DB::getInstance();
        $this->weiboApi = new WeiboAPIFree($cookie);
    }

    /**
     * 执行抽奖
     */
    public function executeLottery($userId, $weiboUrl, $lotteryType, $winnerCount, $mode = 'free') {
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
            logAction($userId, 'lottery_free', "完成免费抽奖，ID: {$lotteryId}");

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
            error_log("免费抽奖失败: " . $e->getMessage());
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
                $result = $this->weiboApi->getLikeUsers($weiboId, 200);
                if ($result['success']) {
                    $participants = $result['data'];
                }
                break;

            case 'comment':
                $result = $this->weiboApi->getCommentUsers($weiboId, 200);
                if ($result['success']) {
                    $participants = $result['data'];
                }
                break;

            case 'repost':
                $result = $this->weiboApi->getRepostUsers($weiboId, 200);
                if ($result['success']) {
                    $participants = $result['data'];
                }
                break;

            case 'mixed':
                $likeResult = $this->weiboApi->getLikeUsers($weiboId, 200);
                $commentResult = $this->weiboApi->getCommentUsers($weiboId, 200);
                $repostResult = $this->weiboApi->getRepostUsers($weiboId, 200);

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

                $validUids = array_intersect_key($likeUids, $commentUids, $repostUids);
                $participants = array_values($validUids);
                break;
        }

        return $participants;
    }

    /**
     * 抽取中奖用户
     */
    private function drawWinners($participants, $winnerCount) {
        if (empty($participants) || $winnerCount <= 0) {
            return [];
        }

        $winners = [];
        $participantsCopy = $participants;

        for ($i = 0; $i < $winnerCount && !empty($participantsCopy); $i++) {
            $randomIndex = array_rand($participantsCopy);
            $winners[] = $participantsCopy[$randomIndex];
            unset($participantsCopy[$randomIndex]);
            $participantsCopy = array_values($participantsCopy);
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
}
