<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Lib\Wechat;
use Think\Log;
use Common\Lib\Constant;
class WechatController extends AdminbaseController{

	public function C_menuList(){
		$_SESSION['CONFIG_ID'] = 0;
		$where = array();
		if(intval($_SESSION['CONFIG_ID']) != 0){
			$where['id'] = intval($_SESSION['CONFIG_ID']);
		}
		$services = M('wechat_config')->where($where)->select();
		$this->assign('services', $services);
		$this->display();
	}

	public function getWechatMenu(){
		$configId = intval(I('get.config_id'));
		$wechat = M('wechat_config')->find(intval($configId));
		if(0 == count($wechat)){
			$rtn = array(
				'info' => '获取服务号菜单信息失败！',
				'status' => 0,
			);
		}else{
			$wechat = new Wechat(intval($configId));
			try{
				$menuList = $wechat->getWechatMenuList();
				$rtn = array(
					'info' => '获取服务号菜单信息成功！',
					'data' => $menuList,
					'status' => 1,
					'config_id' => $configId,
				);
			}catch(\Exception $e){
				Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
				$rtn = array(
					'info' => $e->getMessage(),
					'status' => 0,
				);
			}

		}
		$this->ajaxReturn($rtn);
	}

	public function saveWechatMenu(){
		$configId = intval(I('post.config_id'));
		if(1 > $configId){
			$rtn = array(
				'info' => '保存微信服务号菜单失败！',
				'status' => 0,
			);
		}else{
			$menu = $_POST['data'];
			foreach ($menu['button'] as $k => $v) {
				if(0 == count($v)){
					unset($menu['button'][$k]);
				}else{
					foreach ($v['sub_button'] as $kk => $vv) {
						if(0 == count($vv)){
							unset($menu['button'][$k]['sub_button'][$kk]);
						}
					}
				}
			}
			$menu['button'] = array_values($menu['button']);
			$wechat = new Wechat(intval($configId));
			try{
				$menuList = $wechat->saveWechatMenu($menu);
				$rtn = array(
					'info' => '保存服务号菜单信息成功！',
					'status' => 1,
					'config_id' => $configId,
				);
			}catch(\Exception $e){
				Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
				$rtn = array(
					'info' => $e->getMessage(),
					'status' => 0,
					'menu' => $menu
				);
			}
		}
		$this->ajaxReturn($rtn);
	}
	// http://standard.edshui.com/index.php?g=Api&m=Auth&a=getAuth
	public function C_funsList(){
		$where = array();
		if($this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}else{
			$where['config_id'] = intval($_SESSION['CONFIG_ID']);
		}
		$userModel = M('wechat_user');
		if(IS_POST){
			if($this->_isAdmin){
				if(intval(I('post.config')) != -1) $where['config_id'] = intval(I('post.config'));
			}
			if(intval(I('post.subscribe')) != -1) $where['subscribe'] = intval(I('post.subscribe'));
			$keyword = trim(I('post.keyword'));
			// if(!empty($keyword)) $where["from_base64(nickname)"] = array('like', '%'.$keyword.'%');
            if(!empty($keyword)) $where["nickname"] = array('like', '%'.base64_encode($keyword).'%');
        }

		$count = $userModel 
			-> where($where)
			->count();
        $page = $this->page($count, $this->perpage);
		$data['data'] = $userModel
			// -> field('*,from_base64(nickname) as nickname')
            -> field('*,nickname')
            -> where($where)
			-> order('id desc')
			->limit($page->firstRow, $page->listRows)
			-> select();
		// 加入微信服务号名称
		foreach ($data['data'] as $key => &$value) {
			$value['nickname'] = base64_decode($value['nickname']);
			if($this->_isAdmin){
				$value['wechat_name'] = $wechatConfigModel -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
			}
			$address = M('wechat_user_address') -> where(array('config_id' => $value['config_id'], 'openid' => $value['openid'], 'status' => Constant::USER_ADDRESS_STATUS_DEFAULT)) -> find();

			$userAddress = !empty($address) ? $address['pcd'].$address['detail'] : '';
			$value['default_address'] = $userAddress;
		}
		unset($value);
		$data['show'] = $page->show('Admin');
		$this->assign('data', $data);
		$this->assign('where', $where);
		$this->display();
	}

