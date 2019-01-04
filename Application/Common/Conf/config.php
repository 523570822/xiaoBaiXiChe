<?php
$config = array(
    /* 模块相关配置 */
    'DEFAULT_MODULE'     => 'Home',
    'MODULE_DENY_LIST'   => array('Common'),
    'MODULE_ALLOW_LIST'  => array('Manager','Home','Api','Wap'),
    'DB_FIELD_CACHE'     => false,
    'API_URL'=>"http://".$_SERVER['HTTP_HOST'],
    'TMPL_PARSE_STRING' =>  array(
        '__IND__'=>'/Public/Index/img',
        '__INDCSS__'=>'/Public/Index/css',
        '__INDJS__'=>'/Public/Index/js',
        '__INTCSS__'=>'/Public/Integral/css',
        '__INT__'=>'/Public/Integral/images',
        '__INTJS__'=>'/Public/Integral/js',
    ),

);
$config_path = APP_PATH.'Common/conf/';
// 数据库配置
$mysql_config = require 'mysql.php';
if(!is_array($mysql_config)) $mysql_config = array();
// 网站配置
$website_config = require 'website.php';
if(!is_array($website_config)) $website_config = array();
//// 合并配置并返回
return array_merge($config, $mysql_config, $website_config);