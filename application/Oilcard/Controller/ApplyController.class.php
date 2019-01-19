<?php
namespace Oilcard\Controller;
use Think\Controller;
use Oilcard\Controller\UserController;
use Comment\Controller\CommentoilcardController;
use Think\Log;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 17:22
 */

class ApplyController extends CommentoilcardController
{
    public function __construct(){
        parent::__construct();
    }
    public function apply(){
        $data=I('post.data','');

        $pid=I('post.pid','');
        
        if (empty($pid)) {
            $this->error('套餐数据为空');
        }

        $openId=I('post.openid','');
        $card_number=I('post.card_number','1');//申领的卡数量

        $count=M('oil_card')->where("discount=95 and status=1")->count();
        if ($count<$card_number){
            $this->error('油卡库存不足');
        }

        $this->_empty($data,'填写数据不可为空');
        $this->_empty($data['receive_person'],'收货人不可为空');
        $this->_empty($data['phone'],'联系电话不可为空');
        $this->_empty($data['address'],'收货地址不可为空');
        $this->_empty($data['courier_company'],'快递公司不可为空');

        $Model = M();
        $Model->startTrans();


        $address_data=[
            'openid'=>$openId,
            'address'=>$data['address'],
            'phone'=>$data['phone'],
            'receive_person'=>$data['receive_person']
        ];
        $address_res=M('address')->where($address_data)->find();
        if (empty($address_res)){
            M('address')->add($address_data);
        }else{
            $address_id=$address_res['id'];
        }
        Log::record('创建订单返回:'.json_encode($data));
        /*
         * 调取支付预充值额100元方法
         */
        $wechat = new WechatController();
        $data = $wechat->applyPay($openId,$data,$card_number);
//        var_dump($data);exit;
        if (empty($data))
        {
            echo json_encode(['msg'=>'微信下单失败！','status'=>500]);
            exit();
        }

//        $Wechat = A('Wechat');
//        $Wechat->templateMessage($openId,$data,1);

        echo json_encode(['msg'=>'success','status'=>1000,'data'=>$data]);
        exit();

    }


