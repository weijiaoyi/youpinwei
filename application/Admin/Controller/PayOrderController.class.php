<?php
/**
 * Created by PhpStorm.
 * User: yvette
 * Date: 2019/3/15
 * Time: 下午1:10
 */

namespace Admin\Controller;


class PayOrderController
{
    //支付订单号查询
    public function payOrder(){
        $ordersn = I('ordersn');
        $order = M('order_record')->where(['pay_sn'=>$ordersn])->find();
    }
}