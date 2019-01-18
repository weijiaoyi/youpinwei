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
use Oilcard\Conf\HJCloudConfig;
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
    private $my_uri = 'http://ysy.xiangjianhai.com';
    private $pay_uri = 'https://api.mch.weixin.qq.com';

    public function __construct()
    {
        parent::__construct();
        header('content-type:text/html;charset=utf-8');
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
    public function payOrder($create_res,$record_res,$openid)
    {
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
        $data['out_trade_no'] = $create_res['order_no'];
        $data['fee_type'] = 'CNY';
        $data['total_fee'] = $create_res['real_pay']*100;//$order_item['real_pay'] * 100; // 分
        $data['spbill_create_ip'] = Tool::getClientIp();
        $data['time_start'] = date('YmdHis');
        $data['time_expire'] = date('YmdHis',time()+7200);
//        $data['notify_url'] = $this->my_uri.'/index.php?g=oilcard&m=wechat&a=wxNoticePay';
        $data['notify_url'] = $this->my_uri.'/addMoneyNotify.php';
        $data['trade_type'] = 'JSAPI';
        $data['openid'] = $openid;
        ksort($data);
        $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
        $data['sign'] = md5($string1);

        $content = XML::build($data);
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
    public function agentPay($OrderInfo,$data,$openid)
    {
        log::record('线下申领卡号：'.$checked_card);

        //获取套餐信息
        $package = M('packages')->where(['pid'=>$OrderInfo['pid']])->find();
        //微信统一下单
        $data = [];
        $data['appid']            = CardConfig::$wxconf['appid'];
        $data['mch_id']           = CardConfig::$wxconf['mch_id'];
        $data['device_info']      = 'WEB';
        $data['nonce_str']        = Tool::randomStr(20);
        $data['sign_type']        = 'MD5';
        $data['body']             = $OrderInfo['online']==1?'线上申领油卡':'线下绑定油卡';
        $data['detail']           = '油卡业务办理';
        $data['attach']           = '缴纳年费';
        $data['out_trade_no']     = $OrderInfo['serial_number'];
        $data['fee_type']         = 'CNY';
        $data['total_fee']        = $OrderInfo['real_pay']*100;//$OrderInfo['real_pay']*100
        $data['spbill_create_ip'] = Tool::getClientIp();
        $data['time_start']       = date('YmdHis');
        $data['time_expire']      = date('YmdHis',time()+7200);
        $data['notify_url']       = $this->my_uri.'/agentMoneyNotify.php';
        $data['trade_type']       = 'JSAPI';
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
        $data = [];
        $obj_arr = XML::parse($content);

        if (!$obj_arr){
            return $data;
        }
        $order = false;
        if($obj_arr['result_code'] == 'SUCCESS') {
            $data['appId'] = CardConfig::$wxconf['appid'];
            $data['timeStamp'] = time();
            $data['nonceStr'] = Tool::randomStr(20);
            $data['package'] = 'prepay_id='.$obj_arr['prepay_id'];
            $data['signType'] = 'MD5';
            ksort($data);
            $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
            $data['paySign'] = md5($string1);
            //写入订单表
            $order = M('order_record')->add($OrderInfo);
        }
        if (!$order) return [];
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
     * 油卡升级续费
     * @Author 老王
     * @创建时间   2019-01-07
     * @return [type]     [description]
     */
    public function upgradePay(){
        $openid  =I('post.openid','');
        $card_no =I('post.card_no','');
        $card_id  =I('post.card_id','');//油卡id
        $money   =I('post.money',0);//交的费用
        $pid     =I('post.pid',0);//套餐id
        $type    =I('post.type',1);//操作类型 ,升级 1 ,续费2
        $Member  = M('user')->where(['openid'=>$openid])->find();
        if(!$Member)$this->error('参数错误:缺少用户信息!');
        $CWhere= [
            'id'      =>$card_id,
            'card_no' =>$card_no,
            '_logic'  => 'OR'
        ];
        $Card    = M('oil_card')->where($CWhere)->find();
        if(!$Card)$this->error('参数错误:缺少油卡信息!');
        $package = M('packages')->where(['pid'=>$pid])->find();
        if(!$package)$this->error('参数错误:缺少套餐信息!');
        $OrderSn = date('YmdHis').str_pad(mt_rand(1,999999),6,STR_PAD_LEFT);
        $NowTime = date('Y-m-d H:i:s',TIMESTAMP);
        $config = M('setting')->find();
        $Order = [
            'user_id'        => $Member['id'],
            'card_no'        => $Card['card_no'],
            'serial_number'  => $OrderSn,
            'order_status'   => 1,
            'real_pay'       => $money,
            'recharge_money' => $money,
            'createtime'     => $NowTime,
            'preferential'   => $package['limits'],
            'card_from'      => $Card['agent_id'] ==0?1:2,
            'agent_id'       => $Card['agent_id'] ==0?0:$Card['agent_id'],
            'pid'            => $package['pid'],
            'applyfinish'    =>2
        ];
        switch ($type) {
            case '1':
                //生成升级订单
                $Order['order_type'] =4;
                $body = '油卡升级订单';
                break;
            
            case '2':
                //生成续费订单
                $Order['order_type'] =5;
                $body = '油卡续费订单';
                break;
        }
        if(!$type) $this->error('参数错误:缺少类型!');
        if ($type ==1 &&$Order['order_type'] !=4) $this->error('订单生成失败!');
        if ($type ==2 &&$Order['order_type'] !=5) $this->error('订单生成失败!');

        $PayCon = [
                'body'     => '油卡充值',
                'detail'   => '油卡充值',
                'attach'   => '油卡充值',
                'paymoney' => $config['paymoney']
            ];
        switch ($config['paytype']) {
            case '1': //微信支付
                $data = $this->_WxPay($Order,$Member,$PayCon);
                # code...
                break;
            case '2': //聚合支付
                $data = $this->_HjPay($Order,$Member,$PayCon);
                break;
        }
        if($data){
            $OrderAdd = M('order_record')->add($Order);
            if(!$OrderAdd)$this->error('订单生成失败!');
            $this->success($data);
        }else{
            $this->error('订单生成失败!');
        }
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
     * 油卡充值异步回调
     * @Author 老王
     * @创建时间   2019-01-02
     * @return [type]     [description]
     */
    public function wxNoticePay()
    {
        $IsOver = false;
        $ReturnMsg ='';
        $data = file_get_contents('php://input');
        $obj_arr = XML::parse($data);
        Log::record('微信回调data:'.json_encode($obj_arr));
        $insert = array(
            'content'=>json_encode(array(
                'InsertTime'=>date('Y-m-d H:i:s',time()),
                'desc' =>'充值油卡异步回调',
                'input' =>$obj_arr,
                'data' =>$data,
            ))
        );
        M('testt')->add($insert);

        $sign = $obj_arr['sign'];
        unset($obj_arr['sign']);
        ksort($obj_arr);
        $string1 = urldecode(http_build_query($obj_arr).'&key='.CardConfig::$wxconf['pay_key']);
        $cur_sign = strtoupper(MD5($string1));
        if($cur_sign == $sign) {
            $OrderSn = $obj_arr['out_trade_no'];
            $NowTime = date('Y-m-d H:i:s',TIMESTAMP);
            $openId=$obj_arr['openid'];
            $EndTime = date("Y-m-d H:i:s",strtotime("+1years"));//过期时间 1年
            $Member=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.openid'=>$openId])->find();
            $order_item = M('add_money')->where(['order_no'=>$obj_arr['out_trade_no']])->find();
            $OrderInfo =  M('order_record')->where(['serial_number'=>$obj_arr['out_trade_no']])->find();
            if ($OrderInfo['order_status']==2 && !empty($OrderInfo['pay_sn'])) {
                echo 'SUCCESS';exit;
                return $this->arrayToXml(['return_code'=>'SUCCESS','return_msg'=>'OK']);
            }
            $CardInfo = M('oil_card')->where(['card_no'=>$order_item['card_no']])->find();
            $config = M('setting')->find();
            if($order_item && $obj_arr['result_code']=='SUCCESS') {
                $Things = M();
                $Things->startTrans();
                //更改充值记录信息状态  //更改支付状态
                $AddMoneySave =[
                    'status' => 1,
                    'updatetime' => $NowTime
                ];
                //用户充值记录信息状态修改
                $AddMoneySave = M('add_money')->where(['id'=>$order_item['id']])->save($AddMoneySave);
                if(!$AddMoneySave){
                    $Things->rollback();
                    echo 'FAIL';exit;
                }
                //更改油卡信息状态
                $OilCardSave =[
                    //充值成功,减少可用额度
                    'preferential' =>$CardInfo['pkgid']>1? ($CardInfo['preferential'] - $order_item['money']):0,
                    //增加总充值额度
                    'card_total_add_money' => intval($CardInfo['card_total_add_money'] + $order_item['money'])
                ];
                if ($order_item['is_first']==1) {
                    $OilCardSave['activate'] =2;
                }
                //油卡信息状态修改
                $OilCardSave = M('oil_card')->where(['id'=>$CardInfo['id']])->save($OilCardSave);
                if(!$OilCardSave){
                    $Things->rollback();
                    echo 'FAIL';exit;
                }
                //更改订单支付状态
                $OrderSave = [
                    'order_status'=> 2,
                    'updatetime'=>$NowTime,
                    'pay_sn' => $obj_arr['transaction_id'],
                ];
                //订单状态修改
                $OrderSave = M('order_record')->where(['id'=>$OrderInfo['id']])->save($OrderSave);
                if(!$OrderSave){
                    $Things->rollback();
                    echo 'FAIL';exit;
                }
                //用户信息变动记录
                $MemberSave =[
                    //积分 1：1
                    'integral' => intval($Member['integral'] + $order_item['real_pay']),
                    //总共给用户省下来的钱
                    'already_save_money' => intval($Member['already_save_money'] + $order_item['discount_money']),
                    //总共充值的油卡额度 
                    'total_add_money' => intval($Member['total_add_money'] + $order_item['money']),
                    //用户真实充值的钱
                    'total_real_add_money' =>$Member['total_real_add_money'] + $order_item['real_pay'],
                ];
                //用户信息修改
                $MemberSave = M('user')->where(['openid'=>$openId])->save($MemberSave);
                if(!$MemberSave){
                    $Things->rollback();
                    echo 'FAIL';exit;
                }
                //积分变动记录
                $IntegralAdd = [
                    'user_id' => $Member['id'],
                    'change' => 1,
                    'chang_way' => '充值',
                    'change_value' => $order_item['real_pay'],
                    'createtime' => $NowTime,
                    'updatetime' => $NowTime,
                    'change_from'=> json_encode(['from'=>'OrderRechage','OrderSn'=>$OrderSn])
                ];
                //用户积分变动修改                    
                $IntegralAdd = M('IntegralRecord')->add($IntegralAdd);
                if(!$IntegralAdd){
                    $Things->rollback();
                    echo 'FAIL';exit;
                }
                $EarningsAdd =[];
                $EarningsReduce =[];
                $AgentSave =[];
                $MemberAgentSave = [];
                //如果用户使用加油卷  --  则 减少加油卷数量 
                if (!empty($OrderInfo['coupon_money']) && $OrderInfo['coupon_money'] >0) {
                    if (intval($Member['currt_earnings']) >= intval($OrderInfo['coupon_money'])) {
                        $MemberAgentSave['currt_earnings'] =$Member['currt_earnings'] - $OrderInfo['coupon_money'];
                        //用户信息修改
                        $MemberAgentSave = M('Agent')->where(['openid'=>$openId])->save($MemberAgentSave);
                        //减少当前收益记录
                        $EarningsReduce['openid']       = $openId;
                        $EarningsReduce['agent_id']     = $Member['id'];
                        $EarningsReduce['createtime']   = $NowTime;
                        $EarningsReduce['order_type']   = 1;
                        $EarningsReduce['earning_body'] = 0;
                        $EarningsReduce['earnings']     = $OrderInfo['coupon_money'];
                        $EarningsReduce['updatetime']   = $NowTime;
                        $EarningsReduce['order_id']     = $OrderInfo['id'];
                        $EarningsReduce['sn']           = $OrderSn;
                        $EarningsReduce['log_type']     = 2;
                        //减少当前收益记录
                        $EarningsReduce = M('agent_earnings')->add($EarningsReduce);

                        if(!$MemberAgentSave && !$EarningsReduce){
                            $Things->rollback();
                            echo 'FAIL';exit;
                        }
                    }
                    
                }

                //是否存在上级代理 
                //当用户身份为代理时不做操作
                //当上级代理未绑定时不做操作
                //当上级代理为空 或者上级 代理身份是总部时 不做操作
                if ($Member['role'] !=3 && $Member['agent_bind'] == 1 && $Member['agentid'] !=0 && !empty($Member['agentid'])) {
                    $Agent=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.id'=>$Member['agentid'],'b.role'=>3])->find();
                    // vip_direct_scale  VIP直属会员充值分成
                    // user_direct_scale  普通直属会员充值分成
                    // vip_indirect_scale  VIP间接会员充值分成
                    // user_indirect_scale 普通间接会员充值分成
                    // user_profit  普通卡充值-对代理返利比例
                    // vip_profit  VIP卡充值，对代理返利比例

                    //用户充值的金额 ，使用真实面额
                    $RechageMoney = $order_item['money'];
                    //判断是直属下级还是间接下级身份 /// 此流程不适用
                    switch ($CardInfo['pkgid']) {
                        case '1':
                            $Calculation = $RechageMoney* ($config['user_profit']/100);
                            $rewardMoney  = number_format($Calculation, 4, ".", "");
                            $earning_body = 5; //普通卡充值
                                    break;
                            break;
                        
                        default:
                            $Calculation = $RechageMoney* ($config['vip_profit']/100);
                            $rewardMoney  = number_format($Calculation, 4, ".", "");
                            $earning_body = 6; //VIP卡充值
                            break;
                    }
                    /*switch ($Member['agent_relation']) {
                        case '1': //直接下级
                            //按照当前会员不同的身份为上级代理分润
                            switch ($Member['role']) {
                                case '2': //按照VIP会员充值分成给代理分润
                                    $Calculation = $RechageMoney* ($Agent['vip_direct_scale']/100);
                                    $rewardMoney  = number_format($Calculation, 4, ".", "");
                                    $earning_body = 1; //1直属vip
                                    break;
                                
                                default: //按照普通会员充值分成给代理分润
                                    $Calculation = $RechageMoney* ($Agent['user_direct_scale']/100);
                                    $rewardMoney  = number_format($Calculation, 4, ".", "");
                                    $earning_body = 2; //2直属普通
                                    break;
                            }
                            break;

                        default: //间接下级
                            //按照当前会员不同的身份为上级代理分润
                            switch ($Member['role']) {
                                case '2': //按照VIP会员充值分成给代理分润
                                    $Calculation = $RechageMoney* ($Agent['vip_indirect_scale']/100);
                                    $rewardMoney  = number_format($Calculation, 4, ".", "");
                                    $earning_body = 3; //3间接VIP 
                                    break;
                                
                                default: //按照普通会员充值分成给代理分润
                                    $Calculation = $RechageMoney* ($Agent['user_indirect_scale']/100);
                                    $rewardMoney  = number_format($Calculation, 4, ".", "");
                                    $earning_body = 4; //4 间接普通
                                    break;
                            }
                            break;
                    }*/


                    //代理返利记录
                    $EarningsAdd['openid']       = $openId;
                    $EarningsAdd['agent_id']     = $Member['agentid'];
                    $EarningsAdd['createtime']   = $NowTime;
                    $EarningsAdd['order_type']   = 1;
                    $EarningsAdd['earning_body'] = $earning_body;
                    $EarningsAdd['earnings']     = $rewardMoney;
                    $EarningsAdd['updatetime']   = $NowTime;
                    $EarningsAdd['order_id']     = $OrderInfo['id'];
                    $EarningsAdd['sn']           = $OrderSn;
                    $EarningsAdd['log_type']     = 1;
                    //代理收益记录
                    $EarningsAdd = M('agent_earnings')->add($EarningsAdd);
                    if(!$EarningsAdd){
                        $Things->rollback();
                        echo 'FAIL';exit;
                    }
                    //总收益
                    $AgentSave['total_earnings'] = $Agent['total_earnings'] + $rewardMoney;
                    //当前收益
                    $AgentSave['currt_earnings'] = $Agent['currt_earnings'] + $rewardMoney ;
                    //下线总充值
                    $AgentSave['add_total'] = $Agent['add_total'] + $RechageMoney;
                    //代理信息修改
                    $AgentSave = M('agent')->where(['id'=>$Agent['id']])->save($AgentSave);
                    if(!$AgentSave){
                        $Things->rollback();
                        echo 'FAIL';exit;
                    }
                    
                }
                if ($AddMoneySave && $OilCardSave && $OrderSave && $MemberSave && $IntegralAdd) {
                    $Things->commit();
                    $IsOver = true;
                    $ReturnMsg = '支付成功';
                }else{
                    $ReturnMsg = '支付失败';

                    $Things->rollback();
                }
                /*try{
                    //用户充值记录信息状态修改
                    $AddMoneySave = M('add_money')->where(['id'=>$order_item['id']])->save($AddMoneySave);
                    //油卡信息状态修改
                    $OilCardSave = M('oil_card')->where(['id'=>$CardInfo['id']])->save($OilCardSave);
                    //订单状态修改
                    $OrderSave = M('order_record')->where(['id'=>$OrderInfo['id']])->save($OrderSave);
                    //用户信息修改
                    $MemberSave = M('user')->where(['openid'=>$openId])->save($MemberSave);
                    //用户信息修改
                    if($MemberAgentSave)$MemberAgentSave = M('Agent')->where(['openid'=>$openId])->save($MemberAgentSave);
                    //用户积分变动修改                    
                    $IntegralAdd = M('IntegralRecord')->add($IntegralAdd);
                    //代理收益记录
                    if($EarningsAdd)$EarningsAdd = M('agent_earnings')->add($EarningsAdd);
                    //代理信息修改
                    if($AgentSave)$AgentSave = M('agent')->where(['id'=>$Agent['id']])->save($AgentSave);

                    if ($AddMoneySave && $OilCardSave && $OrderSave && $MemberSave && $IntegralAdd) {
                        $Things->commit();
                        $IsOver = true;
                        $ReturnMsg = '支付成功';
                    }else{
                        $ReturnMsg = '支付失败';

                        $Things->rollback();
                    }
                } catch (\Exception $e){
                    $Things->rollback();
                    $ReturnMsg = '支付失败';
                    Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
                    exit();
                }*/
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
        // ob_end_clean();
        if($IsOver){
            echo 'SUCCESS';exit;
            return $this->arrayToXml($data);
        }else{
            echo 'FAIL';exit;
            return $this->arrayToXml(['return_code'=>'FAIL','return_msg'=>'支付失败']);
        }
        
        return XML::build($data);
    }


    /**
     * 申领油卡异步回掉，包含线上 线下
     * @Author 老王
     * @创建时间   2018-12-31
     * @return [type]     [description]
     */
    public function wxAgentNoticePay()
    {
        $data = file_get_contents('php://input');
        Log::record('银牌申领回调:');
        $obj_arr = XML::parse($data);

        $insert = array(
            'content'=>json_encode(array(
                'InsertTime'=>date('Y-m-d H:i:s',time()),
                'InsertNote'=>'油卡申领',
                'input' =>$obj_arr,
                'data' =>$data,
            ))
        );
        M('testt')->add($insert);

        // $RAW = $GLOBALS['HTTP_RAW_POST_DATA'];
        // $RAW = json_decode($RAW);
        // $obj_arr = object_to_array($RAW);
        


        $openId=$obj_arr['openid'];
        $sign = $obj_arr['sign'];
        $NowTime = date('Y-m-d H:i:s',TIMESTAMP);
        $EndTime = date("Y-m-d H:i:s",strtotime("+1years"));//过期时间 1年
        unset($obj_arr['sign']);
        ksort($obj_arr);
        $string1 = urldecode(http_build_query($obj_arr).'&key='.CardConfig::$wxconf['pay_key']);
        $cur_sign = strtoupper(MD5($string1));
        //签名验证
        if($cur_sign === $sign) {                                                   
            //获取用户信息 根据微信openid查询对应的用户
            $Member=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.openid'=>$openId])->find();
            
            //获取订单信息
            $OrderInfo = M('order_record')->where(['serial_number'=>$obj_arr['out_trade_no']])->find();
            if ($OrderInfo['order_status']==2 && !empty($OrderInfo['pay_sn'])) {
                echo 'SUCCESS';exit;
                return $this->arrayToXml(['return_code'=>'SUCCESS','return_msg'=>'OK']);
            }
            //获取申领记录
            $apply_status=M('user_apply')->where(['serial_number'=>$obj_arr['out_trade_no']])->find();
            //套餐信息
            $Package =M('packages')->where(['pid'=>$OrderInfo['pid']])->find();
            //获取config  押金 邮费
            $config = M('setting')->find();
            $OrderSave =[
                'order_status' => 2,
                'updatetime' => $NowTime,
                'pay_sn' => $obj_arr['transaction_id'],
            ];
            //申领记录更新数据
            $ApplySave =[
                'note' => '油卡申领支付成功',
                'updatetime' => $NowTime,
                'apply_status' => 2,
            ];
            if ($OrderInfo['pid'] ==1) {
                $EndTime = '';
            }
            $Agent =M('agent')->where(['id'=>$Member['agentid'],'role'=>3])->find();
            //线下绑卡设置 -- 直接成功发放油卡，并绑定到用户名下
            if ($OrderInfo['online']==2) {
                //修改油卡信息
                $CardSave = [
                    'user_id'              => $Member['id'],
                    'apply_fo_time'        => $NowTime,
                    'status'               => 2,
                    'updatetime'           => $NowTime,
                    'chomd'                => 2,
                    'agent_status'         => 1,
                    'end_time'             => $EndTime,
                    'preferential'         => $Package['limits'],
                    'pkgid'                => $OrderInfo['pid'],
                    'desc'                 => '线下绑定油卡',
                ];
                $SendCard = M('oil_card')->where(['card_no'=>$OrderInfo['card_no']])->find();
                $CardSaveResult = M('oil_card')->where(['card_no'=>$OrderInfo['card_no']])->save($CardSave);
                //修改申领记录信息
                $ApplySave['deliver_number'] =1;
                $ApplySave['status'] =3;
                //订单修改
                $OrderSave['preferential_type'] =2;
                $OrderSave['send_card_no'] =$OrderInfo['card_no'];
            }else{
                $cardCondition =[
                                'status'    =>1,//库存卡
                                'chomd'     =>1,//未发放的卡
                                'is_notmal' =>1,//可用的卡
                                'activate'  =>1 //未激活的卡
                            ];
                //线上申领油卡，先从库存里获取一张应发卡号，修改此油卡信息
                switch ($OrderInfo['card_from']) {
                    case '2':
                        //从代理库存中取出一张卡号
                        $cardCondition['agent_id'] =$Agent['id'];//代理商名下的卡
                        break;
                    default:
                        //从总部库中取出一张卡号
                        $cardCondition['agent_id'] =0;//总部名下的卡
                        break;
                }
                //获取一张 应发卡
                $SendCard = M('oil_card')->where($cardCondition)->find();
                if ($SendCard) {
                    $OrderSave['send_card_no'] =$SendCard['card_no'];
                    //把应发卡号状态改为 已申领状态
                    $OrderSaveResult = M('oil_card')->where(['card_no'=>$SendCard['card_no']])->save(['status'=>2,'apply_fo_time'=>$NowTime]);
                }
                
            }
            //直接使用油卡的代理id 
            if ($SendCard) {
                $OrderSave['agent_id'] =$SendCard['agent_id'];
                $ApplySave['agentid']  =$SendCard['agent_id'];
            }else{
                $OrderSave['agent_id'] =$Agent['id'];
                $ApplySave['agentid']  =$Agent['id'];
            }
            
            if ($OrderInfo['card_from']==2) {
                //如果是代理发卡 ，代理库存减少 1,如果库存为0 并且是代理发卡则不减少,当总部给代理发卡时补充
                if($Agent['agent_oilcard_stock_num']>0){
                    $agent_oilcard_stock_num = $Agent['agent_oilcard_stock_num'] -1;
                    $ReduceAgentCardStock = M('agent')->where(['id'=>$Member['agentid'],'role'=>3])->save(['agent_oilcard_stock_num'=>$agent_oilcard_stock_num]);    
                }
                
            }
           
            if ($obj_arr['result_code']=='SUCCESS') {
                /* 
                1，判断用户是否为第一张卡，如果是第一次申领，锁定上级邀请人，如果没有，则为总部，锁定上级代理，如果没有，则为总部
                2，继续判断 此次申领油卡套餐是否为VIP套餐 
                    2.1 如果为普通套餐 ，上级邀请人无加油卷返利，对上级代理不做任何操作
                    2.2 如果为VIP套餐，对上级邀请人 返利加油卷 config里获取百分比，对上级代理不做操作
                */
                $MemberAgent=[];
                //用户每申领一张卡，需要增加一次此卡押金
                $MemberAgent['deposit']= ($Member['deposit']+$OrderInfo['user_deposit']);
                
                $isFirst = M('order_record')->where(['user_id'=>$Member['id'],'order_status'=>2])->find();
                if ($OrderInfo['pid'] > 1 && $Member['role']==1) {
                    //如果买的套餐是VIP套餐 就把会员身份改为VIP   -- 只做身份标识 --并没有什么用
                    $MemberAgent['role']=2;
                }
                M('agent')->where(['openid'=>$openId])->save($MemberAgent);

                if (!$isFirst) { //如果为第一次购买
                    $Robate=[];
                    if ($Member['parent_bind'] ==0 && $Member['agent_bind']==0) {
                        $Robate['agent_bind']=1;//锁定上级代理
                        $Robate['parent_bind']=1;//锁定上级邀请人
                    }
                    //判断是否给上级邀请人拉新奖
                    if ($OrderInfo['pid'] > 1 && $Member['is_rebate']==1){//如果购买的是VIP套餐 并且上级邀请人还未获得过拉新奖
                        $Robate['is_rebate']=2; //已完成拉新奖励
                        //获取上级邀请人信息
                        $Invite=M('user')
                                  ->alias('a')
                                  ->join('__AGENT__ b ON a.id=b.id')
                                  ->where(['a.id'=>$Member['parentid']])
                                  ->find();
                        //发放拉新奖--代理不享受此权益
                        if ($Invite && $Invite['role'] !=3) {
                            //保留两位小数 
                            $a = $Package['price'];
                            $b = ($config['scroll']/100);
                            $price = $a*$b;
                            $CouponNum = number_format($price, 2, ".", "");
                            //给上级邀请人增加 拉新奖励池和总收益 
                            $addEarnings = M('agent')->where(['id'=>$Invite['id']])->save([
                                'new_earnings'=>$Invite['new_earnings'] + $CouponNum,
                                'currt_earnings'=>$Invite['currt_earnings'] + $CouponNum,
                                'total_earnings'=>$Invite['total_earnings'] + $CouponNum
                            ]);
                            if ($addEarnings) {
                                //拉新奖励记录
                                $EarningsAdd['openid']       = $openId;
                                $EarningsAdd['agent_id']     = $Invite['id'];
                                $EarningsAdd['createtime']   = $NowTime;
                                $EarningsAdd['order_type']   = 2;
                                $EarningsAdd['earning_body'] = 7;
                                $EarningsAdd['earnings']     = $CouponNum;
                                $EarningsAdd['updatetime']   = $NowTime;
                                $EarningsAdd['order_id']     = $OrderInfo['id'];
                                $EarningsAdd['sn']           = $obj_arr['out_trade_no'];
                                $EarningsAdd = M('agent_earnings')->add($EarningsAdd);
                            }
                        }
                    }
                    //锁定上级代理，上级邀请人，上级拉新奖励已完成
                    if($Robate)M('user')->where(['id'=>$Member['id']])->save($Robate);
                }
                //修改订单状态
                $OrderSaveResult = M('order_record')->where(['id'=>$OrderInfo['id']])->save($OrderSave);
                
                //修改申领记录状态
                $ApplySaveResult = M('user_apply')->where(['id'=>$apply_status['id']])->save($ApplySave);
            }
            
            $result = [];
            $data['requestPayment'] = 'success';
            $data['return_msg'] = 'OK';
            echo 'SUCCESS';exit;
            return $this->arrayToXml(['return_code'=>'SUCCESS','return_msg'=>'OK']);
            // return log::record(XML::build($data));

        } else {
           Log::record('签名错误，订单号:'.$obj_arr['out_trade_no']);
        }
        
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
     * 油卡升级续费异步回调
     * @Author 老王
     * @创建时间   2019-01-08
     * @return [type]     [description]
     */
    public function upgradeNoticePay(){

        $data = file_get_contents('php://input');
        $obj_arr = XML::parse($data);
        $insert = array(
            'content'=>json_encode(array(
                    'InsertTime' =>date('Y-m-d H:i:s',time()),
                    'InsertNote' =>'油卡申领',
                    'input'      =>$obj_arr,
                    'data'       =>$data,
                )
            )
        );
        M('testt')->add($insert);

        $openId=$obj_arr['openid'];
        $sign = $obj_arr['sign'];
        unset($obj_arr['sign']);
        ksort($obj_arr);
        $string1 = urldecode(http_build_query($obj_arr).'&key='.CardConfig::$wxconf['pay_key']);
        $cur_sign = strtoupper(MD5($string1));
        if($cur_sign==$sign) {
            if($obj_arr['result_code']=='SUCCESS'){
                
            

                $NowTime = date('Y-m-d H:i:s',TIMESTAMP);
                $EndTime = date("Y-m-d H:i:s",strtotime("+1years"));//过期时间 1年
                $OrderInfo = M('order_record')->where(['serial_number'=>$obj_arr['out_trade_no'],'order_status'=>1])->find();
                if (!$OrderInfo){
                    echo 'FAIL';exit;  
                } 
                $Member=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.openid'=>$openId])->find();
                $Card = M('oil_card')->where(['card_no'=>$OrderInfo['card_no']])->find();
                $package = M('packages')->where(['pid'=>$OrderInfo['pid']])->find();
                //订单修改
                $OrderSave =[
                    'order_status'      => 2,
                    'updatetime'        => $NowTime,
                    'pay_sn'            => $obj_arr['transaction_id'],
                    'preferential_type' =>2
                ];
                $CardSave = [
                    'pkgid'      =>$package['pid'],
                    'updatetime' =>$NowTime,
                    'end_time'   => $EndTime 
                ];
                switch ($OrderInfo['order_type']) {
                    //升级 ->交会员费 ->把卡变成所购买的登记的油卡
                    case '4':
                        $CardSave['preferential'] = $package['limits'];
                        if ($Member['role'] ==1) {
                            M('agent')->where(['openid'=>$openId])->save(['role'=>2]);
                        }
                        break;
                    //续费->交会员费 ->如果在期限内,把油卡剩余额度叠加到此次购买的额度内->如果已过期,则油卡剩余额度清0,重新加入额度
                    case '5':
                        $cardTime = strtotime($Card['end_time']);
                        if ($cardTime < TIMESTAMP) {
                            $CardSave['preferential'] = $package['limits'];
                        }else{
                            $CardSave['preferential'] = ($Card['preferential']+$package['limits']);
                        }
                        break;
                }
                $insert['content']=json_encode(['OrderSave'=>$OrderSave,'CardSave'=>$CardSave]);
                M('testt')->add($insert);
                $OrderSave=M('order_record')->where(['id'=>$OrderInfo['id']])->save($OrderSave);
                $CardSave=M('oil_card')->where(['id'=>$Card['id']])->save($CardSave);
                if ($OrderSave && $CardSave) {
                    $insert['content']='订单修改成功';
                    M('testt')->add($insert);
                    echo 'SUCCESS';exit;
                }else{
                    $insert['content']='订单修改失败';
                    M('testt')->add($insert);
                    echo 'FAIL';exit;    
                }
            }else{
                $insert['content']='支付失败';
                M('testt')->add($insert);
                echo 'FAIL';exit;
            }
        } else {
            $insert['content']='签名错误';
            M('testt')->add($insert);
            echo 'FAIL';exit;
            Log::record('签名错误，订单号:'.$obj_arr['out_trade_no']);
        }
        exit;
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
        
        M('agent_relation')->where("openid='$openid'")->save(['agent_id'=>$a]);
            
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
    }

    

    /**
     * 新的代理拉vip
     * $openid vip的 openid
     * $recharge vip充值的年费 一次性的
     */
    public function AgentPullVip($openid,$recharge){
        
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
    }

    /**
     * 全新的vip拉vip
     */
    public function VipPullVipNew($openid,$recharge){
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
            
            log::record("添加agent——earnings数据结果");
            //更新代理商表
            $agent_data=[
                'add_total'=>$house_openid['add_total']+$recharge,
                'new_earnings'=>$house_openid['new_earnings']+$money,
                'currt_earnings'=>$house_openid['currt_earnings']+$money,
                'total_earnings'=>$house_openid['total_earnings']+$money
            ];
            M('agent')->where("id='$agent_id'")->save($agent_data);

            
            M('agent_relation')->where("openid='$openid'")->save(['agent_id'=>$a]);

        }
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
            exit(json_encode([
                'msg'=>'数据传输有误',
                'status'=>'500'
            ]));
        }
        $user_data=M('user')->where("openid='".$openid."'")->find();
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
                break;
        }

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
        $param = json_encode(array('scene'=>$openid));
        $json = $param;
        $result = $this->api_notice_increment($qcode, $json);
//        $result = $this->httpRequest($qcode, $param,"POST");
//        }
//

        $path="https://ysy.xiangjianhai.com/application/Oilcard/Controller/wechat/$openid.png";
//        $path="https://ysy.xiangjianhai.com/H/img/$openid.png";

        file_put_contents(__DIR__."/wechat/$openid.png",print_r($result,true));
//        file_put_contents($path,print_r($result,true));
        $this->success($path);


    }
//把请求发送到微信服务器换取二维码
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
        $user_img= I('post.user_img');
        $agent_openid=I('post.agent_openid','');//邀请人openid
        $APPID = 'wxd16b20528d23aff8';
        $AppSecret = 'b303f8f0002cd185cce101d63d342a85';
//        if (empty($code) && empty($data)){
//            log::record('小程序登录数据传输有误');
//            $this->error('数据传输有误');
//        }
//        $test_data = array(
//            'content'=>$agent_openid
//        );
//        M('testt')->add($test_data);
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

            $data= M('user')->where("openid='$openid'")->find();
            if (empty($data)){
                $user_id=M('user')->add(['openid'=>$openid]);
                M('agent')->add(['id'=>$user_id,'openid'=>$openid]);
            }
            //判断是否申领过
/*            $user_apply = M('user_apply')->where("openid='$openid'")->find();
            if(empty($user_apply)){
                if (!empty($agent_openid)){
                    //查询邀请人ID及邀请人代理商ID
                    $parent=M('user')
                        ->alias('u')
                        ->join('__AGENT__ a ON a.openid=u.openid',LEFT)
                        ->where('u.openid="'.$agent_openid.'"')
                        ->find();
                    if($parent['role'] == 3){
                        $parent_data=array(
                            'parentid'=>$parent['id'],//邀请人ID
                            'agentid'=>$parent['id'],//邀请人代理商ID
                            'agent_relation'=>1//直接关系
                        );
                    }else{
                        $parent_data=array(
                            'parentid'=>$parent['id'],//邀请人ID
                            'agentid'=>$parent['agentid'],//邀请人代理商ID
                            'agent_relation'=>2//间接关系
                        );
                    }

                    M('user')->where("openid='$openid'")->save($parent_data);

                }else{
                    $parent_data=array(
                        'parentid'=>0,//邀请人ID
                        'agentid'=>0,//邀请人代理商ID
                        'agent_relation'=>3//关系
                    );
                    M('user')->where("openid='$openid'")->save($parent_data);
                }
            }*/
            $this->success($arr);
            log::record('小程序登录返回数据'.$arr);

        }else {

            $arr= M('user')->where("openid='$openid'")->find();
            if (empty($arr)){
                $user_id=M('user')->add(['openid'=>$openid]);
                M('agent')->add(['id'=>$user_id,'openid'=>$openid]);
            }
//            $nickname=(array)json_decode($nickname);
            M('user')->where("openid='$openid'")->save(['nickname'=>$nickname,'user_img'=>$user_img]);
            $arr= M('user')->where("openid='$openid'")->find();
            log::record($agent_openid);
            //判断是否申领过
   /*         $user_apply = M('user_apply')->where("openid='$openid'")->find();
            if(empty($user_apply)){
                if (!empty($agent_openid)){
                    //查询邀请人ID及邀请人代理商ID
                    $parent=M('user')
                        ->alias('u')
                        ->join('__AGENT__ a ON a.openid=u.openid',LEFT)
                        ->where('u.openid="'.$agent_openid.'"')
                        ->find();
                    if($parent['role'] == 3){
                        $parent_data=array(
                            'parentid'=>$parent['id'],//邀请人ID
                            'agentid'=>$parent['id'],//邀请人代理商ID
                            'agent_relation'=>1//直接关系
                        );
                    }else{
                        $parent_data=array(
                            'parentid'=>$parent['id'],//邀请人ID
                            'agentid'=>$parent['agentid'],//邀请人代理商ID
                            'agent_relation'=>2//间接关系
                        );
                    }
                    M('user')->where("openid='$openid'")->save($parent_data);

                }else{
                    $parent_data=array(
                        'parentid'=>0,//邀请人ID
                        'agentid'=>0,//邀请人代理商ID
                        'agent_relation'=>3//关系
                    );
                    M('user')->where("openid='$openid'")->save($parent_data);
                }
            }*/
            $this->success($arr);
            exit;
        }
        /*$aid= M('agent_relation')->where("openid='$openid'")->getField('agent_id');
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
                            }*/
        /*$aid= M('agent_relation')->where("openid='$openid'")->getField('agent_id');
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
                            }*/
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

    /**
     * 设置异步回调返回地址
     * @Author 老王
     * @创建时间   2019-01-18
     * @param  [type]     $type [description]
     * @return [type]           [description]
     */
    public function _GetNotifyUrl($type){
        $notify_url = '';
        $Myurl = 'http://'.$_SERVER['HTTP_HOST'];
        switch ($type) {
            case '1'://1，申领
                //wxAgentNoticePay
                $notify_url = $Myurl.'/agentMoneyNotify.php';
                break;
            case '3': //3，充值
                //wxNoticePay
                $notify_url = $Myurl.'/addMoneyNotify.php';
                break;
            case '4'://4升级
                //upgradeNoticePay
                $notify_url = $Myurl.'/upgradeNotify.php';
                break;
            case '5': //5续费
                //upgradeNoticePay
                $notify_url = $Myurl.'/upgradeNotify.php';
                break;
        }
        return $notify_url;
    }

    /**
     * 微信支付
     * @Author 老王
     * @创建时间   2019-01-18
     * @param  [type]     $Order  [订单信息]
     * @param  [type]     $Member [用户信息]
     * @param  [type]     $PayCon [支付信息]
     * @return [type]             [description]
     */
    public function _WxPay($Order,$Member,$PayCon)
    {
        switch ($PayCon['paymoney']) {
            case '2':
                $payMoney = 1;
                break;
            default:
                $payMoney = $Order['real_pay']*100;
                break;
        }
        $orderSn = isset($Order['serial_number'])?$Order['serial_number']:$Order['order_no'];
        //微信统一下单
        $data                         = [];
        $data['appid']                = CardConfig::$wxconf['appid'];
        $data['mch_id']               = CardConfig::$wxconf['mch_id'];
        $data['device_info']          = 'WEB';
        $data['nonce_str']            = Tool::randomStr(20);
        $data['sign_type']            = 'MD5';
        $data['body']                 = $PayCon['body'];
        $data['detail']               = isset($PayCon['detail'])?$PayCon['detail']:$PayCon['body'];
        $data['attach']               = isset($PayCon['attach'])?$PayCon['attach']:$PayCon['body'];
        $data['out_trade_no']         = $orderSn;
        $data['fee_type']             = 'CNY';
        $data['total_fee']            = $payMoney;//$order_item['real_pay'] * 100; // 分
        $data['spbill_create_ip']     = Tool::getClientIp();
        $data['time_start']           = date('YmdHis');
        $data['time_expire']          = date('YmdHis',time()+7200);
        //        $data['notify_url'] = $this->my_uri.'/index.php?g=oilcard&m=wechat&a=wxNoticePay';
        $notify_url =$this->_GetNotifyUrl($Order['order_type']);
        if (empty($notify_url)) exit(json_encode(['msg'=>'创建订单失败！','status'=>500]));
        $data['notify_url']           = $notify_url;
        $data['trade_type']           = 'JSAPI';
        $data['openid']               = $Member['openid'];
        ksort($data);
        $string1                      = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
        $data['sign']                 = md5($string1);
        $content                      = XML::build($data);
        $ch_url                       = $this->pay_uri.'/pay/unifiedorder';
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

    public function _HjPay($Order,$Member,$PayCon){
        switch ($PayCon['paymoney']) {
            case '2':
                $payMoney = 1;
                break;
            default:
                $payMoney = $Order['real_pay']*100;
                break;
        }
        $orderSn = isset($Order['serial_number'])?$Order['serial_number']:$Order['order_no'];
        //微信统一下单
        
        $data                = [];
        $data['signType']    = 'RSA';
        $data['appId']       = CardConfig::$wxconf['appid'];
        $data['merchantSn']  = CardConfig::$wxconf['mch_id'];
        $data['outTradeNo']  = $orderSn;
        $data['tradeType']   = 'WX';
        $data['goodsBody']   = $PayCon['body'];
        $data['goodsDetail'] = isset($PayCon['detail'])?$PayCon['detail']:$PayCon['body'];
        $data['feeType']     = 'CNY';
        $data['totalFee']    = $payMoney;
        $data['userId']      = $Member['openid'];
        $data['attach']      = isset($PayCon['attach'])?$PayCon['attach']:$PayCon['body'];
        $data['remark']      = isset($PayCon['attach'])?$PayCon['attach']:$PayCon['body'];
        $data['expiredTime'] = '3';
        $notify_url =$this->_GetNotifyUrl($Order['order_type']);
        if (empty($notify_url)) exit(json_encode(['msg'=>'创建订单失败！','status'=>500]));
        $data['notifyUrl'] = $notify_url;
        p($data);
        // 实例化Demo类
        $hjpay = new HJCloudConfig();
        

        try {
            // 设置秘钥文件
            $hjpay->setPrivateKey('MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBALqZX4pzN6jxJbNbXhuz+Drh8Obt7ekDrEPz2SK0IKoay6SDPiJMJXLqh69doiWjP2pim6/JrrsuBr3QFMjGIx0EnBSf354qorWNhkj+lkAcQnQ98NlziTgTg7vx2o3piCcJAa7i2WhbLegs1xtatwSeEY/weqJwZh7dOxmelEsJAgMBAAECgYAxNW9HsLjV+bpKgWbhAWYOCTWhgM+D6q8MQItbposSsPxRRzckjlY15vmfWp7/M/zuTlDmW9aTkEDA39YLWI07jsmaGOA8RbPinswzIWnowNVFQag/n21tpAL2/CGNkpe+7F667nZyD7htCYwz6ARBMUM+eH52MNEMcPSbOBM9PQJBAPUav3oCgnYx/F8nLzlW9+gSOD1oCK5GQUC1+TTwaPUfZeCl8CeHT/7DgdvyUUMm9CyEzhacl4xZPzWN+ijZIF8CQQDC5NfMtDHSCGknwMnZb4mxTpzrby+pnwVvxmJeOg+QTafAwHqIhh9wVLQNEJy0PojYOMpjA9GE1Wms537Pnq2XAkEArCkij3/NxVms6+UpHXyB2ydZC4DUgBzm3p4zMkUfY/Wu6JGF0y4POWJ4B1b4T1PANLj/zRAmvrU9Wc+lBCYmvwJBALv94esjJatjUYt2+z0xya+uFM9EwMTtD2FyCxC5EKoxPc8/2vI17b189vBjRcTXTUjD/vTjigaHlRejdT7v4KECQA2DEOHgZv39PmNIZJekcfNGXPrdHPU0eAEtanMCr6hTiN+jO4x66rrGXIa4aoZ3ezXq/sASLm4zuGuF65m9bnc=');

            $hjpay->setPublicKey('MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6mV+Kczeo8SWzW14bs/g64fDm7e3pA6xD89kitCCqGsukgz4iTCVy6oevXaIloz9qYpuvya67Lga90BTIxiMdBJwUn9+eKqK1jYZI/pZAHEJ0PfDZc4k4E4O78dqN6YgnCQGu4tloWy3oLNcbWrcEnhGP8HqicGYe3TsZnpRLCQIDAQAB');
            // 设置请求参数
            $hjpay->setRequestData($data);
            // 设置请求地址
            $hjpay->setRequestUrl('https://open.smart4s.com/Api/Service/Pay/Mode/JSApi/tradePayJSApi');
            // 发起请求
            $res = $hjpay->doRequest();
            $res = json_decode($res, true);
            $verifyResult = false;
            $verifyResult = $hjpay->verifyRSASign($res);
            p($hjpay);
            p($res);
            var_dump($verifyResult);

            // 处理返回结果
        } catch (Exception $e) {
            //todo::异常处理
            p($e->getMessage());
        }
        exit;
    }
}
