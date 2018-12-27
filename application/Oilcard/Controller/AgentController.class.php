<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 15:36
 */
namespace Oilcard\Controller;
use Comment\Controller\CommentoilcardController;
use Oilcard\Conf\CardConfig;

class AgentController extends CommentoilcardController
{
    public function __construct(){
        parent::__construct();
    }

    public function agentEarnings()
    {
        $openid  = trim(I('post.openid'));
        if (!isset($openid) || ! $openid)
        {
            $this->error('openid不能为空！');
        }

        $user_info = M('User')->where(['openid'=>$openid])->find();
        if (!$user_info) {
            $this->error('无此用户！');
        }

        $agent_info = M('Agent')->where(['openid'=>$openid])->find();  //本人的agent数据

        if (!$agent_info) {
            $this->error('您还不是代理人！');
        }
        $new_count = M('AgentEarnings')->where(['agent_id'=>$agent_info['id'],'order_type'=>2])->count();  //查询本人的下线
        $earn_list = M('AgentEarnings')->where(['agent_id'=>$agent_info['id']])->order("createtime desc")->select();
        $add_list = [];
        $new_list = [];

        foreach ($earn_list as $k=>$v) {

            if ($v['order_type'] == 1) {
                $order = M('AddMoney')->where(['id'=>$v['order_id']])->find();
                $add_list_val = [];

                $nickname= M('User')->where(['id'=>$order['user_id']])->getField('nickname');
                if(empty($nickname)){
                    $nickname=$v['openid'];
                }
                $add_list_val['time'] = date('Y-m-d',strtotime($order['createtime']));
                $add_list_val['nickname'] =$nickname;
                $add_list_val['add_money'] = $order['money'];
                $add_list_val['earn_money'] = substr($v['earnings'], 0,-2);
                array_push($add_list,$add_list_val);
            }
        }

        $new_list_val=[];
        $agent_data= M('agent')->where("openid='$openid'")->find();// 当前用户  agent数据
        $relation_data=M('agent_earnings')->where('agent_id="'.$agent_data['id'].'"')->order("createtime desc")->select();
        foreach ($relation_data as $ke=>$va){
            $id=$va['id'];//下线的openid
            $agent_id=$va['agent_id'];
            if(!empty($openid) && $va['order_type']==2 || $va['order_type']==3 || $va['order_type']==4){
                $agent_data=M('agent')->where("openid='$openid'")->find();
                $agent_earnings=M('agent_earnings')->where("id='$id' and order_type='2' and agent_id='$agent_id'")->getField('earnings');
                $order_type=$va['order_type'];
                if (!empty($agent_data) && $va['order_type']=2 || $va['order_type']==3 || $va['order_type']==4){
                    $nickname=M('user')->where("openid='".$va['openid']."'")->getField('nickname');
                    if(empty($nickname)){
                        $nickname=$va['openid'];
                    }
                    $new_list_val['nickname']=$nickname;
//                    $new_list_val['time'] = M('agent')->where("openid='".$va['openid']."'")->getField('expire_time');
                    $new_list_val['time'] = $va['createtime'];
                    $new_list_val['money'] = substr($va['earnings'], 0,-2);
                    $new_list_val['order_type'] =$order_type;
                    array_push($new_list,$new_list_val);
                }
            }

        }

        $output = [];
        $output['user_img'] = $user_info['user_img'];
        $output['nickname'] = $user_info['nickname'];
        $output['agent_lv'] = CardConfig::$agent_ame[$agent_info['role']] ?: '普通用户';
        $output['currt_earnings'] = $agent_info['currt_earnings']; //当前奖励
        $output['new_earnings'] = $agent_info['new_earnings'];//拉新奖励
        $output['total_earnings'] = $agent_info['total_earnings'];//充值总奖励
        $output['add_total'] = $agent_info['add_total'];//下线总充值
        $output['new_count'] = $new_count;
        $output['add_list'] = $add_list;
        $output['new_list'] = $new_list;
        echo json_encode(['msg'=>'success','status'=>1000,'data'=>$output]);
        exit();
    }


