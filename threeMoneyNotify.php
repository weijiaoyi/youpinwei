<?php
$GLOBALS['HTTP_RAW_POST_DATA']=file_get_contents("php://input");
file_put_contents('./oilCardPay.log', "\r\n".date('Y-m-d H:i:s', time())."\r\n".$GLOBALS['HTTP_RAW_POST_DATA'], FILE_APPEND | LOCK_EX);

error_reporting(0);

$_POST = array();
$_GET = array();
$_GET['g'] = 'oilcard';
$_GET['m'] = 'ThreePay';
$_GET['a'] = 'threeNoticePay';
include_once("index.php");

