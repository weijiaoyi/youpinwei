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

class WikiController extends CommentoilcardController
{
    private $appId = 'C9q255qIg1Zp72yI';
    private $merchantSn='PHT2017000000002';
    static $card_number;
    static $card_no;
    static $data;
    private $my_uri = 'http://ysy.xiangjianhai.com';
    private $pay_uri = 'https://open.smart4s.com';
    private  $private_key = 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBALqZX4pzN6jxJbNbXhuz+Drh8Obt7ekDrEPz2SK0IKoay6SDPiJMJXLqh69doiWjP2pim6/JrrsuBr3QFMjGIx0EnBSf354qorWNhkj+lkAcQnQ98NlziTgTg7vx2o3piCcJAa7i2WhbLegs1xtatwSeEY/weqJwZh7dOxmelEsJAgMBAAECgYAxNW9HsLjV+bpKgWbhAWYOCTWhgM+D6q8MQItbposSsPxRRzckjlY15vmfWp7/M/zuTlDmW9aTkEDA39YLWI07jsmaGOA8RbPinswzIWnowNVFQag/n21tpAL2/CGNkpe+7F667nZyD7htCYwz6ARBMUM+eH52MNEMcPSbOBM9PQJBAPUav3oCgnYx/F8nLzlW9+gSOD1oCK5GQUC1+TTwaPUfZeCl8CeHT/7DgdvyUUMm9CyEzhacl4xZPzWN+ijZIF8CQQDC5NfMtDHSCGknwMnZb4mxTpzrby+pnwVvxmJeOg+QTafAwHqIhh9wVLQNEJy0PojYOMpjA9GE1Wms537Pnq2XAkEArCkij3/NxVms6+UpHXyB2ydZC4DUgBzm3p4zMkUfY/Wu6JGF0y4POWJ4B1b4T1PANLj/zRAmvrU9Wc+lBCYmvwJBALv94esjJatjUYt2+z0xya+uFM9EwMTtD2FyCxC5EKoxPc8/2vI17b189vBjRcTXTUjD/vTjigaHlRejdT7v4KECQA2DEOHgZv39PmNIZJekcfNGXPrdHPU0eAEtanMCr6hTiN+jO4x66rrGXIa4aoZ3ezXq/sASLm4zuGuF65m9bnc=';
    private $public_key = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6mV+Kczeo8SWzW14bs/g64fDm7e3pA6xD89kitCCqGsukgz4iTCVy6oevXaIloz9qYpuvya67Lga90BTIxiMdBJwUn9+eKqK1jYZI/pZAHEJ0PfDZc4k4E4O78dqN6YgnCQGu4tloWy3oLNcbWrcEnhGP8HqicGYe3TsZnpRLCQIDAQAB';

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
//        $data['goodsBody'] = $OrderInfo['online']==1?'线上申领油卡':'线下绑定油卡';//商品描述
//        $data['goodsDetail'] = '油卡业务办理';//商品详情描述
        $data['totalFee'] = $OrderInfo['real_pay']*100;//总金额
        $data['userId'] = $openid;//用户openid
        $data['attach'] = '缴纳年费';
//        $data['remark'] = '缴纳年费';
//        $data['expiredTime'] = 5;
        $data['notifyUrl'] = $this->my_uri.'/TestWxNotify.php';
        $sign = $this->setRSASign($data);
        $data['sign'] = $sign['sign'];


        $test = array(
            'content'=>json_encode($data)
        );
        M('testt')->add($test);
        $url = $this->pay_uri.'/Api/Service/Pay/Mode/JSApi/tradePayJSApi';
        $con = curl_init($url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ]
        );
        curl_setopt($con, CURLOPT_TIMEOUT, (int)5);
        $content = curl_exec($con);
        curl_close($con);
        $data = [];
        $test = array(
            'content'=>json_encode($content)
        );
        M('testt')->add($test);

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

        //微信统一下单
        $data = [];
        $data['appid']                = CardConfig::$wxconf['appid'];
        $data['mch_id']               = CardConfig::$wxconf['mch_id'];
        $data['device_info']          = 'WEB';
        $data['nonce_str']            = Tool::randomStr(20);
        $data['sign_type']            = 'MD5';
        $data['body']                 = $body;
        $data['detail']               = $body;
        $data['attach']               = $body;
        $data['out_trade_no']         = $OrderSn;
        $data['fee_type']             = 'CNY';
        $data['total_fee']            = $money*100;//正确的是20000
        $data['spbill_create_ip']     = Tool::getClientIp();
        $data['time_start']           = date('YmdHis');
        $data['time_expire']          = date('YmdHis',time()+7200);
        //        $data['notify_url'] = $this->my_uri.'/index.php?g=oilcard&m=wechat&a=wxNoticePay';
        $data['notify_url']           = $this->my_uri.'/upgradeNotify.php';

        $data['trade_type']           = 'JSAPI';
        $data['openid']               = $openid;
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
            $OrderAdd = M('order_record')->add($Order);
            if(!$OrderAdd)$this->error('订单生成失败!');
        }
        $this->success($data);
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
    public function verifyRSASign(array $sign_data){
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

}