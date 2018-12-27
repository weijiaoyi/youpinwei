<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/14
 * Time: 17:18
 */
namespace Oilcard\Conf;
class CardConfig
{
    static $wxconf = [
        'appid'       => 'wxd16b20528d23aff8',
        'appsecret'   => 'b303f8f0002cd185cce101d63d342a85',
        'mch_id'      => '1518293011',
        'pay_key'     => 'ANSGUYa78tsygho890y8o6chga98suJH'
    ];

    static $payStatus = [
        '1'=>'成功',
        '2'=>'失败',
        '3'=>'充值中',
        '4'=>'待支付',
    ];


    static $addMoneyIcon = [
        '1'=>'suc',
        '2'=>'fail',
        '3'=>'continue',
        '4'=>'wait',
    ];

    static $agent_money = [
        '1' => 0,   //普通用户无
        '2' => 0.02,//银牌代理返利
        '3' => 0,   //金牌代理待定
    ];

    static $agent_ame = [
        '1' => '普通用户',
        '2' => 'vip用户',
        '3' => '代理商',
    ];

}