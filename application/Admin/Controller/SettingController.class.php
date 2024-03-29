<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class SettingController extends AdminbaseController{
	
	protected $options_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->options_model = D("Common/Options");
	}	

	/**
	 * 年费设置-暂只读
	 * @创建时间 2018-12-23T15:35:41+0800
	 */
	public function AnnualFee(){
		$list = M('packages')->select();
		$this->assign('packages',$list);
		$this->display();
	}

	/**
	 * 分润设置
	 * @创建时间 2018-12-23T15:35:18+0800
	 */
	public function ProfitSetting(){
		$scroll = I('post.scroll',30);
		$postage = I('post.postage',10);
		$user_profit = I('post.user_profit',2.5);
		$vip_profit = I('post.vip_profit',1);
		$user_deposit = I('post.user_deposit',25);
		$agent_deposit = I('post.agent_deposit',30);
		$data = [
			'scroll' => $scroll,
			'postage' => $postage,
			'user_profit' => $user_profit,
			'vip_profit' => $vip_profit,
			'user_deposit' => $user_deposit,
			'agent_deposit' => $agent_deposit
		];
		$result=M('setting')->where('id=1')->save($data);
		exit(json_encode($result));
	}
	
	// 网站信息
	public function site(){
	    C(S('sp_dynamic_config'));//加载动态配置
		$option=$this->options_model->where("option_name='site_options'")->find();
		$cmf_settings=$this->options_model->where("option_name='cmf_settings'")->getField("option_value");
		$tpls=sp_scan_dir(C("SP_TMPL_PATH")."*",GLOB_ONLYDIR);
		$noneed=array(".","..",".svn");
		$tpls=array_diff($tpls, $noneed);
		$this->assign("templates",$tpls);
		
		$adminstyles=sp_scan_dir("public/simpleboot/themes/*",GLOB_ONLYDIR);
		$adminstyles=array_diff($adminstyles, $noneed);
		$this->assign("adminstyles",$adminstyles);
		if($option){
			$this->assign(json_decode($option['option_value'],true));
			$this->assign("option_id",$option['option_id']);
		}
		
		$cdn_settings=sp_get_option('cdn_settings');
		$profit = M('setting')->find();
		$this->assign($profit);
		$this->assign("cdn_settings",$cdn_settings);
		
		$this->assign("cmf_settings",json_decode($cmf_settings,true));
		
		$this->display();
	}
	
	// 网站信息设置提交
	public function site_post(){
		if (IS_POST) {
			if(isset($_POST['option_id'])){
				$data['option_id']=I('post.option_id',0,'intval');
			}
			$options=I('post.options/a');
			
			$configs["SP_SITE_ADMIN_URL_PASSWORD"]=empty($options['site_admin_url_password'])?"":md5(md5(C("AUTHCODE").$options['site_admin_url_password']));
			$configs["SP_DEFAULT_THEME"]=$options['site_tpl'];
			$configs["DEFAULT_THEME"]=$options['site_tpl'];
			$configs["SP_ADMIN_STYLE"]=$options['site_adminstyle'];
			$configs["URL_MODEL"]=$options['urlmode'];
			$configs["URL_HTML_SUFFIX"]=$options['html_suffix'];
			$configs["COMMENT_NEED_CHECK"]=empty($options['comment_need_check'])?0:1;
			$comment_time_interval=intval($options['comment_time_interval']);
			$configs["COMMENT_TIME_INTERVAL"]=$comment_time_interval;
			$_POST['options']['comment_time_interval']=$comment_time_interval;
			$configs["MOBILE_TPL_ENABLED"]=empty($options['mobile_tpl_enabled'])?0:1;
			$configs["HTML_CACHE_ON"]=empty($options['html_cache_on'])?false:true;
				
			sp_set_dynamic_config($configs);//sae use same function
				
			$data['option_name']="site_options";
			$data['option_value']=json_encode($options);
			if($this->options_model->where("option_name='site_options'")->find()){
				$result=$this->options_model->where("option_name='site_options'")->save($data);
			}else{
				$result=$this->options_model->add($data);
			}
			
			$cmf_settings=I('post.cmf_settings/a');
			$banned_usernames=preg_replace("/[^0-9A-Za-z_\x{4e00}-\x{9fa5}-]/u", ",", $cmf_settings['banned_usernames']);
			$cmf_settings['banned_usernames']=$banned_usernames;

			sp_set_cmf_setting($cmf_settings);
			
			$cdn_settings=I('post.cdn_settings/a');
			sp_set_option('cdn_settings', $cdn_settings);
			
			if ($result!==false) {
				$this->success("保存成功！");
			} else {
				$this->error("保存失败！");
			}
			
		}
	}
	//支付设置
    public function pay(){
	    if($_POST){
            $payway = I('post.payway');
            $paytype = I('post.paytype');
            if($payway == 1){
                $res = M('setting')->where(['id'=>1])->setField('paytype',$paytype);
            }elseif($payway == 2){
                $res = M('three_setting')->where(['id'=>1])->setField('paytype',$paytype);
            }
            if($res){
                echo json_encode(['status'=>200,'msg'=>'修改成功']);
            }else{
                echo json_encode(['status'=>100,'msg'=>'修改失败']);
            }
        }else{
	        $data['wxxiao'] = M('setting')->where(['id'=>1])->getField('paytype');
	        $data['wxgong'] = M('three_setting')->where(['id'=>1])->getField('paytype');
            echo json_encode(['status'=>200,'msg'=>'获取成功','data'=>$data]);
        }

    }


	
	// 密码修改
	public function password(){
		$this->display();
	}
	
	// 密码修改提交
	public function password_post(){
		if (IS_POST) {
			if(empty($_POST['old_password'])){
				$this->error("原始密码不能为空！");
			}
			if(empty($_POST['password'])){
				$this->error("新密码不能为空！");
			}
			$user_obj = D("Common/admin_user");
			$uid=sp_get_current_admin_id();
			$admin=$user_obj->where(array("id"=>$uid))->find();
			
			$old_password=I('post.old_password');
			$password=I('post.password');
			if(sp_compare_password($old_password,$admin['password'])){
				if($password==I('post.repassword')){
					if(sp_compare_password($password,$admin['password'])){
						$this->error("新密码不能和原始密码相同！");
					}else{
						$data['password']=sp_password($password);
						$data['id']=$uid;
						$r=$user_obj->save($data);
						if ($r!==false) {
							$this->success("修改成功！");
						} else {
							$this->error("修改失败！");
						}
					}
				}else{
					$this->error("密码输入不一致！");
				}
	
			}else{
				$this->error("原始密码不正确！");
			}
		}
	}
	
	// 上传限制设置界面
	public function upload(){
	    $upload_setting=sp_get_upload_setting();
	    $this->assign($upload_setting);
	    $this->display();
	}
	
	// 上传限制设置界面提交
	public function upload_post(){
	    if(IS_POST){
	        $result=sp_set_option('upload_setting',I('post.'));
	        if($result!==false){
	            $this->success('保存成功！');
	        }else{
	            $this->error('保存失败！');
	        }
	    }
	    
	}
	
	// 清除缓存
	public function clearcache(){
		sp_clear_cache();
		$this->display();
	}
	
	
}