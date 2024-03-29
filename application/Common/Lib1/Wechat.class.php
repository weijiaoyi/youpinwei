<?php
namespace Common\Lib;
use Common\Lib\Constant;
use Think\Log;
use Common\Lib\WxPay\WxPayData\WxPayUnifiedOrder;
use Common\Lib\WxPay\WxPayApi;
use Common\Lib\WxPay\JsApiPay;
use Common\Lib\WxPay\PayNotifyCallBack;

class Wechat{
    // 微信关注事件
    const WX_EVENT_SUBSCRIBE = 'subscribe';
    // 微信取消关注事件
    const WX_EVENT_UNSUBSCRIBE = 'unsubscribe';
    // 微信用户发送文本事件
    const WX_EVENT_TEXT = 'text';
    // 微信菜单点击事件
    const WX_EVENT_CLICK = 'click';
    //微信扫描带参数二维码事件
    const WX_EVENT_SCAN = 'scan';

    private $_wechatUrls = array(
        'userinfo' => 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=TOKEN&openid=OPENID&lang=zh_CN',
        'token' => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET',
        'menuget' => 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=TOKEN',
        'menusave' => 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=TOKEN',
        'authorize' => 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URL&response_type=code&scope=snsapi_base&state=11#wechat_redirect',
        'access_token' => 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=APPSECRET&code=CODE&grant_type=authorization_code',
        'send_template' => 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=TOKEN',
        'create_qr_ticket' => 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=TOKEN',
        'ticket_url' => 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=TICKET',
    );

    private $_notifyUrl = 'payNotify.php';

    private $_config = array(
        'public_id' => '',
        'appid' 	=> '',
        'appsecret' => '',
        'mchid' 	=> '',
        'mchsecret' => '',
        'token' 	=> '',
        'wechat_name' => '',
        'tel' => '',
        'order_success_tpl_id' => '',
        'order_deliver_tpl_id' => '',
        'order_finish_tpl_id'  => '',
        'order_push_tpl_id'    => '',
        'function_prefix'      => '',
    );

    private $_configId = false;

    private $_callbacks = array();

    public function __construct($configId=false){
        $configId = intval($configId);
        $config = M('wechat_config')->where(array('id'=>$configId))->find();
        if(0 == count($config)){
            throw new \Exception('No Wechat Config Found.', Constant::EXCEPTION_NO_WECHAT_CONFIG_ERROR);
        }else{
            $this->_configId = $configId;
            $this->_config = array(
                // 'public_id' => 'gh_d3b86a58ed7d',
                'public_id' => $config['public_id'],
                'appid' 	=> $config['appid'],
                'appsecret' => $config['appsecret'],
                'mchid' 	=> $config['mchid'],
                'mchsecret' => $config['mchsecret'],
                'token' 	=> $config['token'],
                'wechat_name' => $config['wechat_name'],
                'tel' => $config['tel'],
                'order_success_tpl_id' => $config['order_success_tpl_id'],
                'order_deliver_tpl_id' => $config['order_deliver_tpl_id'],
                'order_finish_tpl_id'  => $config['order_finish_tpl_id'],
                'order_push_tpl_id'    => $config['order_push_tpl_id'],
                'function_prefix'      => $config['function_prefix'],
            );
            Log::write(implode ($this->_config));
        }
    }

