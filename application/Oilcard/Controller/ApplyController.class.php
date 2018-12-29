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
            'receive_person'=>$data['receive_person'],
            'use_time '=>time()
        ];
        $address_res=M('address')->where($address_data)->find();
        if (empty($address_res)){
            M('address')->add($address_data);
        }else{
            $address_id=$address_res['id'];
            M('address')->where("id='$address_id'")->save(['use_time'=>time()]);
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
//       $aaa=json_encode($_POST['data']);
//       log::record($aaa);exit;
//       exit;
        $data['phone']=I('post.phone','');
        $data['address']=I('post.address','');
        $data['receive_person']=I('post.receive_person','');

        $openId=I('post.openid','');
        $from_id=I('post.from_id','');
        $money=I('post.money','');
        $card_no=I('post.card_no','');
        $checked=I('post.checked');
      
        // if ($checked ) {
        if ($checked==2 ) {
            $checked_card=I('post.checked_card','');
            $card= M('OilCard')->where(['card_no'=>$checked_card,'status'=>1])->find();
            $a=M('')->getlastsql();
            log::record($a);
            if(empty($card))
            {
                $this->error('卡号不正确，请输入我平台发放的正确卡号！');
            }

            if (isset($card['user_id']) && $card['user_id']){
                $this->error('该卡号已经被绑定！');
            }

        }
        $issetLoginRes=$this->issetLogin($openId);
        
        // $year=I('post.money');
        // $discount=I('post.discount');

        // $this->_empty($data,'填写数据不可为空');
        // $this->_empty($data['receive_person'],'收货人不可为空');
        // $this->_empty($data['phone'],'联系电话不可为空');
        // $this->_empty($data['address'],'收货地址不可为空');
        // $this->_empty($money,'支付金额为空');

        if (empty($openId)){
            $this->openidError('数据传输错误');
        }

        $agent_id=M('agent_relation')->where("openid='$openId'")->getField('agent_id');
        $agent_arr= M('agent')->where("id='$agent_id'")->find();
        $first_agent_id=M('agent_relation')->where("openid='".$agent_arr['openid']."'")->getField('agent_id');
        if (!empty($agent_id)  && $agent_arr['role']==3 || !empty($first_agent_id)){
            if (!empty($first_agent_id)){
                $OilCardData=M('oil_card')->where("agent_status=1 and agent_id='$first_agent_id' and chomd=2")->find(); //从用户油卡表取出1张卡
            }else{
                $OilCardData=M('oil_card')->where("agent_status=1 and agent_id='$agent_id' and chomd=2")->find(); //从用户油卡表取出1张卡
            }

            if(empty($OilCardData)){
                $this->error('油卡无库存');
            }
            $id=$OilCardData['id'];
        }else{
            $OilCardData=M('oil_card')->where("status=1 and agent_id=0 and chomd=1 and discount=96")->find(); //从用户油卡表取出1张卡
            if(empty($OilCardData)){
                $this->error('油卡无库存');
            }
            $id=$OilCardData['id'];
        }

        Log::record('银牌申领:2');
        $userData=M('user')->where('openid="'.$openId.'"')->find();  //根据微信openid查询对应的用户
      
        if (empty($userData)) {
            $this->error('用户不存');
        }
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
            M('address')->where("id='$address_id'")->save(['use_time'=>time()]);
        }

        if($OilCardData){
            /*
             * 调取支付200元方法
             */
            if (!empty($checked_card)) {
                 $user_applu_data['card_no']=$checked_card;
            }
            $user_data=M('user')->where("openid='$openId'")->find();
            $user_applu_data['user_id']=$user_data['id'];
            $user_applu_data['status']='1';
            $user_applu_data['openid']=$openId;

            $user_applu_data['receive_person']=$data['receive_person'];
            $user_applu_data['phone']=$data['phone'];
            $user_applu_data['address']=$data['address'];

            $res= M('user_apply')->add($user_applu_data);   //单独申领表添加申领信息（未支付成功）
            $wechat = new WechatController();
            if ($checked==='1'){
                $data = $wechat->agentPay($openId,$data,$money,$card_no,$from_id,$res);
            }else{
                $data = $wechat->agentPay($openId,$data,$money,$card_no,$from_id,$res,$checked_card);
            }

            Log::record('创建订单返回:'.json_encode($data));
            if (empty($data))
            {
                echo json_encode(['msg'=>'微信下单失败！','status'=>500]);
                exit();
            }
            $Wechat = A('Wechat');
            $Wechat->templateMessage($openId,$data,1,$from_id);

            // if ($flag==1){  
            //     $card_preferential=M('oil_card')->where("card_no='$card_no'")->getField('preferential');
            //     if ($initial_money<=$card_preferential){
            //         $last_preferential=$card_preferential-$initial_money;
            //         M('oil_card')->where("card_no='$card_no'")->save(['preferential'=>$last_preferential]);
            //      }
            // }else{
            //     $user_preferential=M('user')->where("openid='$openid'")->getField('preferential_quota');
            //     if ($initial_money<=$user_preferential){
            //         $last_preferential=$user_preferential-$initial_money;
            //         M('user')->where("openid='$openid'")->save(['preferential_quota'=>$last_preferential]);
            //     }
            // }


            echo json_encode(['msg'=>'success','status'=>1000,'data'=>$data]);
            exit();

        }else{
            $this->error('创建订单失败');
        }
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
        $address_data=M('address')->where("status='1' and openid='$openid'")->order('use_time desc')->find();
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
        $img = D("Common/Slide")->where(array('slide_id'=>1))->find();
        $packages = M('packages')->select();
        $data = [
            'img'=>sp_get_image_preview_url($img['slide_pic']),
            'plainMember'=>$packages[0]['scale'],
            'vipMember'=>end($packages)['scale'],
        ];
        $this->success($data);
    }

}