CREATE TABLE IF NOT EXISTS `db_admin` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '权限组ID',
  `username` varchar(64) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` char(32) NOT NULL DEFAULT '' COMMENT '密码',
  `salt` char(6) NOT NULL DEFAULT '' COMMENT '干扰码',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态:1可用 0禁用',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上次登录时间',
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员';

CREATE TABLE IF NOT EXISTS `db_admin_group` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `p_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级管理组ID',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '管理组名称',
  `menu` text NOT NULL COMMENT '管理组权限',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态:1可用 0禁用',
  `sort` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `p_id` (`p_id`),
  KEY `status` (`status`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理组';

CREATE TABLE IF NOT EXISTS `db_admin_menu` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `p_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级菜单ID',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `module` varchar(32) NOT NULL DEFAULT '' COMMENT '模块',
  `controller` varchar(32) NOT NULL DEFAULT '' COMMENT '控制器',
  `action` varchar(32) NOT NULL DEFAULT '' COMMENT '方法',
  `router` varchar(128) NOT NULL DEFAULT '' COMMENT '路由',
  `icon` varchar(32) NOT NULL DEFAULT '' COMMENT '图标',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态:1可用 0禁用',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型:1菜单 2按钮',
  `system` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '框架菜单',
  `sort` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `p_id` (`p_id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `system` (`system`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='菜单';

CREATE TABLE IF NOT EXISTS `db_admin_behavior` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `manager_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(64) NOT NULL DEFAULT '' COMMENT '管理员昵称',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `router` varchar(128) NOT NULL DEFAULT '' COMMENT '路由地址',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '完整Url',
  `param` varchar(255) NOT NULL DEFAULT '' COMMENT '访问参数',
  `is_ajax` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '请求方式',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '记录时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员行为日志';


CREATE TABLE IF NOT EXISTS `db_verify` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `module_id` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '验证类型',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1手机 2邮箱',
  `receive` varchar(64) NOT NULL DEFAULT '' COMMENT '接收者',
  `code` char(6) NOT NULL DEFAULT '' COMMENT '验证码',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '内容',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态:1已验证 0未验证',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  KEY `type` (`type`),
  KEY `receive` (`receive`),
  KEY `code` (`code`),
  KEY `status` (`status`),
  KEY `add_time` (`add_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='验证码';

CREATE TABLE IF NOT EXISTS `db_verify_module` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '模板名称',
  `module` varchar(32) NOT NULL DEFAULT '' COMMENT '模板标识',
  `template` varchar(255) NOT NULL DEFAULT '' COMMENT '模板内容',
  PRIMARY KEY (`id`),
  KEY `module` (`module`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='验证码模板';

CREATE TABLE IF NOT EXISTS `db_file` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '文件原名',
  `size` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `ext` varchar(10) NOT NULL DEFAULT '' COMMENT '扩展名',
  `savepath` varchar(48) NOT NULL DEFAULT '' COMMENT '保存路径',
  `path` varchar(64) NOT NULL DEFAULT '' COMMENT '全相对路径',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文件';


CREATE TABLE IF NOT EXISTS `db_advert` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `position_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '位置ID',
  `file_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '图片ID',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '广告位名称',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '广告类型 1.ID 2.链接',
  `value` varchar(255) NOT NULL DEFAULT '' COMMENT '广告跳转值',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
   PRIMARY KEY (`id`),
   KEY `position_id` (`position_id`),
   KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='广告';

CREATE TABLE IF NOT EXISTS `db_advert_position` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '广告位名称',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='广告位置';

CREATE TABLE IF NOT EXISTS `db_article` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文章分类',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布管理员',
  `nickname` varchar(64) NOT NULL DEFAULT '' COMMENT '管理员昵称',
  `cover` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文章封面',
  `title` varchar(128) NOT NULL DEFAULT '' COMMENT '文章名称',
  `content` text NOT NULL COMMENT '文章内容',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `sort` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `view` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  PRIMARY KEY (`id`),
  KEY `cate_id` (`cate_id`),
  KEY `status` (`status`),
  KEY `sort` (`sort`),
  KEY `view` (`view`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文章';

CREATE TABLE IF NOT EXISTS `db_article_cate` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `p_id` int(10) NOT NULL DEFAULT '0' COMMENT '父分类ID',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '分类名称',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `p_id` (`p_id`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文章分类';

CREATE TABLE IF NOT EXISTS `db_config` (
  `id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '配置名称',
  `key` varchar(64) NOT NULL DEFAULT '' COMMENT '配置项',
  `value` varchar(255) NOT NULL DEFAULT '' COMMENT '配置值',
  PRIMARY KEY (`id`),
  KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='站点配置';
