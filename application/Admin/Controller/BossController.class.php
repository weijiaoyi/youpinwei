<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class BossController extends AdminbaseController{

	public function C_bossList(){
		if($this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}
		if(IS_POST){
			$where = '1=1 ';
			
			if(intval(I('post.status')) != -1){
				$where .= ' and status='.intval(I('post.status'));
			}else{
				$where .= ' and status <> 2 ';
			}

			$keyword = trim(I('post.keyword'));

			if(0 != strlen($keyword)) $where .= ' and (name like "%'.$keyword.'%" or tel like "%'.$keyword.'%")';
			if(!$this->_isAdmin){
 				$where .= ' and config_id = '.intval($_SESSION['CONFIG_ID']);
			}else{
				if(intval(I('post.config')) != -1) $where .= ' and config_id = '.intval(I('post.config'));
			}
			$this -> assign('where', $_POST);
 		}else{
            if($_SESSION['CONFIG_ID'] != 0){
            	$where  = ' status <> 2 ';
                $where .= ' and config_id = '.intval($_SESSION['CONFIG_ID']);
            }
        }

 		$deliverModel = M('boss');
		$count = $deliverModel
			->where($where)
			->count();

		$page = $this->page($count, $this->perpage);
		$data['show'] = $page->show('Admin');
		$data['data'] = $deliverModel
			->where($where)
			->limit($page->firstRow, $page->listRows)
			->order('id desc')
			->select();
		// 加入微信服务号名称
		if($this->_isAdmin){
			foreach ($data['data'] as $key => &$value) {
				$value['wechat_name'] = M('wechat_config') -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
			}
			unset($value);
		}
		$this->assign('data', $data);
		$this->display();
	}
	public function C_editBoss(){
		$id = I('get.did', 0, 'intval');
		if($id <= 0) $this->error('访问错误');
		$where = array('id' => $id, 'status' => array('lt', '2'));
		if($_SESSION['CONFIG_ID'] != 0) $where['config_id'] = $_SESSION['CONFIG_ID'];
		$deliver = M('boss') -> where($where) -> find();
		if(empty($deliver)) $this->error('访问的老板不存在');

		$this -> assign('deliver', $deliver);
		$this -> display();
	}
	public function C_editBossPost(){
		$name = I('post.name', '', 'trim');
		if(0 == strlen($name)){
			$this->error('老板姓名不能为空！');
		}
		$tel = I('post.tel', 0);
		if(empty($tel)) $this->error('老板电话不能为空！');
		if(!preg_match("/^1[34578]\d{9}$/", strval($tel))) $this->error('老板电话格式不正确！');		
		$configId = I('post.config_id', 0, 'intval');
		$deliverId = I('post.id', 0, 'intval');
		$deliverData = array(
			'id' => $deliverId,
			'config_id' => $configId,
			'name' => $name,
			'tel' => $tel,
		);

		
		if(M('boss')->save($deliverData) === false){
			$this->error('系统错误，更新老板信息失败！');
		}else{
			$this->success('更新老板信息成功！');
		}
	}

	/**
	 * 禁用老板
	 * @return string json
	 */	
	public function C_closeBoss(){
		$deliverModel = M('boss');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$wheres=array('openid'=>I('get.openid'), 'config_id'=>$_SESSION['CONFIG_ID']);
		$saveDeliver = $deliverModel->where($where)->save(array('status'=>0));
		$user=M('wechat_user')->where($wheres)->save(array('is_boss'=>0));
		if(false !== $saveDeliver && false !== $user){
			$this->success('禁用老板成功！');
		}else{
			$this->error('禁用老板失败！');
		}
	}

	/**
	 * 启用老板
	 * @return string json
	 */	
	public function C_openBoss(){
		$deliverModel = M('boss');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$saveDeliver = $deliverModel->where($where)->save(array('status'=>1));
		 $wheres=array('openid'=>I('get.openid'), 'config_id'=>$_SESSION['CONFIG_ID']);
		$user=M('wechat_user')->where($wheres)->save(array('is_boss'=>1));
		if(false !== $saveDeliver && false !== $user){
			$this->success('启用老板成功！');
		}else{
			$this->error('启用老板失败！');
		}
	}

	/**
	 * 删除老板
	 * @return string json
	 */	
	public function C_deleteBoss(){

		$deliverModel = M('boss');
		$userModel = M('wechat_user');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		// $wheres=array('openid'=>trim(I('post.openid')));
		$user=$userModel->where(['openid'=>I('get.openid')])->save(array('is_boss'=>0));
		// 软删除，有影响，所以改为硬删
		// $saveDeliver = $deliverModel->where($where)->save(array('status'=>2));
		$saveDeliver = $deliverModel->where($where)->delete();

		if(false !== $saveDeliver && false !== $user){
			$this->success('删除老板成功！');
		}else{
			$this->error('删除老板失败！');
		}
	}
}