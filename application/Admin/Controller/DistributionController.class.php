<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Grade\Controller\WechatController;

class DistributionController extends AdminbaseController{
	protected $_distributionModel;
	protected $_weixin;

	public function _initialize() {
		parent::_initialize();
		$this->_distributionModel = D("Distribution");
		$this->_weixin = new WechatController();
	}
	/**
	 * [C_getDistributionList 获取所有分销列表]
	 */
	public function C_getDistributionList(){
		$configId = 0;
		$keyword = '';
		$level = 0;
		if($this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}else{
			$configId = intval($_SESSION['CONFIG_ID']);
		}
		if(IS_POST){
			if($this->_isAdmin){
				if(intval(I('post.config')) != -1) $configId = intval(I('post.config'));
			}
			$keyword = trim(I('post.keyword'));
			$level = intval(I('post.level'));
		}
		$data = $this->_distributionModel->getDistributionList($keyword, $level, $configId);
		if($this->_isAdmin){
			foreach ($data['data'] as $key => &$value) {
				$value['wechat_name'] = $wechatConfigModel -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
			}
			unset($value);
		}
		$this->assign('data', $data);
		$this->assign('where', array('config_id'=>$configId, 'keyword'=>$keyword, 'level'=>$level));
		$this->display();
	}
	/**
	 * [C_getDistributionList 获取用户分销列表]
	 */
	public function C_getUserDistributionList(){
		$keyword = '';
		$level = 0;
		$openid = '';
		if(IS_POST){
			$keyword = trim(I('post.keyword'));
			$level = intval(I('post.level'));
			$openid = trim(I('post.openid'));
		}
		if(IS_GET){
			$openid = trim(I('get.openid'));
			if(strlen($openid) <=0) $this->error('访问错误');
		}
		$data = $this->_distributionModel->getUserDistributionList($keyword, $level, $openid);
		if($this->_isAdmin){
			foreach ($data['data'] as $key => &$value) {
				$value['wechat_name'] = M('wechat_config') -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
			}
			unset($value);
		}
		$this->assign('data', $data);
		$this->assign('where', array('keyword'=>$keyword, 'level'=>$level, 'openid'=>$openid));
		$this->display();
	}

	//添加分销规则
	public function C_addDistribution(){
		
		if($_SESSION['CONFIG_ID'] != 0) $where['config_id'] = $_SESSION['CONFIG_ID'];
		$arr = M('distribution') -> where('id=1') -> find();
		$this -> assign('val', $arr);
		$this -> assign('config_id', $_SESSION['CONFIG_ID']);
		$this -> display();
	}

	public function C_DisributionDetail()
    {
       $detail =  M('distribution_detail')->alias('dd')->join(['__GRADE_DETAIL__ g on dd.grade_id=g.grade_id','__B2C_ORDER__ bo on dd.order_id=bo.id'])->field('dd.*,bo.order_sn,g.grade_name')->order('dd.create_time DESC')->select();
       $this->assign('detail',$detail);
       $this -> display();
    }

    public function DistributionList()
    {
        $list = M('wechat_distribution')->alias('wd')->join('__WECHAT_USER__ wu on wd.wechat_id=wu.id')->field('wd.id,wd.recharge,wu.nickname,wd.create_time')->select();
        $this->assign('list',$list);
        $this -> display();
    }





    public function do_update_disribution()
    {
            $args = I('post.');
            foreach ($args as $val)
            {
                if(!$val)
                {
                    $this->error('不能为空');
                }
            }

           $res =  M('distribution')->where('id=1')->save($args);
            if($res)
            {
                $this->success('修改成功');
            }
    }

