<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/14
 * Time: 17:51
 */

namespace Oilcard\Controller;


use Comment\Controller\CommentoilcardController;

class UserController extends CommentoilcardController
{
    public function userInfo()
    {
        $openid  = trim(I('post.openid'));
        if (!isset($openid) || ! $openid)
        {
            $this->openidError('openid不能为空！');
        }
        $agent_role=M('agent')->where("openid='$openid'")->getField('role');
        $userInfo = M('user')->where(['openid'=>$openid])->find();
        $card_count = count(M('OilCard')->where(['user_id'=>$userInfo['id'],'is_sale=1'])->select());

//        if (!$userInfo)
//        {
//            //跳转到微信登录url
//            redirect(U('oilcard/wechat/getCode'));
//        }

        $output = [];
        $output['nickname'] = $userInfo['nickname'];
        $output['user_img'] = $userInfo['user_img'];
        $output['card_count'] = $card_count;
        $output['phone'] = $userInfo['phone'] ?: '';
        $output['openid'] = $userInfo['openid'];
        $output['integral'] = $userInfo['integral'] ?: 0;
        $output['already_save_money'] = $userInfo['already_save_money'] ?: 0;
        $output['total_add_money'] = $userInfo['total_add_money']?: 0;
        if (empty($agent_role)){
            $agent_role='1';
        }
        $output['agent'] = $agent_role;

        echo json_encode(['msg'=>'success','status'=>1000,'data'=>$output]);
        exit();
    }
}