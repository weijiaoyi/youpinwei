<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/14
 * Time: 15:08
 */

namespace Oilcard\Controller;

use Common\Lib\Tool;
use Common\Lib\XML;
use Endroid\QrCode\QrCode;
use Oilcard\Conf\CardConfig;
use Think\Controller;
use Think\Log;
use Org\Util\phpqrcode;
use Comment\Controller\CommentoilcardController;

//include './phpqrcode.class.php';

class WechatController extends CommentoilcardController
{
    private $appid;
    private $secret;
    static $card_number;
    static $card_no;
    static $data;
    private $base_uri = 'https://api.weixin.qq.com';
    private $my_uri = 'http://ysy.edshui.com';
    private $pay_uri = 'https://api.mch.weixin.qq.com';

    public function __construct()
    {
        parent::__construct();
        $this->appid = CardConfig::$wxconf['appid'];
        $this->secret = CardConfig::$wxconf['appsecret'];
    }

    public function getCode()
    {
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        $redirect_url = urlencode('http://'.$_SERVER['SERVER_NAME'].U('oilcard/wechat/getAccessToken'));
        $url = str_replace('APPID',$this->appid,$url);
        $url = str_replace('REDIRECT_URI',$redirect_url,$url);
        header('location:'.$url);
    }
 
    public function getCodeUrl()
    {
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        $redirect_url = urlencode('http://'.$_SERVER['SERVER_NAME'].U('oilcard/wechat/getAccessToken'));
        $url = str_replace('APPID',$this->appid,$url);
        $url = str_replace('REDIRECT_URI',$redirect_url,$url);
        echo json_encode(['msg'=>'success','status'=>1000,'url'=>$url]);
        exit();
    }

    public function getAccessToken()
    {
        try {
            $code = I('get.code');
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code';
            $url = str_replace('APPID', $this->appid, $url);
            $url = str_replace('SECRET', $this->secret, $url);
            $url = str_replace('CODE', $code, $url);
            $res = $this->curlGet($url);

            $info = json_decode($res, true);

            if (!is_array($info) || !isset($info['openid']))
            {
                echo json_encode([
                    'msg'=>'获取access_code失败！',
                    'status'=>500
                ]);
                exit();
            }

            $userinfo_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN';
            $userinfo_url = str_replace('ACCESS_TOKEN', $info['access_token'], $userinfo_url);
            $userinfo_url = str_replace('OPENID', $info['openid'], $userinfo_url);
            $userinfo = json_decode($this->curlGet($userinfo_url),true);
            if (!is_array($userinfo) || !isset($userinfo['openid']))
            {
                echo json_encode([
                    'msg'=>'获取用户信息失败！',
                    'status'=>500
                ]);
                exit();
            }

            $is_user = M('User')->where(['openid'=>$userinfo['openid']])->find();

            if (!$is_user || empty($is_user) || !isset($is_user['id'])){
                //注册新用户
                $user = array();
                $user['nickname'] = $userinfo['nickname'];
                $user['user_img'] = $userinfo['headimgurl'];
                $user['openid'] = $userinfo['openid'];
                $user['wx_access_token'] = $info['access_token'];
                $user['access_token_expires'] = $info['expires_in']+time();
                $user['refresh_token']=$info['refresh_token'];

                M('User')->add($user);

            }else {
                //更新用户信息
                $user = array();
                $user['nickname'] = $userinfo['nickname'];
                $user['user_img'] = $userinfo['headimgurl'];
                $user['wx_access_token'] = $info['access_token'];
                $user['access_token_expires'] = $info['expires_in']+time();
                $user['refresh_token']=$info['refresh_token'];

                M('User')->where(['openid'=>$userinfo['openid']])->save($user);
            }

            header('location:'.'http://'.$_SERVER['SERVER_NAME'].'/H/html/homepage.html?op='.base64_encode($userinfo['openid']));


        }catch (\Exception $e) {
            echo $e->getMessage();
            Log::write('[' . $e->getCode() . '] ' . $e->getMessage(), 'ERR');
            exit();
        }
    }


    /**
     * 微信充值下单接口
     */
    public function payOrder($order_id,$openid,$flag,$flage,$initial_money)
    {
        file_put_contents(__DIR__.'/data/'.$openid.'flag.txt',print_r($flag,true));
        file_put_contents(__DIR__.'/data/'.$openid.'flage.txt',print_r($flage,true));
        file_put_contents(__DIR__.'/data/'.$openid.'initial_money.txt',print_r($initial_money,true));
        $order_item = M('AddMoney')->where(['id'=>$order_id])->find();
        $out_trade_no = $order_item['order_no'];
        $card_no = $order_item['card_no'];
        file_put_contents(__DIR__.'/data/'.$openid.'card_no.txt',print_r($card_no,true));


        //微信统一下单
        $data = [];
        $data['appid'] = CardConfig::$wxconf['appid'];
        $data['mch_id'] = CardConfig::$wxconf['mch_id'];
        $data['device_info'] = 'WEB';
        $data['nonce_str'] = Tool::randomStr(20);
        $data['sign_type'] = 'MD5';
        $data['body'] = '中国石油加油卡';
        $data['detail'] = '购买加油卡';
        $data['attach'] = '充值购买';
        $data['out_trade_no'] = $out_trade_no;
        $data['fee_type'] = 'CNY';
        $data['total_fee'] = 1;//$order_item['real_pay'] * 100; // 分
        $data['spbill_create_ip'] = Tool::getClientIp();
        $data['time_start'] = date('YmdHis');
        $data['time_expire'] = date('YmdHis',time()+7200);
//        $data['notify_url'] = $this->my_uri.'/index.php?g=oilcard&m=wechat&a=wxNoticePay';
        $data['notify_url'] = $this->my_uri.'/addMoneyNotify.php';
        Log::record('notify_url:'.$data['notify_url']);

        $data['trade_type'] = 'JSAPI';
        $data['openid'] = $order_item['openid'];
        ksort($data);
        $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
        $data['sign'] = md5($string1);

        $content = XML::build($data);
        Log::record('传给微信的XML:'.$content);

        $ch_url = $this->pay_uri.'/pay/unifiedorder';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ch_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $content = curl_exec($ch);
        curl_close($ch);
        Log::record('微信统一下单返回:'.$content);

        $data = [];
        $obj_arr = XML::parse($content);
        if (!$obj_arr){
            return $data;
        }
        if($obj_arr['result_code'] == 'SUCCESS') {
            $data['appId'] = CardConfig::$wxconf['appid'];
            $data['timeStamp'] = time();
            $data['nonceStr'] = Tool::randomStr(20);
            $data['package'] = 'prepay_id='.$obj_arr['prepay_id'];
            $data['signType'] = 'MD5';

            ksort($data);
            $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
            $data['paySign'] = md5($string1);
        }

        return $data;

    }

    /**
     * 申请为银牌代理下单接口
     */
    public function agentPay($openid,$data,$money,$card_no,$from_id,$res,$checked_card='')
    {
        log::record('线下申领卡号：'.$checked_card);

        $money=$money-20;
        file_put_contents(__DIR__.'/data/'.$openid.'receive_person.txt',print_r($data['receive_person'],true));
        file_put_contents(__DIR__.'/data/'.$openid.'address.txt',print_r($data['address'],true));
        file_put_contents(__DIR__.'/data/'.$openid.'phone.txt',print_r($data['phone'],true));
        file_put_contents(__DIR__.'/data/'.$openid.'money.txt',print_r($money,true));
        file_put_contents(__DIR__.'/data/'.$openid.'from_id.txt',print_r($from_id,true));
        if (empty($checked_card)) {
            file_put_contents(__DIR__.'/data/'.$openid.'checked_card.txt',print_r('',true));
        }else{
            file_put_contents(__DIR__.'/data/'.$openid.'checked_card.txt',print_r($checked_card,true));
        }
        
        $date=date('YmdHis').str_pad(mt_rand(1,999999),6,STR_PAD_LEFT);
        //微信统一下单
        $data = [];
        $data['appid'] = CardConfig::$wxconf['appid'];
        $data['mch_id'] = CardConfig::$wxconf['mch_id'];
        $data['device_info'] = 'WEB';
        $data['nonce_str'] = Tool::randomStr(20);
        $data['sign_type'] = 'MD5';
        $data['body'] = '银牌代理年费';
        $data['detail'] = '办理银牌代理';
        $data['attach'] = '缴纳年费';
        $data['out_trade_no'] = $date;
        $data['fee_type'] = 'CNY';
        $data['total_fee'] = 1;//$money*100
        $data['spbill_create_ip'] = Tool::getClientIp();
        $data['time_start'] = date('YmdHis');
        $data['time_expire'] = date('YmdHis',time()+7200);

        $data['notify_url'] = $this->my_uri.'/agentMoneyNotify.php';

        $data['trade_type'] = 'JSAPI';
        $data['openid'] = $openid;
        ksort($data);
        $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
        $data['sign'] = md5($string1);

        $content = XML::build($data);

        $ch_url=$this->pay_uri.'/pay/unifiedorder';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ch_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $content = curl_exec($ch);
        curl_close($ch);
        Log::record('申请为银牌代理下单接口');
        $data = [];
        $obj_arr = XML::parse($content);

        if (!$obj_arr){
            return $data;
        }
        $agent_id=M('agent_relation')->where("openid='$openid'")->getField('agent_id');
        $agent_arr= M('agent')->where("id='$agent_id'")->find();

            Log::record('银牌申领:2');
            $userData=M('user')->where("openid='$openid'")->find(); 
        Log::record($userData);
        $res= M('user_apply')->where("id='$res'")->save(['serial_number'=>$date]);   //单独申领表添加申领信息（未支付成功）

        $vip_money=$money-20;
        if ($vip_money>0) {
            $order_record['preferential']=$vip_money*120;
            $order_record['preferential_type']=1;
        }
        

         //申领记录
        $order_record['user_id']         = $userData['id'];
        $order_record['order_type']      = 1;
        $order_record['serial_number']   = $date;
        $order_record['money']           = $money;
        $order_record['real_pay']        = $money;
        $order_record['discount_money']  = 0;
        $order_record['order_status']  = 1;
        $order_record['shop_name']  = '中国石油加油卡';
        $order_record['serial_name']  = $date;
        $record_res = M('order_record')->add($order_record);

        if($obj_arr['result_code'] == 'SUCCESS') {
            $data['appId'] = CardConfig::$wxconf['appid'];
            $data['timeStamp'] = time();
            $data['nonceStr'] = Tool::randomStr(20);
            $data['package'] = 'prepay_id='.$obj_arr['prepay_id'];
            $data['signType'] = 'MD5';

            ksort($data);
            $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
            $data['paySign'] = md5($string1);
        }
        return $data;

    }

