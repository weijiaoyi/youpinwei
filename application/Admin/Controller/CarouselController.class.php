<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class CarouselController extends AdminbaseController{

	public function C_carouselList(){
		
		if($this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}
		if(IS_POST){
			$where = '1=1';
			if(intval(I('post.status')) != -1){
				$where .= ' and status='.intval(I('post.status'));
			}else{
				$where .= ' and status<>2';
			}
			if(intval(I('post.type')) != -1){
				$where .= ' and type='.intval(I('post.type'));
			}else{
				$where .= ' and type=0';
			}
			
			$keyword = trim(I('post.keyword'));
			if(0 != strlen($keyword)) $where .= ' and (name like "%'.$keyword.'%")';
			if($_SESSION['CONFIG_ID'] != 0){
 				$where .= ' and config_id = '.intval($_SESSION['CONFIG_ID']);
			}else{
				if(intval(I('post.config')) != -1) $where .= ' and config_id = '.intval(I('post.config'));
			}
			$this->assign('where', $_POST);
 		}else{
 			$where = 'status <> 2';
 			if($_SESSION['CONFIG_ID'] != 0){
 				$where .= ' and config_id = '.intval($_SESSION['CONFIG_ID']);
 			}
 		}
 		$carouselModel = M('carousel');
		$count = $carouselModel
			->where($where)
			->count();

		$page = $this->page($count, $this->perpage);
		$data['show'] = $page->show('Admin');
		$data['data'] = $carouselModel
			->where($where)
			->limit($page->firstRow, $page->listRows)
			->order('list_order')
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

	public function C_addCarousel(){
		$this->display();
	}

	public function C_addCarouselPost(){
		$name = I('post.name', '', 'trim');
		if(0 == strlen($name)){
			$this->error('轮播图名称不能为空！');
		}
		$type = I('post.type', 0, 'intval');
		if(0 == strlen($type)){
			$this->error('轮播图使用场景错误！');
		}
		$url = I('post.url', '', 'trim');
		if(0 != strlen($url)){
			if(1 > intval(preg_match('/http:\/\/standard\.edshui\.com\/[a-zA-Z0-9\.]+/', $url))){
				$this->error('跳转地址错误！');
			}
		}
		$imgPath = trim($_POST['smeta']['thumb']);
		if(0 == strlen($imgPath)){
			$this->error('轮播图图片不能为空！');
		}else{
			$imgPath = SITE_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.$imgPath;
			$imgPath = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $imgPath);
			if(!@file_exists($imgPath)){
				$this->error('轮播图图片上传失败！');
			}
		}

		$carouselData = array(
			'config_id' => $_SESSION['CONFIG_ID'],
			'type' => $type,
			'name' => $name,
			'img' => DIRECTORY_SEPARATOR.str_replace(SITE_PATH, '', $imgPath),
			'url' => $url,
			'create_time' => time(),
			'status' => 0,
		);

		$carouselId = M('carousel')->add($carouselData);
		if(1 > $carouselId){
			$this->error('系统错误，新建轮播图失败！');
		}else{
			$this->success('新建轮播图成功！');
		}
	}

	public function C_editCarousel(){
		$id = I('get.cid', 0, 'intval');
		if($id <= 0) $this->error('访问错误');
		$where = array();
		if($_SESSION['CONFIG_ID'] != 0) $where = array('config_id' => $_SESSION['CONFIG_ID']);
		$carousel = M('carousel') -> where($where) -> find($id);
		if(empty($carousel)) $this->error('访问的轮播图不存在');

		$img =  explode('upload/', $carousel['img'])[1];
		$this -> assign('img',$img);
		$this -> assign('carousel', $carousel);
		$this -> display();
	}
	public function C_editCarouselPost(){
		$name = I('post.name', '', 'trim');
		if(0 == strlen($name)){
			$this->error('轮播图名称不能为空！');
		}
		$type = I('post.type', 0, 'intval');
		if(0 == strlen($type)){
			$this->error('轮播图使用场景错误！');
		}
		$url = I('post.url', '', 'trim');
		if(0 != strlen($url)){
			if(1 > intval(preg_match('/http:\/\/standard\.edshui\.com\/[a-zA-Z0-9\.]+/', $url))){
				$this->error('跳转地址错误！');
			}
		}
		$imgPath = trim($_POST['smeta']['thumb']);
		if(0 == strlen($imgPath)){
			$this->error('轮播图图片不能为空！');
		}else{
			$imgPath = SITE_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.$imgPath;
			$imgPath = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $imgPath);
			if(!@file_exists($imgPath)){
				$this->error('轮播图图片上传失败！');
			}
		}

		$configId = I('post.config_id', 0, 'intval');
		$carouselId = I('post.id', 0, 'intval');
		$carouselData = array(
			'id' => $carouselId,
			'config_id' => $configId,
			'type' => $type,
			'name' => $name,
			'img' => DIRECTORY_SEPARATOR.str_replace(SITE_PATH, '', $imgPath),
			'url' => $url,
			'create_time' => time(),
			'status' => 0,
		);

		
		if(M('carousel')->save($carouselData) === false){
			$this->error('系统错误，更新轮播图失败！');
		}else{
			$this->success('更新轮播图成功！');
		}
	}

	public function C_saveCarouselListOrder(){
		$carouselModel = M('carousel');
		$transModel = M();
		$transModel->startTrans();
		foreach ($_POST['listorders'] as $carouselId => $order) {
			$where = array(
				'config_id' => $_SESSION['CONFIG_ID'],
				'id' => intval($carouselId),
			);
			$data = array('list_order' => intval($order));
			try{
				$saveCarouselOrder = $carouselModel->where($where)->save($data);
			}catch(\Exception $e){
				$this->error("排序更新失败！");
			}
			if(false === $saveCarouselOrder){
				$transModel->rollback();
				$this->error("排序更新失败！");
			}
		}
		$transModel->commit();
		$this->success("排序更新成功 ！");
	}

	/**
	 * 禁用轮播图
	 * @return string json
	 */	
	public function C_closeCarousel(){
		$carouselModel = M('carousel');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$saveCarouse = $carouselModel->where($where)->save(array('status'=>1));
		if(false !== $saveCarouse){
			$this->success('禁用轮播图成功！');
		}else{
			$this->error('禁用轮播图失败！');
		}
	}

	/**
	 * 启用轮播图
	 * @return string json
	 */	
	public function C_openCarousel(){
		$carouselModel = M('carousel');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$saveCarouse = $carouselModel->where($where)->save(array('status'=>0));
		if(false !== $saveCarouse){
			$this->success('启用轮播图成功！');
		}else{
			$this->error('启用轮播图失败！');
		}
	}

	/**
	 * 删除轮播图
	 * @return string json
	 */	
	public function C_deleteCarousel(){
		$carouselModel = M('carousel');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$saveCarouse = $carouselModel->where($where)->save(array('status'=>2));
		if(false !== $saveCarouse){
			$this->success('删除轮播图成功！');
		}else{
			$this->error('删除轮播图失败！');
		}
	}
}