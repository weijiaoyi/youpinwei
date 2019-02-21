<?php
/**
 * Author       : BadCat
 * CreatedTime  : 2018/8/23 16:12
 * Description : .....2298820
 */
namespace Oilcard\Conf;
class QFPayConfig {

    private $requestUrl ='https://openapi-test.qfpay.com/';
    private $APP_CODE = 'AAAAFF893B354F66BBAFA41EF6B324C1';
    private $KEY = '6A7984FC3020463A971FB1DB061EE4A9';

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
        $header = array("X-QF-APPCODE: ".$this->APP_CODE, "X-QF-SIGN: ". $this->make_req_sign($data, $this->KEY));
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
        curl_close($ch);
        $header = substr($result, 0, $hsize-4);
        $body = substr($result, $hsize);
        $headerdict = explode("\r\n", $header);
        $sign = "";
        for ($i=1; $i<count($headerdict); $i++) {
            $line = $headerdict[$i];
            if (strncmp($line, "X-QF-SIGN", 9) == 0) {
                $sign = trim(substr($line, 10));
                break;
            }
        }
        if ($sign && $sign != $this->make_resp_sign($body, $this->KEY)) {
            return array(
                'msg'       => 'respinse sign check error:签名错误',
                'sign'      => $sign,
                'resp_sign' => $this->make_resp_sign($body, $this->KEY),
            );
        }
        return $body;
    }
}
