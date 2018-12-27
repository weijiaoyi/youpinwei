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
}