    public function pay_apply($openId,$card_number){
        $userData=M('user')->where('openid="'.$openId.'"')->find();  //根据微信openid查询对应的用户
        if (empty($userData)) {
            $this->error('用户不存在');
        }
        $OilCardData=M('oil_card')->where('status=1 and discount=96')->limit($card_number)->select(); //从用户油卡表取卡
        foreach ($OilCardData as $key=>$val){
            $oilRes=M('oil_card')->where('id='.$val['id'])->save(['status'=>2]);
            if ($oilRes===false) {
                $this->error('油卡调用失败');
            }
        }

        $data=file_get_contents(__DIR__.'/data/'.$openId.'data.txt');
        $arr=(array)json_decode($data);
        $arr['user_id']=$userData['id'];
        $arr['status']='1';
        $arr['card_number']=$card_number;

        M('user_apply')->add($arr);   //添加申领信息

        for ($i=0;$i<$card_number;$i++){
            $OrderRecordData=[
                'user_id'=>$userData['id'],
                'order_type'=>1,
                'status'=>1,
                'money'=>$card_number*100,
                'discount_money'=>0,
                'real_pay'=>$card_number*100
            ];


            $res= M('order_record')->add($OrderRecordData);   //添加订单记录表记录

        }
        $this->templateMessage($openId,$data,1);

        $this->success('ok');
    }


    /**
     * 95卡升级为93卡下单接口
     */
    public function upgradePay(){
        $openid=I('post.openid','');
        $card_no=I('post.card_no','');
        $money=I('post.money','');
        file_put_contents(__DIR__.'/data/'.$openid.'money.txt',print_r($money,true));
        file_put_contents(__DIR__.'/data/'.$openid.'card_no.txt',print_r($card_no,true));
        //微信统一下单
        $data = [];
        $data['appid'] = CardConfig::$wxconf['appid'];
        $data['mch_id'] = CardConfig::$wxconf['mch_id'];
        $data['device_info'] = 'WEB';
        $data['nonce_str'] = Tool::randomStr(20);
        $data['sign_type'] = 'MD5';
        $data['body'] = '升级93卡年费费';
        $data['detail'] = '升级油卡为93折费';
        $data['attach'] = '油卡升级费';
        $data['out_trade_no'] = date('YmdHis').str_pad(mt_rand(1,999999),6,STR_PAD_LEFT);
        $data['fee_type'] = 'CNY';
        $data['total_fee'] = 1;//正确的是20000
        $data['spbill_create_ip'] = Tool::getClientIp();
        $data['time_start'] = date('YmdHis');
        $data['time_expire'] = date('YmdHis',time()+7200);
//        $data['notify_url'] = $this->my_uri.'/index.php?g=oilcard&m=wechat&a=wxNoticePay';
        $data['notify_url'] = $this->my_uri.'/upgradeNotify.php';
        Log::record('notify_url:'.$data['notify_url']);

        $data['trade_type'] = 'JSAPI';
        $data['openid'] = $openid;
        ksort($data);
        $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
        $data['sign'] = md5($string1);

        $content = XML::build($data);
        Log::record('传给微信的XML:'.$content);

        $ch_url = $this->pay_uri.'/pay/unifiedorder';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ch_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $content = curl_exec($ch);
        curl_close($ch);
        Log::record('微信统一下单返回:'.$content);

        $data = [];
        $obj_arr = XML::parse($content);


        if (!$obj_arr){
            $this->success($data);
        }
        if($obj_arr['result_code'] == 'SUCCESS') {
            $data['appId'] = CardConfig::$wxconf['appid'];
            $data['timeStamp'] = time();
            $data['nonceStr'] = Tool::randomStr(20);
            $data['package'] = 'prepay_id='.$obj_arr['prepay_id'];
            $data['signType'] = 'MD5';

            ksort($data);
            $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
            $data['paySign'] = md5($string1);
        }
        $this->success($data);
    }


    /**
     * 获取JS微信配置
     */
    public function jsConfig()
    {

        Log::record('获取JS微信配置:');
        $appid = $this->appid;
        $curtime = time();
        $nonceStr = Tool::randomStr(20);
        $url = $this->my_uri.'/';

        $token_url = $this->base_uri.'/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=SECRET';
        $token_url = str_replace('APPID', $this->appid, $token_url);
        $token_url = str_replace('SECRET', $this->secret, $token_url);

        $response = json_decode($this->curlGet($token_url));

        if(isset($response->access_token)) {

            $obj = $response;

            $params = array();
            $params['access_token'] = $obj->access_token;
            $params['type'] = 'jsapi';

            $ticket_url = $this->base_uri."/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi";
            $ticket_url = str_replace('ACCESS_TOKEN', $obj->access_token, $ticket_url);

            $response = json_decode($this->curlGet($ticket_url));
            Log::record($response);

            if($response->errmsg == 'ok' && isset($response->ticket)) {
                $obj = $response;

                if($obj->errcode == 0) {
                    $jsapi_ticket = $obj->ticket;
                    $tmpArry = [
                        'noncestr='.$nonceStr,
                        'jsapi_ticket='.$jsapi_ticket,
                        'timestamp='.$curtime,'url='.$url.'H/html/tool_select_02.html'
                    ];
                    sort($tmpArry, SORT_STRING);
                    $tmpStr = implode('&',$tmpArry);

                    $signature = sha1($tmpStr);

                    $data = [];
                    $data['appId'] = $appid;
                    $data['timestamp'] = $curtime;
                    $data['nonceStr'] = $nonceStr;
                    $data['signature'] = $signature;

                    echo json_encode(['msg'=>'success',
                        'status'=>1000,
                        'data'=>$data]);
                    exit();
                }
            }
        }
        echo json_encode(['msg'=>'fail!',
            'status'=>500,
        ]);
        exit();
    }