    public function agentRelation()
    {
        $openid =  I('request.openid');
        $agent_op = I('request.agent_op');
        if (empty($openid)) $this->error('openid不能为空！');
        if (empty($agent_op)) $this->error('代理商openid不能为空!');

        if ($openid == $agent_op) $this->error('不能成为自己的代理！');

        $agent = M('Agent')->where(['openid'=>$agent_op])->find();

        if (!$agent) $this->error('没有该代理商！');

        $is_agent = M('Agent')->where(['openid'=>$openid])->find();

        if ($is_agent) $this->error('该用户身份已经是代理!');

        $user_agent = M('AgentRelation')->where(['openid'=>$openid])->find();

        if ($user_agent) $this->error('该用户已经是别人的下线！');

        
        
        $insert['openid'] = $openid;
        $insert['agent_id'] = $agent['id'];
        $res = M('agentRelation')->add($insert);
       

        if (!$res) {
            $this->error('添加下线失败！');
        }

        $this->success();

    }

    /**
     * 代理首页数据
     */
    public function agentIndexData(){
        $openid=I('post.openid','');
        if (empty($openid)){
            $this->openidError('数据传输缺少');
        }
        $agent_arr=M('agent')->where("openid='$openid'") ->find();
        if(empty($agent_arr) || $agent_arr['role']!=3){
            $this->error('不是代理商');
        }
        $data=[];
        $data['total_earnings']=$agent_arr['total_earnings']; //总收益
        $user_id=M('user')->where("openid='$openid'")->getField('id');
        $card_count=M('oil_card')->where("agent_id='$user_id'")->count();
        $data['card_count']=$card_count;   //卡数量
        $agent_id=$agent_arr['id'];
        $user_count=M('agent_relation')->where("agent_id='$agent_id'")->count();
        $data['customer_count']=$user_count;   //客户数量
        $this->success($data);

    }

    /**
     * 代理订单管理
     */
        public function agentOrder(){
        $openid=I('post.openid','');
        $p=I('post.p','1');
        $flag=I('post.flag','1');
        $offset=I('post.offset','20');
        if (empty($p)){
            $page=0;
        }else{
            $page=($p-1)*$offset;
        }

        if (empty($openid)){
            $this->openidError('数据传输缺少');
        }
        //查看是否为代理商


        $agent_id=M('agent')->where("openid='$openid'")->getField('id');
        $this->_empty($agent_id,'无此用户');
        $agent_relation_data=M('agent_relation')->where("agent_id='$agent_id'")->order('createtime desc')->select();
        $data=[];
        foreach ($agent_relation_data as $key=>$val){
            $b=[];
            $user_apply=M('user_apply')->where("status='$flag' and apply_status=2 and  openid='".$val['openid']."'")->limit($page,$offset)->select();
            foreach ($user_apply as $k=>$v){
                $nickname=M('user')->where("openid='".$v['openid']."'")->getField('nickname');
                if (empty($nickname)){
                    $nickname=M('user')->where("openid='".$v['openid']."'")->getField('openid');
                }else{
                    $user_apply['nickname']=$nickname;
                }


                $a['nickname']=$nickname;
                $a['openid']=$v['openid'];
                $a['card_number']=$v['card_number'];
                if (empty($v['receive_person'])) {
                    $receive_person='';
                }else{
                    $receive_person=$v['receive_person'];
                }
                $a['receive_person']=$receive_person;
                $a['phone']=$v['phone'];
                if (empty($v['address'])) {
                    $address='';
                }else{
                    $address=$v['address'];
                }
                $a['address']=$address;
                $a['createtime']=$v['createtime'];
                $a['serial_number']=$v['serial_number'];
                $a['user_apply_id']=$v['id'];
                $a['note']=$v['note'];

                array_push($data,$a);
            }
            if (!empty($b)){
                array_push($data,$b);
            }

        }
        $this->success($data);

    }