    /**
     * 申领VIP油卡
     * @Author 老王
     * @创建时间   2018-12-29
     * @return [type]     [description]
     */
    public function agentApply(){
        $openid=I('post.openid','');
        if (empty($openid))$this->openidError('参数错误！');
        $this->issetLogin($openid);
        //获取config
        $config = M('setting')->find();
        
        $data['phone']=I('post.phone','');
        $data['address']=I('post.address','');
        $data['receive_person']=I('post.receive_person','');
        $money=I('post.money',0);
        $card_no=I('post.card_no','');
        $checked=I('post.checked');
        $checked_card=I('post.checked_card','');
        $pid = intval(I('post.pid',1));
        if ($checked == 2 || $pid >1) { //任意VIP套餐 和 现场绑卡不需要邮费
            $postage = 0 ;//油卡邮费
        }else{
            $postage=I('post.postage',$config['postage']);
        }
        $user_deposit = I('post.user_deposit',$config['user_deposit']);
        
        $Member = M('user')->where(['openid'=>$openid])->find();
        if (empty($Member['nickname']) && empty($Member['user_img']) ) $this->error('请先授权登录');
        //获取当前代理商信息
        $Agent= M('agent')->where(['id'=>$Member['agentid'],'role'=>3])->find();
        //获取当前上级邀请人
        $ParentMember= M('user')->where(['id'=>$Member['parentid']])->find();
        //生成订单号
        $sn = date('YmdHis').str_pad(mt_rand(1,999999),6,STR_PAD_LEFT);
        //获取套餐信息
        $packages = M('packages')->where(['pid'=>$pid])->find();
        $SysWhere=[
            'agent_id'  =>0,
            'is_notmal' =>1,
            'activate'  =>1,
            'status'    =>1,
            'chomd'     =>1
        ]; 
        $Syscount = M('oil_card')->where($SysWhere)->count();
        $card_from =0;
        $aid = 0;
        switch ($checked) {
            case '1'://线上办卡-邮寄油卡
                //查询代理名下油卡库存是否足够
                //如果代理油卡数量大于等于1,并且不等于0,代理发卡
                if ($Agent && $Agent['agent_oilcard_stock_num']>=1 && $Agent['agent_oilcard_stock_num'] !=0) {
                    $card_from =2; // 最终由代理发卡
                    $aid =  $Member['agentid'];
                }else{
                    if ($Syscount >=1) {
                        //如果总部有卡,代理无卡,总部发卡
                        $card_from =1; // 最终由总部发卡
                        $aid = 0 ;
                    }else{//如果总部无卡,代理也无卡
                        $card_from =2; // 最终由代理发卡
                        $aid =  $Member['agentid'];
                    }
                }
                break;
            case '2'://现场办卡
                //线下绑定油卡时
                $card= M('oil_card')->where(['card_no'=>$checked_card])->find();
                $aid  = isset($card['agent_id'])?$card['agent_id']:0;
                if(!$card) $this->error('油卡号不正确，请输入我平台发放的正确油卡号！');
                if (!empty($card['user_id'])) $this->error('该卡号已经被绑定！');
                switch ($card['is_notmal']) {
                    case '2':# code...冻结使用
                        $log = M('oil_option')->where(['cardid'=>$card['id']])->order('id desc')->find();
                        switch ($log['type']) {
                            case '1':
                                $this->error('该油卡正在办理退卡！');
                                break;
                            case '2':
                                $this->error('该油卡已有用户挂失！');
                                break;
                            default:
                                $this->error('系统已禁止此油卡使用！');
                                break;
                        }
                        break;
                    case '3':# code...注销油卡
                        $this->error('此油卡已被系统废弃！');
                        break;
                }
                $card_from =$aid==0?1:2; // 1总部卡，2代理卡
                // $OrderInfo['card_no'] =$card['card_no']; // 线下绑定的卡号
                break;
        }
        
        //生成订单信息
        $OrderInfo = [
            'user_id'       =>$Member['id'],//购买人id
            'serial_number' => $sn,
            'card_no'       => $checked ==2?$card['card_no']:'',// 线下绑定的卡号
            'pid'           => $pid,
            'online'        => $checked,
            'createtime'    => date('Y-m-d H:i:s',TIMESTAMP),
            //本次购卡时 最近的邀请人id--暂不锁定邀请人
            'parentid'      => isset($ParentMember['id'])?$ParentMember['id']:0 ,
            // 0总部发放，代理id  --暂不锁定代理id
            'agent_id'      =>$aid, 
            'real_pay'      => $money,
            'user_deposit'  => $user_deposit,
            'postage'       => $postage,
            'order_type'    =>$checked,
            'order_status'  =>1,
            'preferential'  =>$packages['limits'],
            'card_from'     =>$card_from
        ];
        

        $address_data=[
            'openid'=>$openid,
            'phone'=>$data['phone'],
            'receive_person'=>$data['receive_person']
        ];
        //收货人地址
        $address_res=M('address')->where($address_data)->find();
        if (empty($address_res)){
            $address_data['address']=$data['address'];
            $OrderInfo['addressid']=M('address')->add($address_data);
        }else{
            $OrderInfo['addressid'] =$address_res['id'];
        }
        $user_applu_data =[];
        if (!empty($checked_card)) {//线下领卡
             $user_applu_data['card_no']=$checked_card;
        }

        $user_data=M('user')->where("openid='$openid'")->find();
        $user_applu_data['user_id']        =$Member['id'];
        $user_applu_data['status']         ='1';
        $user_applu_data['openid']         =$openid;
        $user_applu_data['receive_person'] =$data['receive_person'];
        $user_applu_data['phone']          =$data['phone'];
        $user_applu_data['address']        =$data['address'];
        $user_applu_data['serial_number']  =$OrderInfo['serial_number'];
        $user_applu_data['agentid']        =$aid;
        $user_applu_data['money']          =$packages['price'];
        $user_applu_data['name']           =$checked==1?'线上申领油卡':'线下绑定油卡';
        $user_applu_data['discount']       =$packages['scale'];

        
        
        /*$wechat = new WechatController();
        $data = $wechat->agentPay($OrderInfo,$data,$openid);*/
        $PayCon = [
                'body'     => '油卡充值',
                'detail'   => '油卡充值',
                'attach'   => '油卡充值',
                'paymoney' => $config['paymoney']
            ];
        $PayMent = new WechatController();
        switch ($config['paytype']) {
            case '1': //微信支付
                $data = $PayMent->_WxPay($OrderInfo,$Member,$PayCon);
                $OrderInfo['payment_code'] = 'wxpay';
                # code...
                break;
            case '2': //聚合支付
                $data = $PayMent->_HjPay($OrderInfo,$Member,$PayCon);
                $OrderInfo['payment_code'] = 'hjpay';
                break;
        }

        // $Wiki = new WikiController();
        // $data = $Wiki->agentPay($OrderInfo,$data,$openid);

        if (empty($data))exit(json_encode(['msg'=>'微信下单失败！','status'=>500]));
        $res= M('user_apply')->add($user_applu_data);   //单独申领表添加申领信息（未支付成功）
        $OrderInfo= M('order_record')->add($OrderInfo);   //添加订单
        if ($res && $OrderInfo) {
            exit(json_encode(['msg'=>'success','status'=>1000,'data'=>$data]));
        }else{
            exit(json_encode(['msg'=>'微信下单失败！','status'=>500]));
        }
//        $Wechat = A('Wechat');
//        $Wechat->templateMessage($openid,$data,1,$from_id);
    }

