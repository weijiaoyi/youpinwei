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
    private $appid;
    private $secret;
    static $card_number;
    static $card_no;
    static $data;
    private $base_uri = 'https://api.weixin.qq.com';
    private $my_uri = 'http://ysy.xiangjianhai.com';
    private $pay_uri = 'https://open.smart4s.com';

    public function __construct()
    {
        parent::__construct();
        header('content-type:text/html;charset=utf-8');
        $this->appid = CardConfig::$wxconf['appid'];
        $this->secret = CardConfig::$wxconf['appsecret'];
    }


    /**
     * 充值下单接口
     */
    public function payOrder($create_res,$record_res,$openid)
    {
        //微信统一下单
        $data = [];
        $data['signType'] = 'RSA';//签名类型
        $data['appId'] = CardConfig::$wxconf['appid'];//开发者APPid
        $data['merchantSn'] = CardConfig::$wxconf['mch_id'];//商户编号
        $data['outTradeNo'] = $create_res['order_no'];//商户订单号
        $data['tradeType'] = 'WX';//支付类型
        $data['goodsBody'] = '中国石油加油卡';//商品描述
        $data['goodsDetail'] = '购买加油卡';//商品详情描述
        $data['totalFee'] = $create_res['real_pay']*100;//总金额
        $data['userId'] = $openid;//用户openid
        $data['attach'] = '充值购买';
        $data['remark'] = '';
        $data['expiredTime'] = '';
        $data['notifyUrl'] = $this->my_uri.'/TestWxNotify.php';
        ksort($data);
        $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
        $data['sign'] = md5($string1);

        $content = XML::build($data);
        $ch_url = $this->pay_uri.'/Api/Service/Pay/Mode/MiniProgram/tradePayMiniProgram';
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
        var_dump($obj_arr);exit;
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
     * 申请为银牌代理下单接口
     */
    public function agentPay($OrderInfo,$data,$openid)
    {
        //微信统一下单
        $data = [];
        $data['signType'] = 'MD5';//签名类型
        $data['appId'] = CardConfig::$wxconf['appid'];//开发者APPid
        $data['merchantSn'] = CardConfig::$wxconf['mch_id'];//商户编号
        $data['outTradeNo'] = $OrderInfo['serial_number'];//商户订单号
        $data['tradeType'] = 'WX';//支付类型
        $data['goodsBody'] = $OrderInfo['online']==1?'线上申领油卡':'线下绑定油卡';//商品描述
        $data['goodsDetail'] = '油卡业务办理';//商品详情描述
        $data['totalFee'] = $OrderInfo['real_pay']*100;//总金额
        $data['userId'] = $openid;//用户openid
        $data['attach'] = '缴纳年费';
        $data['remark'] = '';
        $data['expiredTime'] = '';
        $data['notifyUrl'] = $this->my_uri.'/TestWxNotify.php';
        ksort($data);
        $string1 = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
        $data['sign'] = md5($string1);

        $content = XML::build($data);
        $ch_url=$this->pay_uri.'/Api/Service/Pay/Mode/MiniProgram/tradePayMiniProgram';
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


}