<?php
$GLOBALS['HTTP_RAW_POST_DATA']=file_get_contents("php://input");
file_put_contents('./pay.log', "\r\n".date('Y-m-d H:i:s', time())."\r\n".$GLOBALS['HTTP_RAW_POST_DATA'], FILE_APPEND | LOCK_EX);

error_reporting(0);

$_POST = array();
$_GET = array();
$_GET['g'] = 'Home';
$_GET['m'] = 'Wechat';
$_GET['a'] = 'wechatPayNotify';
include_once("index.php");

