<?php
namespace Oilcard\Controller;
use Think\Controller;
use Oilcard\Controller\UserController;
use Oilcard\Conf\CardConfig;
use Comment\Controller\CommentoilcardController;
use Think\Log;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 17:22
 */

class ThreeController extends CommentoilcardController
{
    private $appid;
    private $secret;

    public function __construct(){
        parent::__construct();
        header('content-type:text/html;charset=utf-8');
//        $this->appid = CardConfig::$wxconf['appid'];
//        $this->secret = CardConfig::$wxconf['appsecret'];
        $this->appid = 'wx2fdc78cdc9c7d7b4';
        $this->secret = '025b938a78d46c8f3a0c55169e86b55d';

    }

    public function getCode()
    {
        $sign = trim(I('get.sign'));
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        $redirect_url = urlencode('https://'.$_SERVER['SERVER_NAME'].U('oilcard/Three/getAccessToken',array('sign'=>$sign)));
        $url = str_replace('APPID',$this->appid,$url);
        $url = str_replace('REDIRECT_URI',$redirect_url,$url);
        header('location:'.$url);
    }

    public function getCodeUrl()
    {
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        $redirect_url = urlencode('http://'.$_SERVER['SERVER_NAME'].U('oilcard/Three/getAccessToken'));
        $url = str_replace('APPID',$this->appid,$url);
        $url = str_replace('REDIRECT_URI',$redirect_url,$url);
        echo json_encode(['msg'=>'success','status'=>1000,'url'=>$url]);
        exit();
    }

