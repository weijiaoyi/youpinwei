<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/14
 * Time: 11:50
 */

namespace Oilcard\Controller;

use Comment\Controller\CommentoilcardController;
use Oilcard\Conf\CardConfig;
use Think\Log;

class IntegralController extends CommentoilcardController
{
    /**
     * 积分记录
     */
    public function integralRecord()
    {
        $openid = trim(I('post.openid'));
        if (!isset($openid) || ! $openid)
        {
            $this->error('openid不能为空！');
        }

        $user = M('User')->where(['openid'=>$openid])->find();

        if (!$user)
        {
            //跳转到微信登录url
            return redirect(U('oilcard/wechat/getCode'));
        }

        $integral_record = M('integralRecord')->where(['user_id'=>$user['id']])->order("createtime desc")->select();
        $record = [];
        foreach ($integral_record as $k=>$v)
        {
            $record[$k]['time'] = $v['createtime'];
            $record[$k]['change'] = $v['change'];
            $record[$k]['chang_way'] = $v['chang_way'];
            $record[$k]['change_value'] = $v['change_value'];
            if ($v['change'] == 1){
                $record[$k]['change_value'] = '+'.$v['change_value'];
            }elseif ($v['change'] == 2){
                $record[$k]['change_value'] = '-'.$v['change_value'];
            }



        }
        $integral = [];
        $integral['time'] = date('Y年m月d日 H:i:s',time());
        $integral['integral'] = $user['integral'] ?: 0;

        $output = [];
        $output['integral'] = $integral;
        $output['record']= $record;

        echo json_encode(['msg'=>'success','status'=>1000,'data'=>$output]);
        exit();
    }


    /**
     * 充值记录
     */
    public function addMoneyRecord()
    {
        $openid  = trim(I('post.openid','os2aR0QzCHW2sqbrDGj1s0L5TJSQ'));
        $page = trim(I('post.page',1)) - 1;
        $offset = trim(I('post.offset',20));
        if (!isset($openid) || ! $openid)
        {
            $this->error('openid不能为空！');
        }

        $user = M('User')->where(['openid'=>$openid])->find();

        if (!$user)
        {
            //跳转到微信登录url
            return redirect(U('oilcard/wechat/getCode'));
        }

        $record = M('addMoney')->where(['user_id'=>$user['id']])->limit($page*$offset,$offset)->order("createtime desc")->select();
        $count = M('addMoney')->where(['user_id'=>$user['id']])->count();
        $output = [];
        foreach ($record as $k=>$v)
        {
            $output[$k]['order_no'] = 'SN'.$v['order_no'];
            $output[$k]['card_no'] = $v['card_no'];
            $output[$k]['money'] = $v['money'];
            $output[$k]['real_pay'] = $v['real_pay'];
            $output[$k]['discount_money'] = $v['discount_money'];
            $output[$k]['status'] = CardConfig::$payStatus[$v['status']];
            $output[$k]['createtime'] = $v['createtime'];
            $output[$k]['flag'] = CardConfig::$addMoneyIcon[$v['status']];
        }

        echo json_encode(['msg'=>'success',
            'status'=>1000,
            'count'=>$count,
            'page_count'=>ceil($count/$offset),
            'data'=>$output]);
        exit();
    }

    /**
    *查询此卡是否充值过
    */
    public function cardOrder($card_no){

        if (empty($card_no)) {
            $this->error('数据传输缺失');
        }

        $cardOrderData=M('order_record')->where("card_no='$card_no'")->find();
        if (empty($cardOrderData)) {
            $this->error('此前未充值，无法享受单词200元充值','502');
        }else{
            $this->success();
        }

    }

