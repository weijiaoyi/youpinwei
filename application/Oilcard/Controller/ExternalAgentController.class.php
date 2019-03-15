<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 15:36
 */
namespace Oilcard\Controller;
use Comment\Controller\CommentoilcardController;
use Oilcard\Conf\CardConfig;

class ExternalAgentController extends CommentoilcardController
{
    public function __construct(){
        parent::__construct();
    }

    
    public function RegisterMember(){

        $key = 'sdfsdfdsfsdafsdfsdafsadf';
        $arr = $this->encryptStr('sssss',$key);
        echo json_encode(array('st'=>$arr));
        exit;
    }

    /**
     * 数据加密
     * @Author   Mr.Wang
     * @DateTime 2019-02-15
     */
    public function DataEncrypt(){

    }

    /**
     * 数据解密
     * @Author   Mr.Wang
     * @DateTime 2019-02-15
     */
    public function DataDecrypt(){

    }

    // 加密
    public function encryptStr($str, $key){
      $block = mcrypt_get_block_size('des', 'ecb');
      $pad = $block - (strlen($str) % $block);
      $str .= str_repeat(chr($pad), $pad);
      $enc_str = mcrypt_encrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
      return base64_encode($enc_str);
    }
    // 解密
    public function decryptStr($str, $key){
      $str = base64_decode($str);
      $str = mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
      $block = mcrypt_get_block_size('des', 'ecb');
      $pad = ord($str[($len = strlen($str)) - 1]);
      return substr($str, 0, strlen($str) - $pad);
    }


}