    /**
     *充值回调
     */
    public function wxNoticePay()
    {

        $data = file_get_contents('php://input');
        $obj_arr = XML::parse($data);
        Log::record('微信回调data:'.json_encode($obj_arr));
        $openId=$obj_arr['openid'];
        $sign = $obj_arr['sign'];
        unset($obj_arr['sign']);
        ksort($obj_arr);
        $string1 = urldecode(http_build_query($obj_arr).'&key='.CardConfig::$wxconf['pay_key']);
        $cur_sign = strtoupper(MD5($string1));

        if($cur_sign == $sign) {

            $order_item = M('AddMoney')->where(['order_no'=>$obj_arr['out_trade_no']])->find();

            if($order_item) {
                if ($order_item['status'] != 1){

                    M('AddMoney')->startTrans();
                    M('User')->startTrans();
                    M('IntegralRecord')->startTrans();
                    M('OilCard')->startTrans();
                    M('OrderRecord')->startTrans();
                    M('Coupon')->startTrans();
                    M('AgentEarnings')->startTrans();

                    try{

                         $flag=file_get_contents(__DIR__.'/data/'.$openId.'flag.txt');
                            $initial_money=file_get_contents(__DIR__.'/data/'.$openId.'initial_money.txt');
                            $card_no=file_get_contents(__DIR__.'/data/'.$openId.'card_no.txt');
                            // if (!empty($order_status)) {
                               
                                if ($flag==1){  
                                    $card_preferential=M('oil_card')->where("card_no='$card_no'")->getField('preferential');
                                    $a=M('')->getLastSql();
                                   
                                    if ($initial_money<=$card_preferential){
                                        $last_preferential=$card_preferential-$initial_money;
                                        $res=M('oil_card')->where("card_no='$card_no'")->save(['preferential'=>$last_preferential]);
                                        log::record($res);
                                    }
                                }else{
                                    $user_preferential=M('user')->where("openid='$openId'")->getField('preferential_quota');
                                    if ($initial_money<=$user_preferential){
                                        $last_preferential=$user_preferential-$initial_money;
                                        M('user')->where("openid='$openId'")->save(['preferential_quota'=>$last_preferential]);
                                    }
                                }
                            // }

                        //更改支付状态
                        $order_item['status'] = 1; //支付成功
                        $order_item['pay_at'] = date('Y-m-d H:i:s');
                        $order_status = M('AddMoney')->where(['order_no'=>$obj_arr['out_trade_no']])->save($order_item);

                        $order_record = M('OrderRecord')->where(['serial_number'=>$obj_arr['out_trade_no']])->find();
                        $order_record['order_status'] = 2;//成功
                        $order_record_status = M('OrderRecord')->where(['serial_number'=>$obj_arr['out_trade_no']])->save($order_record);

                        if (!$order_status || !$order_record_status){
                            M('AddMoney')->rollback();
                            M('OrderRecord')->rollback();
                        }

                        //增加用户累计数据
                        $user = M('User')->where(['openid'=>$order_item['openid']])->find();
                        $user['integral']           += intval($order_item['real_pay']);
                        $user['already_save_money'] += intval($order_item['discount_money']);
                        $user['total_add_money']    += intval($order_item['money']);
                        $user_status = M('User')->where(['openid'=>$order_item['openid']])->save($user);

                        $card = M('OilCard')->where(['card_no'=>$order_item['card_no']])->find();
                        $card['card_total_add_money'] += intval($order_item['money']);
                        $card_status = M('OilCard')->where(['card_no'=>$order_item['card_no']])->save($card);

                        //记录积分变动
                        $integral = [];
                        $integral['user_id'] = $user['id'];
                        $integral['change']  = 1;//增加;
                        $integral['chang_way'] = '充值';
                        $integral['change_value'] = $order_item['real_pay'];

                        $integral_status = M('IntegralRecord')->add($integral);

                        //处理分销收益
                        $agent_status = true;
                        $agent_inc = true;
                        log::record("agent_id".$order_item['agent_id']);

                        if (isset($order_item['agent_id']) && !empty($order_item['agent_id'])) {
                            $flage=file_get_contents(__DIR__.'/data/'.$openId.'flage.txt');
                            log::record($flage);

                            $agent_earnings = [];

                            $discount=M('setting')->find();

                            $card_no=$order_item['card_no'];
                            $agent_library_data=M('agent_library')->where("start_card_no<='$card_no' && end_card_no>='$card_no'")->find();
                            if (!empty($agent_library_data)) {
                                $a=M('agent')->where(['openid'=>$agent_library_data['openid']])->getField('id');
                            }else{
                                $a='0';
                            }

            // if ($flag==1){
            // $card_preferential=M('oil_card')->where("card_no='$card_no'")->getField('preferential');
            // if ($initial_money<=$card_preferential){
            // $last_preferential=$card_preferential-$initial_money;
            // M('oil_card')->where("card_no='$card_no'")->save(['preferential'=>$last_preferential]);
            // }
            // }else{
            // $user_preferential=M('user')->where("openid='$openid'")->getField('preferential_quota');
            // if ($initial_money<=$user_preferential){
            // $last_preferential=$user_preferential-$initial_money;
            // M('user')->where("openid='$openid'")->save(['preferential_quota'=>$last_preferential]);
            // }
            // }

                            if ($flage==96){
            log::record('是否95'.$flage);
                                $res=M('agent')->where(['id'=>$order_item['agent_id']])->find();
                                $ress=M('agent_earnings')->where(['openid'=>$res['openid']])->find();
                               $a= isset($ress)?$ress['agent_id']:$order_item['agent_id'];

                                 $agent_arr=M('agent_relation')->where("openid='$openId'")->find();
                                // $a=$agent_arr['agent_id'];
                                $agent_earnings['agent_id'] =$a;//上线的agent_id

                                $agent_earnings['order_type'] = 1; //充值订单
                                $agent_earnings['order_id'] = $order_item['id']; //充值订单
                                $agent_earnings['openid'] = $obj_arr['openid']; //充值订单
                                $agent_earnings['earnings'] = round(($order_item['money'] /100*$discount['plainprofit']),2); //充值订单
                                $agent_status = M('agent_earnings')->add($agent_earnings);
                            }else{


                                $res=M('agent')->where(['id'=>$order_item['agent_id']])->find();
                                $ress=M('agent_earnings')->where(['openid'=>$res['openid']])->find();
                               $a= isset($ress)?$ress['agent_id']:$order_item['agent_id'];

                                 $agent_arr=M('agent_relation')->where("openid='$openId'")->find();
                                // $a=$agent_arr['agent_id'];
                                $agent_earnings['agent_id'] =$a;//上线的agent_id

                                $agent_earnings['order_type'] = 1; //充值订单
                                $agent_earnings['order_id'] = $order_item['id']; //充值订单
                                $agent_earnings['openid'] = $obj_arr['openid']; //充值订单
                                $agent_earnings['earnings'] = round(($order_item['money'] /100*$discount['vipprofit']),2); //充值订单
                                $agent_status = M('agent_earnings')->add($agent_earnings);
                            }


                            $agent = M('Agent')->where(['id'=>$order_item['agent_id']])->find();
                            $agent['total_earnings'] += $agent_earnings['earnings'];
                            $agent['currt_earnings'] += $agent_earnings['earnings'];
                            $agent['add_total'] += $order_item['real_pay'];
                            $agent_id= $order_item['agent_id'];
                            $agent_inc = M('Agent')->where("id='$agent_id'")->save($agent);

                        }
                        if(!$order_status || !$user_status || !$integral_status || !$card_status || !$agent_status || !$agent_inc) {
                            M('AddMoney')->rollback();
                            M('User')->rollback();
                            M('IntegralRecord')->rollback();
                            M('OilCard')->rollback();
                            M('OrderRecord')->rollback();
                        
                            M('Coupon')->rollback();
                            M('AgentEarnings')->rollback();

                            Log::record('回调修改数据状态失败!');
                        } else {
                            M('AddMoney')->commit();
                            M('User')->commit();
                            M('IntegralRecord')->commit();
                            M('OilCard')->commit();

                           



                            M('OrderRecord')->commit();
                            M('Coupon')->commit();
                            M('AgentEarnings')->commit();


                            //发送微信通知
                            //1.充值成功通知
                            $notice = [];
                            $notice['card_no'] = $order_item['card_no'];
                            $notice['money'] = $order_item['money'];
                            $notice['careatetime'] = date('Y-m-d H:i:s',time());
                            $Wechat = A('Wechat');
                            $Wechat->templateMessage($order_item['openid'],$notice,3);

                            //2.积分变动通知
                            $notice = [];
                            $notice['change'] = 1;
                            $notice['change_value'] = intval($order_item['real_pay']);
                            $Wechat->templateMessage($order_item['openid'],$notice,4);
                        }
                    } catch (\Exception $e){
                        M('AddMoney')->rollback();
                        M('User')->rollback();
                        M('IntegralRecord')->rollback();
                        M('OilCard')->rollback();
                        M('OrderRecord')->rollback();
                        M('Coupon')->rollback();
                        M('AgentEarnings')->rollback();
                        Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
                        exit();
                    }
                }
                $openId=$obj_arr['openid'];

                $card_no=$order_record['card_no'];
                $money=$order_record['money'];



                if ($flag==1){
                    $card_arr=M('oil_card')->where("card_no='$card_no'")->find();

                    if ($card_arr['end_time']>=date('Y-m-d H:i:s') && $card_arr['preferential']>=$money) {
                        $last_money = (string)$card_arr['preferential'] - (string)$money;
                        M('oil_card')->where("card_no='$card_no'")->save(['end_time' => date("Y-m-d H:i:s", strtotime("+1years")), 'preferential' => $last_money]);
                    }
                }else{
                    $agent_arr=M('agent')->where("openid='$openId'")->find();
                    if (empty($agent_arr['currt_earnings']) && $agent_arr['currt_earnings']>=$money){
                        $last_money = (string)$agent_arr['currt_earnings'] - (string)$money;
                        M('agent')->where("openid='$openId'")->save([ 'currt_earnings' => $last_money]);
                    }
                }

                //首次充值必须充值1000元
                $first_add = M('AddMoney')->where(['openid'=>$openId,'status'=>1])->count();
                if ( $first_add<=1  && $money >= 1000) {
                    $deposit=M('agent')->where("openid='$openId'")->getField('deposit');
                    $preferential_quota=M('user')->where("openid='$openId'")->getField('preferential_quota');
                    if (empty($deposit) ){
                        M('agent')->where("openid='$openId'")->save(['deposit'=>$deposit-20]);
                        M('user')->where("openid='$openId'")->save(['preferential_quota'=>$preferential_quota+20]);
                    }
                }

            } else {
                Log::record('微信回调无此订单:'.$obj_arr['out_trade_no']);
            }
        } else {
            Log::record('签名错误，订单号:'.$obj_arr['out_trade_no']);
        }

        // 返回代码
        $data = [];
        $data['return_code'] = 'SUCCESS';
        $data['return_msg'] = 'OK';
        ob_end_clean();return XML::build($data);
    }

    /**
     * 申领银牌支付回调
     */
    public function wxAgentNoticePay()
    {
        $data = file_get_contents('php://input');
        Log::record('银牌申领回调:');
        $obj_arr = XML::parse($data);
        $openId=$obj_arr['openid'];
//        $openId="oKBRH4-nLGXUms_fY0xglT8xfesE";

        $sign = $obj_arr['sign'];
        unset($obj_arr['sign']);
        ksort($obj_arr);
        $string1 = urldecode(http_build_query($obj_arr).'&key='.CardConfig::$wxconf['pay_key']);
        $cur_sign = strtoupper(MD5($string1));

        if($cur_sign === $sign) {
            //添加到代理表
            //如果有上级，给上级40元
            Log::record('银牌申领回调1');


//            Log::record('银牌申领回调:2');
            $userData=M('user')->where("openid='$openId'")->find();  //根据微信openid查询对应的用户
//            if (empty($userData)) {
//                $this->error('用户不存在');
//            }
           
            //将指定的卡号添加93优惠套餐
            Log::record('银牌申领回调:3');
            $card_no=file_get_contents(__DIR__.'/data/'.$openId.'card_no.txt');
            $money=file_get_contents(__DIR__.'/data/'.$openId.'money.txt');
            $user_applu_data['receive_person']=file_get_contents(__DIR__.'/data/'.$openId.'receive_person.txt');
            $user_applu_data['phone']=file_get_contents(__DIR__.'/data/'.$openId.'phone.txt');
            $user_applu_data['address']=file_get_contents(__DIR__.'/data/'.$openId.'address.txt');
            $checked_card=file_get_contents(__DIR__.'/data/'.$openId.'checked_card.txt');
            $from_id=file_get_contents(__DIR__.'/data/'.$openId.'from_id.txt');

            $out_trade_no=$obj_arr['out_trade_no'];
            log::record("订单编号：".$out_trade_no);
            $apply_status=M('user_apply')->where("serial_number='$out_trade_no'")->find();
            log::record($apply_status);
            log::record("是否由此单好".$apply_status);
            if (empty($apply_status)) {

                $agent_id=M('agent_relation')->where("openid='$openId'")->getField('agent_id');
                $agent_arr= M('agent')->where("id='$agent_id'")->find();
                $first_agent_id=M('agent_relation')->where("openid='".$agent_arr['openid']."'")->getField('agent_id');

                //判断是否油卡有库存
                log::record("线下申领逻辑卡".$checked_card);
                //判断是否存在此油卡，
                $this->issetCard($openId,$agent_id,$agent_arr,$first_agent_id,$checked_card,$out_trade_no);

                    if (empty($checked_card)){

                        $this->cardIncreaseQuota($card_no,$checked_card,$money);
                        
                        Log::record('银牌申领回调:33');

                        //修改为银牌代理身份
                        $vip_money=$money-20;
                        file_put_contents(__DIR__.'/vip.log',print_r($vip_money,true));
                        $arr= M('agent')->where('openid="'.$openId.'"')->find();

                        if ($money>20){

                                $agent_arr=M('agent')->where("openid='$openId'")->find();
                                if ($agent_arr['role']==3) {
                                    $role=3;
                                }else{
                                    $role=2;
                                }
                                $agent_data=[
                                    'openid'=>$openId,
                                    'status'=>'1',
                                    'role'=>$role,
                                    'expire_time'=>date("Y-m-d H:i:s",strtotime("+1years")),
                                ];
                            if (!empty($arr)){
                                if ($arr['expire_time']>date('Y-m-d H:i:s')){
                                    $catime = strtotime($arr['expire_time'])+60*60*24*365;
                                    $nowtimes = date('Y-m-d H:i:s',$catime);
                                }
                                log::record("agent修改状态：".$agent_data['role']);
                                $agent_res=M('agent')->where("openid='$openId'")->save($agent_data);
                                log::record("修改用户为代理商;".$agent_res);
                                // 修改订单表订单状态
                                $record_res = M('order_record')->where("serial_number='$out_trade_no'")->save(['order_status'=>2]);
                                log::record('添加订单银牌代理充值记录记录表记录'.$record_res);

                            }else{
                                $agent_res=M('agent')->add($agent_data);
                            }
                        }
                        // 修改申领表订单状态
                        M('user_apply')->where("serial_number='$out_trade_no'")->save(['apply_status'=>2]);   //修改申领状态
                    }else{
                            log::record('线下申领逻辑');
                        if (!empty($checked_card)){
                            $card_arr=M('oil_card')->where("card_no='$checked_card'")->find();
                            $lastmoney=($money-20)*120;
                            if ($card_arr['end_time']<date('Y-m-d H:i:s')){ 
                                M('oil_card')->where("card_no='$checked_card'")->save(['end_time'=>date("Y-m-d H:i:s",strtotime("+1years")),'preferential'=>$lastmoney]);
                            }else{

                                $last_money=(string)$card_arr['preferential']+(string)$lastmoney;
                                M('oil_card')->where("card_no='$checked_card'")->save(['end_time'=>date("Y-m-d H:i:s",strtotime("+1years")),'preferential'=>$last_money]);
                            }
                        }

                        M('order_record')->where("serial_number='$out_trade_no'")->save(['order_status'=>2]);

                    }

                //申请代理未上线添加拉新收益
                $agent_data=M('agent')->where("openid='$openId'")->find();

                if ($vip_money>0){
                    $role=2;
                }else{
                    $role=1;
                }

                $agent_id=M('agent_relation')->where("openid='$openId'")->getField('agent_id');
                $b=M('')->getLastSql();
                $agent_role=M('agent')->where("id='$agent_id'")->getField('role');
                $a=M('')->getLastSql();

                    log::record("role".$a);
                    log::record("role".$role);
                    log::record("role".$agent_role);

                if ($role==1 && $agent_role==2){
                    $this->VipPullOrdinary($openId,$money);
                }else if($role==1 && $agent_role==3){
                    $this->GoldPullOrdinary($openId,$money);
                }else if($role==2 && $agent_role==3){
                    $this->AgentPullVip($openId,$money);
                }else if($role==2 && $agent_role==2){
                    $this->VipPullVipNew($openId,$money);
                }

            }

            $result = [];
            $data[' requestPayment'] = 'success';
            $data['return_msg'] = 'OK';

            echo log::record(XML::build($result));
        } else {
           Log::record('签名错误，订单号:'.$obj_arr['out_trade_no']);
        }
        
    }

