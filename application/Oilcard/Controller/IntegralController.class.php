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
        $p=I('post.p','');
        $offset=I('post.offset','20');

        if (empty($p)){
            $page=0;
        }else{
            $page=($p-1)*$offset;
        }
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

        $integral_record = M('integralRecord')
            ->where(['user_id'=>$user['id']])
            ->order("createtime desc")
            ->limit($page,$offset)
            ->select();
        $record = [];
        foreach ($integral_record as $k=>$v)
        {
            $record[$k]['time'] =date('Y-m-d H:i',strtotime($v['createtime']));
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
        $integral['integral'] = $user['integral'];

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
         if (empty($pay_money) || $pay_money < 0 ) $this->error('支付金额不正确！');
        $save=trim(I('post.save')); //优惠金额
        $flag=trim(I('post.flag',1));//1，选择卡优惠  2，选择账户优惠
        $NowTime = date('Y-m-d H:i:s',TIMESTAMP);
        $initial_money=trim(I('post.money'));//折扣前价格
        $jyj =trim(I('post.jyj',0));
        $zk = trim(I('post.zk',0));
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
                        $this->error('系统已对此油卡冻结使用，请向管理员或代理查询！');
                        break;
                }
            }else{
                $this->error('系统已对此油卡冻结使用，请向管理员或代理查询！');
//                $this->error('系统已对此油卡冻结使用，理由：'.$CardInfo['is_notmal']==2?'油卡信息异常':'用户挂失或已废弃');
            }
        }
        //订单号
        $orderSn = date('YmdHis').str_pad(mt_rand(1,999999),6,STR_PAD_LEFT);

        $OrderAdd = [
            'user_id'        => $Member['id'],
            'card_no'        => $card_no,
            'order_type'     => 3,
            'serial_number'  => $orderSn,
            'order_status'   => 1,
            'real_pay'       => $pay_money,
            'recharge_money' => $money,
            'createtime'     => $NowTime,
            'card_from'      => $CardInfo['agent_id']==0?1:2,
            'agent_id'       => !empty($CardInfo['agent_id'])?$CardInfo['agent_id']:0,
            'parentid'       => $Member['parentid'],
            'coupon_money'   => $jyj,
            'discount_money' => $zk,
        ];
        //判断当前油卡额度
        $BeforRechage =$CardInfo['preferential'];
        //判断充值后的额度
        
        $AfterRechage = $CardInfo['preferential'] - $money;
        if ($CardInfo['pkgid']>1 && ( intval($BeforRechage) < 1 || intval($AfterRechage) < 0) ) {
            $this->error('此油卡可用充值额度不足!');
        }
        $OrderAdd['pid'] = $CardInfo['pkgid'];
        $RechageCount = M('add_money')->where(['card_no'=>$card_no,'openid'=>$openid,'status'=>1])->find();
        $is_first =2;
        if (!$RechageCount) { // 是否是首充
            if (intval($money) < intval( $config['first_rechage']) ){
                $this->error('当前油卡首次充值额度必须大于'.$config['first_rechage'].'元额度才能被激活！');
            } 
            $is_first = 1;
        }
        $AddMoneySave = [
            'user_id'        => $Member['id'],
            'openid'         => $openid,
            'card_no'        => $card_no,
            'money'          => $money,
            'discount_money' => $save,
            'real_pay'       => $pay_money,
            'pay_way'        => 1,
            'note'           => $is_first==1?'用户对此油卡的首次充值':'油卡额度充值',
            'status'         => 2,
            'createtime'     => $NowTime,
            'order_no'       => $orderSn,
            'agent_id'       => $Member['agentid'],
            'is_first'       => $is_first,
        ];
        //如果 折扣加 加油卷 把充值金额全部抵消  则直接完成订单  并直接 给上级邀请人分润 
        $IsOver  = $this->FinishThisOrder($OrderAdd,$AddMoneySave,$Member,$Package,$config);
        if ($IsOver) {
            $data['order_no'] = $orderSn;
            exit(json_encode(['msg'=>'success','status'=>2000,'data'=>$data]));
        }else{
            //生成订单
            // $data = $wechat->payOrder($AddMoneySave,$OrderAdd,$openid);
            $PayCon = [
                'body'     => '油卡充值',
                'detail'   => '油卡充值',
                'attach'   => '油卡充值',
                'paymoney' => $config['paymoney'],
                'payType'  => 3,
            ];
            $PayMent = new WechatController();
            switch ($config['paytype']) {
                case '1': //微信支付
                    $data = $PayMent->_WxPay($OrderAdd,$Member,$PayCon);
                    $OrderAdd['payment_code'] = 'wxpay';
                    break;
                case '2': //聚合支付
                    $data = $PayMent->_HjPay($OrderAdd,$Member,$PayCon);
                    $OrderAdd['payment_code'] = 'hjpay';
                    break;
                case '3': //钱方支付
                    $data = $PayMent->_QFPay($OrderAdd,$Member,$PayCon);
                    $OrderAdd['payment_code'] = 'qfpay';
                    break;
                case '9': //易宝支付
                    $data = $PayMent->_YEEPay($OrderAdd,$Member,$PayCon);
                    $OrderAdd['payment_code'] = 'yeepay';
                    break;
                case '4': //易支付
                    $data = $PayMent->_YZPay($OrderAdd,$Member,$PayCon);
                    $OrderAdd['payment_code'] = 'yzpay';
                    break;
            }
            if (empty($data))exit(json_encode(['msg'=>'微信下单失败！','status'=>500]));
            if($data)$data['order_no'] = $AddMoneySave['order_no'];
            $record_res = M('OrderRecord')->add($OrderAdd);
            if(!$record_res)$this->error('订单生成失败，请重试！');

            //添加充值记录
            $create_res = M('add_money')->add($AddMoneySave);
            exit(json_encode(['msg'=>'success','status'=>1000,'data'=>$data]));
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
        $data['qackage']= $qackage;
        $data['discount']=$user_data['role'] ==3?0:$discount;
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
        $where = [
                'R.user_id'=>$user_id,
                'R.order_type'=>1,
                'R.order_status'=>2,
                'R.online' =>1,
                'R.preferential_type'=>1,
            ];
        $field = 'R.*,A.id as apply_id';
        $Order = M('order_record')
                    ->alias('R')
                    ->field($field)
                    ->join('__USER_APPLY__ A ON A.serial_number=R.serial_number')
                    ->where($where)
                    ->select();
        if (empty($Order)){
            echo  json_encode([
                'data'=>json_encode($Order),
                'status'=>1000
            ]);
        }else{
            echo  json_encode([
                'data'=>json_encode($Order),
                'status'=>1000
            ]);
        }
        exit;
    }

    /**
     * 判断是否直接完成订单  -- 如果 折扣+加油券 把支付金额全部抵消  则直接完成订单
     * @Author 老王
     * @创建时间   2019-01-02
     * @param  [type]     $OrderAdd     [订单信息]
     * @param  [type]     $AddMoneySave [充值信息]
     * @param  [type]     $Member       [用户信息]
     * @param  [type]     $Package      [套餐信息]
     * @param  [type]     $config       [config]
     */
    public function FinishThisOrder($OrderAdd,$AddMoneySave,$Member,$Package,$config){
        $IsOver  = false;
        //获取充值面额
        $OrderRechage = $OrderAdd['recharge_money'];
        //获取真实支付金额
        $RealPay      = $OrderAdd['real_pay'];
        //获取使用的折扣
        $Discount     = $OrderAdd['discount_money'];
        //获取使用的加油券
        $Coupon       = $OrderAdd['coupon_money'];

        //判断优惠额度是否把充值金额抵消
        $IsZero = $OrderRechage - $Discount - $Coupon ;
        if (intval($IsZero) != 0 && intval($OrderRechage)!=0) {
            return $IsOver;  // 未抵消 继续充值
        }

        //直接完成订单  -- 并给上级代理分润 ,如果存在
        $OrderSn = $OrderAdd['card_no'];
        $NowTime = date('Y-m-d H:i:s',TIMESTAMP);
        $openid=$Member['openid'];
        $EndTime = date("Y-m-d H:i:s",strtotime("+1years"));//过期时间 1年
        $Member=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.openid'=>$openid])->find();
        $CardInfo = M('oil_card')->where(['card_no'=>$OrderAdd['card_no']])->find();
        $config = M('setting')->find();
        //更改充值记录信息状态  //更改支付状态
        $OrderAdd['updatetime'] = $NowTime ;
        $OrderAdd['order_status']=2;
        $OrderAdd['pay_sn']='折扣抵消,直接充值';
        
        

        $AddMoneySave['status'] = 1;
        $AddMoneySave['updatetime'] = $NowTime;

        

        //更改油卡信息状态
        $OilCardSave =[
            'preferential' =>$CardInfo['preferential'] - $AddMoneySave['money'],
            'card_total_add_money' => intval($CardInfo['card_total_add_money'] + $AddMoneySave['money'])
        ];
        if ($AddMoneySave['is_first']==1) {
            $OilCardSave['activate'] =2;
        }
        //用户信息变动记录
        $MemberSave =[
            //积分 1：1
            'integral'             => intval($Member['integral'] + $AddMoneySave['real_pay']),
            //总共给用户省下来的钱
            'already_save_money'   => intval($Member['already_save_money'] + $AddMoneySave['discount_money']),
            //总共充值的油卡额度 
            'total_add_money'      => intval($Member['total_add_money'] + $AddMoneySave['money']),
            //用户真实充值的钱
            'total_real_add_money' => $Member['total_real_add_money'] + $AddMoneySave['real_pay'],
        ];
        //积分变动记录
        $IntegralAdd = [
            'user_id'      => $Member['id'],
            'change'       => 1,
            'chang_way'    => '充值',
            'change_value' => $AddMoneySave['real_pay'],
            'createtime'   => $NowTime,
            'updatetime'   => $NowTime,
            'change_from'  => json_encode(['from'=>'OrderRechage','OrderSn'=>$OrderSn])
        ];
        $EarningsAdd =[];
        $AgentSave =[];
        $MemberAgentSave = [];
        //如果用户使用加油卷  --  则 减少加油卷数量 
        if (!empty($OrderAdd['coupon_money']) && $OrderAdd['coupon_money'] >0) {
            //如果使用加油卷并且加油卷数量大于使用的加油卷数量 
            if (intval($Member['currt_earnings']) >= intval($OrderInfo['coupon_money'])  && ($Member['currt_earnings'] - $OrderAdd['coupon_money']>=0)) {
                $MemberAgentSave['currt_earnings'] =$Member['currt_earnings'] - $OrderAdd['coupon_money'];
            }
            //此次操作，是否按照使用数量减少，或者是。。。。
            
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
            //用户充值的金额 ，使用充值额度
            $RechageMoney = $OrderRechage;

            //判断是直属下级还是间接下级身份 /// 此流程不适用
            switch ($CardInfo['pkgid']) {
                case '1':
                    $Calculation = $RechageMoney* ($config['user_profit']/100);
                    $rewardMoney  = number_format($Calculation, 4, ".", "");
                    $earning_body = 5; //普通卡充值
                    break;
                
                default:
                    $Calculation = $RechageMoney* ($config['vip_profit']/100);
                    $rewardMoney  = number_format($Calculation, 4, ".", "");
                    $earning_body = 6; //VIP卡充值
                    break;
            }
            //判断是直属下级还是间接下级身份
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

                case '2': //间接下级
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
            $EarningsAdd['openid']       = $openid;
            $EarningsAdd['agent_id']     = $Member['agentid'];
            $EarningsAdd['createtime']   = $NowTime;
            $EarningsAdd['order_type']   = 1;
            $EarningsAdd['earning_body'] = $earning_body;
            $EarningsAdd['earnings']     = $rewardMoney;
            $EarningsAdd['updatetime']   = $NowTime;
            $EarningsAdd['order_id']     = $OrderAdd['id'];
            $EarningsAdd['sn']           = $OrderSn;

            //总收益
            $AgentSave['total_earnings'] = $Agent['total_earnings'] + $rewardMoney;
            //当前收益
            $AgentSave['currt_earnings'] = $Agent['currt_earnings'] + $rewardMoney ;
            //下线总充值
            $AgentSave['add_total'] = $Agent['add_total'] + $RechageMoney;
            
        }
        $Things = M();
        $Things->startTrans();
        try{
            //用户充值记录信息增加
            $AddMoneySave = M('add_money')->add($AddMoneySave);
            //油卡信息状态修改
            $OilCardSave = M('oil_card')->where(['id'=>$CardInfo['id']])->save($OilCardSave);
            //订单增加
            $OrderAdd = M('order_record')->add($OrderAdd);
            //用户信息修改
            $MemberSave = M('user')->where(['openid'=>$openid])->save($MemberSave);
            //用户信息修改
            if($MemberAgentSave)$MemberAgentSave = M('Agent')->where(['openid'=>$openid])->save($MemberAgentSave);
            //用户积分变动修改                    
            $IntegralAdd = M('IntegralRecord')->add($IntegralAdd);
            //代理收益记录
            if($EarningsAdd)$EarningsAdd = M('agent_earnings')->add($EarningsAdd);
            //代理信息修改
            if($AgentSave)$AgentSave = M('Agent')->where(['id'=>$Agent['id']])->save($AgentSave);

            if ($AddMoneySave && $OilCardSave && $OrderAdd && $MemberSave && $IntegralAdd) {
                $Things->commit();
                $IsOver = true;
            }else{
                $Things->rollback();
                $IsOver = false;
            }
        } catch (\Exception $e){
            $Things->rollback();
            $IsOver = false;
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
        }
        
        return $IsOver;
    }

}