    public function getAccessToken()
    {
        try {
            $code = I('get.code');
            $sign = I('get.sign');

            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code';
            $url = str_replace('APPID', $this->appid, $url);
            $url = str_replace('SECRET', $this->secret, $url);
            $url = str_replace('CODE', $code, $url);
            $res = $this->curlGet($url);

            $info = json_decode($res, true);

            if (!is_array($info) || !isset($info['openid']))
            {
                /*echo json_encode([
                    'msg'=>'获取access_code失败！',
                    'status'=>500
                ]);*/
                echo '获取access_code失败！';
                exit();
            }

            $userinfo_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN';
            $userinfo_url = str_replace('ACCESS_TOKEN', $info['access_token'], $userinfo_url);
            $userinfo_url = str_replace('OPENID', $info['openid'], $userinfo_url);
            $userinfo = json_decode($this->curlGet($userinfo_url),true);
            if (!is_array($userinfo) || !isset($userinfo['openid']))
            {
               /* echo json_encode([
                    'msg'=>'获取用户信息失败！',
                    'status'=>500
                ]);*/
               echo '获取用户信息失败！';
                exit();
            }

//            $is_user = M('User')->where(['openid'=>$userinfo['openid']])->find();
            $is_user = M('User')->where(['sign'=>$sign])->find();

            if (!$is_user || empty($is_user) || !isset($is_user['id'])){
                echo '该手机号未绑定油卡！';
                exit();

                /*//注册新用户
                $user = array();
                $user['nickname'] = $userinfo['nickname'];
                $user['user_img'] = $userinfo['headimgurl'];
                $user['openid'] = $userinfo['openid'];
                $user['wx_access_token'] = $info['access_token'];
                $user['access_token_expires'] = $info['expires_in']+time();
                $user['refresh_token']=$info['refresh_token'];
                //第三方
                $user['phone']=$is_sign['phone'];
                $user['fromId']=$is_sign['fromId'];

                M('User')->add($user);*/

            }else {
                //更新用户信息
                $user = array();
                $user['nickname'] = $userinfo['nickname'];
                $user['user_img'] = $userinfo['headimgurl'];
                $user['wx_access_token'] = $info['access_token'];
                $user['access_token_expires'] = $info['expires_in']+time();
                $user['refresh_token']=$info['refresh_token'];

//                M('User')->where(['openid'=>$userinfo['openid']])->save($user);
                M('User')->where(['sign'=>$sign])->save($user);
            }

            header('location:'.'http://'.$_SERVER['SERVER_NAME'].'/Three/index.html?op='.base64_encode($userinfo['openid']));


        }catch (\Exception $e) {
            echo $e->getMessage();
            Log::write('[' . $e->getCode() . '] ' . $e->getMessage(), 'ERR');
            exit();
        }
    }
    /**
     * @param $url
     * @return mixed
     * curl Get
     */
    private function curlGet($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            if(intval($aStatus["http_code"]) == 301){
                return $aStatus['redirect_url'];
            }
        }
    }
    /**
     * @desc 第三方绑定油卡
     * @author langzhiyao
     * @time 20190219
     */
    public function bindCard(){
        //获取第三方传过来的信息
        if(!empty($_POST)){
            $from = trim(I('post.from'));//如：wxwt
            $phone = trim(I('post.phone'));
            $card_no = trim(I('post.card_no'));
            if(empty($from)){echo json_encode(array('status'=>100,'message'=>'第三方标识不能为空'));exit;}
            if(empty($phone)){echo json_encode(array('status'=>100,'message'=>'用户手机号不能为空'));exit;}
            if(!preg_match("/^1[345678]\d{9}$/", $phone)){echo json_encode(array('status'=>100,'message'=>'手机号格式错误'));exit;}
            if(empty($card_no)){echo json_encode(array('status'=>100,'message'=>'卡号不能为空'));exit;}
            //开启事务
            M()->startTrans();
            //判断是否具有第三方标识
            $is_three = M('three_scale')->where(array('from'=>$from))->find();
            if(!empty($is_three)){
                //判断手机号是否已注册
                $is_phone = M('user')->where(array('phone'=>$phone))->find();
                //判断卡号是否存在
                $is_card = M('oil_card')->where(array('card_no'=>$card_no))->find();
                if(!empty($is_card)){
                    //判断卡号是否已被申领
                    if($is_card['status'] == 1){
                        //判断卡号是否已被其他第三方绑定
                        if($is_card['is_threeBind'] == 0){
                            //插入用户信息
                           if(!empty($is_phone)){
                               $result = $is_phone['id'];
                           }else{
                               $sign = MD5($phone.$card_no);
                               $user = array(
                                   'fromId'=>$is_three['id'],
                                   'phone'=>$phone,
                                   'sign'=>$sign,
                               );
                               $result = M('user')->add($user);
                           }
                            if($result){
                                $res = M('oil_card')->where(array('card_no'=>$card_no))->save(array('is_threeBind'=>$is_three['id'],'user_id'=>$result,'status'=>2));
                                if($res){
                                    M()->commit();
                                    echo json_encode(array('status'=>200,'message'=>'绑定成功'));exit;
                                }else{
                                    M()->rollback();
                                    echo json_encode(array('status'=>100,'message'=>'油卡状态修改失败'));exit;
                                }
                            }else{
                                M()->rollback();
                                echo json_encode(array('status'=>100,'message'=>'用户信息插入失败'));exit;
                            }
                        }else{
                            echo json_encode(array('status'=>100,'message'=>'卡号已被绑定，无法重复操作'));exit;
                        }
                    }else{
                        echo json_encode(array('status'=>100,'message'=>'卡号已被其他用户申领，无法绑定'));exit;
                    }
                }else{
                    echo json_encode(array('status'=>100,'message'=>'卡号不存在'));exit;
                }
            }else{
                echo json_encode(array('status'=>100,'message'=>'第三方标识错误'));exit;
            }
        }else{
            echo json_encode(array('status'=>100,'message'=>'未获取到用户信息'));exit;
        }
    }

    /**
     * @desc 第三方充值
     * @author langzhiyao
     * @time 20190219
     */
    public function payCard(){
        if(!empty($_POST)){
            $openid  = trim(I('post.openid'));
            $card_no = trim(I('post.card_no'));
            if (empty($card_no) || !$card_no)exit(json_encode(['msg'=>'卡号不能为空！','status'=>100]));
            $money   = trim(I('post.money'));//实际充值金额
            if (empty($money)) exit(json_encode(['msg'=>'请选填充值金额！','status'=>100]));
            $pay_money=trim(I('post.pay_money'));//实际支付金额
            $save=trim(I('post.save')); //优惠金额
            $flag=trim(I('post.flag',1));//1，选择卡优惠  2，选择账户优惠
            $NowTime = date('Y-m-d H:i:s',TIMESTAMP);
            $initial_money=trim(I('post.money'));//折扣前价格
            $jyj =trim(I('post.jyj',0));
            $zk = trim(I('post.zk',0));
            //油卡信息
            $CardInfo = M('oil_card')->where(['card_no'=>$card_no,'status'=>2])->find();
            if (empty($CardInfo))exit(json_encode(['msg'=>'无效卡号！','status'=>100]));
            //用户信息
            $Member=M('user')->alias('a')->where(['a.openid'=>$openid])->find();
            if (!$Member)exit(json_encode(['msg'=>'需要先授权登陆之后才能做此操作！','status'=>100]));
            if(!$Member['nickname'] || !$Member['user_img'])exit(json_encode(['msg'=>'需要先授权登陆之后才能做此操作！','status'=>100]));
            if ($Member['is_notmal'] !=1)exit(json_encode(['msg'=>'当前用户信息异常，已被冻结用户信息，请向管理员或代理查询！','status'=>100]));

//            $Package = M('three_scale')->where(['from'=>$from])->find();//获取卡折扣
            $config = M('setting')->find();
            //不是正常油卡
            if ($CardInfo['is_notmal'] !=1) {
                //对此卡的操作信息
                $CardOption = M('oil_option')->where(['userid'=>$Member['id'],'cardid'=>$CardInfo['id']])->find();
                if($CardOption){
                    switch ($CardOption['type']) {
                        case '1':
                            exit(json_encode(['msg'=>'此油卡持有者已向后台申请退卡请求！','status'=>100]));
                            break;
                        case '2':
                            exit(json_encode(['msg'=>'此油卡持有者已向后台申请挂失请求！','status'=>100]));
                            break;
                        default:
                            exit(json_encode(['msg'=>'系统已对此油卡冻结使用，请向管理员或代理查询！','status'=>100]));
                            break;
                    }
                }else{
                    exit(json_encode(['msg'=>'系统已对此油卡冻结使用，请向管理员或代理查询！','status'=>100]));
                }
            }
            //订单号
            $orderSn = date('YmdHis').str_pad(mt_rand(1,999999),6,STR_PAD_LEFT);
            $OrderAdd = [
                'user_id'        => $Member['id'],
                'card_no'        => $card_no,
                'order_type'     => 3,
                'serial_number'  => $orderSn,
                'order_status'   => 1,
                'real_pay'       => $pay_money,
                'recharge_money' => $money,
                'createtime'     => $NowTime,
                'card_from'      => $CardInfo['agent_id']==0?1:2,
                'agent_id'       => !empty($CardInfo['agent_id'])?$CardInfo['agent_id']:0,
                'parentid'       => $Member['parentid'],
                'coupon_money'   => $jyj,
                'discount_money' => $zk,
            ];

            $RechageCount = M('add_money')->where(['card_no'=>$card_no,'openid'=>$openid,'status'=>1])->find();
            $is_first =2;
            if (!$RechageCount) { // 是否是首充
                if (intval($money) < intval( $config['first_rechage']) ){
                    $this->error('当前油卡首次充值额度必须大于'.$config['first_rechage'].'元额度才能被激活！');
                }
                $is_first = 1;
            }
            $AddMoneySave = [
                'user_id'        => $Member['id'],
                'openid'         => $openid,
                'card_no'        => $card_no,
                'money'          => $money,
                'discount_money' => $save,
                'real_pay'       => $pay_money,
                'pay_way'        => 1,
                'note'           => $is_first==1?'用户对此油卡的首次充值':'油卡充值',
                'status'         => 2,
                'createtime'     => $NowTime,
                'order_no'       => $orderSn,
                'is_first'       => $is_first,
            ];

                //生成订单
                // $data = $wechat->payOrder($AddMoneySave,$OrderAdd,$openid);
                $PayCon = [
                    'body'     => '油卡充值',
                    'detail'   => '油卡充值',
                    'attach'   => '油卡充值',
                    'paymoney' => $config['paymoney']
                ];
                $PayMent = new WechatController();
                switch ($config['paytype']) {
                    case '1': //微信支付
                        $data = $PayMent->_WxPay($OrderAdd,$Member,$PayCon);
                        $OrderAdd['payment_code'] = 'wxpay';
                        break;
                    case '2': //聚合支付
                        $data = $PayMent->_HjPay($OrderAdd,$Member,$PayCon);
                        $OrderAdd['payment_code'] = 'hjpay';
                        break;
                }
                if (empty($data))exit(json_encode(['msg'=>'微信下单失败！','status'=>500]));
                if($data)$data['order_no'] = $AddMoneySave['order_no'];
                $record_res = M('OrderRecord')->add($OrderAdd);
                if(!$record_res)$this->error('订单生成失败，请重试！');
                //添加充值记录
                $create_res = M('add_money')->add($AddMoneySave);
                exit(json_encode(['msg'=>'success','status'=>1000,'data'=>$data]));
        }else{
            exit(json_encode(['msg'=>'系统错误','status'=>1000]));
        }

    }

    public function getCardInfo(){
        //由第三方跳转到充值页面
        $openid = trim(I('post.openid'));
        $openid = base64_decode($openid);
        //获取用户信息
        $user = M('user')->where(array('openid'=>$openid))->find();
        if(empty($user)){echo json_encode(array('status'=>100,'message'=>'获取用户信息失败！'));exit();}
        //获取用户卡折扣
        $scale=M('three_scale')->where(array('id'=>$user['fromid']))->getField('scale');
        if(empty($scale)){echo json_encode(array('status'=>100,'message'=>'获取卡折扣失败！'));exit();}
        //获取用户绑定卡
        $cardList = M('oil_card')->where(array('user_id'=>$user['id'],'is_notmal'=>1,'is_threeBind'=>array('neq',0)))->field('card_no')->select();
        $item =[];
        if(!empty($cardList)){
            foreach($cardList as $key=>$value){
                $item[$key]['title'] .=$value['card_no'];
                $item[$key]['value'] .=$value['card_no'];
            }
        }
        //充值金额选项
         $price = array(200,500,1000,2000,5000,10000);
         $html='';
        foreach ($price as $k=>$v){
            $truePay = sprintf("%.2f",$v*$scale/100);
            $savePay = sprintf("%.2f",$v*(1-$scale/100));
            if($k%2 == 0){
                $html .='<div class="zf_ms_item" data-money="'.$v.'" data-zf="'.$truePay.'" data-js="'.$savePay.'">
                    <a href="javascript:;">
                        <div class="zf_jine">￥'.$v.'</div>
                        <div class="zf_info">
                            <p>支付￥'.$truePay.'</p>
                            <p>节省￥'.$savePay.'</p>
                        </div>
                    </a>
                </div>';
            }else{
                $html .= '<div class="zf_ms_item floatr" data-money="'.$v.'" data-zf="'.$truePay.'" data-js="'.$savePay.'">
                    <a href="javascript:;">
                        <div class="zf_jine">￥'.$v.'</div>
                        <div class="zf_info">
                            <p>支付￥'.$truePay.'</p>
                            <p>节省￥'.$savePay.'</p>
                        </div>
                    </a>
                </div>';
            }
        }
        echo json_encode(array('status'=>200,'item'=>$item,'scale'=>$scale,'html'=>$html));

    }

}