    /**
    *银牌申领时给已有卡增加额度
    */
    public function cardIncreaseQuota($card_no,$checked_card,$money){

        if (!empty($card_no) || !empty($checked_card)){ 
            $card_arr=M('oil_card')->where("card_no='$card_no'")->find();
            $lastmoney=($money-20)*100;
            if ($card_arr['end_time']<date('Y-m-d H:i:s')){ 
                M('oil_card')->where("card_no='$card_no'")->save(['end_time'=>date("Y-m-d H:i:s",strtotime("+1years")),'preferential'=>$lastmoney]);
            }else{
                $last_money=(string)$card_arr['preferential']+(string)$lastmoney;
                M('oil_card')->where("card_no='$card_no'")->save(['end_time'=>date("Y-m-d H:i:s",strtotime("+1years")),'preferential'=>$last_money]);
            }
        }
    }


    /**

    *判断是否有油卡
    */
    public function issetCard($openid,$agent_id,$agent_arr,$first_agent_id,$checked_card,$out_trade_no){

        log::record('判断是否有油卡：'.$checked_card);

        M('user_apply')->where("serial_number='$out_trade_no'")->save(['status'=>2]);

        if (!empty($checked_card)) {
            $user_arr=M('user')->where("openid='$openid'")->find();
           $res=M('oil_card')->where("card_no='$checked_card'")->save(['user_id'=>$user_arr['id'],'status'=>2,'agent_status'=>1]);
           $a=M('')->getLastSql();
           log::record('判断是否有油卡sql ：'.$a);

        }

        if (!empty($agent_id) && $agent_arr['role']==3 || !empty($first_agent_id)){
            log::record('111');
            if (!empty($first_agent_id)){
                $OilCardData=M('oil_card')->where("agent_status=1 and status=1 and agent_id='$first_agent_id' and chomd=2")->find(); //从用户油卡表取出1张卡
            }else{
                $OilCardData=M('oil_card')->where("agent_status=1 and status=1 and agent_id='$agent_id' and chomd=2")->find(); //从用户油卡表取出1张卡
            }
            if(empty($OilCardData)){
                log::record('1');
                $this->error('油卡无库存');
            }
            $id=$OilCardData['id'];

            log::record("代理啦普通id".$id);

            $res=M('oil_card')->where("id='$id'" )->save(['status'=>2]);
            log::record("代理啦普通".$res);
        }else{
            log::record('222');
            $OilCardData=M('oil_card')->where('status=1 and discount=96 and chomd=1')->find(); //从用户油卡表取出1张卡
            if(empty($OilCardData)){
                log::record('2');
                $this->error('油卡无库存');
            }
            $id=$OilCardData['id'];
            $res=M('oil_card')->where("id='$id'" )->save(['status'=>2]);
        }

    }

