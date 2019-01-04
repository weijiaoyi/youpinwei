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
        $p=I('post.p','1');
        $l=I('post.l',10);
        $flag=I('post.flag','1');
        $this->_empty($openId,'数据传输失败');
        $Member=M('user')->alias('a')->join('__AGENT__ b ON a.id=b.id')->where(['a.openid'=>$openId])->find();
        $where = [
            'R.user_id' =>$Member['id'],
            'R.order_status' =>2
        ];

        $field = 'R.*,M.id as rechageid,A.id as applyid,R.real_pay,M.money as recharge_money,M.discount_money';
        $order_data=M('order_record')
            ->alias('R')
            ->field($field)
            ->join('__ADD_MONEY__ as M ON M.order_no = R.serial_number','LEFT')
            ->join('__USER_APPLY__ as A ON A.serial_number = R.serial_number','LEFT')
            ->where($where)
            ->order('R.createtime desc')
            ->page($p,$l)
            ->select();

        $count=M('order_record')
            ->alias('R')
            ->join('__ADD_MONEY__ as M ON M.order_no = R.serial_number')
            ->join('__USER_APPLY__ as A ON A.serial_number = R.serial_number')
            ->where($where)
            ->count(); //数据总条数
        
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