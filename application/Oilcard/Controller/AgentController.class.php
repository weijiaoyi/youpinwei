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
        if (!isset($openid) || !$openid)$this->error('openid不能为空！');
        $Agent=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.openid'=>$openid])->find();
        if (!$Agent) $this->error('无此用户！');
        $M = M('agent_earnings');
        $where = [
            'e.agent_id' => $Agent['id'],
        ];
        if ($Agent['role']==3) {
            //充值奖励列表
            $Earnings = $M->alias('e')
                        ->field('u.nickname,e.earnings as earn_money,m.money,e.createtime as time')
                        ->join('__ADD_MONEY__ m ON e.sn=m.order_no')
                        ->join('__USER__ u ON e.openid=u.openid')
                        ->where($where)
                        ->select();
        }else{
            //拉新奖励列表
            $Earnings = $M->alias('e')
                        ->field('u.nickname,e.earnings,a.money as add_money,e.createtime as time')
                        ->join('__USER_APPLY__ a ON e.sn=a.serial_number')
                        ->join('__USER__ u ON e.openid=u.openid')
                        ->where($where)
                        ->select();
        }

        $count = $M->alias('e')
                        ->join('__USER_APPLY__ a ON e.sn=a.serial_number')
                        ->join('__USER__ u ON e.openid=u.openid')
                        ->where($where)
                        ->count();
        foreach ($Earnings as $key => $v) {
            if (isset($Earnings['earn_money']))$Earnings['earn_money'] =ncPriceFormatb($Earnings['earn_money']);
            if (isset($Earnings['money']))$Earnings['money'] =ncPriceFormatb($Earnings['money']);
            if (isset($Earnings['earnings']))$Earnings['earnings'] =ncPriceFormatb($Earnings['earnings']);
        }
        $output = [];
        $output['user_img'] = $Agent['user_img'];
        $output['nickname'] = $Agent['nickname'];
        $output['agent_lv'] = CardConfig::$agent_ame[$Agent['role']] ?: '普通用户';
        $output['currt_earnings'] = ncPriceFormatb($Agent['currt_earnings']); //当前奖励
        $output['new_earnings'] = ncPriceFormatb($Agent['new_earnings']);//拉新奖励
        $output['total_earnings'] = ncPriceFormatb($Agent['total_earnings']);//充值总奖励
        $output['add_total'] = ncPriceFormatb($Agent['add_total']);//下线总充值
        $output['new_count'] = $count;
        $output['add_list'] = $Agent['role']==3?$Earnings:[];
        $output['new_list'] = $Agent['role']!=3?$Earnings:[];
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
        $agent_arr=M('agent')->where(['openid'=>$openid]) ->find();
        if(empty($agent_arr) || $agent_arr['role']!=3){
            $this->error('不是代理商');
        }
        $data=[];
        $user_id=M('user')->where("openid='$openid'")->getField('id');
        $card_count=M('oil_card')->where("agent_id='$user_id'")->count();
        $agent_id=$agent_arr['id'];
        $user_count=M('user')->where(['agentid'=>$agent_id,'agent_bind'=>1])->count();
        $data['total_earnings']=$agent_arr['total_earnings']; //总收益
        $data['card_count']=$card_count;   //卡数量
        $data['customer_count']=$user_count;   //客户数量
        $this->success($data);

    }

    /**
     * 代理订单管理
     */
    public function agentOrder(){
        $openid=I('post.openid','');
        $p=I('post.p','1');
        $l=I('post.l',10);
        $flag=I('post.flag','1');
        if (empty($openid))$this->openidError('数据传输缺少');
        $agentid=M('user')->where(['openid'=>$openid])->getField('id');
        $M = M('user_apply');
        $where =[
            'o.agent_id'=> $agentid,
            'o.card_from' => 2,
            'o.order_status'=>2,
            'a.status' =>$flag,
        ];
        $Order = $M->alias('a')
                   ->field('a.card_number,a.receive_person,a.phone,a.address,a.createtime,a.serial_number,a.id as user_apply_id,a.note,u.openid,u.nickname,u.user_img,o.send_card_no,o.card_no,o.online,o.pid,a.status')
                   ->join('__USER__ u ON a.user_id=u.id')
                   ->join('__ORDER_RECORD__ o ON o.serial_number=a.serial_number')
                   ->where($where)
                   ->page($p,$l)
                   ->select();
        $count = $M->alias('a')
                   ->join('__USER__ u ON a.user_id=u.id')
                   ->join('__ORDER_RECORD__ o ON o.serial_number=a.serial_number')
                   ->where($where)
                   ->count();          
        $this->success($Order);
    }

    /**
     *客户管理
     */
    public function customerManage(){
        $openid=I('post.openid','');
        $p=I('post.p','1');
        $l=I('post.l',10);
        $flag=I('post.flag','1');
        if (empty($openid))$this->openidError('数据传输缺少');
        $agent_id=M('agent')->where(['openid'=>$openid])->getField('id');
        $User = M('user');
        
        $UWhere=[
            'a.agentid'=>$agent_id,
            'b.role' =>$flag,
            'a.agent_bind'=>1
        ];
        $MemberList=$User
                    ->alias('a')
                    ->field('a.nickname,a.createtime,a.openid,a.user_img')
                    ->join('__AGENT__ b ON a.id=b.id')
                    ->where($UWhere)
                    ->page($p,$l)
                    ->select();
        $count=$User
                ->alias('a')
                ->join('__AGENT__ b ON a.id=b.id')
                ->where($UWhere)
                ->count();            
        return $this->success($MemberList);
    }

    /**
     * 油卡管理
     */
    public function cardManage(){
        $openid=I('post.openid','');
        $p=I('post.p','1');
        $l=I('post.l',10);
        $flag=I('post.flag','1');
        if (empty($openid))$this->openidError('数据传输缺少');
        $Agent=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.openid'=>$openid])->find();
        if (empty($Agent))$this->openidError('无此用户!');
        $Card = M('oil_card');
        $where = [
            'agent_id' => $Agent['id'],
            'status' => $flag,
        ];
        $card_data=$Card->where($where)->page($p,$l)->select();
        $count = $Card->where($where)->count();
        $this->success(['card'=>$card_data,'count'=>$count]);    
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