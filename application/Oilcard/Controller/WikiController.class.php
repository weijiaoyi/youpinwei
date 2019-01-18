<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/14
 * Time: 15:08
 */
namespace Oilcard\Controller;

use Org\Util\phpqrcode;
use Comment\Controller\CommentoilcardController;

class WikiController extends CommentoilcardController
{
    private $appId = 'C9q255qIg1Zp72yI';
    private $merchantSn='PHT2017000000002';
    private $my_uri = 'http://ysy.xiangjianhai.com/TestWxNotify.php';
    private $pay_uri = 'https://open.smart4s.com/Api/Service/Pay/Mode/JSApi/tradePayJSApi';
    private  $private_key = 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBALqZX4pzN6jxJbNbXhuz+Drh8Obt7ekDrEPz2SK0IKoay6SDPiJMJXLqh69doiWjP2pim6/JrrsuBr3QFMjGIx0EnBSf354qorWNhkj+lkAcQnQ98NlziTgTg7vx2o3piCcJAa7i2WhbLegs1xtatwSeEY/weqJwZh7dOxmelEsJAgMBAAECgYAxNW9HsLjV+bpKgWbhAWYOCTWhgM+D6q8MQItbposSsPxRRzckjlY15vmfWp7/M/zuTlDmW9aTkEDA39YLWI07jsmaGOA8RbPinswzIWnowNVFQag/n21tpAL2/CGNkpe+7F667nZyD7htCYwz6ARBMUM+eH52MNEMcPSbOBM9PQJBAPUav3oCgnYx/F8nLzlW9+gSOD1oCK5GQUC1+TTwaPUfZeCl8CeHT/7DgdvyUUMm9CyEzhacl4xZPzWN+ijZIF8CQQDC5NfMtDHSCGknwMnZb4mxTpzrby+pnwVvxmJeOg+QTafAwHqIhh9wVLQNEJy0PojYOMpjA9GE1Wms537Pnq2XAkEArCkij3/NxVms6+UpHXyB2ydZC4DUgBzm3p4zMkUfY/Wu6JGF0y4POWJ4B1b4T1PANLj/zRAmvrU9Wc+lBCYmvwJBALv94esjJatjUYt2+z0xya+uFM9EwMTtD2FyCxC5EKoxPc8/2vI17b189vBjRcTXTUjD/vTjigaHlRejdT7v4KECQA2DEOHgZv39PmNIZJekcfNGXPrdHPU0eAEtanMCr6hTiN+jO4x66rrGXIa4aoZ3ezXq/sASLm4zuGuF65m9bnc=';
    private $public_key = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1BjaenGX21GHyjopCrNW8mLrZCG1uNMTSbgHjV9uwywPHJ1SaV/FInbgWGvQ9MTlRTRBE4+XMJEx4hlHMG87BnrQFjGV9EH7QK5Fi651908q5WhDLXDLrWAn19ZpVbLbwZuzY+xICGCLjoQxFi0lHk0eU9h8QZOS0WnplnC0L1r/y3PTn5R4+W9pD/Diibh+hGmELmQaS3lHVWEvuYQLEepuT0U32kiHzty66bGaT7za6CiUtQKBx8khwdpeKwaX+c2kgKJ6QbhitSxrHD9eG5RHKdGipyhvEzT/ba3sQvEZwipePc0y6i/lPPoBwNULiICQjS0a3w6+D11YXdutsQIDAQAB';
    public function __construct()
    {
        parent::__construct();
        header('content-type:text/html;charset=utf-8');
    }

    /**
     * 申请
     */
    public function agentPay($OrderInfo,$data,$openid)
    {
        //微信统一下单
        $data = [];
        $data['signType'] = 'RSA';//签名类型
        $data['appId'] = $this->appId;//开发者APPid
        $data['merchantSn'] = $this->merchantSn;//商户编号
        $data['outTradeNo'] = $OrderInfo['serial_number'];//商户订单号
        $data['tradeType'] = 'WX';//支付类型
        $data['totalFee'] = $OrderInfo['real_pay']*100;//总金额
        $data['userId'] = $openid;//用户openid
        $data['attach'] = '缴纳年费';
        $data['notifyUrl'] = $this->my_uri;
        $sign = $this->setRSASign($data);

        $con = curl_init((string)$this->pay_uri);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_POSTFIELDS, json_encode($sign, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($sign, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ]
        );
        curl_setopt($con, CURLOPT_TIMEOUT, (int)5);
        $content= curl_exec($con);
        // 处理返回结果
        $this->handleResponse($content);
    }

    /**
     * 把请求参数添加签名
     * @param array $requestData
     * @return array
     */
    public function setRSASign($requestData){
        $sign = $this->RSASign($requestData, $this->private_key);
        $requestData['sign'] = $sign;
        return $requestData;
    }

    /**
     * 签名
     * @param array $sign_data
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function RSASign($sign_data,$path){
        if (empty($path))
            $this->error('私钥不存在');
        foreach ($sign_data as $k => $v) {
            if (is_array($v))
                $sign_data[$k] = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        ksort($sign_data);
        $sign_str = '';
        foreach ($sign_data as $k => $v) {
            $sign_str .= $k . '=' . $v . '&';
        }
        $sign_str = trim($sign_str, '&');
        $private_key_content = $path;
        $sign = '';
        $pem = chunk_split($private_key_content, 64, "\n");
        $pem = "-----BEGIN RSA PRIVATE KEY-----\n$pem-----END RSA PRIVATE KEY-----\n";
        $is_pass = openssl_sign($sign_str, $sign, $pem, OPENSSL_ALGO_SHA1);
        if ($is_pass) {
            $result = base64_encode($sign);
            return $result;
        } else {
            $this->error('证书不可用');
        }
    }

    /**
     * 验证签名
     * @param array $sign_data
     * @return bool
     * @throws Exception
     */
    public function verifyRSASign($sign_data){
        $public_key_path = $this->public_key;
        if (empty($public_key_path))
        $this->error('公钥不存在');
        $sign = $sign_data['sign'];
        if (!isset($sign_data['sign']))
        $this->error('签名字段不存在');
        unset($sign_data['sign']);
        foreach ($sign_data as $k => $v) {
            if (is_array($v))
                $sign_data[$k] = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        ksort($sign_data);
        $sign_str = '';
        foreach ($sign_data as $k => $v) {
            $sign_str .= $k . '=' . $v . '&';
        }
        $sign_str = trim($sign_str, '&');
        $public_content = file_get_contents($public_key_path);

        $pem = chunk_split($public_content, 64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n$pem-----END PUBLIC KEY-----\n";

        $result = (bool)openssl_verify($sign_str, base64_decode($sign), $pem, OPENSSL_ALGO_SHA1);
        return $result;
    }

    /**
     * 处理返回结果
     * @param string $res
     * @param Demo $demo
     */
    public function handleResponse($res) {
        $res = json_decode($res, true);
        $verifyResult = false;
        $verifyResult = $this->verifyRSASign($res);
        if ($verifyResult){
            // todo::验签成功
            echo '验签成功';
        } else {
            // todo::验签失败
            echo '验签失败';
        }
    }

}