<?php
namespace Comment\Controller;
use Think\Controller;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 17:22
 */

class CommentoilcardController extends Controller
{

    private $appId      =   'wxf9ec6181d6c28b72';
    private $appSecret  =   'f811e92a1340bb4cd3506f373bfe12a2';

	public function __construct(){
		parent::__construct();
	}
    

    public function GetLogin(){
        $openid = I('post.openid');
        $info = M('user')->where(['openid'=>$openid])->find();
        if ($info) {
            $info['agent'] = M('agent')->where(['openid'=>$openid])->find();
            if ($info['agent']) {
                switch ($info['agent']['role']) {
                    case '1':
                        $info['agent']['sign'] ='普通用户';
                        break;
                    case '2':
                        $info['agent']['sign'] ='VIP用户';
                        break;
                    case '3':
                        $info['agent']['sign'] ='代理商';
                        break;
                }
            }
        }
        return $info;
    }

    /**
    *返回失败信息
    *$msg       失败提示信息
    *$status    失败状态码
    */
    public function error($msg='失败',$status='500'){
        echo json_encode([
            'msg'=>$msg,
            'status'=>$status
        ]);exit;
    }
    /**
     *返回失败信息
     *$msg       openid失败提示信息
     *$status    失败状态码
     */
    public function openidError($msg='失败',$status='501'){

        echo json_encode([
            'msg'=>$msg,
            'status'=>$status
        ]);exit;
    }

    /**
    *返回成功信息
    *$msg       成功提示信息
    *$status    成功状态码
    */
    public function success($data='成功',$status='1000'){
        echo json_encode([
            'msg'=>'success',
            'data'=>$data,
            'status'=>$status
        ]);exit;
    }

    /**
    *判断数据是否为空
    *$data    要判断的数据
    *$msg     提示信息
    */
    public function _empty($data,$msg='数据为空'){
        if (empty($data)) {
            return  $this->error($msg);
        }
    }

    //获取code
    public function getCode(){
            $url ='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appId.'&redirect_uri='.urlencode("http://118.24.95.245/a.php").'&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
            header('location:'.$url);
    } 
     //access_token
    public function getAccessToken(){
        $access_token = S('token_result');
        if (empty($access_token)) {

            $code = $_GET['code'];

            $access_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appId.'&secret='.$this->appSecret.'&code='.$code.'&grant_type=authorization_code';

            $token_result   =   json_decode($this->httpGet($token_url));
            S('token_result',$token_result,7100);

        }
        return $token_result;
    } 

    /**
    *跳转微信接口方法
    **/
    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        // var_dump($res);exit;
        curl_close($curl);
        return $res;
    }


    /**
    *判断是否登录
    */
    public function issetLogin($openid=''){
        
        if (empty($openid)) {
            $this->openidError($openid);
        }
        
        $user_data=M('user')->where("openid='$openid'")->find();
        if (empty($user_data['nickname']) && empty($user_data['user_img']) ) {
            $this->error('请先授权登录');
        }
        return true;
    }

    /**
    *判断是否登录接口
    */
    public function issetLoginInterface(){
        if (empty($openid)) {
            $openid=I('post.openid','');
        }
        if (empty($openid)) {
            $this->openidError($openid);
        }
        
        $user_data=M('user')->where("openid='$openid'")->find();
        if (empty($user_data['nickname']) && empty($user_data['user_img']) ) {
            $this->error('请先授权登录');
        }else{
            $this->success('');
        }
    }


}