    /**
     *客户管理
     */
    public function customerManage(){
        $openid=I('post.openid','');
        if (empty($openid)){
            $this->openidError('数据传输缺少');
        }

        $p=I('post.p','1');
        $flag=I('post.flag','1');

        $offset=I('post.offset','20');
        if (empty($p)){
            $page=0;
        }else{
            $page=($p-1)*$offset;
        }

        $arr=[];
        $agent_id=M('agent')->where("openid='$openid'")->getField('id');
        if ($flag==1){
            $data=M('agent as b')->join('left join agent_relation as a on b.openid=a.openid')->where("role='1' and a.agent_id='$agent_id'")->limit($page,$offset)->select();
        }else{
            $data=M('agent as b')->join('left join agent_relation as a on b.openid=a.openid')->where("role='2' and a.agent_id='$agent_id'")->limit($page,$offset)->select();
        }

       foreach ($data as $k=>$v){
           $nickname=M('user')->where("openid='".$v['openid']."'")->getField('nickname');

           if (empty($nickname)){
               $nickname=M('user')->where("openid='".$v['openid']."'")->getField('openid');
           }
          $arr[$k]['nickname']=$nickname;
          $arr[$k]['createtime']=$v['createtime'];
          $arr[$k]['note']=$v['note'];
          $arr[$k]['openid']=$v['openid'];
       }


        return $this->success($arr);
    }

    /**
     * 油卡管理
     */
    public function cardManage(){
        $openid=I('post.openid','');
        $p=I('post.p','1');
        $flag=I('post.flag','1');

        $offset=I('post.offset','20');
        if (empty($p)){
            $page=0;
        }else{
            $page=($p-1)*$offset;
        }

        if (empty($openid)){
            $this->openidError('数据传输缺少');
        }

        $agent_arr=M('agent')->where("openid='$openid'")->find();
        $agent_id=$agent_arr['id'];
        $card_data=M('oil_card')->where("agent_id='$agent_id' and agent_status ='$flag'")->limit($page,$offset)->select();
        $card_count=M('oil_card')->where("agent_id='$agent_id' and agent_status ='$flag'")->count();
        $data['count']=$card_count;
        $data['card']=$card_data;
        $this->success($data);
    }

    /**
     * 代理商给下线添加备注
     */
    public function offlineNote(){
        $openid=I('post.openid','');
        $note=I('post.note','');
        if (empty($openid)){
            $this->openidError('数据传输缺失');
        }
        if (empty($note)){
            $this->error('数据缺失');
        }
       $res= M('agent_relation')->where("openid='$openid'")->save(['note'=>$note]);
        if ($res!==false){
            $this->success('备注修改成功');
        }else{
            $this->error('备注修改失败');
        }

    }

    /**
     * 代理商给下线发货
     */
    public function deliverGoods(){
        $openid=I('post.openid','');
        $id=I('post.user_apply_id','');
        $from_id=I('post.from_id','');
        $data=M('user_apply')->where("id='$id'")->find();
        if (empty($openid)){
            $this->openidError('数据传输缺失');
        }
        $this->_empty($id);
        M('')->startTrans();
        $user_res=M('user_apply')->where("id='$id'")->save(['status'=>2,'updatetime'=>date('Y-m-d H:i:s')]);


        $agent_id=M('agent')->where("openid='$openid'")->getField('id');
        $card_id=M('oil_card')->where("agent_id='$agent_id' and agent_status=1 and chomd=2")->getField('id');
        $card_res=M('oil_card')->where("id='$card_id'")->save(['agent_status'=>2,'date'=>date('Y-m-d H:i:s')]);
        if ($user_res!==false && $card_res!==false){
            M('')->commit();
            $Wechat = A('Wechat');
            $a=$Wechat->templateMessage($openid,$data,5,$from_id);
            $this->success('发货成功');
        }else{
            M('')->rollback();
            $this->error('发货失败');
        }

    }


    public function orderNote(){
       $openid= I('post.openid','');
       $note= I('post.note','');
       $user_apply_id= I('post.user_apply_id','');
       $id=M('user')->where("openid='$openid'")->getField('id');
       $res=M('user_apply')->where("id='$user_apply_id' ")->save(['note'=>$note]);
       if ($res!==false){
           $this->success('ok');
       }else{
           $this->error('no');
       }
    }

}