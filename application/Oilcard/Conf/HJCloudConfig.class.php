<?php
/**
 * Author       : BadCat
 * CreatedTime  : 2018/8/23 16:12
 * Description : .....
 */
namespace Oilcard\Conf;
class HJCloudConfig {

    private $requestUrl;
    private $requestData;
    private $privateKey;
    private $publicKey;

    /**
     * 设置请求地址
     * @param string $url
     */
    public function setRequestUrl( $url) {
        $this->requestUrl = $url;
    }

    /**
     * 设置请求参数
     * @param array $requestData
     */
    public function setRequestData($requestData) {
        $this->requestData = $this->setRSASign($requestData);
    }

    /**
     * 设置私钥文件
     * @param string $keyFile
     */
    public function setPrivateKey( $keyFile) {
        $this->privateKey = $keyFile;
    }

    /**
     * 设置公钥文件
     * @param string $keyFile
     */
    public function setPublicKey( $keyFile) {
        $this->publicKey = $keyFile;
    }

    /**
     * 发起POST请求
     * @return string
     * @throws Exception
     */
    public function doRequest() {
        if (!$this->requestUrl)
            throw new Exception('未设置请求地址');
        if (!$this->requestData)
            throw new Exception('未设置请求参数');
        if (!$this->privateKey)
            throw new Exception('未设置请求私钥');
        $con = curl_init((string)$this->requestUrl);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_POSTFIELDS, json_encode($this->requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($this->requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ]
        );
        curl_setopt($con, CURLOPT_TIMEOUT, (int)5);
        return curl_exec($con);
    }

    /**
     * 把请求参数添加签名
     * @param array $requestData
     * @return array
     */
    private function setRSASign($requestData) {
        $sign = $this->RSASign($requestData, $this->privateKey);
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
    private function RSASign($sign_data, $path) {
        if (empty($path))
            throw new Exception('私钥不存在');
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
        $private_key_content = file_get_contents($path);
        $sign = '';
        $pem = chunk_split($private_key_content, 64, "\n");
        $pem = "-----BEGIN RSA PRIVATE KEY-----\n$pem-----END RSA PRIVATE KEY-----\n";
        $is_pass = openssl_sign($sign_str, $sign, $pem, OPENSSL_ALGO_SHA1);
        if ($is_pass) {
            $result = base64_encode($sign);
            return $result;
        } else {
            throw new Exception('证书不可用');
        }
    }

    /**
     * 验证签名
     * @param array $sign_data
     * @return bool
     * @throws Exception
     */
    public function verifyRSASign($sign_data){
        $public_key_path = $this->publicKey;
        if (!file_exists($public_key_path))
            throw new Exception('公钥不存在');
        $sign = $sign_data['sign'];
        if (!isset($sign_data['sign']))
            throw new Exception('签名字段不存在');
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
}
