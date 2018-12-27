<?php
/**
 * Created by PhpStorm.
 * User: 1006a
 * Date: 2018/1/20
 * Time: 17:34
 */

namespace Admin\Controller;


use Apis\Controller\ApiController;
use Think\Controller;
use Common\Lib\Wechat;
use Think\Log;

class WechaController extends Controller
{
    /*
 * 想微信老板发送新订单消息
 */
    public function _sendWechatBossOrderTplMsg($configId, $openid ,$order, $orderDetail){
        try{
            $wechat = new Wechat($configId);
            $orderSn = $order['order_sn'];
            $orderName = $orderDetail['goods_name'];
            $first = '您的有新的订单需要处理';
            $remark = '点击详情查看';
            $data = array(
                'first'    => $first,
                'keyword1' => $orderSn,
                'keyword2' => $orderName,
                'remark'   => $remark,
            );
            $prefix = $wechat->getWechatFunctionPrefix();
            $file = 'boss-order-list.html';
            $url = HOSTNAME.$prefix.'/'.$file.'#config_id='.$configId.'&openid='.$openid;
            $templateId = $wechat->getTemplateMsgId('order_finish_tpl_id');
            $wechat->sendWechatTemplateMsg($openid, $templateId, $data, $url);
        }catch(\Exception $e){
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            record_error($e);
            return $e->getMessage();
        }
    }
}