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
     * 油卡充值接口
     * @Author 老王
     * @创建时间   2018-12-31
     * @return [type]     [description]
     */
    public function createAddMoneyOrder()
    {
        $openid  = trim(I('post.openid'));
        $card_no = trim(I('post.card_no'));
        if (empty($card_no) || !$card_no)$this->error('卡号不能为空！');
        $money   = trim(I('post.money'));//实际充值金额
         if (empty($money)) $this->error('请选填充值金额');
        $pay_money=trim(I('post.pay_money'));//实际支付金额
        $save=trim(I('post.save')); //优惠金额
        $flag=trim(I('post.flag',1));//1，选择卡优惠  2，选择账户优惠
        $NowTime = date('Y-m-d H:i:s',TIMESTAMP);
        $initial_money=trim(I('post.money'));//折扣前价格
        //油卡信息
        $CardInfo = M('oil_card')->where(['card_no'=>$card_no,'status'=>2])->find();
        if (empty($CardInfo))$this->error('无效卡号！');
        //用户信息
        $Member=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.openid'=>$openid])->find();
        if (!$Member)$this->error('需要先授权登陆之后才能做此操作！');
        if(!$Member['nickname'] || !$Member['user_img'])$this->error('需要先授权登陆之后才能做此操作！');
        if ($Member['is_notmal'] !=1)$this->error('当前用户信息异常，已被冻结用户信息，请向管理员或代理查询！');
        //当前油卡所选择的套餐信息
        $Package = M('packages')->where(['pid'=>$CardInfo['pkgid']])->find(); 
        $config = M('setting')->find();
        //不是正常油卡
        if ($CardInfo['is_notmal'] !=1) {
            //对此卡的操作信息
            $CardOption = M('oil_option')->where(['userid'=>$Member['id'],'cardid'=>$CardInfo['id']])->find();
            if($CardOption){
                switch ($CardOption['type']) {
                    case '1':
                        $this->error('此油卡持有者已向后台申请退卡请求！');
                        break;
                    case '2':
                        $this->error('此油卡持有者已向后台申请挂失请求！');
                        break;
                    default:
                        $this->error('系统已对此油卡冻结使用，理由：'.empty($CardOption['desc'])?:'无');
                        break;
                }
            }else{
                $this->error('系统已对此油卡冻结使用，理由：'.$CardInfo['is_notmal']==2?'油卡信息异常':'用户挂失或已废弃');  
            }
        }
        //订单号
        $orderSn = date('YmdHis').str_pad(mt_rand(1,999999),6,STR_PAD_LEFT);
        $OrderAdd = [
            'user_id' => $Member['id'],
            'card_no' => $card_no,
            'order_type' => 3,
            'serial_number' => $orderSn,
            'order_status' => 1,
            'real_pay' => $pay_money,
            'recharge_money' => $money,
            'createtime' => $NowTime,
            'card_from' => $CardInfo['agent_id']==0?1:2,
            'agent_id' => empty($CardInfo['agent_id'])?$CardInfo['agent_id']:0,
            'parentid' => $Member['parentid'],
        ];
        //判断当前油卡额度
        $BeforRechage =$CardInfo['preferential'];
        $AfterRechage = $CardInfo['preferential'] - $money;
        if ($BeforRechage < 1 || $AfterRechage <0) {
            $OrderAdd['pid'] = 1;
        }else{
            $OrderAdd['pid'] = $CardInfo['pkgid'];
        }
        switch ($flag) {
            case '2': // 使用加油卷抵扣
                $OrderAdd['coupon_money'] = $save;
                break;
            default: //使用油卡本身自带的折扣
                $OrderAdd['discount_money'] = $save;
                break;
        }
        //生成订单
        $record_res = M('OrderRecord')->add($OrderAdd);
        if(!$record_res)$this->error('订单生成失败，请重试！');
        $RechageCount = M('AddMoney')->where(['card_no'=>$card_no])->count();
        $is_first =2;
        if ($RechageCount==1) {
            if ($money < $config['first_rechage']) $this->error('当前油卡首次充值额度必须大于'.$config['first_rechage'].'元额度才能被激活！');
            $is_first = 1;
        }
        $AddMoneySave = [
            'user_id' => $Member['id'],
            'openid' => $openid,
            'card_no' => $card_no,
            'money' => $money,
            'discount_money' => $save,
            'real_pay' => $pay_money,
            'pay_way' => 1,
            'note' => $RechageCount ==1?'用户对此油卡的首次充值':'油卡额度充值',
            'status' => 2,
            'createtime' => $NowTime,
            'order_no' => $orderSn,
            'agent_id' => $Member['agentid'],
            'is_first' => $is_first,
        ];
        $create_res = M('add_money')->add($AddMoneySave);


        if ($create_res && $record_res){

            $wechat = new WechatController();
            $data = $wechat->payOrder($AddMoneySave,$OrderAdd,$openid);
            $data['order_no'] = $AddMoneySave['order_no'];
            
            if (empty($data))exit(json_encode(['msg'=>'微信下单失败！','status'=>500]));
            exit(json_encode(['msg'=>'success','status'=>1000,'data'=>$data]));

        }else {
            exit(json_encode(['msg'=>'创建订单失败！','status'=>500]));
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