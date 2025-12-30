<?php
/**
 * 数据库操作类
 * @神奇奶酪
 */

class DB {
    private static $instance = null;
    private $pdo = null;
    private $prefix = '';

    /**
     * 构造函数
     */
    private function __construct() {
        try {
            $this->prefix = DB_PREFIX;
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }

    /**
     * 获取实例（单例模式）
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取PDO对象
     */
    public function getPdo() {
        return $this->pdo;
    }

    /**
     * 获取表前缀
     */
    public function getPrefix() {
        return $this->prefix;
    }

    /**
     * 执行SQL查询
     */
    public function query($sql, $params = []) {
        try {
            $sql = str_replace('{prefix}', $this->prefix, $sql);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    /**
     * 查询单行数据
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }

    /**
     * 查询多行数据
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * 查询单个值
     */
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchColumn();
        }
        return false;
    }

    /**
     * 插入数据
     */
    public function insert($table, $data) {
        $table = $this->prefix . $table;
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = ':' . implode(', :', $keys);

        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$placeholders})";

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        if ($this->query($sql, $params)) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * 更新数据
     */
    public function update($table, $data, $where, $whereParams = []) {
        $table = $this->prefix . $table;

        $set = [];
        $params = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}` = :{$key}";
            $params[':' . $key] = $value;
        }
        $setString = implode(', ', $set);

        // 合并where参数
        $params = array_merge($params, $whereParams);

        $sql = "UPDATE `{$table}` SET {$setString} WHERE {$where}";

        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * 删除数据
     */
    public function delete($table, $where, $params = []) {
        $table = $this->prefix . $table;
        $sql = "DELETE FROM `{$table}` WHERE {$where}";

        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * 统计记录数
     */
    public function count($table, $where = '1', $params = []) {
        $table = $this->prefix . $table;
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where}";
        return $this->fetchColumn($sql, $params);
    }

    /**
     * 检查记录是否存在
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }

    /**
     * 开始事务
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * 获取最后插入的ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * 执行SQL文件
     */
    public function executeSqlFile($file) {
        if (!file_exists($file)) {
            return false;
        }

        $sql = file_get_contents($file);
        $sql = str_replace('{prefix}', $this->prefix, $sql);

        // 分割SQL语句
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && substr($stmt, 0, 2) !== '--';
            }
        );

        try {
            $this->beginTransaction();
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->pdo->exec($statement);
                }
            }
            $this->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollback();
            error_log("SQL File Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 测试数据库连接
     */
    public static function testConnection($host, $user, $pass, $dbname) {
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * 防止克隆
     */
    private function __clone() {}

    /**
     * 防止反序列化
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