    /**
     * 创建带参数的二维码
     * @param  integer  $sceneId 场景ID
     * @param  boolean  $limited 是否是永久的
     * @return URL      二维码的url
     */
    public function createParameteredWechatQr($sceneId, $limited=true){
        $sceneId = intval($sceneId);
        if($sceneId < 1){
            return false;
        }

        $data = array(
            'action_name' => $limited ? "QR_LIMIT_SCENE" : "QR_SCENE",
            'expire_seconds' => $limited ? 0 : 86400*30,
            'action_info' => array('scene'=>array('scene_id'=>$sceneId))
        );
        if($limited) unset($data['expire_seconds']);

        try{
            $token = $this->getAccessToken();
            $url = $this->_wechatUrls['create_qr_ticket'];
            $url = str_replace('TOKEN', $token, $url);
            $result = $this->_httpPost($url, json_encode($data));
            $result = json_decode($result, true);
            if($result && (!isset($result['errcode']) || $result['errcode'] == 0)){
                $ticket = $result['ticket'];
            }else{
                $token = $this->getAccessTokenFromWechat();
                $url = $this->_wechatUrls['create_qr_ticket'];
                $url = str_replace('TOKEN', $token, $url);
                $result = $this->_httpPost($url, json_encode($data));
                $result = json_decode($result, true);
                if($result && (!isset($result['errcode']) || $result['errcode'] == 0)){
                    $ticket = $result['ticket'];
                }else{
                    throw new \Exception('Get Wechat Parametered Qr Ticket Error', 1);
                }
            }
        }catch(\Exception $e){
            echo $e->getMessage();
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            record_error($e);
            exit();
        }

        $ticketUrl = $this->_wechatUrls['ticket_url'];
        $ticketUrl = str_replace('TICKET', $ticket, $ticketUrl);
        return $ticketUrl;
    }

    public function wechatPayNotify($obj=false, $callback=false){
        if(method_exists($obj, $callback)){
            $notify = new PayNotifyCallBack();
            $res = $notify->Handle(false, $this->_config, $obj, $callback);
        }else{
            throw new \Exception('Wechat Pay Notify Callback Function Not Exists', 1);
        }
    }

