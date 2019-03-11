<?php
/**
 * Created by PhpStorm.
 * User: yvette
 * Date: 2019/3/7
 * Time: 上午11:14
 */
namespace Oilcard\Controller;
use Common\Lib\Tool;
use Common\Lib\XML;
use Endroid\QrCode\QrCode;
use Oilcard\Conf\CardConfig;
use Oilcard\Conf\HJCloudConfig;
use Oilcard\Conf\QFPayConfig;
use Think\Controller;
use Think\Log;
use Org\Util\phpqrcode;
use Comment\Controller\CommentoilcardController;
class ThreePayController extends CommentoilcardController
{


    //第三方充值异步回调
    public function threeNoticePay(){
        $data = file_get_contents('php://input');
        $obj_arr = XML::parse($data);
        if (!$obj_arr) {
            $obj_arr= json_decode($data,TRUE);
        }

        Log::record('微信回调data:'.json_encode($obj_arr));
        $insert = [];
        $insert['content']['InsertTime'] = date('Y-m-d H:i:s',time());
        $insert['content']['InsertNote'] = '油卡充值';
        $insert['content']['input'] = $obj_arr;
        $insert['content']['return'] = I('post.');
        $insert['content']['data'] = $data;
        $insert['content']['hh'] = 1;


        $sign = $obj_arr['sign'];
        unset($obj_arr['sign']);
        ksort($obj_arr);
        $string1 = urldecode(http_build_query($obj_arr).'&key='.CardConfig::$wxconf['pay_key']);
        $cur_sign = strtoupper(MD5($string1));

        $obj_arr['paymentType'] = 'WxPay';
        if (isset($obj_arr['event'])) {
            $obj_arr['out_trade_no']   = $obj_arr['outTradeNo'];
            $obj_arr['transaction_id'] = $obj_arr['reqId'];
            $obj_arr['result_code']    = $obj_arr['tradeStatus']==1?'SUCCESS':'FAIL';
            $obj_arr['openid']         = $obj_arr['payDetailInfo']['wxSubOpenId'];
            $obj_arr['paymentType']    = 'HjPay';
        }elseif(isset($obj_arr['syssn'])){
            $obj_arr['transaction_id'] = $obj_arr['syssn'];
            $obj_arr['result_code']    = $obj_arr['status']==1?'SUCCESS':'FAIL';
            $obj_arr['paymentType']    = 'QFPay';

        }


        //签名验证
        if( ($cur_sign === $sign && $obj_arr['paymentType'] == 'WxPay' ) || ($obj_arr['paymentType'] == 'HjPay' && $obj_arr['tradeStatus']==1) || ($obj_arr['respcd']=='0000' && $obj_arr['paymentType'] == 'QFPay' )) {
            $insert['content']['signs'] = '签名正确';
            $insert['content'] = json_encode($insert['content']);
            M('testt')->add($insert);
            $OrderSn = $obj_arr['out_trade_no'];

            $nowTime = time();
            //查询订单记录
            $OrderInfo =  M('three_order')->where(['order_number'=>$OrderSn])->find();
            $userInfo = M('three_user')->where(['three_user_id'=>$OrderInfo['three_user_id']])->find();

            if ($OrderInfo['pay_status']==2 && !empty($OrderInfo['pay_sn'])) {
                echo 'SUCCESS';exit;
                return $this->arrayToXml(['return_code'=>'SUCCESS','return_msg'=>'OK']);
            }
            //修改订单状态
            $threeOrder = [
                'pay_status' =>2,
                'pay_sn'     =>$obj_arr['transaction_id'],
                'pay_time'   => $nowTime,
            ];
            $saveMoney = $userInfo['already_save_money'] + $OrderInfo['save_amount'];
            $addMoney = $userInfo['total_add_money'] + $OrderInfo['real_amount'];
            //修改该用户的已省金额和已充值金额
            $User = [
                'already_save_money' => $saveMoney,
                'total_add_money'    => $addMoney,
                'update_time'        => $nowTime,
            ];
            $Things = M();
            $Things->startTrans();
            //查看该卡是不是第一次充值
            $is_first = M('three_order')->where(['card_no'=>$OrderInfo['card_no'],'pay_status'=>2])->find();
            if(!$is_first){
                //如果是第一次充值，将该卡状态设置为激活
                M('three_card')->where(['three_card_no'=>$OrderInfo['card_no']])->setField('is_activate',1);
            }


            $order = M('three_order')->where(['order_number'=>$OrderSn])->save($threeOrder);
            $user = M('three_user')->where(['three_user_id'=>$OrderInfo['three_user_id']])->save($User);
            $Tree = M('three')->where(['id'=>$userInfo['three_id']])->find();
            $url = $Tree['url'];
            if($order && $user) {
                $Things->commit();
                $param = [
                    'order_sn' => $OrderSn,
                    'pay_money' => $OrderInfo['real_amount'],
                    'phone'    => $userInfo['phone'],
                    'from'     =>$Tree['from'],
                    'return_code' => '100',
                    'return_msg' => '支付成功',
                    'pay_time' => $nowTime,
                ];
            }else{
                $Things->rollback();
                $param = [
                    'return_msg' => '支付成功，写入数据库失败',
                    'return_code' => '200',
                    'pay_time' => $nowTime,
                ];
            }
            //向外部--网信网通发送通知
            $this->CurlPost($url, $param);
            if ($obj_arr['paymentType'] == 'WxPay') {
                echo 'SUCCESS';exit;
            }elseif($obj_arr['paymentType'] == 'HjPay'){
                exit(json_encode(['result'=>'SUCCESS']));
            }elseif($obj_arr['paymentType'] == 'QFPay'){
                echo 'SUCCESS';exit;
            }
            return $this->arrayToXml(['return_code'=>'SUCCESS','return_msg'=>'OK']);
        }else{
            // echo 'FAIL';exit;
            $OrderSn = $obj_arr['out_trade_no'];
            $OrderInfo =  M('three_order')->where(['order_number'=>$OrderSn])->find();
            $userInfo = M('three_user')->where(['three_user_id'=>$OrderInfo['three_user_id']])->find();
            $Tree = M('three')->where(['id'=>$userInfo['three_id']])->find();
            $url = $Tree['url'];
            $param = [
                'return_msg' => '支付失败',
                'return_code' => '300',
            ];
            //向外部--网信网通发送通知
            $this->CurlPost($url, $param);
            if ($obj_arr['paymentType'] == 'WxPay') {
                echo 'FALI';exit;
            }elseif($obj_arr['paymentType'] == 'HjPay'){
                exit(json_encode(['result'=>'SUCCESS']));
            }elseif($obj_arr['paymentType'] == 'QFPay'){
                echo 'SUCCESS';exit;
            }
            return $this->arrayToXml(['return_code'=>'FAIL','return_msg'=>'支付失败']);
        }
        return XML::build($data);
    }

