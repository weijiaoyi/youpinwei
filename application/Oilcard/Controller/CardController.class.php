<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 18:10
 */

namespace Oilcard\Controller;

use Comment\Controller\CommentoilcardController;
use Think\Exception;
use Think\Log;

class CardController extends CommentoilcardController
{
    /**
     * @throws Exception
     * 用户绑定油卡
     */
    public function bindCard()
    {
//        try{
            $card_no = (string)trim(I('post.card_no',''));
            $openid  = trim(I('post.openid',''));
            $id  = trim(I('post.id',''));
            $card_no = str_replace(" ",'',$card_no);
            $leng = intval(strlen($card_no));
            if($leng != 16 )$this->error('卡号错误！');
            $issetLoginRes=$this->issetLogin($openid);


            if (!isset($card_no) || !$card_no)$this->error('卡号不能为空！');
            if (!isset($openid) || !$openid)$this->error('openid不能为空！');

            $user = M('User')->where(['openid'=>$openid])->find();

            $NowTime = date('Y-m-d H:i:s',TIMESTAMP);
            $EndTime = date("Y-m-d H:i:s",strtotime("+1years"));//过期时间 1年

            $Member=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.openid'=>$openid])->find();
            $field = 'R.*,A.id as apply_id';
            $where = [
                'R.user_id'=>$Member['id'],
                'R.order_type'=>1,
                'R.order_status'=>2,
                'R.online' =>1,
                'R.preferential_type'=>1,
                'R.send_card_no' => $card_no,
            ];

            $Order = M('order_record')
                        ->alias('R')
                        ->field($field)
                        ->join('__USER_APPLY__ A ON A.serial_number=R.serial_number')
                        ->where($where)
                        ->find();
            unset($where['R.send_card_no']);
            if ($id) $where['R.id'] =$id;            
            if (!$Order) {
                $Order = M('order_record')
                        ->alias('R')
                        ->field($field)
                        ->join('__USER_APPLY__ A ON A.serial_number=R.serial_number')
                        ->where($where)
                        ->find();
            }
            if (!$Order)$this->error('您当前并没有申领过油卡!');
            $Package = M('packages')->where(['pid'=>$Order['pid']])->find();
            $card = M('OilCard')->where(['card_no'=>$card_no])->find();
            if($card['agent_id'] != $Member['agentid']){
                if ($card['agent_id'] != 0) {
                    $this->error('无效卡号!');
                }
            }
            if(empty($card))$this->error('无效的卡号！');
            if($card['user_id'] && !empty($card['user_id']) && intval($card['user_id'])>0){
                $this->error('该卡号已经被绑定！');
            }
            $Things = M();
            $Things->startTrans();
            //修改订单状态
            $OrderSave = [
                'card_no' => $card['card_no'],
                'updatetime' => $NowTime,
                'preferential' => $Package['limits'],
                'preferential_type' => 2,
                'applyfinish' =>2,
                'order_type' =>2
            ];
            //修改订单申领状态
            $ApplySave = [
                'status' =>3 ,
                'note' => '油卡绑定成功',
                'updatetime' => $NowTime
            ];
            $sendcardInfo = M('oil_card')->where(['card_no'=>$Order['send_card_no'] ])->find();
            //修改之前油卡信息状态
            if (($card['card_no'] != $Order['send_card_no']) && empty($sendcardInfo['user_id'])) {
                $BeforCard = [
                    'status' =>1,
                ];
                $BeforCard = M('oil_card')->where(['card_no'=>$Order['send_card_no'] ])->save($BeforCard);
            }
        $CardSave =[
            'user_id'       =>$Member['id'],
            'apply_fo_time' =>$Order['createtime'],
            'status'        =>2,
            'updatetime'    => $NowTime,
            'chomd'         => 2 ,
            'agent_status'  =>1,
            'end_time'      =>$EndTime,
            'preferential'  => $Package['limits'],
            'pkgid'         =>$Order['pid'],
            'desc'          => '油卡绑定成功',
        ];
            //修改当前油卡信息为当前绑定人
            if ($Order['pid'] ==1)unset($CardSave['end_time']);



            $OrderSave = M('order_record')->where(['id'=>$Order['id'] ])->save($OrderSave);

            $ApplySave = M('user_apply')->where(['id'=>$Order['apply_id'] ])->save($ApplySave);

            $CardSave = M('oil_card')->where(['id'=>$card['id'] ])->save($CardSave);
            if ($OrderSave && $ApplySave && $CardSave) {
                $Things->commit();
                $this->success('绑定成功！');
            }else{
                $Things->rollback();
                $this->error('绑定失败！');
            }

 
    }


