<?php
/**
 * Created by PhpStorm.
 * User: 1006a
 * Date: 2018/3/28
 * Time: 9:34
 */

namespace UserShare\Controller;


use Think\Controller;
use think\Session;

class HomeController extends Controller
{
    public $member;
    public $domain;

    public function _initialize()
    {
        $member = session('member_info');;
        $this -> domain = 'http://'.$_SERVER['HTTP_HOST'];
        $this -> member = $member;
        if (!empty($member)) {
            if (in_array(strtolower(CONTROLLER_NAME), ['login', 'register'])) {
                header('location:' . U('index/index'));
            }
        } else {
            // 非登陆
                $user = session('wechat_user');
                if(empty($user))
                {
                    $this->oauth();
                }
        }
    }


    public function oauth()
    {
            session('target_url', U('grade/login/login'));
            // 跳转到授权功能
            $oauth_url = $this -> domain.U('grade/wechat/wechatUserOauth');
            header('location:'.$oauth_url);
            exit;

    }
}