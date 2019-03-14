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
        $scene  = trim(I('post.scene'));
        if (!isset($openid) || ! $openid)
        {
            $this->openidError('openid不能为空！');
        }
        $agent_role=M('agent')->where("openid='$openid'")->getField('role');
        $userInfo = M('user')->where(['openid'=>$openid])->find();
        $card_count = count(M('OilCard')->where(['user_id'=>$userInfo['id']])->select());

        //总人数
        $user_count = M('user')->count();
        $num_count = 11118+intval($user_count);
//        if (!$userInfo)
//        {
//            //跳转到微信登录url
//            redirect(U('oilcard/wechat/getCode'));
//        }
        //判断是否申领过
//        $user_apply = M('user_apply')->where("openid='$openid'")->find();
//        if(empty($user_apply)){

        if($userInfo['agent_bind'] == 0 && $userInfo['parent_bind'] == 0){
            if (!empty($scene)){
                //查询邀请人ID及邀请人代理商ID
                $parent=M('user')
                    ->alias('u')
                    ->join('__AGENT__ a ON a.openid=u.openid',LEFT)
                    ->where('u.openid="'.$scene.'"')
                    ->find();
                if($parent['role'] == 3){//邀请人是一级代理商
                    $parent_data=array(
                        'parentid'=>$parent['id'],//邀请人ID
                        'agentid'=>$parent['id'],//邀请人代理商ID
                        'agent_relation'=>1//直接关系
                    );
                }else{
                    if(empty($parent['agentid'])){//邀请人不是代理商，邀请人没有上级代理商
                        $parent_data=array(
                            'parentid'=>$parent['id'],//邀请人ID
                            'agentid'=>$parent['agentid'],//邀请人代理商ID
                            'agent_relation'=>3//关系
                        );
                    }else{//邀请人不是代理商，邀请人有上级代理商
                        $parent_data=array(
                            'parentid'=>$parent['id'],//邀请人ID
                            'agentid'=>$parent['agentid'],//邀请人代理商ID
                            'agent_relation'=>2//间接关系
                        );
                    }

                }
                $parent_data['agent_bind'] = 1;//锁定上级代理人
                $parent_data['parent_bind'] = 1;//锁定邀请人

                M('user')->where("openid='$openid'")->save($parent_data);
            }else{//没有邀请人
                $parent_data=array(
                    'parentid'=>0,//邀请人ID
                    'agentid'=>0,//邀请人代理商ID
                    'agent_relation'=>3//关系
                );
                $parent_data['agent_bind'] = 1;//锁定上级代理人
                $parent_data['parent_bind'] = 1;//锁定邀请人
                M('user')->where("openid='$openid'")->save($parent_data);
            }
        }

//        }

        $output = [];
        $output['nickname'] = base64_decode($userInfo['nickname']);
        $output['user_img'] = $userInfo['user_img'];
        $output['card_count'] = $card_count;
        $output['phone'] = $userInfo['phone'] ?: '';
        $output['openid'] = $userInfo['openid'];
        $output['integral'] = $userInfo['integral'] ?: 0;
        $output['already_save_money'] = $userInfo['already_save_money'] ?: 0;
        $output['total_add_money'] = $userInfo['total_add_money']?: 0;
        $output['agent'] = $agent_role;
        $output['num_count'] = $num_count;

        echo json_encode(['msg'=>'success','status'=>1000,'data'=>$output]);
        exit();
    }
}