    /**
     * 获取发起微信支付时的js参数
     * @param  [type]  $outTradeNo [description]
     * @param  string  $openId     [description]
     * @param  string  $fee        [description]
     * @param  string  $body       [description]
     * @param  string  $attach     [description]
     * @param  string  $goodsTag   [description]
     * @param  integer $expireIn   [description]
     * @return [type]              [description]
     */
    public function getJsApiParameters($outTradeNo, $openId=false, $fee='1', $body='test', $attach='test', $goodsTag='test', $expireIn=600){
        $config = array(
            'appid' => $this->_config['appid'],
            'appsecret' => $this->_config['appsecret'],
            'mchid' => $this->_config['mchid'],
            'mchsecret' => $this->_config['mchsecret'],
            'sslcert_path' => $this->_config['sslcert_path'],
            'sslkey_path' => $this->_config['sslkey_path']
        );
        $tools = new JsApiPay();
        $input = new WxPayUnifiedOrder($config);
        $input->SetBody($body);
        $input->SetAttach($attach);
        $input->SetOut_trade_no($outTradeNo);
        //$input->SetTotal_fee($fee);
        $input->SetTotal_fee('1');
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + $expireIn));
        $input->SetGoods_tag($goodsTag);
        $input->SetNotify_url(HOSTNAME.$this->_notifyUrl);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);

        $order = WxPayApi::unifiedOrder($config, $input);
        return $tools->GetJsApiParameters($config, $order);
    }

    /**
     * 获取商家电话
     * @return [type] [description]
     */
    public function getWechatTel(){
        return ($this->_config['tel']) ? $this->_config['tel'] : false;
    }

    /**
     * 获取商家名称（服务号名称）
     * @return [type] [description]
     */
    public function getWechatName(){
        return ($this->_config['wechat_name']) ? $this->_config['wechat_name'] : false;
    }

    /**
     * 获取模版消息ID
     * @param  string $key [description]
     * @return [type]      [description]
     */
    public function getTemplateMsgId($key=''){
        if(0 != strlen($key)){
            return ($this->_config[$key]) ? $this->_config[$key] : false;
        }else{
            return false;
        }
    }

    public function getWechatFunctionPrefix(){
        return ($this->_config['function_prefix']) ? $this->_config['function_prefix'] : false;
    }

    /**
     * [sendWxTemplateMsg description] 发送微信模板消息
     *
     * 需要在微信中开启模板消息功能，并选择模板，获取模板消息的templa_id
     *
     * @param  boolean $openid     [description] 接受者的openid
     * @param  boolean $templateId [description] 模板消息template_id
     * @param  array   $data       [description] 模板内容data
     * @param  boolean $url        [description] 模板消息点击详情时的 H5 URL
     * @param  string  $topColor   [description] 模板消息标题颜色
     * @param  string  $dataColor  [description] 模板消息内容颜色
     * @return [type]              [description] wx response
     */
    public function sendWechatTemplateMsg($openid=false, $templateId=false, $data=array(), $detailUrl=false, $topColor='#0D9CCC', $dataColor='#0D9CCC'){
        $token = $this->getAccessToken();
        $url = $this->_wechatUrls['send_template'];
        $url = str_replace('TOKEN', $token, $url);
        $post = array(
            'touser' => $openid,
            'template_id' => $templateId,
            'url' => $detailUrl,
            'topcolor' => $topColor,
            'data' => array()
        );
        foreach ($data as $k => $v) {
            $post['data'][$k] = array(
                'value' => $v,
                'color' => $dataColor
            );
        }
        $res = $this->_httpPost($url, $this->_jsonEncode($post));
        $res = json_decode($res, true);
        if(is_array($res) && isset($res['errcode']) && 0 != intval($res['errcode'])){
            $token = $this->getAccessTokenFromWechat();
            $url = $this->_wechatUrls['send_template'];
            $url = str_replace('TOKEN', $token, $url);
            $res = $this->_httpPost($url, $this->_jsonEncode($post));
            $res = json_decode($res, true);
            if(is_array($res) && isset($res['errcode']) && 0 != intval($res['errcode'])){
                throw new \Exception('Send wechat template msg error: ['.intval($res['errcode']).'] '.$res['errmsg'], Constant::EXCEPTION_SEND_WECHAT_TEMPLATE_MSG_ERROR);
            }else{
                return true;
            }
        }else{
            return true;
        }
    }

    /**
     * 微信页面用户授权－1，引导用户到授权页面
     * @return redirect 微信授权url
     */
    public function getWechatUserAuthorize($redirectUrl){
        $url = $this->_wechatUrls['authorize'];
        $url = str_replace('APPID', $this->_config['appid'], $url);
        $url = str_replace('REDIRECT_URL', urlencode($redirectUrl), $url);
        header("Location:".$url);
    }

    /**
     * 微信页面用户授权－2，授权回调后，code获取用户授权access_token，进而获取用户基本信息，跳转微信页面
     * @return redirect 微信页面url
     */
    public function wechatUserAuthorizeCodeToAccessToken(){
        $code = I('get.code');
        $url = $this->_wechatUrls['access_token'];
        $url = str_replace('APPID', $this->_config['appid'], $url);
        $url = str_replace('APPSECRET', $this->_config['appsecret'], $url);
        $url = str_replace('CODE', $code, $url);

        $accessToken = $this->_httpGet($url);
        $accessToken = json_decode($accessToken, true);
        if(is_array($accessToken) && isset($accessToken['errcode']) && 0 != intval($accessToken['errcode'])){
            throw new \Exception('Get Openid From Code Error: ['.intval($accessToken['errcode']).'] '.$accessToken['errmsg'], Constant::EXCEPTION_CODE_TO_OPENID_ERROR);
        }else{
            return $this->getWxUserBasicInfo($accessToken['openid']);
        }
    }

    /**
     * 获取微信服务号菜单列表
     * @return [type] [description]
     */
    public function getWechatMenuList(){
        $token = $this->getAccessToken();
        $url = $this->_wechatUrls['menuget'];
        $url = str_replace('TOKEN', $token, $url);
        $menu = $this->_httpGet($url);
        $menu = json_decode($menu, true);
        if(is_array($menu) && isset($menu['errcode']) && $menu['errcode'] != 0){
            $token = $this->getAccessTokenFromWechat();
            $url = $this->_wechatUrls['menuget'];
            $url = str_replace('TOKEN', $token, $url);
            $menu = $this->_httpGet($url);
            $menu = json_decode($menu, true);
            if(is_array($menu) && isset($menu['errcode']) && $menu['errcode'] != 0){
                throw new \Exception('Get Wechat Menu List Error: ['.intval($menu['errcode']).'] '.$menu['errmsg'], Constant::EXCEPTION_GET_MENU_LIST_ERROR);
            }else{
                return $menu;
            }
        }else{
            return $menu;
        }
    }

    /**
     * 保存微信菜单
     * @param  [type] $wechatMenuData [description]
     * @return [type]                 [description]
     */
    public function saveWechatMenu($wechatMenuData){
        $token = $this->getAccessToken();
        $url = $this->_wechatUrls['menusave'];
        $url = str_replace('TOKEN', $token, $url);

        $save = $this->_httpPost($url, $this->_jsonEncode($wechatMenuData));
        $save = json_decode($save, true);
        if(is_array($save) && $save['errmsg'] == 'ok'){
            return true;
        }else{
            $token = $this->getAccessTokenFromWechat();
            $url = $this->_wechatUrls['menusave'];
            $url = str_replace('TOKEN', $token, $url);

            $save = $this->_httpPost($url, $this->_jsonEncode($wechatMenuData));
            $save = json_decode($save, true);
            if(is_array($save) && $save['errmsg'] == 'ok'){
                return true;
            }else{
                throw new \Exception('Save Wechat Menu List Error: ['.intval($save['errcode']).'] '.$save['errmsg'], Constant::EXCEPTION_SAVE_MENU_LIST_ERROR);
            }
        }
    }

    /**
     * 获取本实例构造时的 wechat config id
     * @return [type] [description]
     */
    public function getConfigId(){
        return intval($this->_configId);
    }

    /**
     * 获取微信用户基本信息
     * @param  [type] $openid [description]
     * @return [type]         [description]
     */
    public function getWxUserBasicInfo($openid){
        $wxUserModel = M('wechat_user');
        $user = $wxUserModel->where(array('openid'=>$openid))->find();
        if(0 != count($user)){
            return $user;
        }else{
            $token = $this->getAccessToken();
            $url = $this->_wechatUrls['userinfo'];
            $url = str_replace('TOKEN', $token, $url);
            $url = str_replace('OPENID', $openid, $url);
            $user = $this->_httpGet($url);
            $user = json_decode($user, true);
            if(is_array($user) && !isset($user['errcode']) && !isset($user['errmsg'])){
                return $user;
            }else{
                $token = $this->getAccessTokenFromWechat();
                $url = $this->_wechatUrls['userinfo'];
                $url = str_replace('TOKEN', $token, $url);
                $url = str_replace('OPENID', $openid, $url);
                $user = $this->_httpGet($url);
                $user = json_decode($user, true);
                if(is_array($user) && !isset($user['errcode']) && !isset($user['errmsg'])){
                    return $user;
                }else{
                    throw new \Exception('Get Wechat User Info Error: ['.intval($user['errcode']).'] '.$user['errmsg'], Constant::EXCEPTION_GET_USER_INFO_ERROR);
                }
            }
        }
    }

    /**
     * 获取微信接口的access_token
     * @return [type] [description]
     */
    public function getAccessToken(){
        $tokenModel = M('wechat_access_token');
        $token = $tokenModel->where(array(
            'config_id' => $this->_configId,
            'expire_time'=>array('gt', time())))
            ->order('expire_time desc')
            ->find();
        if(count($token) != 0){
            return $token['token'];
        }else{
            $url = $this->_wechatUrls['token'];
            $url = str_replace('APPID', $this->_config['appid'], $url);
            $url = str_replace('APPSECRET', $this->_config['appsecret'], $url);

            $result = $this->_httpGet($url);
            $token = json_decode($result, true);
            if(is_array($token) && !isset($token['errcode']) && !isset($token['errmsg'])){
                $tokenModel->add(array(
                    'config_id' => $this->_configId,
                    'token' => $token['access_token'],
                    'expire_time' => time() + intval($token['expires_in'])
                ));
                return $token['access_token'];
            }else{
                throw new \Exception('Get Wechat Access Token Error: ['.intval($token['errcode']).'] '.$token['errmsg'], Constant::EXCEPTION_GET_ACCESS_TOKEN_ERROR);
            }
        }
    }
    /**
     * 获取微信接口的access_token
     * @return [type] [description]
     */
    public function getAccessTokenFromWechat(){
        $tokenModel = M('wechat_access_token');
        $url = $this->_wechatUrls['token'];
        $url = str_replace('APPID', $this->_config['appid'], $url);
        $url = str_replace('APPSECRET', $this->_config['appsecret'], $url);

        $result = $this->_httpGet($url);
        $token = json_decode($result, true);
        if(is_array($token) && !isset($token['errcode']) && !isset($token['errmsg'])){
            $tokenModel->add(array(
                'config_id' => $this->_configId,
                'token' => $token['access_token'],
                'expire_time' => time() + intval($token['expires_in'])
            ));
            return $token['access_token'];
        }else{
            throw new \Exception('Get Wechat Access Token Error: ['.intval($token['errcode']).'] '.$token['errmsg'], Constant::EXCEPTION_GET_ACCESS_TOKEN_ERROR);
        }
    }
    /**
     * GET 请求
     * @param string $url
     */
    private function _httpGet($url){
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
     * POST 请求
     * @param string $url
     * @param array $param
     * @return string content
     */
    private function _httpPost($url,$param){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (is_string($param)) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }

    /**
     * 微信api不支持中文转义的json结构
     * @param array $arr
     */
    private function _jsonEncode($arr) {
        $parts = array ();
        $is_list = false;
        //Find out if the given array is a numerical array
        $keys = array_keys ( $arr );
        $max_length = count ( $arr ) - 1;
        if (($keys [0] === 0) && ($keys [$max_length] === $max_length )) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for($i = 0; $i < count ( $keys ); $i ++) { //See if each key correspondes to its position
                if ($i != $keys [$i]) { //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }
        foreach ( $arr as $key => $value ) {
            if (is_array ( $value )) { //Custom handling for arrays
                if ($is_list)
                    $parts [] = self::_jsonEncode ( $value ); /* :RECURSION: */
                else
                    $parts [] = '"' . $key . '":' . self::_jsonEncode ( $value ); /* :RECURSION: */
            } else {
                $str = '';
                if (! $is_list)
                    $str = '"' . $key . '":';
                //Custom handling for multiple data types
                if (is_numeric ( $value ) && $value<2000000000)
                    $str .= $value; //Numbers
                elseif ($value === false)
                    $str .= 'false'; //The booleans
                elseif ($value === true)
                    $str .= 'true';
                else
                    $str .= '"' . addslashes ( $value ) . '"'; //All other things
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
                $parts [] = $str;
            }
        }
        $json = implode ( ',', $parts );
        if ($is_list)
            return '[' . $json . ']'; //Return numerical JSON
        return '{' . $json . '}'; //Return associative JSON
    }

    /**
     * 响应微信事件回调
     * @return [type] [description]
     */
    public function handleEvent(){
        $xmlData = (array)simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA);

        try{
            $user = $this->getWxUserBasicInfo($xmlData['FromUserName']);
        }catch(\Exception $e){
            echo $e->getMessage();
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            record_error($e);
            exit();
        }

        // 处理关注事件
        if($xmlData['MsgType'] == 'event' && $xmlData['Event'] == 'subscribe'){
            $sceneId = false;
            $ticket = false;
            if(isset($xmlData['EventKey'])){
                $scene = $xmlData['EventKey'];
                $scene = explode('_', $scene);
                $sceneId = intval($scene[1]);
            }
            if(isset($xmlData['Ticket'])){
                $ticket = $xmlData['Ticket'];
            }
            if(isset($this->_callbacks[Wechat::WX_EVENT_SUBSCRIBE])){
                foreach ($this->_callbacks[Wechat::WX_EVENT_SUBSCRIBE] as $k => $v) {
                    $obj = $v['obj'];
                    $func = $v['callback'];
                    call_user_method($func, $obj, $user, $sceneId, $ticket);
                }
            }
        }

        //处理扫码关注事件
        if($xmlData['MsgType'] == 'event' && $xmlData['Event'] == 'SCAN') {
            if(isset($this->_callbacks[Wechat::WX_EVENT_SCAN])){
                foreach ($this->_callbacks[Wechat::WX_EVENT_SCAN] as $k => $v) {
                    $obj = $v['obj'];
                    $func = $v['callback'];
                    call_user_method($func, $obj, $user,$xmlData['EventKey']);
                }
            }
        }

        // 处理取消关注事件
        if($xmlData['MsgType'] == 'event' && $xmlData['Event'] == 'unsubscribe'){
            if(isset($this->_callbacks[Wechat::WX_EVENT_UNSUBSCRIBE])){
                foreach ($this->_callbacks[Wechat::WX_EVENT_UNSUBSCRIBE] as $k => $v) {
                    $obj = $v['obj'];
                    $func = $v['callback'];
                    call_user_method($func, $obj, $user, $xmlData['Content']);
                }
            }
        }

        // 处理用户在服务号发送的文本消息
        if($xmlData['MsgType'] == 'text' && !isset($xmlData['Event'])){
            if(isset($this->_callbacks[Wechat::WX_EVENT_TEXT])){
                foreach ($this->_callbacks[Wechat::WX_EVENT_TEXT] as $k => $v) {
                    $obj = $v['obj'];
                    $func = $v['callback'];
                    call_user_method($func, $obj, $user, $xmlData['Content']);
                }
            }
        }

        // 处理菜单点击事件
        if($xmlData['MsgType'] == 'event' && $xmlData['Event'] == 'CLICK' && $xmlData['EventKey']){
            $eventKey = $xmlData['EventKey'];
            if(isset($this->_callbacks[Wechat::WX_EVENT_CLICK])){
                foreach ($this->_callbacks[Wechat::WX_EVENT_CLICK] as $k => $v) {
                    $obj = $v['obj'];
                    $func = $v['callback'];
                    call_user_method($func, $obj, $user, $eventKey);
                }
            }
        }
    }

    /**
     * XML形式回复微信
     * @param  string $openid 接收消息的微信用户
     * @param  string $msg    消息内容
     * @return XML            XML消息体
     */
    public function xmlTextResponse($openid='', $msg=''){
        $str =  '<xml>
                <ToUserName><![CDATA['.$openid.']]></ToUserName>
                <FromUserName><![CDATA['.$this->_config['public_id'].']]></FromUserName>
                <CreateTime>'.time().'</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA['.$msg.']]></Content>
            </xml>';
        echo $str;
        Log::write($str);
        exit();
    }

    /*
     *点击菜单跳转链接时的事件推送
     */
    public function xmlSendMesage($openid="",$url='',$menuid='')
    {
        $str = '<xml>
                <ToUserName><![CDATA['.$this->_config['public_id'].']]></ToUserName>
                <FromUserName><![CDATA['.$openid.']]></FromUserName>
                <CreateTime>'.time().'</CreateTime>
                <MsgType><![CDATA[event]]></MsgType>
                <Event><![CDATA[VIEW]]></Event>
                <EventKey><![CDATA['.$url.']]></EventKey>
                <MenuId>'.$menuid.'</MenuId>
                </xml>';
        echo $str;
        Log::write($str);
        exit();
    }

    /**
     * 设置微信事件回调时的回调函数
     * @param [type] $event    [description]
     * @param [type] $obj      [description]
     * @param [type] $callback [description]
     */
    public function setEventCallback($event, $obj, $callback){
        if(method_exists($obj, $callback)){
            $this->_callbacks[$event][] = array('obj'=>$obj, 'callback'=>$callback);
        }else{
            throw new \Exception('Event Callback Function Not Exist.', Constant::EXCEPTION_EVENT_CALLBACK_ERROR);
        }
    }

    /**
     * 开启微信服务号，服务器配置（开发者模式）
     * @return [type] [description]
     */
    public function openWechatServerMode(){
        $echoStr = isset($_GET["echostr"]) ? $_GET["echostr"]: '';
        if($echoStr){
            if ($this->_checkSignature($this->_config['token'])){
                die($echoStr);
            }else{
                die('no access');
            }
        }
        die('no access');
    }

    /**
     * 微信验证
     * For weixin server validation
     */
    private function _checkSignature($token){
        $signature = isset($_GET["signature"])?$_GET["signature"]:'';
        $timestamp = isset($_GET["timestamp"])?$_GET["timestamp"]:'';
        $nonce = isset($_GET["nonce"])?$_GET["nonce"]:'';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
           echo $_GET['echostr'];
        }else{
            return false;
        }
    }
}
