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

//        if ($flage=='1') {
//            $where=[
//                'user_id'=>$user_data['id'],
//                'order_type'=>'3'1
//            ];
//            $order_data=M('order_record')->where($where)->order('createtime desc')->limit($page,$offset)->select();    //展示数据
//        }else{

            $order_data=M('order_record')
                ->where('user_id="'.$user_data["id"].'" AND order_type=3 AND order_status=2')
                ->order('createtime desc')
                ->limit($page,$offset)
                ->select();    //展示数据
//        }
        foreach ($order_data as $key => $v) {
           if ($v['order_type']=='1') {
               $order_data[$key]['flag']='suc';
               $order_data[$key]['order_flag']='shenling';

           }else if($v['order_type']=='2'){

                $order_data[$key]['flag']='fail';
                $order_data[$key]['order_flag']='bangdin';

           }else if($v['order_type']=='3'){

                $order_data[$key]['flag']='continue';
                $order_data[$key]['order_flag']='chongzhi';

           }else{
                $order_data[$key]['flage']='wait';
                $order_data[$key]['order_flag']='jifen';
           }
        }
        $count=M('order_record')->where('user_id='.$user_data['id'])->count();   //数据总条数
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
        $order_data=M('order_record')->where('id='.$id)->find();
        if (empty($order_data)) {
            $this->error('订单详情数据为空');
        }
        $user_id=$order_data['user_id'];
        $serial_number=$order_data['serial_number'];
        $data=M('user_apply')->where("user_id='$user_id' and serial_number='$serial_number'")->find();
        switch ($data['shop_name']) {
            case '1':
                $shop_name='中国石油加油卡';
                break;
        }

        $data['name']=$shop_name;
        $data['money']=$order_data['money']-20;

        $data['discount']=$order_data['preferential'];
        $order_data=array_filter($data);
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