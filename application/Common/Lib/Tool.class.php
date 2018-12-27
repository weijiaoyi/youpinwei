<?php
namespace Common\Lib;

class Tool{
	/**
	 * 检测手机号格式
	 * @param  [type] $phone [description]
	 * @return [type] $phone | false [description]
	 */
	public static function checkPhone($phone){
		if (!is_numeric($phone)) {
        	return false;
    	}
    	return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $phone) ? true : false;
	}

    public static function randomStr($len,$isnum=false)
    {
        $str = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';//62个字符
        $strlen = strlen($str);
        if($isnum)
        {
            $str = '1234567890';
            $strlen = strlen($str);
        }
        while($len > $strlen){
            $str .= $str;
            $strlen += strlen($str);
        }
        $str = str_shuffle($str);
        return substr($str,0,$len);
    }

    public static function getClientIp() {
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            // for php-cli(phpunit etc.)
            $ip = defined('PHPUNIT_RUNNING') ? '127.0.0.1' : gethostbyname(gethostname());
        }

        if($ip == '::1') $ip = '127.0.0.1';

        return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
    }
}