    //数组转xml
    public function ArrToXml($arr)
    {
        if(!is_array($arr) || count($arr) == 0) return '';

        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     *95卡升级为93折回调
     */
    public function upgradeNoticePay(){

        $data = file_get_contents('php://input');
        $obj_arr = XML::parse($data);
        $openId=$obj_arr['openid'];

        $sign = $obj_arr['sign'];
        unset($obj_arr['sign']);
        ksort($obj_arr);
        $string1 = urldecode(http_build_query($obj_arr).'&key='.CardConfig::$wxconf['pay_key']);
        $cur_sign = strtoupper(MD5($string1));
        //以下为$obj_arr的值
        //        Array
        //        (
        //            [appid] => wx2fdc78cdc9c7d7b4
        //            [attach] => 附加数据
        //            [bank_type] => CFT
        //            [cash_fee] => 1
        //            [device_info] => WEB
        //            [fee_type] => CNY
        //            [is_subscribe] => Y
        //            [mch_id] => 1518293011
        //            [nonce_str] => 3idykdhwwc9z9p2k6a70q8ggi0e2toe8
        //            [openid] => o5DGTwUNmjGj4ivjAU0iEL_j2zZ8
        //            [out_trade_no] => 201804272157055212
        //            [result_code] => SUCCESS
        //            [return_code] => SUCCESS
        //            [time_end] => 20180427215710
        //            [total_fee] => 1
        //            [trade_type] => JSAPI
        //            [transaction_id] => 4200000157201804277557396086
        //        )

        if($cur_sign==$sign) {
            //将指定的95卡改为93.5卡

            $card_no=file_get_contents(__DIR__.'/data/'.$openId.'card_no.txt');
            $money=file_get_contents(__DIR__.'/data/'.$openId.'money.txt');

            $card_arr=M('oil_card')->where("card_no='$card_no'")->find();
            $a=M('')->getLastSql();
            $preferential=$card_arr['preferential'];
            $lmoney=$money*120;
            log::record('充值金额'.$money);
            log::record('充值金额后'.$lmoney);

            $arr=M('agent')->where("openid='$openId'")->find();
            if (empty($card_arr['end_time']) || $card_arr['end_time']<date('Y-m-d H:i:s')){
                $res= M('oil_card')->where("card_no='$card_no'")->save(['end_time'=>date("Y-m-d H:i:s",strtotime("+1years")),'preferential'=>$lmoney]);
                $a=M('')->getLastSql();
                log::record($a);
                if ($arr['role']=='3'){
                    $agent_data=[
                        'openid'=>$openId,
                        'expire_time'=>date('Y-m-d H:i:s', strtotime("+1 year")),
                        'status'=>'1',
                        'role'=>'3',
                    ];
                }else{
                    $agent_data=[
                        'openid'=>$openId,
                        'expire_time'=>date('Y-m-d H:i:s', strtotime("+1 year")),
                        'status'=>'1',
                        'role'=>'2',
                    ];
                }
                $agent_res=M('agent')->where("openid='$openId'")->save($agent_data);
            }else{

                $last_money=$preferential+$lmoney;
                 log::record($preferential);
                  log::record($lmoney);
                $res= M('oil_card')->where("card_no='$card_no'")->save(['end_time'=>date("Y-m-d H:i:s",strtotime("+1years")),'preferential'=>$last_money]);
                $a=M('')->getLastSql();
                log::record($a);
            }
            $this->offlinIncome($openId,$money);

        } else {
            Log::record('签名错误，订单号:'.$obj_arr['out_trade_no']);
        }
        // 返回代码

        $data = [];

        $data['return_code'] = 'SUCCESS';

        $data['return_msg'] = 'OK';
        log::record(XML::build($data));
        ob_end_clean();
        echo  XML::build($data);exit;
//        ob_end_clean();
//        echo "<xml>
//              <return_code><![CDATA[SUCCESS]]></return_code>
//              <return_msg><![CDATA[OK]]></return_msg>
//            </xml>";exit;
    }

    /**
     * 申请银牌代理为上线添加拉新奖励
     */
    public function offlinIncome($openid,$recharge){

        $discount=70;
        $money=$recharge/100*$discount;

        $agent_relation=M('agent_relation')->where("openid='$openid'")->find();

        if (empty($agent_relation) || empty($agent_relation['agent_id'])){
            return true;
        }else{
            $agent_id=$agent_relation['agent_id'];
            $agent_arr=M('agent')->where("id='$agent_id'")->find();
            $agentSaveArr=[
                'new_earnings'=>(string)$agent_arr['new_earnings']+$money,
                'currt_earnings'=>(string)$agent_arr['currt_earnings']+$money,
                'total_earnings'=>(string)$agent_arr['total_earnings']+$money
            ];
            $agent_id=$agent_relation['agent_id'];
            $res= M('agent')->where("id='$agent_id'")->save($agentSaveArr);


//        if(empty($result)){
            //分销收益表更新数据
            $where=[
                'openid'=>$openid,
                'order_type'=>2
            ];
//            $earnings_money=M('agent_earnings')->where($where)->find();
            // print_r($earnings_moneys);exit;
            $earnings_data=[
                'openid'=>$openid,
                'agent_id'=>$agent_id,
                'order_type'=>2,
                'earnings'=>$money
            ];
            M('agent_earnings')->where($where)->add($earnings_data);

        }

    }

    /**
     * 成为后为上线增加收益
     */
    public function earningsAddMoney( $openid,$money){
//        $openid='oKBRH4-nLGXUms_fY0xglT8xfesE';

        $agent_data= M('agent')->where("openid='$openid'")->find();// 当前用户  agent数据
        $relation_data=M('agent_relation')->where("openid='$openid'")->find();
        $agent_id=$relation_data['agent_id'];
        $result=M('agent_earnings')->where(" order_type=2 and agent_id='$agent_id' and openid='$openid'")->find();

        $agent_arr=M('agent')->where("id='$agent_id'")->find();
        Log::record('下线如数据:'.json_encode($result));
        if (empty($result)){
//            $agent_where['new_earnings']=$agent_arr['new_earnings']+40;
//            $agent_where['currt_earnings']=$agent_arr['currt_earnings']+40;
//            $agent_where['total_earnings']=$agent_arr['total_earnings']+40;
//            $agent_res=M('agent')->where('id='.$agent_id)->save($agent_where);
            if ($money>0){
                $earnings_data['order_type']=2;
            }else{
                $earnings_data['order_type']=4;
            }
            $earnings_data=[
                'earnings'=>40,
                'agent_id'=>$agent_data['id'],
                'openid'=>$openid
            ];
//            M('agent_earnings')->add($earnings_data);

        }

    }

   
    /**
     * 银牌拉普通
     * $openid 银牌openid
     * $recharge 充值金额
     */
    public function VipPullOrdinary($openid,$recharge){
        log::record("银牌拉普通");
        // $openid='os2aR0Xw35QanKcflDq1sDNmMnNU';
        // $recharge=15;
        // $money=10;
        //查找上家的id和上家的openid
        $agent_house=M('agent_relation')->where("openid='$openid'")->find();

        $agent_id=$agent_house['agent_id'];//上家的id

        $house_openid=M('agent')->where("id='$agent_id'")->find();
        $agent_openid=$house_openid['openid'];//上家的openid

        $a= M('agent_relation')->where("openid='$agent_openid'")->getField('agent_id');  //vipa的上线agent_id

        //根据上线的id查出上家在user的数据
        $user_data=M('user')->where("id='$agent_id'")->find();
        $user=M('user')->where("openid='$openid'")->find();
        //向订单表中添加数据
        $order_data=[
            'user_id'=>$user['id'],
            'order_type'=>1,
            'order_status'=>1,
            'agent_id'=>$agent_id,
            'money'=>$recharge,
            'real_pay'=>$recharge,
            'createtime'=>date("Y-m-d H:i:s",time())
        ];
        $res=M('order_record')->add($order_data);

        //向个人优惠表中添加数据
        $agency_data=[
            'user_id'=>$user['id'],
            'openid'=>$openid,
            'discount'=>'96折',
            'preferential_quota'=>$recharge*100,
            'status'=>1,
            'start_time'=>date("Y-m-d H:i:s",time()),
            'end_time'=>date("Y-m-d H:i:s",strtotime('+1year'))
        ];
        M('agency_preferences')->add($agency_data);
        // $only_data=[
        //     'openid'=>$openid,
        //     'agent_id'=>$agent_id,
        //     'order_type'=>1,
        // ];
        // $result=M('agent_earnings')->where($only_data)->find();
        // if(empty($result)){
            //向分销收益表添加数据
            // $earnings_data=[
            //     'openid'=>$openid,
            //     'agent_id'=>$agent_id,
            //     'order_type'=>4,
            //     'earnings'=>$money,
            //     'createtime'=>date("Y-m-d H:i:s",time())
            // ];
            // M('agent_earnings')->add($earnings_data);

            // $agent_data=[
            //     'add_total'=>$house_openid['add_total']+$recharge,
            //     'currt_earnings'=>$house_openid['currt_earnings']+$money,
            //     'total_earnings'=>$house_openid['total_earnings']+$money
            // ];
            // M('agent')->where("id='$agent_id'")->save($agent_data);

            M('agent_relation')->where("openid='$openid'")->save(['agent_id'=>$a]);
            //更新代理商表
            // $agent_data=[
            //       'add_total'=>$house_openid['add_total']+$recharge,
            //       'new_earnings'=>$house_openid['new_earnings']+$money,
            //       'currt_earnings'=>$house_openid['currt_earnings']+$money,
            //       'total_earnings'=>$house_openid['total_earnings']+$money
            // ];
            // M('agent')->where("id='$agent_id'")->save($agent_data);
            // //充值的钱*100 是优惠额度
            // $data=[
            //     'preferential_quota'=> $user['preferential_quota']+$recharge
            // ];
            // M('user')->where("openid='$openid'")->save($data);
        // }
//        else{
//            //更新代理商表
//            // $agent_data=[
//            //       'add_total'=>$house_openid['add_total']+$recharge,
//            //       'currt_earnings'=>$house_openid['currt_earnings']+$money,
//            //       'total_earnings'=>$house_openid['total_earnings']+$money
//            // ];
//            // M('agent')->where("id='$agent_id'")->save($agent_data);
//            //充值的钱*100 是优惠额度
//            $data=[
//                'preferential_quota'=> $user['preferential_quota']+$recharge*100
//            ];
//            M('user')->where("openid='$openid'")->save($data);
//        }
    }


    /**
     * 金牌拉金牌
     * $openid 金牌openid
     * $recharge 充值金额
     */
    public function GoldPullGold($openid,$recharge){
        // $openid='os2aR0Xw35QanKcflDq1sDNmMnNU';
        // $recharge=80;
        $discount=0.5;
        $money=$recharge*$discount;
        //查找上家的id和上家的openid
        $agent_house=M('agent_relation')->where("openid='$openid'")->find();
        $agent_id=$agent_house['agent_id'];//上家的id
        $house_openid=M('agent')->where("id='$agent_id'")->find();
        $agent_openid=$house_openid['openid'];//上家的openid
        //根据上线的id查出上家在user的数据
        $user_data=M('user')->where("id='$agent_id'")->find();
        $user=M('user')->where("openid='$openid'")->find();
        //向订单表中添加数据
        $order_data=[
            'user_id'=>$user['id'],
            'order_type'=>1,
            'order_status'=>1,
            'agent_id'=>$agent_id,
            'money'=>$recharge,
            'real_pay'=>$recharge,
            'createtime'=>date("Y-m-d H:i:s",time())
        ];
        $res=M('order_record')->add($order_data);

        //向个人优惠表中添加数据
        $agency_data=[
            'user_id'=>$user['id'],
            'openid'=>$openid,
            'discount'=>'93折',
            'preferential_quota'=>$recharge*100,
            'status'=>1,
            'start_time'=>date("Y-m-d H:i:s",time()),
            'end_time'=>date("Y-m-d H:i:s",strtotime('+1year'))
        ];
        M('agency_preferences')->add($agency_data);

        //查询个人分销表是否是一个人拉新
        $only_data=[
            'agent_id'=>$agent_id,
            'openid'=>$openid,
            'order_type'=>3,
        ];
        $result=M('agent_earnings')->where($only_data)->find();
        if(empty($result)){
            //分销收益表更新数据
            $earnings_data=[
                'agent_id'=>$agent_id,
                'earnings'=>$money
            ];
            $where=[
                'openid'=>$openid,
                'order_type'=>3
            ];
            M('agent_earnings')->where($where)->save($earnings_data);
            //更新代理商表
            $agent_data=[
                'add_total'=>$house_openid['add_total']+$recharge,
                'new_earnings'=>$house_openid['new_earnings']+$money,
                'currt_earnings'=>$house_openid['currt_earnings']+$money,
                'total_earnings'=>$house_openid['total_earnings']+$money,
                'role'=>3,
            ];
            M('agent')->where("id='$agent_id'")->save($agent_data);

            //充值的钱*100 是优惠额度
            $data=[
                'preferential_quota'=> $user['preferential_quota']+$recharge*100
            ];
            M('user')->where("openid='$openid'")->save($data);
        }else{

            //更新代理商表
            $agent_data=[
                'add_total'=>$house_openid['add_total']+$recharge,
                'currt_earnings'=>$house_openid['currt_earnings']+$money,
                'total_earnings'=>$house_openid['total_earnings']+$money,
                'role'=>3,
            ];
            M('agent')->where("id='$agent_id'")->save($agent_data);

            //充值的钱*100 是优惠额度
            // $data=[
            //     'preferential_quota'=> $user['preferential_quota']+$recharge*100
            // ];
            // M('user')->where("openid='$openid'")->save($data);
        }
    }

    /**
     * 金牌拉普通
     * $openid 普通openid
     * $recharge 充值金额
     */
    public function GoldPullOrdinary($openid,$recharge){
        // $openid='os2aR0Xw35QanKcflDq1sDNmMnNU';
//        $openid=I('post.openid');
//         $recharge=80;
//        $discount=10;
        // $money=10;
        //查找上家的id和上家的openid
        $agent_house=M('agent_relation')->where("openid='$openid'")->find();
        $agent_id=$agent_house['agent_id'];//上家的id
        $house_openid=M('agent')->where("id='$agent_id'")->find();
        $agent_openid=$house_openid['openid'];//上家的openid
        //根据上线的id查出上家在user的数据
        $user_data=M('user')->where("id='$agent_id'")->find();
        $user=M('user')->where("openid='$openid'")->find();

        //向订单表中添加数据
        $order_data=[
            'user_id'=>$user['id'],
            'order_type'=>1,
            'order_status'=>1,
            'agent_id'=>$agent_id,
            'money'=>$recharge,
            'real_pay'=>$recharge,
            'createtime'=>date("Y-m-d H:i:s",time())
        ];
        $res=M('order_record')->add($order_data);

        //向个人优惠表中添加数据
        $agency_data=[
            'user_id'=>$user['id'],
            'openid'=>$openid,
            'discount'=>'95折',
            'preferential_quota'=>$recharge*100,
            'status'=>1,
            'start_time'=>date("Y-m-d H:i:s",time()),
            'end_time'=>date("Y-m-d H:i:s",strtotime('+1year'))
        ];
        M('agency_preferences')->add($agency_data);
        // $only_data=[
        //     'openid'=>$openid,
        //     'agent_id'=>$agent_id,
        //     'order_type'=>4,
        // ];
        // $result=M('agent_earnings')->where($only_data)->find();
        // if(empty($result)){
            //分销收益表添加数据
//            $earnings_data=[
//                'openid'=>$openid,
//                'agent_id'=>$agent_id,
//                'earnings'=>$money,
//                'order_type'=>4,
//                'createtime'=>date("Y-m-d H:i:s",time())
//            ];
//            M('agent_earnings')->add($earnings_data);
//            $a=M('')->getLastSql();
//            //更新代理商表
//            $agent_data=[
//                'add_total'=>$house_openid['add_total']+$recharge,
//                'new_earnings'=>$house_openid['new_earnings']+$money,
//                'currt_earnings'=>$house_openid['currt_earnings']+$money,
//                'total_earnings'=>$house_openid['total_earnings']+$money
//            ];
//            M('agent')->where("id='$agent_id'")->save($agent_data);

            //充值的钱*100 是优惠额度
//            $data=[
//                'preferential_quota'=> $user['preferential_quota']+$recharge*100
//            ];
//            M('user')->where("openid='$openid'")->save($data);
        // }
//        else{
//            $where=[
//                'openid'=>$openid,
//                'order_type'=>1
//            ];
//            $earnings_money=M('agent_earnings')->where($where)->find();
//            $earnings_data=[
//                'openid'=>$openid,
//                'agent_id'=>$agent_id,
//                'earnings'=>$earnings_money['earnings']+$money,
//            ];
//
//            M('agent_earnings')->where($where)->save($earnings_data);
//            //更新代理商表
//            $agent_data=[
//                'add_total'=>$house_openid['add_total']+$recharge,
//                'new_earnings'=>$house_openid['new_earnings']+$money,
//                'currt_earnings'=>$house_openid['currt_earnings']+$money,
//                'total_earnings'=>$house_openid['total_earnings']+$money
//            ];
//            M('agent')->where("id='$agent_id'")->save($agent_data);
//
//            //充值的钱*100 是优惠额度
//            // $data=[
//            //     'preferential_quota'=> $user['preferential_quota']+$recharge*100
//            // ];
//            // M('user')->where("openid='$openid'")->save($data);
//        }

    }

    /**
     * 金牌拉银牌
     * $openid 银牌openid
     * $recharge 充值金额
     */
//    public function GoldPullVip($openid,$recharge){
//        // $openid='os2aR0Xw35QanKcflDq1sDNmMnNU';
//        // $recharge=80;
//        $discount=0.6;
//        $money=$recharge*$discount;
//
//        //查找上家的id和上家的openid
//        $agent_house=M('agent_relation')->where("openid='$openid'")->find();
//        $agent_id=$agent_house['agent_id'];//上家的id
//        $house_openid=M('agent')->where("id='$agent_id'")->find();
//        $agent_openid=$house_openid['openid'];//上家的openid
//        //根据上线的id查出上家在user的数据
//        $user_data=M('user')->where("id='$agent_id'")->find();
//
//        $user=M('user')->where("openid='$openid'")->find();
//
//        //向订单表中添加数据
//        $order_data=[
//            'user_id'=>$user['id'],
//            'order_type'=>1,
//            'order_status'=>1,
//            'agent_id'=>$agent_id,
//            'money'=>$recharge,
//            'real_pay'=>$recharge,
//            'createtime'=>date("Y-m-d H:i:s",time())
//        ];
//        $res=M('order_record')->add($order_data);
//
//        //向个人优惠表中添加数据
//        $agency_data=[
//            'user_id'=>$user['id'],
//            'openid'=>$openid,
//            'discount'=>'93折',
//            'preferential_quota'=>$recharge*100,
//            'status'=>1,
//            'start_time'=>date("Y-m-d H:i:s",time()),
//            'end_time'=>date("Y-m-d H:i:s",strtotime('+1year'))
//        ];
//        M('agency_preferences')->add($agency_data);
//        //查看分销表是否一个人拉新
//        $only_data=[
//            'openid'=>$openid,
//            'agent_id'=>$agent_id,
//            'order_type'=>2
//        ];
//        $result=M('agent_earnings')->where($only_data)->find();
//        if(empty($result)){
//            //分销收益表更新数据
//            $earnings_data=[
//                'agent_id'=>$agent_id,
//                'earnings'=>$money
//            ];
//            $where=[
//                'openid'=>$openid,
//                'order_type'=>2
//            ];
//            M('agent_earnings')->where($where)->save($earnings_data);
//            //更新代理商表
//            $agent_data=[
//                'add_total'=>$house_openid['add_total']+$recharge,
//                'new_earnings'=>$house_openid['new_earnings']+$money,
//                'currt_earnings'=>$house_openid['currt_earnings']+$money,        var_dump($relation_data);exit;
//                'total_earnings'=>$house_openid['total_earnings']+$money
//            ];
//            M('agent')->where("id='$agent_id'")->save($agent_data);
//
//            //充值的钱*100 是优惠额度
//            $data=[
//                'preferential_quota'=> $user['preferential_quota']+$recharge*100
//            ];
//            M('user')->where("openid='$openid'")->save($data);
//
//        }else{
//            //分销收益表更新数据
//            $where=[
//                'openid'=>$openid,
//                'order_type'=>2
//            ];
//            $earnings_money=M('agent_earnings')->where($where)->find();
//
//            $earnings_data=[
//                'agent_id'=>$agent_id,
//                'earnings'=>$earnings_money['earnings']+$money
//            ];
//            M('agent_earnings')->where($where)->save($earnings_data);
//            //更新代理商表
//            $agent_data=[
//                'add_total'=>$house_openid['add_total']+$recharge,
//                'new_earnings'=>$house_openid['new_earnings']+$money,
//                'currt_earnings'=>$house_openid['currt_earnings']+$money,
//                'total_earnings'=>$house_openid['total_earnings']+$money
//            ];
//            M('agent')->where("id='$agent_id'")->save($agent_data);
//            //充值的钱*100 是优惠额度
//            $data=[
//                'preferential_quota'=> $user['preferential_quota']+$recharge*100
//            ];
//            M('user')->where("openid='$openid'")->save($data);
//
//        }
//    }


    /**
     * 新的代理拉vip
     * $openid vip的 openid
     * $recharge vip充值的年费 一次性的
     */
    public function AgentPullVip($openid,$recharge){
        // $openid='os2aR0QqEutxi-P515cUo4ec2CgI';
        // $recharge='100';
        // $discount=70;
        // $recharge=$recharge-20;
        // $money=$recharge/100*$discount;
        //查找上家的id和上家的openid
        $agent_house=M('agent_relation')->where("openid='$openid'")->find();
        $agent_id=$agent_house['agent_id'];//上家的id
        $house_openid=M('agent')->where("id='$agent_id'")->find();
        $agent_openid=$house_openid['openid'];//上家的openid
        //根据上线的id查出上家在user的数据
        $user_data=M('user')->where("id='$agent_id'")->find();

        $user=M('user')->where("openid='$openid'")->find();

        //向订单表中添加数据
        $order_data=[
            'user_id'=>$user['id'],
            'order_type'=>1,
            'order_status'=>1,
            'agent_id'=>$agent_id,
            'money'=>$recharge,
            'real_pay'=>$recharge,
            'createtime'=>date("Y-m-d H:i:s",time())
        ];
        $res=M('order_record')->add($order_data);

        //向个人优惠表中添加数据
        $agency_data=[
            'user_id'=>$user['id'],
            'openid'=>$openid,
            'discount'=>'93折',
            'preferential_quota'=>$recharge*100,
            'status'=>1,
            'start_time'=>date("Y-m-d H:i:s",time()),
            'end_time'=>date("Y-m-d H:i:s",strtotime('+1year'))
        ];
        M('agency_preferences')->add($agency_data);
        //查看分销表是否一个人拉新
        // $only_data=[
        //     'openid'=>$openid,
        //     'agent_id'=>$agent_id,
        //     'order_type'=>2
        // ];
//         $result=M('agent_earnings')->where($only_data)->find();
// //        if(empty($result)){
//             //分销收益表更新数据
//             $where=[
//                 'openid'=>$openid,
//                 'order_type'=>2
//             ];
//            $earnings_money=M('agent_earnings')->where($where)->find();
            // print_r($earnings_moneys);exit;
            // $earnings_data=[
            //     'openid'=>$openid,
            //     'agent_id'=>$agent_id,
            //     'order_type'=>2,
            //     'earnings'=>$money
            // ];
            // M('agent_earnings')->where($where)->add($earnings_data);
            // //更新代理商表
            // $agent_data=[
            //     'add_total'=>$house_openid['add_total']+$recharge,
            //     'new_earnings'=>$house_openid['new_earnings']+$money,
            //     'currt_earnings'=>$house_openid['currt_earnings']+$money,
            //     'total_earnings'=>$house_openid['total_earnings']+$money
            // ];
            // M('agent')->where("id='$agent_id'")->save($agent_data);

            //充值的钱*100 是优惠额度
            // $data=[
            //     'preferential_quota'=> $user['preferential_quota']+$recharge
            // ];
            // M('user')->where("openid='$openid'")->save($data);

//        }
//        else{
//            //更新代理商表
//            $agent_data=[
//                'add_total'=>$house_openid['add_total']+$recharge,
//                // 'new_earnings'=>$house_openid['new_earnings']+$money,
//                // 'currt_earnings'=>$house_openid['currt_earnings']+$money,
//                // 'total_earnings'=>$house_openid['total_earnings']+$money
//            ];
//            M('agent')->where("id='$agent_id'")->save($agent_data);
//            //充值的钱*100 是优惠额度
//            $data=[
//                'preferential_quota'=> $user['preferential_quota']+$recharge
//            ];
//            M('user')->where("openid='$openid'")->save($data);
//
//        }
    }

    /**
     * 全新的vip拉vip
     */
    public function VipPullVipNew($openid,$recharge){
//        $agentopenid='os2aR0QzCHW2sqbrDGj1s0L5TJSQ';//代理商的openid
//        $openid='oKBRH4-nLGXUms_fY0xglT8xfesE';//新进的vip的openid
//
//        $recharge=30;
        $recharge=$recharge-20;

        
       
        $plainprofit=M('setting')->getField('scroll');
        // $agent_money=$recharge/100*$agent_quota;//代理的奖励钱

        $money=$recharge/100*$plainprofit;//拉新vip的奖励钱

        //查找上家的拉新vip id和上家的openid
        $agent_house=M('agent_relation')->where("openid='$openid'")->find();
        $agent_id=$agent_house['agent_id'];//上家的id   vipa的relation数据

        $house_openid=M('agent')->where("id='$agent_id'")->find();//vipa的agent数据
        $agent_openid=$house_openid['openid'];//上家的openid  vipa的openid
        $a= M('agent_relation')->where("openid='$agent_openid'")->getField('agent_id');  //vipa的上线agent_id
        $agentopenid=M('agent')->where("id='$a'")->getField('openid');  //代理的openid
        //根据上线的id查出上家在user的数据
        $user_data=M('user')->where("id='$agent_id'")->find();
        $user=M('user')->where("openid='$openid'")->find();
        //查出代理商的在agent的数据
        $agent_where=[
            'openid'=>$agentopenid,
            'role'=>3
        ];
        $pullmoney=M('agent')->where($agent_where)->find();

        // $benefit_data=[
        //     'new_earnings'=>$pullmoney['new_earnings']+$agent_quota,
        // ];
        // M('agent')->where($agent_where)->save($benefit_data);

        $order_data=[
            'user_id'=>$user['id'],
            'order_type'=>1,
            'order_status'=>1,
            'agent_id'=>$agent_id,
            'money'=>$recharge,
            'real_pay'=>$recharge,
            'createtime'=>date("Y-m-d H:i:s",time())
        ];
        $res=M('order_record')->add($order_data);

        //向个人优惠表中添加数据
        $agency_data=[
            'user_id'=>$user['id'],
            'openid'=>$openid,
            'discount'=>'93折',
            'preferential_quota'=>$recharge*100,
            'status'=>1,
            'start_time'=>date("Y-m-d H:i:s",time()),
            'end_time'=>date("Y-m-d H:i:s",strtotime('+1year'))
        ];
        M('agency_preferences')->add($agency_data);
        //查看分销表是否一个人拉新
        $only_data=[
            'openid'=>$openid,
            'agent_id'=>$agent_id,
            'order_type'=>2
        ];
        $result=M('agent_earnings')->where($only_data)->find();
        log::record($result);
        if(empty($result)){
            //分销收益表更新数据
            $earnings_data=[
                'agent_id'=>$agent_id,
                'earnings'=>$money,
                'openid'=>$openid,
                'order_type'=>2
            ];
            M('agent_earnings')->add($earnings_data);
            // $agent_earnings_data=[
            //     'agent_id'=>$a,
            //     'earnings'=>$agent_money,
            //     'openid'=>$openid,
            //     'order_type'=>2
            // ];
            // M('agent_earnings')->add($agent_earnings_data);

            log::record("添加agent——earnings数据结果");
            //更新代理商表
            $agent_data=[
                'add_total'=>$house_openid['add_total']+$recharge,
                'new_earnings'=>$house_openid['new_earnings']+$money,
                'currt_earnings'=>$house_openid['currt_earnings']+$money,
                'total_earnings'=>$house_openid['total_earnings']+$money
            ];
            M('agent')->where("id='$agent_id'")->save($agent_data);

            // $a_data=M('agent')->where("id='$a'")->find();  //代理的openid
            // $agent_data=[
            //     'add_total'=>$a_data['add_total']+$recharge,
            //     'new_earnings'=>$a_data['new_earnings']+$agent_money,
            //     'currt_earnings'=>$a_data['currt_earnings']+$agent_money,
            //     'total_earnings'=>$a_data['total_earnings']+$agent_money
            // ];
            // M('agent')->where("id='$a'")->save($agent_data);
            M('agent_relation')->where("openid='$openid'")->save(['agent_id'=>$a]);
// //            //充值的钱*120 是优惠额度
//             $data=[
//                 'preferential_quota'=> $user['preferential_quota']+$recharge
//             ];
//             M('user')->where("openid='agent_id'")->save($data);

        }
//        else{
//            //分销收益表更新数据
//            $where=[
//                'openid'=>$openid,
//                'order_type'=>2
//            ];
//            $earnings_money=M('agent_earnings')->where($where)->find();
//
//            $earnings_data=[
//                'agent_id'=>$agent_id,
//                'earnings'=>$earnings_money['earnings']+$money
//            ];
//            M('agent_earnings')->where($where)->save($earnings_data);
//            //更新代理商表
//            $agent_data=[
//                'add_total'=>$house_openid['add_total']+$recharge,
//                'new_earnings'=>$house_openid['new_earnings']+$money,
//                'currt_earnings'=>$house_openid['currt_earnings']+$money,
//                'total_earnings'=>$house_openid['total_earnings']+$money
//            ];
//            M('agent')->where("id='$agent_id'")->save($agent_data);
//            //充值的钱*100 是优惠额度
//            $data=[
//                'preferential_quota'=> $user['preferential_quota']+$recharge*120
//            ];
//            M('user')->where("openid='$openid'")->save($data);
//
//        }
    }

    /**
     * 微信刷新accesstoken
     */
    public function refreshAccessToken($refresh){

        $url="https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=".$this->appid."&grant_type=refresh_token&refresh_token=".$refresh;
        $jsonData=$this->curlGet($url);
        $data=(array)json_decode($jsonData);
        if (empty($data['errcode'])) {
            return false;
        }else{
            return $data;
        }
    }


    /**
     *微信模板消息推送
     * $flage  模板通知类型   1，申领  2，绑卡  3，充值  4，积分变动  5，油卡发货
     */
    public function templateMessage($openid,$data,$flage,$from_id){

        Log::record('模板消息:'.json_encode($data));

        if (empty($openid)) {
            echo json_encode([
                'msg'=>'数据传输有误',
                'status'=>'500'
            ]);exit;
        }

        $user_data=M('user')->where("openid='".$openid."'")->find();

//        $access_token=S('access_token');
//        if(empty($access_token)){
//        $getAccessTokenUrl="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->secret;  ##公众号

        $getAccessTokenUrl="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->secret;
        $accessTokenData=(array)json_decode($this->curlGet($getAccessTokenUrl));
        $access_token=$accessTokenData['access_token'];
        S('access_token',$access_token,7000);
//        }

        $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $url="https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token;
        $date=date('Y-m-d H:i:s');
        switch ($flage) {
            case '1':
                $template_id='O2hqarAUwBUkt2LySetp-7PWVQVqQladw4O97AjU8YY';
                $template_data='{
                   
                    "keyword1":{
                        "value":"'.$date.'",
                        "color":"#173177"
                    },
                    "keyword2": {
                        "value":"油卡申领",
                        "color":"#173177"
                    },
                    "keyword3": {
                        "value":"具体卡号以到手卡号为准", 
                        "color":"#173177"
                    },
                     "keyword4": {
                        "value":"请关注快递信息", 
                        "color":"#173177"
                    },
                     "keyword5": {
                        "value":"快递发货", 
                        "color":"#173177"
                    },
                    "remark":{
                        "value":"如有问题请联系客服微信号",
                        "color":"#173177"
                    }
                }';

                break;
            case '2':
                $template_id='j8S2SHm1n5SbHKotPFQtRbvfjTWXnycei5K5lTgAQGg';
                $template_data='{
                    "first": {
                        "value":"您好，您已成功绑定加油卡",
                        "color":"#173177"
                    },
                    "keyword1":{
                        "value":"尊敬的"'.$user_data['nickname'].',
                        "color":"#173177"
                    },
                    "keyword2": {
                        "value":"卡号详情以收到卡号为准",
                        "color":"#173177"
                    },
                    "keyword3": {
                        "value":"'.$data['careatetime'].'",
                        "color":"#173177"
                    },
                    "remark":{
                        "value":"如有问题请联系客服微信号",
                        "color":"#173177"
                    }
                }';
                break;
            case '3':

                $oilCardData=M('oil_card')->where('openid='.$openid)->find();

                $template_id='Xj5LqKlkKN8WWluJ2X0K7QCR0kkredXlKfXqtHZJ4WY';
                $template_data='{
                    "first": {
                        "value":"您好，您卡号为'.$data['card_no'].'的加油卡充值信息如下",
                        "color":"#173177"
                    },
                    "keyword1":{
                        "value":"'.$data['money'].'",
                        "color":"#173177"
                    },
                    "keyword2": {
                        "value":"'.$oilCardData['money'].'",
                        "color":"#173177"
                    },
                    "keyword3": {
                        "value":"'.$data['careatetime'].'",
                        "color":"#173177"
                    },
                    "remark":{
                        "value":"如有问题请联系客服微信号",
                        "color":"#173177"
                    }
                }';
                break;
            case '4':

