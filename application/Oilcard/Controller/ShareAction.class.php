<?php
namespace Comment\Controller;
use Think\Controller;

class ShareController extends Controller
{	
	private $appId		=	'wxfa5488d89e33b4e9';
	private $appSecret	=	'e724a62e625980998b914caa34bb782c';
	
	public function _initialize(){
        parent::_initialize();
	}

	
	//访问加载js-sdk配置参数
	public function Share(){
		$access_token	=	$this->getAccessToken();
		
		$jsapi_ticket	=	$this->getJsApiTicket($access_token);
		$noncestr 		=	$this->createNonceStr();
		$timestamp		=	time();
		var_dump($_GET);
		$url = $_GET['shareUrl'];

		$post_id=$_GET['post_id']?$_GET['post_id']:'';
		if (empty($post_id)) {
			$post=$this->PublicNumberSharing(6,$url);
		}else{
			$post=$this->PublicNumberSharing($post_id,$url);
		}
   	    
		$string 		= 	"jsapi_ticket=".$jsapi_ticket."&noncestr=".$noncestr."&timestamp=".$timestamp."&url=".$url;
    	$signature = sha1($string);

    	$array	 = array(
				      "appId"     => $this->appId,
				      "nonceStr"  => $noncestr,
				      "timestamp" => $timestamp,
				      "signature" => $signature,
				      "rawString" => $string,
				      "post" => $post,
				    );
    	echo json_encode($array);

	}

	 function PublicNumberSharing($post_id='',$url){
        
        //根据帖子id查询帖子数据
        if (empty($post_id)) {
            $post_data=D('Book_post')->where('id=6')->find();
            $list=D('Book_post_content')->where('status=0 and post_id=6')->order('floor_id')->find();
        }else{
            $post_data=D('Book_post')->where('id='.$post_id)->find();
            $list=D('Book_post_content')->where('status=0 and post_id='.$post_id)->order('floor_id')->find();
        }

        //拼接落地页地址
        $post_data['link']="http://card.gungunbook.com/index.php/Share/Transfer?url=".$url."&post_id=".$post_id;

        $post_data['content']=mb_substr($list['content'], 0,30).'······';
       
        //返回title等数据
        return $post_data;
    }  
    public function Transfer(){
        $post_id=$_GET['post_id']?$_GET['post_id']:'';

        $url=$_GET['url']?$_GET['url']:"http://card.gungunbook.com/";
        // echo $url;exit;
        if (empty($post_id)) {
            $this->assign('url',$url);
        }else{
            $data=$this->PublicNumberSharing($post_id);
            #判断是否有指定跳转路径
            if (empty($data['jump_url'])) {

                $this->assign('url',$url);
            }else{
                $this->assign('url',$data['jump_url']);
            }
        }
        $this->display();

    }
	//获取access_token
	public function getAccessToken(){
		$access_token = S('access_token');
		if (empty($access_token)) {
			$token_url 		= 	"https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;
			$token_result 	= 	json_decode($this->httpGet($token_url));
			$access_token 	= 	$token_result->access_token;
			S('access_token',$access_token,7100);

		}
      	return $access_token;
	}

	//获得jsapi_ticket
	public function getJsApiTicket($access_token){
		$jsapi_ticket=S('jsapi_ticket');
		if (empty($jsapi_ticket)) {
		$ticket_url		=	"https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=".$access_token;

		$ticket_result = json_decode($this->httpGet($ticket_url));
      	$jsapi_ticket = $ticket_result->ticket;	
      	S('jsapi_ticket',$jsapi_ticket,7100);
		}
		

      	return $jsapi_ticket;
	}

	//获取随机字符串
	private function createNonceStr($length = 16) {
	    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	    $str = "";
	    for ($i = 0; $i < $length; $i++) {
	      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	    }
	    return $str;
  	}

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
}
	
?>