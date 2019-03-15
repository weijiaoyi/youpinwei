<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/21
 * Time: 10:48
 */

namespace Oilcard\Controller;


use Comment\Controller\CommentoilcardController;

class CouponController extends CommentoilcardController
{
    public function __construct(){
        parent::__construct();
    }

    public function couponList()
    {
        //有卡号展示该卡可用的优惠券
        //无卡号展示所有优惠券
        $openid = 'dfdf';trim(I('openid'));
        $card_no = '12345';trim(I('card_no'));

        if (!isset($openid) || ! $openid)
        {
            $this->error('openid不能为空！');
        }

        $coupon = M('Coupon')->where(['openid'=>$openid])->find();

        $output = [];


        if (!$coupon) {
            echo json_encode(['msg'=>'success','status'=>1000,'data'=>$output]);
            exit();
        }

        if ($card_no) {
            if (!is_numeric($card_no)){
                $this->error('卡号格式错误！');
            }

            $card_coupon = M('Coupon')->where([
                'type'=>1,
                'card_no'=>$card_no,
                'openid'=>$openid,
                'status'=>1,
                ])->select();
        }else{
            $card_coupon = M('Coupon')->where([
                'openid'=>$openid,
                'status'=>1,
            ])->select();
        }

        foreach ($card_coupon as $k=>$v){
            $output[$k]['coupon_id'] = $v['id'];
            $output[$k]['type'] = $v['type'];
            $output[$k]['replace_money'] = $v['replace_money'];
            $output[$k]['expire_time'] = $v['expire_time'];
        }

        echo json_encode(['msg'=>'success',
            'status'=>1000,
            'data'=>$output]);
        exit();
    }
}