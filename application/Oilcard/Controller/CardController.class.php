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
            $card_no = trim(I('post.card_no',''));
            $openid  = trim(I('post.openid',''));
            $id  = trim(I('post.id',''));
            if (empty($openid)){
                $this->openidError('数据传输错误');
            }
            $issetLoginRes=$this->issetLogin($openid);


            if (!isset($card_no) || ! $card_no)
            {
                $this->error('卡号不能为空！');
            }
            if (!isset($openid) || ! $openid)
            {
                $this->error('openid不能为空！');
            }

            $user = M('User')->where(['openid'=>$openid])->find();

//            if (!$user)
//            {
//                //跳转到微信登录url，待完善
//                return redirect(U('oilcard/wechat/getCode'));
//            }


        if (!empty($id)){
            $card = M('OilCard')->where(['card_no'=>$card_no,'status'=>2])->find();
            //判断卡号是否已申领/已有人
            if(empty($card))
            {
                $this->error('无效的卡号！');
            }

            if (isset($card['user_id']) && $card['user_id']){
                $this->error('该卡号已经被绑定！');
            }
            $card['user_id'] = $user['id'];
            $update_oilCard = array(
                'user_id'=>$user['id'],
                'updatetime'=>date('Y-m-d H:i:s',time()),
            );
            $res = M('OilCard')->where(['card_no'=>$card_no,'status'=>2])->save($update_oilCard);
            if ($res!==false){
                //发送100元优惠券
                /*$coupon = [];
                $coupon['user_id'] = $user['id'];
                $coupon['openid'] = $user['openid'];
                $coupon['card_no'] = $card_no;
                $coupon['type'] = '1';
                $coupon['status'] = '1';
                $coupon['replace_money'] = '100';
                M('coupon')->add($coupon);*/

//                M('order_record')->add(['order_type'=>2,'user_id'=>$user['id'],'card_no'=>$card_no,'order_status'=>2]);

                $orderRecordModel = M('order_record');$userApplyModel=M('user_apply');
                //查询订单号
                $serial_number = $orderRecordModel->where(['id'=>$id,'order_type'=>1,'user_id'=>$user['id'],'order_status'=>2])->getField('serial_number');
                //修改订单状态
                $orderRecordModel->where(['id'=>$id,'order_type'=>1,'user_id'=>$user['id'],'order_status'=>2])->save(['order_type'=>2,'updatetime'=>date('Y-m-d H:i:s',time())]);
                //修改申请表
                $userApplyModel->where('serial_number="'.$serial_number.'" AND status !=3')->save(['status'=>3,'updatetime'=>date('Y-m-d H:i:s',time())]);

//                    M('order_record')->where("id='$id'")->save(['preferential_type'=>2]);
                    $preferential=M('order_record')->where("id='$id'")->getField('preferential');
                    $card_arr=M('oil_card')->where("card_no='$card_no' AND user_id='".$user['id']."'")->find();
//                    $end_time=$card_arr['end_time'];
//                    if ($end_time<date('Y-m-d H:i:s')){
//                        M('oil_card')->where("card_no='$card_no'")->save(['preferential'=>$preferential,'end_time'=>date("Y-m-d H:i:s",strtotime("+1years"))]);
//                    }else{
//                        $end_preferential=$card_arr['preferential'];
//                        $send_preferential=$preferential+$end_preferential;
                        M('oil_card')->where("card_no='$card_no'")->save(['end_time'=>date("Y-m-d H:i:s",strtotime("+1years"))]);
//                    }
                }

                //微信通知
//                $notice = [];
//                $notice['card_no'] = $card_no;
//                $notice['careatetime'] = date('Y-m-d H:i:s',time());
//                $Wechat = A('Wechat');
//                $Wechat->templateMessage($openid,$notice,2);

                $this->success('绑定成功！');
            }

//        }catch (\Exception $e) {
//            echo $e->getMessage();
//            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
//            exit();
//        }
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
        $normal ='';
        $abnormal='';
        $process='';



        foreach ($cardList as $k=>$v)
        {
            // var_dump($v);
            if($v['pkgid']>1){
                $role =2;
            }else{
                $role=1;
            }
            if (strtotime($v['end_time'])>0) {
                $end_time=$v['end_time'];
            }else{
                $end_time='';
            }
            $normal[$k]['role'] =$role;
            $normal[$k]['card_no'] = $v['card_no'];
            $normal[$k]['status'] = $v['status'];
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
        $card_no=I('post.card_no','');
        $desc = trim( I('post.desc','') );

        $openid=I('post.openid','');
        $this->_empty($card_no,'数据传输错误');
        $user_arr=M('user')->where(['openid'=>$openid])->find();

        $cardInfo = M('oil_card')->where(['card_no'=>$card_no,'user_id'=>$user_arr['id']])->find();
        if (empty($cardInfo)){
            $this->error('非法请求!');
        }
        $res= M('oil_card')->where(['card_no'=>$card_no])->save([
            'is_notmal'=>2,
            'desc' => '用户于【'.date('Y-m-d H:i:s',TIMESTAMP).'】申请退卡，冻结油卡！'
        ]);

        if ($res) {
           //添加到禁用日志
          $addLog = [
            'userid'     => $user_arr['id'],
            'cardid'     => $cardInfo['id'],
            'addtime'    => TIMESTAMP,
            'updatetime' => TIMESTAMP,
            'desc'       => !empty($desc)?$desc:'申请退卡',
            'type'       => 1,
            'status'     => 1,
            'adminid'    => 0,
          ];
          $result = M('oil_option')->add($addLog);
          if($result)$this->success('退卡成功');
       }
       $this->error('退卡失败');

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