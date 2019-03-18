<?php
/**
 * Created by PhpStorm.
 * User: yvette
 * Date: 2019/3/15
 * Time: 下午1:10
 */

namespace Admin\Controller;
use Oilcard\Conf\CardConfig;
use Oilcard\Conf\QFPayConfig;
use Common\Lib\Tool;
use Common\Lib\XML;
use Common\Controller\AdminbaseController;
class PayOrderController extends AdminbaseController
{

    private $pay_uri = 'https://api.mch.weixin.qq.com';



    //查询支付订单页
    public function ordersn(){
        $is_first = I('request.fir',0);
        if(!$is_first){
            $phone = I('phone');
            $card_no = I('card_no');
            $p = trim(I('get.p','1'));
            if(!empty($phone)){
                $where['U.phone'] = $phone;
            }
            if(!empty($card_no)){
                $where['R.card_no'] = $card_no;
            }

            $order = M('order_record')
                ->alias('R')
                ->field('R.*,U.phone,U.nickname')
                ->join('__USER__ U ON U.id=R.user_id',LEFT)
                ->where($where)
                -> page($p,'10')
                ->order('id desc')
                ->select();
            $count = M('order_record')
                ->alias('R')
                ->field('R.*,U.phone,U.nickname')
                ->join('__USER__ U ON U.id=R.user_id',LEFT)
                ->where($where)
                ->count();
            $page = $this->page($count, 10);
            $this->assign("page", $page->show('Admin'));
            $this->assign('order',$order);
        }

        echo  11;die;
        $this->display();

    }
   
    //查询
    public function payOrder(){
        $ordersn = I('ordersn');
        $code = I('code');
        if($code == 'wxpay'){
            $msg = $this->wxpay($ordersn);

        }elseif ($code == 'qfpay'){
            $msg = $this->qfpay($ordersn);
        }
        echo json_encode(['msg' => $msg, 'status' => 200]);

    }

    //微信订单查询
    public function wxpay($ordersn){
        $data['appid']                = CardConfig::$wxconf['appid'];
        $data['mch_id']               = CardConfig::$wxconf['mch_id'];
        $data['nonce_str']            = Tool::randomStr(20);
        $data['transaction_id']       = $ordersn;
        ksort($data);
        $string1                      = urldecode(http_build_query($data).'&key='.CardConfig::$wxconf['pay_key']);
        $data['sign']                 = md5($string1);
        $content                      = XML::build($data);
        $ch_url                       = $this->pay_uri.'/pay/orderquery';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ch_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $content = curl_exec($ch);
        curl_close($ch);
        $data = [];
        $obj_arr = XML::parse($content);
        if (!$obj_arr){
            return $data;
        }
        if(($obj_arr['return_code'] == 'SUCCESS') && ($obj_arr['result_code'] == 'SUCCESS')) {
            $msg['msg'] = $obj_arr['trade_state'];
        }else{
            echo json_encode(['msg' => '查询失败', 'status' => 100]);exit;
        }

        return $msg;
    }
    //钱方订单查询

    public function qfpay($ordersn){
        $data['syssn'] = $ordersn;
        $QfPay = new QFPayConfig();

        $result = $QfPay->request("query", $data);

        if($result){
            $result = json_decode($result,TRUE);
            $msg['msg'] = $result['respcd'];
        }
        return $msg;
    }


}