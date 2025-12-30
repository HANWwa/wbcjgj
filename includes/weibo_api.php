<?php
/**
 * 微博API类
 * @神奇奶酪
 */

class WeiboAPI {
    private $appKey;
    private $appSecret;
    private $accessToken;
    private $apiBase = 'https://api.weibo.com/2/';

    /**
     * 构造函数
     */
    public function __construct($appKey = null, $appSecret = null, $accessToken = null) {
        if ($appKey && $appSecret) {
            $this->appKey = $appKey;
            $this->appSecret = $appSecret;
            $this->accessToken = $accessToken;
        } else {
            // 从数据库加载配置
            $this->loadConfig();
        }
    }

    /**
     * 从数据库加载API配置
     */
    private function loadConfig() {
        $db = DB::getInstance();
        $config = $db->fetchOne(
            "SELECT * FROM {prefix}api_keys WHERE is_active = 1 ORDER BY id DESC LIMIT 1"
        );

        if ($config) {
            $this->appKey = $config['app_key'];
            $this->appSecret = $config['app_secret'];
            $this->accessToken = $config['access_token'];
        }
    }

    /**
     * 解析微博链接，提取微博ID
     */
    public function parseWeiboUrl($url) {
        // 支持多种微博链接格式
        $patterns = [
            '/weibo\.com\/\d+\/([a-zA-Z0-9]+)/',  // PC端长链接
            '/m\.weibo\.cn\/status\/(\d+)/',       // 移动端链接
            '/weibo\.com\/\w+\/([a-zA-Z0-9]+)/',  // 用户主页链接
            '/t\.cn\/([a-zA-Z0-9]+)/',            // 短链接
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }

    /**
     * 获取微博详情
     */
    public function getWeiboInfo($weiboId) {
        $endpoint = 'statuses/show.json';
        $params = [
            'id' => $weiboId,
            'access_token' => $this->accessToken
        ];

        $response = $this->request($endpoint, $params);

        if ($response && isset($response['id'])) {
            return [
                'success' => true,
                'data' => [
                    'id' => $response['id'],
                    'text' => $response['text'] ?? '',
                    'user' => $response['user'] ?? [],
                    'attitudes_count' => $response['attitudes_count'] ?? 0,  // 点赞数
                    'comments_count' => $response['comments_count'] ?? 0,    // 评论数
                    'reposts_count' => $response['reposts_count'] ?? 0,      // 转发数
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
        $endpoint = 'attitudes/show.json';
        $params = [
            'id' => $weiboId,
            'count' => min($count, 200),  // 微博API限制单次最多200条
            'access_token' => $this->accessToken
        ];

        $response = $this->request($endpoint, $params);

        if ($response && isset($response['attitudes'])) {
            $users = [];
            foreach ($response['attitudes'] as $item) {
                if (isset($item['user'])) {
                    $users[] = [
                        'uid' => $item['user']['id'],
                        'name' => $item['user']['screen_name'],
                        'avatar' => $item['user']['profile_image_url'] ?? '',
                    ];
                }
            }
            return [
                'success' => true,
                'data' => $users,
                'total' => count($users)
            ];
        }

        return [
            'success' => false,
            'message' => '获取点赞列表失败',
            'data' => []
        ];
    }

    /**
     * 获取评论用户列表
     */
    public function getCommentUsers($weiboId, $count = 200) {
        $endpoint = 'comments/show.json';
        $params = [
            'id' => $weiboId,
            'count' => min($count, 200),
            'access_token' => $this->accessToken
        ];

        $response = $this->request($endpoint, $params);

        if ($response && isset($response['comments'])) {
            $users = [];
            $uidMap = [];  // 去重

            foreach ($response['comments'] as $comment) {
                if (isset($comment['user'])) {
                    $uid = $comment['user']['id'];
                    if (!isset($uidMap[$uid])) {
                        $users[] = [
                            'uid' => $uid,
                            'name' => $comment['user']['screen_name'],
                            'avatar' => $comment['user']['profile_image_url'] ?? '',
                        ];
                        $uidMap[$uid] = true;
                    }
                }
            }

            return [
                'success' => true,
                'data' => $users,
                'total' => count($users)
            ];
        }

        return [
            'success' => false,
            'message' => '获取评论列表失败',
            'data' => []
        ];
    }

    /**
     * 获取转发用户列表
     */
    public function getRepostUsers($weiboId, $count = 200) {
        $endpoint = 'statuses/repost_timeline.json';
        $params = [
            'id' => $weiboId,
            'count' => min($count, 200),
            'access_token' => $this->accessToken
        ];

        $response = $this->request($endpoint, $params);

        if ($response && isset($response['reposts'])) {
            $users = [];
            $uidMap = [];  // 去重

            foreach ($response['reposts'] as $repost) {
                if (isset($repost['user'])) {
                    $uid = $repost['user']['id'];
                    if (!isset($uidMap[$uid])) {
                        $users[] = [
                            'uid' => $uid,
                            'name' => $repost['user']['screen_name'],
                            'avatar' => $repost['user']['profile_image_url'] ?? '',
                        ];
                        $uidMap[$uid] = true;
                    }
                }
            }

            return [
                'success' => true,
                'data' => $users,
                'total' => count($users)
            ];
        }

        return [
            'success' => false,
            'message' => '获取转发列表失败',
            'data' => []
        ];
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo($uid) {
        $endpoint = 'users/show.json';
        $params = [
            'uid' => $uid,
            'access_token' => $this->accessToken
        ];

        $response = $this->request($endpoint, $params);

        if ($response && isset($response['id'])) {
            return [
                'success' => true,
                'data' => [
                    'uid' => $response['id'],
                    'name' => $response['screen_name'],
                    'avatar' => $response['profile_image_url'] ?? '',
                    'description' => $response['description'] ?? '',
                ]
            ];
        }

        return [
            'success' => false,
            'message' => '获取用户信息失败'
        ];
    }

    /**
     * 发送HTTP请求
     */
    private function request($endpoint, $params = [], $method = 'GET') {
        $url = $this->apiBase . $endpoint;

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("微博API请求失败: {$error}");
            return false;
        }

        if ($httpCode != 200) {
            error_log("微博API返回错误状态码: {$httpCode}");
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error_code'])) {
            error_log("微博API错误: " . ($data['error'] ?? '未知错误'));
            return false;
        }

        return $data;
    }

    /**
     * 验证Access Token是否有效
     */
    public function verifyAccessToken() {
        if (empty($this->accessToken)) {
            return false;
        }

        $endpoint = 'account/get_uid.json';
        $params = [
            'access_token' => $this->accessToken
        ];

        $response = $this->request($endpoint, $params);

        return $response && isset($response['uid']);
    }

    /**
     * 刷新Access Token（如果支持）
     */
    public function refreshAccessToken() {
        // TODO: 实现刷新逻辑
        // 微博API的refresh token机制需要根据实际文档实现
        return false;
    }

    /**
     * 获取授权URL
     */
    public function getAuthUrl($redirectUri, $state = '') {
        $params = [
            'client_id' => $this->appKey,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state ?: md5(time())
        ];

        return 'https://api.weibo.com/oauth2/authorize?' . http_build_query($params);
    }

    /**
     * 通过授权码获取Access Token
     */
    public function getAccessToken($code, $redirectUri) {
        $url = 'https://api.weibo.com/oauth2/access_token';
        $params = [
            'client_id' => $this->appKey,
            'client_secret' => $this->appSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];

            // 保存到数据库
            $db = DB::getInstance();
            $expireTime = isset($data['expires_in']) ?
                date('Y-m-d H:i:s', time() + $data['expires_in']) : null;

            $db->update(
                'api_keys',
                [
                    'access_token' => $data['access_token'],
                    'token_expire' => $expireTime
                ],
                'app_key = :app_key',
                [':app_key' => $this->appKey]
            );

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'] ?? 0
            ];
        }

        return [
            'success' => false,
            'message' => $data['error_description'] ?? '获取Access Token失败'
        ];
    }
}