    /**
     * @return false|string|void
     * 用户油卡列表
     * abnormal_data   已退油片
     * abnormal_data   正在使用卡片
     */
    public function cardList()
    {
        $openid  = trim(I('post.openid',''));//os2aR0QzCHW2sqbrDGj1s0L5TJSQ
        $flag  = trim(I('post.flag',''));

        if (!isset($openid) || ! $openid)
        {
            $this->error('openid不能为空！');
        }

        $user = M('User')->where(['openid'=>$openid])->find();

        // if (!$user)
        // {
        //     //跳转到微信登录url
        //     redirect(U('oilcard/wechat/getCode'));
        // }

        $cardList = M('OilCard')
            ->alias('o')
            ->join('__PACKAGES__ p ON p.pid=o.pkgid',LEFT)
            ->where(['o.user_id'=>$user['id']])
            ->order("o.createtime desc")
            ->select();
        if (empty($cardList)){
            $this->success('账户下无对应的加油卡','1001');exit;
        }
        $data=[];
        $normal =[];
        $abnormal=[];
        $process=[];



        foreach ($cardList as $k=>$v)
        {
            // var_dump($v);
            if($v['pkgid']>1){
                $role =2;
            }else{
                $role=1;
            }
            if ($role==1) {
                $end_time='';
            }else{
                $end_time=$v['end_time'];
            }
            $normal[$k]['role'] =$role;
            $normal[$k]['card_id'] =$v['id'];
            $normal[$k]['card_no'] = $v['card_no'];
            $normal[$k]['status'] = $v['is_notmal'];//卡状态
            $normal[$k]['now_scale'] = $v['scale'];
            $normal[$k]['end_time'] = $end_time;
            $normal[$k]['discount'] = $v['discount'];
            $normal[$k]['createtime'] = $v['createtime'];
            $normal[$k]['card_note'] = $v['card_note'] ?: '暂无备注';
            $normal[$k]['preferential'] = $v['preferential'] ?: '';
            $normal[$k]['end_time'] = $v['end_time'] ?: '';

//            if ($v['is_sale']==3){
//                $abnormal[$k]['card_no'] = $v['card_no'];
//                $abnormal[$k]['role'] =$role;
//                $abnormal[$k]['status'] = $v['status'];
//                $abnormal[$k]['end_time'] = $end_time;
//                $abnormal[$k]['is_sale'] = $v['is_sale'];
//                $abnormal[$k]['discount'] = $v['discount'];
//                $abnormal[$k]['createtime'] = $v['createtime'];
//                $abnormal[$k]['card_note'] = $v['card_note'] ?: '暂无备注';
//            }else if($v['is_sale']==2){
//                $process[$k]['role'] =$role;
//                $process[$k]['card_no'] = $v['card_no'];
//                $process[$k]['status'] = $v['status'];
//                $process[$k]['end_time'] = $end_time;
//                $process[$k]['is_sale'] = $v['is_sale'];
//                $process[$k]['discount'] = $v['discount'];
//                $process[$k]['createtime'] = $v['createtime'];
//                $process[$k]['card_note'] = $v['card_note'] ?: '暂无备注';
//            }else{
//                $normal[$k]['role'] =$role;
//                $normal[$k]['card_no'] = $v['card_no'];
//                $normal[$k]['status'] = $v['status'];
//                $normal[$k]['is_sale'] = $v['is_sale'];
//                $normal[$k]['end_time'] = $end_time;
//                $normal[$k]['discount'] = $v['discount'];
//                $normal[$k]['createtime'] = $v['createtime'];
//                $normal[$k]['card_note'] = $v['card_note'] ?: '暂无备注';
//                $normal[$k]['preferential'] = $v['preferential'] ?: '';
//                $normal[$k]['end_time'] = $v['end_time'] ?: '';
//            }

        }
        if (empty($abnormal)){$abnormal='';}//已退卡
        if (empty($normal)){$normal='';}  //使用中
        if (empty($process)){$process='';}  //退卡中
        $data['abnormal_data']=$abnormal;
        $data['normal']=$normal;
        $data['process']=$process;

        echo json_encode([
            'msg' => 'success',
            'status' => 1000,
            'data' => $data
        ]);exit;

    }


