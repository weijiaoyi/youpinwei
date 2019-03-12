<?php
/**
 * Author       : BadCat
 * CreatedTime  : 2018/8/23 16:12
 * Description : .....2298820
 */
namespace Oilcard\Conf;
class QFPayConfig {

    private $requestUrl ;
    private $APP_CODE ;
    private $KEY ;

    public function __construct($payType=1){
        //钱方支付不支持 自定义设置 支付异步回调地址 ，只能一个code 和key 对应一个回调地址
        //所以由钱方后台设置多个code和key ，以 满足不同的需求对应不同的地址
        switch ($payType) {
            case '1': //1，申领 agentMoneyNotify.php
                $this->requestUrl = 'http://openapi.quanyipay.com/';//'https://openapi-test.qfpay.com/';
                $this->APP_CODE   = '901AEC7024CD40BCB2A548B10941EE3A';
                $this->KEY        = '64D98349B8774B3E9A79BF2CB7616D8D';
                break;
            case '2': //2，申领 agentMoneyNotify.php
                # code...
                $this->requestUrl = 'http://openapi.quanyipay.com/';//'https://openapi-test.qfpay.com/';
                $this->APP_CODE   = '901AEC7024CD40BCB2A548B10941EE3A';
                $this->KEY        = '64D98349B8774B3E9A79BF2CB7616D8D';
                break;
            case '3': //3，充值 addMoneyNotify.php
                # code...
                $this->requestUrl = 'http://openapi.quanyipay.com/';//'https://openapi-test.qfpay.com/';
                $this->APP_CODE   = '915F8B1756B54ABB84151FE62BE67EF2';
                $this->KEY        = '0E52AFEE2F9D49859085D8EC7AFFC57D';
                break;
            case '4': ////4升级 upgradeNotify.php
                # code...
                $this->requestUrl = 'http://openapi.quanyipay.com/';//'https://openapi-test.qfpay.com/';
                $this->APP_CODE   = 'BACD85C59A7447E2AFABA96D50E90B58';
                $this->KEY        = 'F9916B8D04D74898B7A9EA45B74A8D0D';
                break;
            case '5': //5续费 upgradeNotify.php
                # code...
                $this->requestUrl = 'http://openapi.quanyipay.com/';//'https://openapi-test.qfpay.com/';
                $this->APP_CODE   = 'BACD85C59A7447E2AFABA96D50E90B58';
                $this->KEY        = 'F9916B8D04D74898B7A9EA45B74A8D0D';
                break;
            case '6': //网信 或者其他外部
                # code...
                $this->requestUrl = 'http://openapi.quanyipay.com/';//'https://openapi-test.qfpay.com/';
                $this->APP_CODE   = '53CA91F4691F4935887A6DF12B8984A8';
                $this->KEY        = 'F31CE258DCE34795A2FBC72095BF6224';
                break;
            default: //默认使用的key和code
                $this->requestUrl = 'http://openapi.quanyipay.com/';//'https://openapi-test.qfpay.com/';
                $this->APP_CODE   = 'EBEEFB4A63CB45C2A667F3A6C9F76C12';
                $this->KEY        = 'C6FBE7410F9848F48ADD977A002A8F21';
                break;
        }
    }

    /**
     * 生产签名
     * @Author   Mr.Wang
     * @DateTime 2019-02-19
     * @param    [type]     $data [description]
     * @param    [type]     $key  [description]
     * @return   [type]           [description]
     */
    public function make_req_sign($data, $key) {
        ksort($data);

        $p = array();
        foreach ($data as $k => $v) {
            array_push($p, "$k=$v");
        }

        $s = join("&", $p) . $key;

        $ret = strtoupper(md5($s));
        return $ret;
    }
    /**
     * 生产签名
     * @Author   Mr.Wang
     * @DateTime 2019-02-19
     * @param    [type]     $data [description]
     * @param    [type]     $key  [description]
     * @return   [type]           [description]
     */
    public function make_resp_sign($data, $key) {
        return strtoupper(md5($data . $key));
    }

    /**
     * 生产支付数据
     * @Author   Mr.Wang
     * @DateTime 2019-02-19
     * @param    [type]     $name [description]
     * @param    [type]     $data [description]
     * @return   [type]           [description]
     */
    public function request($name, $data) {

        $url = $this->requestUrl . "/trade/v1/" . $name . "?" . http_build_query($data);

        $header = array("X-QYF-APPCODE: ".$this->APP_CODE, "X-QYF-SIGN: ". $this->make_req_sign($data, $this->KEY));
        

        // p($url);
        //var_dump($url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);  
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $result = curl_exec($ch);

        $info = curl_getinfo($ch);

        $hsize = $info['header_size'];
        // p($info);
        //var_dump($info);

        curl_close($ch);

        $header = substr($result, 0, $hsize-4);
        
        $body = substr($result, $hsize);

        // echo "header:".$header.'\n';
        // echo "body:".$body.'\n';

        $headerdict = explode("\r\n", $header);
        // p($headerdict);

        //var_dump($headerdict);
        $sign = "";
        $signkey = "X-QYF-SIGN";
        $keylen = strlen($signkey);
        for ($i=1; $i<count($headerdict); $i++) {
            $line = $headerdict[$i];
            if (strncmp($line, $signkey, $keylen) == 0) {
                $sign = trim(substr($line, $keylen+1));
                break;
            }
        }
        // p($sign);
        if ($sign && $sign != $this->make_resp_sign($body, $this->KEY)) {
            exit(json_encode(['msg'=>'签名验证失败','status'=>500]));
        }
        return $body;
    }
}