    public function CurlPost($url, $param = [],$is_post=1, $timeout = 5)
    {
        //初始化curl
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url); // 设置请求的路径
        if($is_post == 1){
            curl_setopt($curl, CURLOPT_POST, 0); //设置POST提交
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //显示输出结果 1 代表 把结果转化为字符串进行处理
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);

        //提交数据
        if($is_post == 1){
            if (is_array($param)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
            }
        }

        //执行请求
        $data = $data_str = curl_exec($curl);

        //处理错误
        if ($error = curl_error($curl)) {
            $log_data = array(
                'url' => $url,
                'param' => $param,
                'error' => '<span style="color:red;font-weight: bold">' . $error . '</span>',
            );

            var_dump($log_data);
            exit;
        }

        # 关闭CURL
        curl_close($curl);

        //json数据转换为数组
        $data = json_decode($data, true);

        if (!is_array($data)) {
            $data = $data_str;
        }

        #调用完接口写一个日志文件
        $log = [
            'url'=> $url,
            'param' => $param,
            'response' => $data_str
        ];
        file_put_contents(__DIR__.'/wechat.log',print_r($log,true));

        return $data;
    }


    public function arrayToXml(array $data)
    {
        $xml = "<xml>";
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                $xml .= "<{$k}>{$v}</{$k}>";
            } else {
                $xml .= "<{$k}><![CDATA[{$v}]]></{$k}>";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

}