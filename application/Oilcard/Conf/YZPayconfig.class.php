<?php
/**
 * Created by PhpStorm.
 * User: yvette
 * Date: 2019/3/13
 * Time: 下午3:18
 */

namespace Oilcard\Conf;


class YZPayconfig
{
    public function request($json_arr)
    {
        $data = array();
        foreach($json_arr as $k=>$var){
            if(is_scalar($var) && $var !== '' && $var !== null){//如果给出的变量参数 var 是一个标量，is_scalar() 返回 TRUE，否则返回 FALSE。标量变量是指那些包含了 integer、float、string 或 boolean的变量，而 array、object 和 resource 则不是标量。
                $data[$k] = $var;
            }else if(is_object($var)){
                $data[$k] =array_filter((array) $var);
            }else if(is_array($var)){
                $data[$k] =array_filter($var);
            }
            if(empty($data[$k])){
                unset($data[$k]);
            }
        }//foreach -end

        ksort($data);//按照 键名 对关联数组进行升序排序：

        $hmacSource = '';
        foreach($data as $key => $value){
            if (is_array($value)) {
                ksort($value);
                foreach ($value as $key2 => $value2) {

                    if (is_object($value2)) {
                        $value2 = array_filter((array)$value2);
                        ksort($value2);
                        foreach ($value2 as $oKey => $oValue) {
                            $oValue .= '#';
                            $hmacSource .= trim($oValue);

                        }
                    } else if(is_array($value2)){
                        ksort($value2);
                        foreach ($value2 as $key3 => $value3) {
                            if (is_object($value3)) {
                                $value3 = array_filter((array)$value3);
                                ksort($value3);
                                foreach ($value3 as $oKey => $oValue) {
                                    $oValue .= '#';
                                    $hmacSource .= trim($oValue);
                                }
                            } else{
                                $value3 .= '#';
                                $hmacSource .= trim($value3);
                            }
                        }
                    } else{
                        $value2 .= '#';
                        $hmacSource .= trim($value2);
                    }
                }
            } else {
                $value .= '#';
                $hmacSource .= trim($value);
            }
        }
        $sha1mac=sha1($hmacSource,true); //SHA1加密

        $pubKey = file_get_contents('client.pfx');//私钥签名
        $results=array();
        $worked=openssl_pkcs12_read($pubKey,$results,'123456');
        $rs=openssl_sign($sha1mac,$hmac,$results['pkey'],"md5");
        $hmac=base64_encode($hmac);


        $hmacarr=array();
        $hmacarr["hmac"]=$hmac;

        $arr_t=(array_merge($json_arr,$hmacarr)); //合并数组
        $json_str=json_encode($arr_t,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $str1='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $randStr = str_shuffle($str1);//打乱字符串
        $rands= substr($randStr,0,16);

        $screct_key = $rands;
        $str = trim($json_str);
        $str= $this->addPKCS7Padding($str);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_ECB),MCRYPT_RAND);
        $encrypt_str =  mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_ECB, $iv);
        $date = base64_encode($encrypt_str);
        $verifyKey4Server = file_get_contents('test.cer');  //公钥加密AES
        $pem = chunk_split(base64_encode($verifyKey4Server),64,"\n");//转换为pem格式的公钥
        $public_key = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
        $pu_key =  openssl_pkey_get_public($public_key);//这个函数可用来判断公钥是否是可用的
        openssl_public_encrypt($rands,$encryptKey,$pu_key);//公钥加密
        $encryptKey = base64_encode($encryptKey);

        $url="https://apis.5upay.com/onlinePay/order";
        //$url="https://api.ehking.com/onlinePay/order";

        $curl = curl_init($url);
        curl_setopt($curl,CURLOPT_HEADER, 1 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_HTTPHEADER,array(
            'Content-Type: application/vnd.5upay-v3.0+json',
            //'Content-Type: application/vnd.ehking-v2.0+json',
            'encryptKey: '.$encryptKey,
            'merchantId: '.$json_arr["merchantId"],
            'requestId: '.$json_arr["requestId"]
        ));
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl,CURLOPT_POST,true); // post传输数据
        curl_setopt($curl,CURLOPT_POSTFIELDS,$date);// post传输数据
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证

        $responseText = curl_exec($curl);


        if (curl_errno($curl) || $responseText === false) {
            curl_close($curl);
            throw new InvalidRequestException(array(
                'error_description'=> 'Request Error'
            ));
        }
        curl_close($curl);

        preg_match_all('/(encryptKey|merchantId|data"):(\s+|")([^"\s]+)/s',$responseText,$m);
        list($encryptKey, $merchantId, $data) = $m[3];
        $responsedata = array("data" =>$data,"encryptKey"=>$encryptKey,"merchantId"=>$merchantId);


        $encryptKey =$responsedata['encryptKey'];
        $pubKey = file_get_contents('client.pfx');
        $results=array();
        $worked=openssl_pkcs12_read($pubKey,$results,'123456');
        $private_key=$results['pkey'];
        $pi_key =  openssl_pkey_get_private($private_key);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        openssl_private_decrypt(base64_decode($encryptKey),$decrypted,$pi_key);//私钥解密
        $responsedatadata=$responsedata['data'];
        $date = base64_decode($responsedatadata);
        $screct_key = $decrypted;
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_ECB),MCRYPT_RAND);
        $encrypt_str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $screct_key, $date, MCRYPT_MODE_ECB, $iv);
        $encrypt_str = preg_replace('/[\x00-\x1F]/','',$encrypt_str);
        return $encrypt_str;

    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    public function addPKCS7Padding($source){
        $source = trim($source);
        $block = mcrypt_get_block_size('rijndael-128', 'ecb');
        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }
}