    /**
     * 根据卡号获取优惠额度
     */
    public function discountMoney()
    {
        $card_no = trim(I('post.card_no'));
        $money   = trim(I('post.money'));
        if (empty($card_no)) $this->error('卡号不能为空');
        // if (empty($money)) $this->error('金额不能为空');

        $res = M('OilCard')->where(['card_no'=>$card_no])->find();
        if (!$res) $this->error('无效卡号！');

        $real_pay = ($money * $res['discount'])/100;
        $discount_money = $money - $real_pay;

        echo json_encode(['msg'=>'success','status'=>1000,'data'=>['discount_money'=>$discount_money,'real_pay'=>$real_pay]]);
        exit();
    }

    /**
     *  添加卡备注
     */
    public function cardNote(){

        $card_no = trim(I('post.card_no'));
        $note   = trim(I('post.note'));
        $openid  = trim(I('post.openid'));
        $flag  = trim(I('post.flag',''));
        if (empty($openid))$this->error('openid不能为空！');
        if (empty($card_no)) $this->error('卡号不能为空!');
        if (empty($note)) $this->error('备注不能为空!');
        if (mb_strlen($note) > 100) $this->error('备注字数超出限制!');

        $agent_role=M('agent')->where("openid='$openid'")->getField('role');
        if (!empty($flag)){
            $res=M('oil_card')->where("card_no='$card_no'")->save(['card_note'=>$note]);
        }else{
            if ($agent_role==3){
                $res = M('OilCard')->where(['card_no'=>$card_no])->save(['agent_remarks'=>$note]);
            }else{
                $res = M('OilCard')->where(['card_no'=>$card_no])->save(['card_note'=>$note]);
            }
        }


        if ($res===false) {
            $this->error('添加备注失败!');
        }else{
            $this->success('添加备注成功');
        }



    }

    /**
     * 用户申请退卡
     * @Author 老王
     * @创建时间   2018-12-29
     * @return [type]     [description]
     */
    public function withdrawCard(){
        $card_no=trim(I('post.card_no',''));
        $desc = trim( I('post.desc','') );
        $type = trim( I('post.type','') );
        $phone = trim( I('post.phone','') );

        $openid  = trim(I('post.openid'));
        $this->_empty($card_no,'数据传输错误');
        $user_arr=M('user')->where(['openid'=>$openid])->find();

        $cardInfo = M('oil_card')->where(['card_no'=>$card_no,'user_id'=>$user_arr['id']])->find();
        if (empty($cardInfo)){
            $this->error('非法请求!');
        }
        if($type == 1){
            $res= M('oil_card')->where(['id'=>$cardInfo['id']])->save([
                'is_notmal'=>2,
                'desc' => '用户于【'.date('Y-m-d H:i:s',TIMESTAMP).'】申请退卡，冻结油卡！'
            ]);

            if ($res) {
                //添加到禁用日志
                $addLog = [
                    'userid'     => $user_arr['id'],
                    'cardid'     => $cardInfo['id'],
                    'phone'     => $phone,
                    'addtime'    => TIMESTAMP,
                    'updatetime' => TIMESTAMP,
                    'desc'       => !empty($desc)?$desc:'申请退卡',
                    'type'       => 1,
                    'hand'       => 1,
                    'status'     => 2,
                    'adminid'    => 0,
                ];
                $result = M('oil_option')->add($addLog);
                if($result)$this->success('退卡成功');
            }
            $this->error('退卡失败');
        }else{
            $res= M('oil_card')->where(['id'=>$cardInfo['id']])->save([
                'is_notmal'=>2,
                'desc' => '用户于【'.date('Y-m-d H:i:s',TIMESTAMP).'】申请挂失，冻结油卡！'
            ]);
            if ($res) {
                //添加到禁用日志
                $addLog = [
                    'userid'     => $user_arr['id'],
                    'cardid'     => $cardInfo['id'],
                    'phone'     => $phone,
                    'addtime'    => TIMESTAMP,
                    'updatetime' => TIMESTAMP,
                    'desc'       => !empty($desc)?$desc:'申请挂失',
                    'type'       => 2,
                    'hand'       => 1,
                    'status'     => 2,
                    'adminid'    => 0,
                ];
                $result = M('oil_option')->add($addLog);
                if($result)$this->success('申请挂失成功');
            }
            $this->error('申请挂失失败');

        }




        /*M('order_record')->add([
                'card_no'=>$card_no,
                'user_id'=>$user_arr['id'],
                'order_type'=>4,
                'serial_number'=>date('YmdHis').str_pad(mt_rand(1,999999),6,STR_PAD_LEFT),
                'order_status'=>$order_status
            ]);

        if ($res!==false){
            $this->success('退卡成功');
        }else{
            $this->error('退卡失败');
        }*/

    }


    
}