	public function C_funsAddressList(){
		$where = array(' a.status'=>array('neq',2));
		$addressModel = M('wechat_user_address');
		if($this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}else{
			$where['a.config_id'] = $_SESSION['CONFIG_ID'];
		}
		if(IS_POST){
			$status = intval(I('post.status'));
			if($status != -1){
				$where['a.status'] = $status;
			}
			if($this->_isAdmin){
				if(intval(I('post.config')) != -1) $where['a.config_id'] = intval(I('post.config'));
			}
			$openid = trim(I('post.openid'));
			if(0 != strlen($openid)) $where['a.openid'] = $openid;
			$keyword = trim(I('post.keyword'));
			if(!empty($keyword)){
				$map['a.tel'] = array('like', '%'.$keyword.'%');
				$map['a.name'] = array('like', '%'.$keyword.'%');
//				$map["from_base64(u.nickname)"] = array('like', '%'.$keyword.'%');
                $map["u.nickname"] = array('like', '%'.$keyword.'%');
				$map['_logic'] = 'or';
				$where['_complex'] = $map;
			}

		}
		if(IS_GET){
			$openid = trim(I('get.openid'));
			if(0 != strlen($openid)) $where['a.openid'] = array('like', "%".$openid."%");
		}
		$count = $addressModel -> where($where)
			->alias('a')
			->join("left join ".C('DB_PREFIX').'wechat_user as u on a.openid = u.openid', 'left')
			->count();
		$page = $this->page($count, $this->perpage);
		$data['data'] = $addressModel -> where($where)
		    ->alias('a')
			->join(C('DB_PREFIX').'wechat_user u on a.openid = u.openid', 'left')
//			->field('a.*,from_base64(u.nickname) nickname, headimgurl')
            ->field('a.*,u.nickname as nickname, headimgurl')
			->order('a.id desc')
			->limit($page->firstRow, $page->listRows)
			->select();
		if($this->_isAdmin){
			foreach ($data['data'] as $key => &$value) {
				$value['wechat_name'] = $wechatConfigModel -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
			}
			unset($value);
		}
		$data['show'] = $page->show('Admin');
		$this->assign('data', $data);
		$this->assign('where', $where);
		$this->assign('openid', $openid);
		$this->display();
	}
	public function C_funsTicketList(){
		$where = array();
		$ticketModel = M('wechat_user_ticket');
		if($this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}else{
			$where['t.config_id'] = $_SESSION['CONFIG_ID'];
		}
		if(IS_POST){
			$status = intval(I('post.status'));
			if($status != -1){
				$where['t.status'] = $status;
			}
			if($this->_isAdmin){
				if(intval(I('post.config')) != -1) $where['t.config_id'] = intval(I('post.config'));
			}
			$openid = trim(I('post.openid'));
			if(0 != strlen($openid)) $where['t.openid'] = array('like', "%".$openid."%");
			$keyword = trim(I('post.keyword'));
			if(!empty($keyword)){
				$map['t.goods_name'] = array('like', '%'.$keyword.'%');
				$map['t.name'] = array('like', '%'.$keyword.'%');
//				$map["from_base64(u.nickname)"] = array('like', '%'.$keyword.'%');
                $map["u.nickname"] = array('like', '%'.$keyword.'%');
				$map['_logic'] = 'or';
				$where['_complex'] = $map;
			}
		}
		if(IS_GET){
			$openid = trim(I('get.openid'));
			if(0 != strlen($openid)) $where['t.openid'] = array('like', "%".$openid."%");
		}
		$count = $ticketModel -> where($where)
			->alias('t')
			->join(C('DB_PREFIX').'wechat_user as u on t.openid = u.openid', 'left')
			->count();
		$page = $this->page($count, $this->perpage);
		$data['data'] = $ticketModel -> where($where)
		    ->alias('t')
			->join(C('DB_PREFIX').'wechat_user as u on t.openid = u.openid', 'left')
//			->field('t.*,from_base64(u.nickname) nickname, headimgurl')
            ->field('t.*,u.nickname as nickname, headimgurl')
			->order('t.id desc')
			->limit($page->firstRow, $page->listRows)
			->select();
		if($this->_isAdmin){
			foreach ($data['data'] as $key => &$value) {
				$value['wechat_name'] = $wechatConfigModel -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
			}
			unset($value);
		}
		$data['show'] = $page->show('Admin');
		$this->assign('data', $data);
		$this->assign('where', $where);
		$this->assign('openid', $openid);
		$this->display();
	}

	/**
	 * 用户单独定价
	 */
	public function C_singlePriced(){
		$configId = I('config_id', '0' ,'intval');
		$openid = I('openid', '', 'trim');
		if($configId <= 0){
			$this -> error('参数错误');
		}
		if(strlen($openid) <= 0){
			$this -> error('参数错误');
		}
		$goods = M('goods') -> where(array(
			'config_id' => $configId,
			'status' => array('neq', 2),
			))
			-> field('id, name, img, price')
			-> order('list_order asc')
			-> select();
		//查询已设置的定价
		foreach ($goods as $key => &$value) {
			$newGoods = M('user_goods') -> where(array(
				'config_id' => $configId,
				'openid' => $openid,
				'goods_id' => $value['id'],
				))
				->find();
			if(!empty($newGoods)){
				$value['new_price'] = $newGoods['goods_price'];
			}
		}
		unset($value);
		$this -> assign('openid', $openid);
		$this -> assign('goods', $goods);
		$this->display();
	}
	/**
	 * 用户单独定价提交
	 */
	public function C_singlePricedPost(){
		$configId = I('config_id', '0' ,'intval');
		$openid = I('openid', '', 'trim');
		if($configId <= 0){
			$this -> error('参数错误');
		}
		if(strlen($openid) <= 0){
			$this -> error('参数错误');
		}
		$goodsId = $_POST['goodsid'];
		$newPrice = $_POST['newprice'];
		$userGoods = array();
		foreach ($goodsId as $key => $value) {
			$userGoods[$key]['goods_id'] = $value;
			$userGoods[$key]['goods_price'] = intval($newPrice[$key]) > 0 ? intval($newPrice[$key]) : 0;
			$userGoods[$key]['goods_price'] = sprintf('%0.2f', floatval($userGoods[$key]['goods_price']));
			if($userGoods[$key]['goods_price'] == 0){
				unset($userGoods[$key]);
				continue;
			}
			$goods = M('goods') -> where(array(
				'config_id' => $configId,
				'id' => $value,
				))
				-> field('price')
				-> find();
			if(!empty($goods)){
				$userGoods[$key]['config_id'] = $configId;
				$userGoods[$key]['openid'] = $openid;
				$userGoods[$key]['config_id'] = $configId;
				$userGoods[$key]['old_goods_price'] = sprintf('%0.2f', floatval($goods['price']));
				$userGoods[$key]['create_time'] = time();
			}
		}
		// 删除已存在的定价信息
		try{
			$del = M('user_goods') -> where(array(
				'config_id' => $configId,
				'openid' => $openid,
				))
				-> delete();
			if($del!==false){
				$res = M('user_goods') -> addAll($userGoods);
			}
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('定价成功', U('Admin/Wechat/C_funsList'));
	}
}