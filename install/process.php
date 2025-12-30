<?php
/**
 * 安装处理程序
 * @神奇奶酪
 */

define('IN_INSTALL', true);
require_once dirname(__DIR__) . '/includes/config.php';

// 检查是否已安装
if (isInstalled()) {
    jsonResponse(false, '系统已安装，无法重复安装');
}

// 检查请求方法
if (!isPost()) {
    jsonResponse(false, '无效的请求方法');
}

$action = post('action');

switch ($action) {
    case 'test_db':
        testDatabaseConnection();
        break;
    case 'install_db':
        installDatabase();
        break;
    case 'create_admin':
        createAdminAccount();
        break;
    default:
        jsonResponse(false, '无效的操作');
}

/**
 * 测试数据库连接
 */
function testDatabaseConnection() {
    $host = post('db_host');
    $port = post('db_port', 3306);
    $name = post('db_name');
    $user = post('db_user');
    $pass = post('db_pass');
    $prefix = post('db_prefix', 'wb_');

    // 验证必填字段
    if (empty($host) || empty($name) || empty($user)) {
        jsonResponse(false, '请填写完整的数据库信息');
    }

    // 验证表前缀格式
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
        jsonResponse(false, '表前缀只能包含字母、数字和下划线');
    }

    // 测试连接
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // 保存配置到session
        $_SESSION['install_db_config'] = [
            'host' => $host,
            'port' => $port,
            'name' => $name,
            'user' => $user,
            'pass' => $pass,
            'prefix' => $prefix
        ];

        jsonResponse(true, '数据库连接成功！');
    } catch (PDOException $e) {
        jsonResponse(false, '数据库连接失败：' . $e->getMessage());
    }
}

/**
 * 安装数据库
 */
function installDatabase() {
    // 获取配置
    if (!isset($_SESSION['install_db_config'])) {
        jsonResponse(false, '请先配置数据库连接');
    }

    $config = $_SESSION['install_db_config'];

    try {
        // 创建配置文件
        $configContent = "<?php\n";
        $configContent .= "/**\n";
        $configContent .= " * 数据库配置文件\n";
        $configContent .= " * @神奇奶酪\n";
        $configContent .= " */\n\n";
        $configContent .= "define('DB_HOST', '{$config['host']}');\n";
        $configContent .= "define('DB_NAME', '{$config['name']}');\n";
        $configContent .= "define('DB_USER', '{$config['user']}');\n";
        $configContent .= "define('DB_PASS', '{$config['pass']}');\n";
        $configContent .= "define('DB_PREFIX', '{$config['prefix']}');\n";
        $configContent .= "define('DB_PORT', {$config['port']});\n";

        $configFile = dirname(__DIR__) . '/config_db.php';
        if (file_put_contents($configFile, $configContent) === false) {
            jsonResponse(false, '配置文件写入失败，请检查目录权限');
        }

        // 连接数据库
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // 读取并执行SQL文件
        $sqlFile = dirname(__DIR__) . '/database.sql';
        if (!file_exists($sqlFile)) {
            jsonResponse(false, 'SQL文件不存在');
        }

        $sql = file_get_contents($sqlFile);
        $sql = str_replace('wb_', $config['prefix'], $sql);

        // 分割SQL语句
        $statements = explode(';', $sql);

        $pdo->beginTransaction();
        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && substr($statement, 0, 2) !== '--') {
                    $pdo->exec($statement);
                }
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            jsonResponse(false, '数据库初始化失败：' . $e->getMessage());
        }

        jsonResponse(true, '数据库安装成功！');
    } catch (Exception $e) {
        jsonResponse(false, '安装失败：' . $e->getMessage());
    }
}

/**
 * 创建管理员账户
 */
function createAdminAccount() {
    $username = post('admin_username');
    $email = post('admin_email');
    $password = post('admin_password');
    $passwordConfirm = post('admin_password_confirm');

    // 验证必填字段
    if (empty($username) || empty($email) || empty($password)) {
        jsonResponse(false, '请填写完整的管理员信息');
    }

    // 验证用户名
    if (strlen($username) < 3 || strlen($username) > 20) {
        jsonResponse(false, '用户名长度必须在3-20个字符之间');
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        jsonResponse(false, '用户名只能包含字母、数字和下划线');
    }

    // 验证邮箱
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, '邮箱格式不正确');
    }

    // 验证密码
    if (strlen($password) < 6) {
        jsonResponse(false, '密码长度至少6个字符');
    }

    if ($password !== $passwordConfirm) {
        jsonResponse(false, '两次输入的密码不一致');
    }

    try {
        // 加载数据库配置
        require_once dirname(__DIR__) . '/config_db.php';

        // 连接数据库
        $db = DB::getInstance();

        // 检查用户名是否已存在
        $exists = $db->exists('users', 'username = :username', [':username' => $username]);
        if ($exists) {
            jsonResponse(false, '用户名已存在');
        }

        // 检查邮箱是否已存在
        $exists = $db->exists('users', 'email = :email', [':email' => $email]);
        if ($exists) {
            jsonResponse(false, '邮箱已被使用');
        }

        // 创建管理员账户
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userId = $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => 'admin',
            'is_vip' => 1,
            'status' => 1
        ]);

        if (!$userId) {
            jsonResponse(false, '管理员账户创建失败');
        }

        // 创建安装锁
        $db->insert('install_lock', [
            'installed' => 1
        ]);

        // 记录日志
        logAction($userId, 'install', '系统安装完成，创建管理员账户');

        // 清除session
        unset($_SESSION['install_db_config']);

        jsonResponse(true, '管理员账户创建成功！', ['user_id' => $userId]);
    } catch (Exception $e) {
        jsonResponse(false, '创建失败：' . $e->getMessage());
    }
}