    /**
    *充值下单接口
    */
    public function createAddMoneyOrder()
    {
        $card_no = trim(I('post.card_no'));
        $openid  = trim(I('post.openid'));

        $money   = trim(I('post.money'));

        if (empty($money)) {
            $this->error('请选填充值金额');
        }


        $initial_money=trim(I('post.money'));//折扣前价格
        $flag=trim(I('post.flag',''));//1，选择卡优惠  2，选择账户优惠

        $coupon_id = trim(I('post.coupon_id'));
        $flage=96;
        if (empty($flag)){
            $flage=1;
        }

        Log::record('创建订单金额:'.$money.'元！');

        if (!isset($card_no) || ! $card_no)
        {
            $this->error('卡号不能为空！');
        }
        $card_sale=M('oil_card')->where("card_no='$card_no'")->getField('is_sale');
        if ($card_sale==2){
            $this->error('正在退卡中');
        }else if($card_sale==3){
            $this->error('此卡已退卡');
        }
        if (!isset($openid) || ! $openid)
        {
            $this->error('openid不能为空！');
        }

        $user_status=M('user')->where("openid='$openid'")->getField('is_notmal');
        if ($user_status==2){
            $this->error('当前用户已被冻结');
        }elseif ($user_status==3){
            $this->error('当前用户已被注销');
        }

        $oil_card = M('OilCard')->where(['card_no'=>$card_no,'status'=>'2'])->find();
        $a=M('')->getLastSql();
        if (empty($oil_card)){ $this->error('无效卡号！');}

        $user = M('User')->where(['openid'=>$openid])->find();
        if (!$user)
        {
            //跳转到微信登录url
            return redirect(U('oilcard/wechat/getCode'));
        }

        $order_record = [];
        $order_item = [];
        $order_item['user_id']           = $user['id'];
        $order_item['openid']            = $openid;
        $order_item['card_no']           = $card_no;
        $order_item['money']             = $money;
        if ($flag==1){
            $date=date('Y-m-d H:i:s');
            $card_arr=M('oil_card')->where("card_no='$card_no' and end_time>'$date' and is_sale!='3' ")->find();
            $preferential=$card_arr['preferential'];
            log::record($card_no);
            log::record($preferential);
            if (empty($preferential)){
                $order_item['real_pay']= ($money * $oil_card['discount'])/100;
                log::record('折扣定型1');
                $flage=96;
            }else{
//                if($preferential<($money * $oil_card['discount'])/100){
                if($preferential>$money){
//                    $order_item['real_pay']= $money /100* $oil_card['discount']-$preferential;
                    $order_item['real_pay']= $money /100*93.5;
                    $flage=93;
                    log::record('折扣定型2');
                    log::record($money);
                    log::record($preferential);
                }else{
                    $order_item['real_pay']=$money /100* $oil_card['discount'];
                    $flage=96;
                    log::record('折扣定型3');
                }
            }
        }else{
            $card_order=M('agent')->where("openid='$openid'")->find();
            if (empty($card_order['preferential']) && $card_order['currt_earnings']<$money){
                $order_item['real_pay']= $money/100 * 93.5;
                $flage=96;
                log::record('折扣定型4');
            }else{
                $order_item['real_pay']= $money /100* $oil_card['discount'];
                $flage=93;
                log::record('折扣定型5');
            }

        }

        $order_item['discount_money']    = $money-$order_item['real_pay'];
        $order_item['pay_way']           = '1';
        $order_item['order_no']          = date('YmdHis').str_pad(mt_rand(1,999999),6,STR_PAD_LEFT);
        $order_item['status']            = 4;//待支付

        if ($coupon_id) {
            $coupon = M('Coupon')->where(['id'=>$coupon_id,'status'=>1])->find();
            if ($coupon) {
                $order_item['coupon_id'] = $coupon_id;
                $order_item['real_pay'] = $order_item['real_pay'] - intval($coupon['replace_money']);
                $order_record['coupon_id'] = $coupon_id;
            }
        }

        //如果不是代理并且有上线，添加分销关系
        $is_agent = M('Agent')->where(['openid'=>$openid])->find();
        if (!empty($is_agent)) {
            $user_agent = M('AgentRelation')->where(['openid'=>$openid])->find();
            log::record("充值下单添加addmoney数据".json_encode($order_item));
            if (!empty($user_agent['agent_id'])) {
                $agent = M('Agent')->where(['id'=>$user_agent['agent_id']])->find();
//                if (!empty($agent) && $agent['expire_time'] > date('Y-m-d H:i:s')){
                    $order_item['agent_id'] = $user_agent['agent_id'];
                    $order_record['agent_id'] = $user_agent['agent_id'];
//                }
            }

        }

        log::record("充值下单添加addmoney数据".json_encode($order_item));
        $create_res = M('AddMoney')->add($order_item);

        $order_record['user_id']         = $user['id'];
        $order_record['card_no']         = $card_no;
        $order_record['order_type']      = 3;
        $order_record['serial_number']   = $order_item['order_no'];
        $order_record['order_status']    = 1;
        $order_record['money']           = $order_item['money'];
        $order_record['real_pay']        = $order_item['real_pay'];
        $order_record['discount_money']  = $order_item['discount_money'];

        $record_res = M('OrderRecord')->add($order_record);


        if ($create_res && $record_res){

            $wechat = new WechatController();
            log::record('debug');
            $data = $wechat->payOrder($create_res,$openid,$flag,$flage,$initial_money);
            $data['order_no'] = $order_item['order_no'];
            Log::record('创建订单返回:'.json_encode($data));
            if (empty($data))
            {
                echo json_encode(['msg'=>'微信下单失败！','status'=>500]);
                exit();
            }
            $order_no=$order_item['order_no'];
            $order_status=M('OrderRecord')->where("order_status='2' and serial_number='$order_no'")->find();
             log::record('充值返回是否成功:'.json_encode($order_status));
            $a=M('')->getLastSql();
            log::record('查询充值返回是否成功:'.$a);
           
            
            
            echo json_encode(['msg'=>'success','status'=>1000,'data'=>$data]);
            exit();

        }else {
            echo json_encode(['msg'=>'创建订单失败！','status'=>500]);
            exit();
        }


    }

    /**
     * 充值获取可用优惠额度和套餐
     */
    public  function discount_surplus(){
        $openid=I('post.openid','');
        $card_no=I('post.card_no','');

     // 查询此卡首冲是否为500以上
        if (empty($card_no)){
            $this->error('数据传输缺失');
        }

        $this->_empty($openid,'用户未登陆');

        $user_data=M('agent')->where("openid='$openid'")->find();
        $discount=$user_data['currt_earnings'];//用户名下优惠额度
        $qackage=M('oil_card')->where("card_no='$card_no'")->getField('preferential');//绑定卡的优惠额度
        $data['qackage']=$qackage;
        $data['discount']=$discount;
        if (empty($data)){
            $this->error('0');
        }else{
            $card_order=M('order_record')->where("card_no='$card_no' and order_type=3 and order_status=2")->find();
            if (empty($card_order)){
                $this->success($data,1001);
            }
            $this->success($data);
        }
    }
    /**
     * 绑定获取优惠额度套餐
     */
    public  function  bindCardQuota(){
        $openid=I('post.openid','');
        $this->_empty($openid);
        $user_id=M('user')->where("openid='$openid'")->getField('id');
        $order_arr=M('order_record')->where("user_id='$user_id' and order_status='2' and order_type='1' and preferential>0 and preferential_type='1'")->select();
        if (empty($order_arr)){
            echo  json_encode([
                'data'=>json_encode($order_arr),
                'status'=>1000
            ])  ;exit;
        }else{
            echo  json_encode([
                'data'=>json_encode($order_arr),
                'status'=>1000
            ])  ;exit;
//            $this->success($data);
        }
    }

}