    /**
     * @param $openid
     * @return false|mixed|\PDOStatement|string|\think\Collection|void
     * 查询用户发货地址
     */
    public function  addressList(){
        $openid=I('post.openid','');
        if (empty($openid)){
            $this->error('用户标志为空，请重新登录');
        }
        $address_data=M('address')->where("status='1' and openid='$openid'")->find();
        if ($address_data){
            $this->success($address_data);
        }else{
           $this->error('暂无储存数据');
        }

    }

// 导入卡
     public function addCard(){
        $data=$_POST;
        $flag=true;
        $arr=[];
        for ($i=$data['start_card_no']; $i <= $data['end_card_no']; $i++) {
            $arr['card_no']=$i;
            $arr['card_note']=$data['card_note'];
            $arr['discount']='96';
            $arr['createtime'] = date('Y-m-d H:i:s');
            $arr['system_id'] = 'SN'.rand(100000000,999999999);

            $res=M('oil_card')->add($arr);
            if (!$res) {
                $flag=false;
            }
        }
        if($flag===true){
            echo json_encode([
                'msg'=>1000,
                'status'=>'油卡导入成功'
            ]);exit;
        }else{
            echo json_encode([
                'msg'=>500,
                'status'=>'入库失败失败'
            ]);exit;
        }
    }
// 给代理商发卡
   public function confirmSendCard(){
        $data = I('post.');
        if( empty($data['openId']) || empty('user_id') ){
            echo '请求失败';exit;
        }
        if( empty($data['end']) ){
            echo '结尾卡号必填';exit;
        }
        if( empty($data['each_price']) ){
            echo '请选择拿卡价格';exit;
        }
        if( empty($data['mode']) ){
            echo '请选择发货状态';exit;
        }
        if( empty($data['address']) ){
            echo '请填写收获地址';exit;
        }
        if( empty($data['name']) ){
            echo '请填写收货人名称';exit;
        }
        if( empty($data['phone']) ){
            echo '请填写收货人手机号';exit;
        }
        $UserModel = M('user');
        $user_info = $UserModel -> where( ['openid' => $data['openid']] ) -> find();

        // 获取发卡卡段区间（操作修改oil_Card）
        for( $i = $data['start']; $i <= $data['end']; $i++ ){
            $arr[] = $i;
            $save_oilcard_where = [ 'card_no' => $i ];
            $save_oilcard_data = [
                // 'status' => 2,
                'agent_create_time' => date('Y-m-d H:i:s'),
                'agent_id' => $data['user_id'],
                'agent_status' => 1,
                'user_id' => $user_info['id'],
                'chomd' => 2
            ];
            # 将此区间的卡状态更改为启用
            $OilCardModel = M('oil_card');
            $result1 = $OilCardModel -> where( $save_oilcard_where ) -> save( $save_oilcard_data );
        }

        //添加卡附属信息（记录该代理拿卡区间）
        $insert_agent_library_data = [
            'user_id' => $data['user_id'],
            'openid' => $data['openid'],
            'start_card_no' => $data['start'],
            'end_card_no' => $data['end'],
            'each_price' => $data['each_price'],
            'card_mode' => $data['mode'],
            'createtime' => date('Y-m-d H:i:s'),
        ];
        $AgentLibraryModel = M('agent_library');
        $result2 = $AgentLibraryModel -> add( $insert_agent_library_data );

        //记录代理的附属信息
        $insert_user_apply_data = [
            'user_id' => $data['user_id'],
            'receive_person' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'card_number' => count($arr),
            'shop_name' => '中石油加油卡',
            'deliver_number' => count($arr),
            'serial_number' => '20180313'.rand(100000,999999)
        ];
        $UserApplyModel = M('user_apply');
        $result3 = $UserApplyModel -> add($insert_user_apply_data);

        if( $result1 && $result2 && $result3 ){
            echo json_encode(['msg' => 'success','status' => 1000]);
        }else{
            echo json_encode(['msg' => 'error','status' => 500]);
        }
    }
    /**
    *获取VIP优惠套餐
    */
    public function applySetMeal(){
        $data=M('packages')->select();
        foreach ($data as $key => $v) {
            if ($key==0) {
                $data[$key]['scale']=substr($v['scale'], 0, -2).'/折';
            }else{
                $data[$key]['scale']=$v['scale'].'/折';
            }


            $data[$key]['price']=substr($v['price'], 0, -5);


            if ($v['limits']==0){
                 $data[$key]['limits']='无限制';
            }else{
                $data[$key]['limits']=substr($v['limits'], 0, -3).'元';
            }
        }
        $this->success($data);
    }

    public function GetCommentInfo(){
        $img = D("Common/Slide")->select();
        $packages = M('packages')->select();
        $config = M('setting')->find();
        $data = [
            'img'=>sp_get_image_preview_url($img[0]['slide_pic']),
            'img01'=>sp_get_image_preview_url($img[1]['slide_pic']),
            'img02'=>sp_get_image_preview_url($img[2]['slide_pic']),
            'service_img'=>sp_get_image_preview_url($img[3]['slide_pic']),
            'plainMember'=>$packages[0]['scale'],
            'vipMember'=>end($packages)['scale'],
        ];
        unset($config['id']);
        $data=array_merge($data,$config);
        $this->success($data);
    }

}