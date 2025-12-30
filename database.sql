-- ===================================================
-- 微博抽奖系统数据库结构
-- @神奇奶酪
-- ===================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";

-- ===================================================
-- 用户表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `is_vip` tinyint(1) DEFAULT 0,
  `vip_expire` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=正常 0=封禁',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `is_vip` (`is_vip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ===================================================
-- 抽奖记录表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_lottery_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `weibo_url` varchar(500) NOT NULL,
  `weibo_id` varchar(50) NOT NULL,
  `lottery_type` enum('like','comment','repost','mixed') NOT NULL COMMENT '抽奖类型',
  `filter_conditions` text COMMENT '筛选条件JSON',
  `winner_count` int(11) DEFAULT 1,
  `total_participants` int(11) DEFAULT 0,
  `mode` enum('vip','free') DEFAULT 'vip' COMMENT '抽奖模式',
  `verify_code` varchar(20) NOT NULL COMMENT '验证码',
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `weibo_id` (`weibo_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抽奖记录表';

-- ===================================================
-- 中奖名单表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_lottery_winners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_id` int(11) NOT NULL,
  `weibo_uid` varchar(50) NOT NULL,
  `weibo_name` varchar(100) NOT NULL,
  `weibo_screen_name` varchar(100) DEFAULT NULL,
  `rank` int(11) DEFAULT 1 COMMENT '中奖排名',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lottery_id` (`lottery_id`),
  KEY `weibo_uid` (`weibo_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='中奖名单表';

-- ===================================================
-- 网站设置表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `setting_type` varchar(20) DEFAULT 'text' COMMENT 'text/number/boolean/json',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站设置表';

-- 插入默认设置
INSERT INTO `wb_site_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', '微博抽奖系统', 'text', '网站名称'),
('site_description', '专业的微博抽奖工具平台', 'text', '网站描述'),
('site_keywords', '微博抽奖,抽奖工具,微博营销', 'text', '网站关键词'),
('enable_register', '1', 'boolean', '是否开放注册'),
('enable_vip', '0', 'boolean', '是否启用会员功能'),
('vip_price', '99', 'number', '会员价格(元)'),
('vip_duration', '365', 'number', '会员有效期(天)'),
('copyright', '@神奇奶酪', 'text', '版权信息'),
('icp_number', '', 'text', 'ICP备案号');

-- ===================================================
-- API密钥配置表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_key` varchar(100) NOT NULL,
  `app_secret` varchar(100) NOT NULL,
  `access_token` varchar(255) DEFAULT NULL,
  `token_expire` datetime DEFAULT NULL,
  `callback_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微博API密钥配置表';

-- ===================================================
-- 支付配置表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_type` enum('qrcode','alipay','wechat') NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `config_data` text COMMENT '配置数据JSON',
  `qrcode_image` varchar(255) DEFAULT NULL COMMENT '收款码图片路径',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_type` (`payment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付配置表';

-- 插入默认支付配置
INSERT INTO `wb_payment_settings` (`payment_type`, `is_enabled`) VALUES
('qrcode', 0),
('alipay', 0),
('wechat', 0);

-- ===================================================
-- 交易记录表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `payment_type` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `product_type` varchar(50) DEFAULT 'vip' COMMENT '商品类型',
  `product_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','paid','cancelled','refunded') DEFAULT 'pending',
  `pay_time` datetime DEFAULT NULL,
  `trade_no` varchar(100) DEFAULT NULL COMMENT '第三方交易号',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='交易记录表';

-- ===================================================
-- 安全设置表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_security_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='安全设置表';

-- 插入默认安全设置
INSERT INTO `wb_security_settings` (`setting_key`, `setting_value`) VALUES
('enable_email_verify', '0'),
('enable_math_verify', '1'),
('smtp_host', ''),
('smtp_port', '465'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_from_email', ''),
('smtp_from_name', '微博抽奖系统');

-- ===================================================
-- 系统日志表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统日志表';

-- ===================================================
-- 邮箱验证码表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_email_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `expire_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `expire_at` (`expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮箱验证码表';

-- ===================================================
-- 安装锁定表
-- ===================================================
CREATE TABLE IF NOT EXISTS `wb_install_lock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `installed` tinyint(1) DEFAULT 1,
  `install_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='安装锁定表';
