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


}