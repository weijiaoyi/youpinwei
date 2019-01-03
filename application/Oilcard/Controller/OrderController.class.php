<?php 
namespace Oilcard\Controller;

use Think\Controller;
use Oilcard\Controller\UserController;
use Comment\Controller\CommentoilcardController;

class OrderController extends CommentoilcardController
{
    public function __construct(){
        parent::__construct();
    }
    /**
    *订单展示
    */
    public function orderList(){
        $openId=I('post.openid','');
        $flage=I('post.flage','');
        $p=I('post.p','');
        $offset=I('post.offset','20');

        if (empty($p)){
            $page=0;
        }else{
            $page=($p-1)*$offset;
        }

        $this->_empty($openId,'数据传输失败');
        $user_data=M('user')->where("openid='".$openId."'")->find();

            $order_data=M('order_record')
                ->where('user_id="'.$user_data["id"].'" AND order_status=2')
                ->order('createtime desc')
                ->limit($page,$offset)
                ->select();  

        $count=M('order_record')->where('user_id="'.$user_data["id"].'" AND order_status=2')->count();   //数据总条数
        $count=ceil($count/$offset);     //数据总页数
        
        $data=[
            'orderdata'=>$order_data,
            'p'=>$p,
            'count'=>$count
        ];

        $this->success($data);
        echo json_encode($data);exit;

    }

    /**
    *订单详情
    */                  
    public function orderDetails(){
        $id = I('post.order_id','');
        $this->_empty($id,'参数错误');
        $order_data=M('order_record')
            ->alias('o')
            ->join('user_apply u ON u.serial_number=o.serial_number',LEFT)
            ->where('o.id='.$id)
            ->find();
        if (empty($order_data)) {
            $this->error('订单详情数据为空');
        }

        echo json_encode($order_data);exit;
    }

    /**
     * 判断是否绑定油卡
     */
    public function issetCard($openid){
        $user_id=M('user')->where("openid='$openid'")->getField('id');
        $card_arr=M('oil_card')->where("user_id='$user_id' and status=2 ")->select();
        return $card_arr;
    }

    /**
     * 指定一张油卡设为93折
     */
    public function upCardDiscount($card_no){

        $res=M('Oil_card')->where("card_no='$card_no'")->save(['discount'=>'93']);
        return $res;
    }


}