                if ($data['change']=='1') {
                    $data['change']='增加积分'.$data['change_value'];
                }else{
                    $data['change']='消费积分'.$data['change_value'];
                }

                $template_id='Xj5LqKlkKN8WWluJ2X0K7QCR0kkredXlKfXqtHZJ4WY';
                $template_data='{
                    "first": {
                        "value":"亲爱的'.$user_data['nickname'].'您当前的积分账户发生变更",
                        "color":"#173177"
                    },
                    "keyword1":{
                        "value":"'.$user_data['integral'].'",
                        "color":"#173177"
                    },
                    "keyword2": {
                        "value":"'.$data['change'].'",
                        "color":"#173177"
                    },
                    "remark":{
                        "value":"如有问题请联系客服微信号",
                        "color":"#173177"
                    }
                }';
                break;
            case '5':
//                $order_data=M('order_record')->where('card_no='.$data['card'])->find();
                $template_id='YedzPQhI70K3Pb7pN5yQBUWL41hYfwPkjS0ESYxJYxM';
                $a=[];
                $a=[
                    'keyword1'=>['value'=>'1'],
                    'keyword2'=>['value'=>'给客户发卡'],
                    'keyword3'=>['value'=>$date],
                    'keyword4'=>['value'=>$date],
                    'keyword5'=>['value'=>'中石油折扣卡'],
                    'keyword6'=>['value'=>$data['phone']],
                    'keyword7'=>['value'=>'中石油折扣卡'],
                    'keyword8'=>['value'=>$data['address']],
                    'keyword9'=>['value'=>$data['receive_person']],
                ];
                $a=json_encode($a);

               $template_data='{
                   "keyword1":{
                       "value":"1",
                   },
                   "keyword2": {
                       "value":"给客户发卡",
                   },
                    "keyword3": {
                       "value":"$date",
                   },
                    "keyword4": {
                       "value":"'.$date.'",
                   },
                    "keyword5": {
                       "value":"中石油折扣卡",
                   },
                    "keyword6": {
                       "value":"'.$data['phone'].'",
                   },
                    "keyword7": {
                       "value":"中石油折扣卡",
                   },
                    "keyword8": {
                       "value":"'.$data['address'].'",
                   },
                    "keyword9": {
                       "value":"'.$data['receive_person'].'",
                   },
               }';
            //     $template_data='{
            //     "keyword1": {
            //         "value": "339208499"
            //     },
            //     "keyword2": {
            //         "value": "2015年01月05日 12:30"
            //     },
            //     "keyword3": {
            //         "value": "腾讯微信总部"
            //     },
            //     "keyword4": {
            //         "value": "广州市海珠区新港中路397号"
            //     }
            // },';
                break;
        }