	//添加
	public function C_editDistributionPost(){
		$number = I('post.number', '', 'trim');
		$configId = I('post.config_id', 0, 'intval');
		$arr = M('distribution')->where('config_id='.$configId)->find();
		if(0 == count($arr)){
			$data['config_id'] = $configId;
			$data['number'] = $number;
			$str = M('distribution')->add($data);
		}else{
			$str = M('distribution')->where('config_id='.$configId)->save(array('number'=>$number));
		}

		if($str === false){
			$this->error('系统错误，更新分销信息信息失败！');
		}else{
			$this->success('更新分销信息成功！');
		}
	}
	// 代理商提现管理
	public function withdrawal()
    {
        $draw = M('grade_draw_reply')->alias('gd')->join('__GRADE_DETAIL__ g on gd.grade_id=g.grade_id')->field('gd.id,gd.money,gd.status,gd.grade_id,gd.create_time,g.grade_name')->select();
        foreach ($draw as $k => $val)
        {
        	if($val['status']==0){
 				$draw[$k]['draw_status'] = '未审核';
        	}else if($val['status']==1){
        		$draw[$k]['draw_status'] = '已审核';
        	}else{
        		$draw[$k]['draw_status'] = '审核未通过';
        	}
        }
        $this->assign('draw',$draw);
        $this->display();
    }
    // 提现
    public function dodrawal()
    {
        $drawal_id = I('get.id')?36:36;
        $drawal_detail =  M('grade_draw_reply')->where('id='.$drawal_id)->find();
        $grade =  M('grade')->alias('g')->join('__WECHAT_USER__ wu on g.wechat_id = wu.id')->where('g.id='.$drawal_detail['grade_id'])->field('wu.openid')->find();
        $res = $this->_weixin ->sendMoney($grade['openid'],$drawal_detail['money']);
        print_r($res);die;
    }
    /**
     * 代理商审核提现，只有审核没有提现
     */
    public function docheck()
    {
    	$grade = M('grade');
    	$reply = M('grade_draw_reply');
        // $drawal_id = I('post.id');
        $drawal_id = I('get.id');
        
        $data =  $reply->where('id='.$drawal_id)->field('grade_id,money')->find();
        $arr =  $grade->where('id='.$data['grade_id'])->field('balance')->find();

        if($arr['balance']<$data['money']){
        	$ret = $reply->where('id='.$drawal_id)->save(['status'=>2]);
        	$this->error('审核未通过，超出提现额度',U("distribution/wechatdrawal"));
        }else if($arr['balance']==0){
        	$this->error('审核未通过，无提现额度');
        }else{
        	$balance = $arr['balance'] - $data['money'];

	        $res  = $grade->where('id='.$data['grade_id'])->save(['balance'=>$balance]);

	        if($res){
	        	$this->success('审核成功', U("distribution/withdrawal"));
	        }else{
	        	$this->error('审核失败');
	        }
        }
    }
    // 粉丝提现管理
	public function wechatdrawal()
    {
    	$where = array();
		if(!$this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}else{
			$where['config_id'] = intval($_SESSION['CONFIG_ID']);
			$where['config_id'] = intval(7);
		}
		$userModel = M('wechat_user');
		$reModel   = M('wechat_distribution_reply');

		$userdata = $reModel->alias('re')
					->join(' yds_wechat_user as u ON u.id=re.wechat_id ')
					->where($where)
					->field('re.id,nickname,headimgurl,re.money,re.create_time,re.status')
					->select();

		foreach ($userdata as $k => $v) {
			if($v['status']==0){
 				$userdata[$k]['draw_status'] = '未审核';
        	}else if($v['status']==1){
        		$userdata[$k]['draw_status'] = '已审核';
        	}else{
        		$userdata[$k]['draw_status'] = '审核未通过';
        	}
		}

        $this->assign('draw',$userdata);
        $this->display();
    }
    /**
     * 粉丝审核提现，只有审核没有提现
     */
    public function wechat_docheck()
    {
    	$userModel = M('wechat_user');
    	$disModel  = M('wechat_distribution');
		$reModel   = M('wechat_distribution_reply');
        $re_id     = I('get.id');

        // 查询提现记录
        $reall   = $reModel->where(['id'=>$re_id])->select();
        // 分享奖励总额
        $disdata = $disModel->where(['wechat_id'=>$reall[0]['wechat_id']])->sum('recharge');
        // 提现成功总额
		$redata  = $reModel->where(['wechat_id'=>$reall[0]['wechat_id'],'status'=>1])->sum('money');
		// 判断提现金额
		if($reall[0]['money']==0){
			$this->error('审核未通过，无提现额度');
		}
		if(($disdata-$redata)<0){
			$reModel->where(['id'=>$re_id])->save(['status'=>2]);
			$this->error('审核未通过，超出提现额度',U("distribution/wechatdrawal"));
		}else{
			$res  = $reModel->where(['id'=>$re_id])->save(['status'=>1]);
	        if($res){
	        	$this->success('审核成功',U("distribution/wechatdrawal"));
	        }else{
	        	$this->error('审核失败');
	        }
		}
	
    }
}