//        $templateData='{
//           "touser":"'.$openid.'",
//           "template_id":"'.$template_id.'",
//           "data":'.$template_data.'
//        }';
        // log::recode($b);
        $templateData='{
            "touser":"'.$openid.'",
            "template_id": "'.$template_id.'",
            "form_id": "'.$from_id.'",
            "data": '.$a.',
            "emphasis_keyword": "keyword1.DATA"
        }';
        $res=$this->CurlPost($url,$templateData);
        file_put_contents(__DIR__.'/data/receive_person.txt',print_r($res,true));
        if ($flage==5){
            return json_encode($res);
        }
    }


    /**
     * @param $url
     * @return mixed
     * curl Get
     */
    private function curlGet($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            if(intval($aStatus["http_code"]) == 301){
                return $aStatus['redirect_url'];
            }
        }
    }


    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @return string content
     */
    private function _httpPost($url,$param){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (is_string($param)) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }

    function HttpPost($url,$param){

        $ch = curl_init();
        //如果$param是数组的话直接用
        curl_setopt($ch, CURLOPT_URL, $url);
        //如果$param是json格式的数据，则打开下面这个注释
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //         'Content-Type: application/json',
        //         'Content-Length: ' . strlen($param))
        // );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //如果用的协议是https则打开下面这个注释
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        $data = curl_exec($ch);

        curl_close($ch);
        return $data;

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

    /**
     * 代理商H5推荐二维码
     */
    public function qrcode(){
        $openid=I('post.openid','');
        $APPID = 'wxd16b20528d23aff8';
        $AppSecret = 'b303f8f0002cd185cce101d63d342a85';
        if(empty($openid)){
            $this->error('参数生成错误');
        }

        $user_arr=M('user')->where("openid='$openid'")->find();
        $user_id=$user_arr['id'];
        $user_card_data=M('oil_card')->where("user_id='$user_id'")->select();

//        if (empty($user_card_data)){
//            $this->error('',501);
//        }else{
//            foreach ($user_card_data as $k=>$v){
//                $card_order=M('order_record')->where("card_no='".$v['card_no']."'")->find();
//                if ($card_order<500){
//                    $this->error('',501);
//                }
//            }
//        }


//        $img=file_get_contents(__DIR__."/wechat/$openid.png");
//        if (empty($img)){
        $access_token=S('program_access_token');
//        if (empty($access_token)){
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$APPID."&secret=".$AppSecret;
        $jsonData=$this->curlGet($url);
        $accessData=(array)json_decode($jsonData);
        $access_token=$accessData['access_token'];
        if (!empty($accessData['errcode'])){
            $this->error('签名生成错误');
        }
        S('program_access_token',$access_token,'7000');
//        }
        if(empty($access_token)){
            $this->error('参数生成错误');
        }
        $qcode ="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$access_token";
//        $param = json_encode(['page'=>'pages/vip/vip','scene'=>$openid]);
        $param = json_encode(['scene'=>$openid]);
        $json = $param;
        $result = $this->api_notice_increment($qcode, $json);
//        }
//

        $path="https://ysy.edshui.com/application/Oilcard/Controller/wechat/$openid.png";
//        $path="https://ysy.edshui.com/H/img/$openid.png";

        file_put_contents(__DIR__."/wechat/$openid.png",print_r($result,true));
//        file_put_contents($path,print_r($result,true));
        $this->success($path);


    }
    public function api_notice_increment($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return $ch;
        } else {
            curl_close($ch);
            return $tmpInfo;
        }
    }


    public function httpRequest($url, $data='', $method='GET'){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if($method=='POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '')
            {
//                $data = json_decode($data,true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }

        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }





    /**
     * 推荐跳转接口
     */
    public function Receive(){
        $url=this.location.href;
        var_dump($url);exit;
    }


    #*********************************************************************************************************************************************************************
    public function smallProgramGetOpenid(){
        log::record('小程序登录');
        $code= I('post.code','');
        $openid= I('post.openid','');
        $nickname= I('post.nickname','');
        $user_img= I('post.user_img','');
        $agent_openid=I('post.agent_openid','');
        $APPID = 'wxd16b20528d23aff8';
        $AppSecret = 'b303f8f0002cd185cce101d63d342a85';
//        if (empty($code) && empty($data)){
//            log::record('小程序登录数据传输有误');
//            $this->error('数据传输有误');
//        }
        //开发者使用登陆凭证 code 获取 session_key 和 openid
        include_once "wxBizDataCrypt.php";

        if (!empty($code)){
            $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$APPID."&secret=".$AppSecret."&js_code=".$code."&grant_type=authorization_code";
            $arr = $this->vget($url);  // 一个使用curl实现的get方法请求
            $arr = json_decode($arr,true);
            $openid = $arr['openid'];
            $session_key = $arr['session_key'];
            S('session_key',$session_key);
            log::record($agent_openid);
            if (!empty($agent_openid)){
                $aid= M('agent_relation')->where("openid='$openid'")->getField('agent_id');
                if (!empty($agent_openid)){
                    $a_id= M('agent')->where("openid='$agent_openid'")->getField('id');

                     $ad= M('agent_relation')->where("openid='$openid'")->find();
                    if (empty($ad)) {

                        $res= M('agent_relation')->add(['agent_id'=>$a_id,'openid'=>$openid]);
                    }else{
                        $earnings_data=M('agent_earnings')->where("openid='$openid' and agent_id='$ad'")->find();
                        if (empty($earnings_data)) {
                             M('agent_relation')->save(['agent_id'=>$a_id]);
                        }
                        $res= M('agent_relation')->save(['agent_id'=>$aid]);
                    }
                    
                    log::record($res);
                }
            }
            $data= M('user')->where("openid='$openid'")->find();
            if (empty($data)){
                $user_id=M('user')->insertGetId(['openid'=>$openid]);
                M('agent')->add(['id'=>$user_id,'openid'=>$openid]);
            }
            $this->success($arr);
            log::record('小程序登录返回数据'.$arr);

        }else {
<<<<<<< HEAD
            $arr= M('user')->where("openid='$openid'")->find();
            if (empty($arr)){
                $user_id=M('user')->insertGetId(['openid'=>$openid]);
                M('agent')->add(['id'=>$user_id,'openid'=>$openid]);
=======
            $data= M('user')->where(['openid'=>$openid])->find();
            if (empty($data)){
                M('user')->add(['openid'=>$openid]);
                $agent_id=M('agent')->add(['openid'=>$openid]);
>>>>>>> af9257d61fa79b40e3685fb704d08a82de06bc76
            }
//            $nickname=(array)json_decode($nickname);
            M('user')->where("openid='$openid'")->save(['nickname'=>$nickname,'user_img'=>$user_img]);
            $arr= M('user')->where("openid='$openid'")->find();
            $this->success($arr);
            exit;
        }


// 数据签名校验
        //        $signature = I('get.signature');
        //        $signature2 = sha1($_GET['rawData'].$session_key);  //记住不应该用TP中的I方法，会过滤掉必要的数据
        //        if ($signature != $signature2) {
        //            echo '数据签名验证失败！';die;
        //        }
        //
        //        //开发者如需要获取敏感数据，需要对接口返回的加密数据( encryptedData )进行对称解密
        //        Vendor("PHP.wxBizDataCrypt");  //加载解密文件，在官方有下载
        //       $encryptedData = $_GET['encryptedData'];
//            $iv = $_GET['iv'];
//            $pc = new \WXBizDataCrypt($APPID, $session_key);
//            $errCode = $pc->decryptData("7TZ+H5vlVVFWyBzmNXIZUsO0cOVmVaydgdwu57zF0ruS2UMpdJnjGE6sxzeCVBdmF8QGwX5NmfEkir/8mr9KZDJNZzXHk/va12bTBzviE+XLjWtXDXta2PKXZxjZlOr8juLEhtU5QFmPZ0ITiSLtEvPYCIdtiA9Xh/mDzxosdUp0KwPpGeMyoBZfEXdQcT5kAkAi68GMobt0XVUAFohB7zPylM9aXLamK0scJDjq1xl1Rbce6gy6J8i45qG1mpayVVLt2koBNmc4WfY5uPN/81kwV//faK2b6Zs8BEC4MIaKZ0Wq0/TJ78HgeBFvpOP6xHy6Ut3z6YND8ZT8WXkqTNF5RfyRyDaAfLkB+Y6Md2YKxpUjWQvkMWQr2M6KtnnZiRBNvIHmfw28c/+wDct+PCq7AlfESBil1fV7XhB4/2pp/hTHBIqsop/X8/xVeIdhJOUF4uMx5WmoxMLbWYQ4Cw==", "7TZ+H5vlVVFWyBzmNXIZUsO0cOVmVaydgdwu57zF0ruS2UMpdJnjGE6sxzeCVBdmF8QGwX5NmfEkir/8mr9KZDJNZzXHk/va12bTBzviE+XLjWtXDXta2PKXZxjZlOr8juLEhtU5QFmPZ0ITiSLtEvPYCIdtiA9Xh/mDzxosdUp0KwPpGeMyoBZfEXdQcT5kAkAi68GMobt0XVUAFohB7zPylM9aXLamK0scJDjq1xl1Rbce6gy6J8i45qG1mpayVVLt2koBNmc4WfY5uPN/81kwV//faK2b6Zs8BEC4MIaKZ0Wq0/TJ78HgeBFvpOP6xHy6Ut3z6YND8ZT8WXkqTNF5RfyRyDaAfLkB+Y6Md2YKxpUjWQvkMWQr2M6KtnnZiRBNvIHmfw28c/+wDct+PCq7AlfESBil1fV7XhB4/2pp/hTHBIqsop/X8/xVeIdhJOUF4uMx5WmoxMLbWYQ4Cw==");  //其中$data包含用户的所有数据
//       var_dump($errCode);exit;
//       if ($errCode != 0) {
//                echo '解密数据失败！';die;
//            }
        //
        //        //生成第三方3rd_session
        //        $session3rd  = null;
        //        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        //        $max = strlen($strPol)-1;
        //        for($i=0;$i<16;$i++){
        //            $session3rd .=$strPol[rand(0,$max)];
        //        }
        //        echo $session3rd;
    }

    public function vget($url){
        $info=curl_init();
        curl_setopt($info,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($info,CURLOPT_HEADER,0);
        curl_setopt($info,CURLOPT_NOBODY,0);
        curl_setopt($info,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info,CURLOPT_URL,$url);
        $output= curl_exec($info);
        curl_close($info);
        return $output;
    }

}
