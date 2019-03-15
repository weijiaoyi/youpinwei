<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Lib\Wechat;
use Think\Log;

class StationController extends AdminbaseController{

	public function C_stationList(){
		if($this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}
		$where = '1=1 and status<>2';
		
		 //添加一个默认
		 if(empty($_POST))
		 {
		 	$check['status'] = -1;
		 	$this->assign('where',$check);
		 }
		if(IS_POST){
			if(intval(I('post.status')) != -1){
				$where .= ' and status='.intval(I('post.status'));
			}
			$keyword = trim(I('post.keyword'));
			if(0 != strlen($keyword)){
				$where .= ' and (name like "%'.$keyword.'%" or tel like "%'.$keyword.'%" or boss_name like "%'.$keyword.'%")';
			}
			if($_SESSION['CONFIG_ID'] != 0){
 				$where .= ' and config_id = '.$_SESSION['CONFIG_ID'];
			}else{
				if(intval(I('post.config')) != -1) $where .=" and config_id = ".intval(I('post.config'));
			}
			$this->assign('where', $_POST);
 		}else{
 			if($_SESSION['CONFIG_ID'] != 0) $where .=" and config_id = ".intval($_SESSION['CONFIG_ID']);
 		}
 	 	$count = M('station')
 	 		->where($where)
		 	->count();
		 $page = new \Think\Page($count, $this->perpage);
		 $data['show'] = $page->show();
		 $data['data'] = M('station')
		 	->where($where)
		 	->order('id desc')
		 	->limit($page->firstRow.','.$page->listRows)
		 	->select();
		foreach ($data['data'] as &$station) {
			if(mb_strlen($station['name'], 'utf8') > 12){
				$station['name'] = mb_substr($station['name'], 0, 12, 'utf8').'...';
			}
			if(mb_strlen($station['address'], 'utf8') > 20){
				$station['address'] = mb_substr($station['address'], 0, 20, 'utf8').'...';
			}
			if($this->_isAdmin){
				$station['wechat_name'] = M('wechat_config') -> where(array('id' => $station['config_id'])) -> getField('wechat_name');
			}
			$station['grade_name']=M('grade_detail')->where('grade_id='.$station['grade_id'])->getField('grade_name');
		}
		unset($station);
		$this->assign('data',$data);
		$this->display();
	}

	public function C_stationAdd(){


		if(IS_GET){
            // $config_id = session('CONFIG_ID');
            $config_id =7;

            $grade = M('grade')->alias('g')->join('__GRADE_DETAIL__ gd on g.id=gd.grade_id')->where('g.config_id='.$config_id.' and status=0')->select();
            $this->assign('grade',$grade);
			$this->display();
		}else{
			$data = $this->_formatStationData($_POST);
			//验证信息
			if(0 == strlen($data['name'])) $this->error('姓名不能为空！');
			if(0 == strlen($data['tel'])) $this->error('电话不能为空！');
			if(!preg_match("/^1[34578]{1}\d{9}$/",strval($data['tel']))) $this->error('电话不正确！');
			if(0 == strlen($data['address'])) $this->error('地址不能为空！');
			$configId = intval($_SESSION['CONFIG_ID']);
			// 如果没有服务号ID，则为易点水
			$configId = $configId == 0 ? 1 : $configId;
			$data = array_merge($data, array(
				'bind_code' => $this->_createStationBindCode(),
				'create_time' => time(),
				'status' => 0,
				'config_id' => $configId,
			));
			$transModel = M();
			$transModel->startTrans();
			try{
				$stationId = M('station')->add($data);
				if($stationId > 0){
					$wechat = new Wechat($configId);
					$qrUrl = $wechat->createParameteredWechatQr($stationId, true);
					if($qrUrl){
						$saveQr = M('station')->where(array('id'=>$stationId))->save(array(
							'qr_url' => $qrUrl,
						));
						if($qrUrl){
							$transModel->commit();
							$rtn = array(
								'info' => '新建水站成功！',
								'status' => 1,
								'data' => array(
									'id' => $stationId
								),
								'url' => U('Admin/Station/C_stationList')
							);
						}else{
							$transModel->rollback();
							$rtn = array(
								'info' => '新建水站失败！',
								'status' => 0,
							);
						}
					}
				}else{
					$transModel->rollback();
					$rtn = array(
						'info' => '新建水站失败！',
						'status' => 0,
					);
				}
			}catch(\Exception $e){
				$transModel->rollback();
				$rtn = array(
					'info' => '新建水站失败！',
					'status' => 0,
				);
				\Think\Log::write('[10001]Insert Station Error: '.$e->getMessage(), 'EMERG');
			 }
				
			$this->success('新建水站成功',U('Admin/Station/C_stationList'));
		}
	}

	public function stationOrderAnalysis(){
		$stations = M('station')->where(array('status'=>0))->select();
		foreach ($stations as &$station) {
			$todayOrders = M('b2c_order')->where(array(
				'station_id'=>$station['id'],
				'order_status' => array('in', array(0,1,2,3)),
				'create_time' => array('egt', date('Y-m-d H:i:s', strtotime('today'))),
			))->select();
			$station['today_count'] = count($todayOrders);
			$pointed = 0;
			$finished = 0;
			foreach ($todayOrders as $order) {
				if($order['order_status'] == 3){
					$finished++;
				}
				if($order['deliver_id'] || $order['deliver_type'] == 1){
					$pointed++;
				}
			}
			$station['pointed'] = $pointed;
			$station['unpointed'] = intval(count($todayOrders) - $pointed);
			$station['delivering'] = intval(count($todayOrders) - $finished);
			$station['finished'] = $finished;
			$historyOrderCount = M('b2c_order')->where(array(
				'station_id'=>$station['id'],
				'order_status' => array('in', array(0,1,2,3)),
				'create_time' => array('lt', date('Y-m-d H:i:s', strtotime('today'))),
			))->count();
			$station['history_count'] = $historyOrderCount;
		}
		$this->assign('stations', $stations);
		$this->display();
	}

	public function stationRange(){
		$stations = M('station')->where(array('status'=>0,'config_id'=>$_SESSION['CONFIG_ID']))->select();
		$this->assign('stations', json_encode($stations));
		$this->display();
	}

	public function C_stationEdit(){
		if(IS_GET){
			$sid = intval($_GET['sid']);
			$station = array();
			if(0 < $sid){
				$station = M('station')->find($sid);
			}
			$this->assign('station', $station);
			$this->display();
		}else{
			 $sid = intval($_POST['sid']);
			if(1 > $sid){
				$rtn = array(
					'info' => '参数错误！',
					'status' => 0,
				);
				\Think\Log::write('[10002]Save Station No ID: '.strval(json_encode($_POST)), 'ALERT');
			}else{
			 	$data = $this->_formatStationDataE($_POST);
			 	Log::write(json_encode($_POST));
			 	if(0 == strlen($data['name'])) $this->error('姓名不能为空！');
				if(0 == strlen($data['tel'])) $this->error('电话不能为空！');
				if(!preg_match("/^1[34578]{1}\d{9}$/",strval($data['tel']))) $this->error('电话不正确！');
				// if(0 == strlen($data['address'])) $this->error('地址不能为空！');
				// if(0 == strlen($data['range'])) $this->error('地址不能为空！');
				Log::write($data,'INFO');
			 	$saveRes = M('station')->where(array('id'=>$sid))->save($data);
				if(false !== $saveRes){
					$rtn = array(
						'info' => '编辑水站成功！',
						'status' => 1,
						'data' => array(
							'id' => $sid,
						),
						'url' => U('Admin/Station/C_stationList')
					);
				}else{
					$rtn = array(
						'info' => '编辑水站失败！',
						'status' => 0,
					);
				}
			}
			$this->ajaxReturn($rtn);
		}
	}


	public function C_autoSaveStatus()
	{
		//接受传过来的参数
		//当前需要处理的id，当前的状态
		$id = (int)$_GET['uid'];
		$status = trim($_GET['status']);
		if($status==0)
		{
			//当前状态为0修改成1
			$info = M('station')->where('id='.$id)->save(['status'=>1]);
			if(false!==$info)
			{
				
				$this->success('禁用成功！');
			}
			else
			{
				
				$this->error('禁用失败！');
			}
		}
		else
		{
			//当前状态为1修改为0
			$info = M('station')->where('id='.$id)->save(['status'=>0]);
			if(false!==$info)
			{
				
				$this->success('启用成功！');
			}
			else
			{
				
				$this->error('启用失败！');
			}
		}
	}

	//删除
	public function C_stationDel()
	{
		//接受传过来的参数
		$id = (int)$_GET['uid'];
		$info = M('station')->where('id='.$id)->save(['status'=>2]);
			if(false!==$info)
			{
				$this->success('删除成功！');
			}
			else
			{
				$this->error('删除失败！');
			}
	}

	private function _createStationBindCode(){
		return substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ0123456789'), -10);
	}

	private function _formatStationDataE($data){
		return array(
			'name' => trim($data['name']),
			'tel' => trim($data['tel']),
			'address' => trim($data['address']),
			'gps' => trim($data['address_gps']),
			// 'bind_code' => 0,
			'desc' => trim($data['desc']),
			'range' => $data['range'],
            // 'grade_id'=>trim($data['grade_id']),
		);
	}
	private function _formatStationData($data){
		return array(
			'name' => trim($data['name']),
			'tel' => trim($data['tel']),
			'address' => trim($data['address']),
			'gps' => trim($data['address_gps']),
			'bind_code' => 0,
			'desc' => trim($data['desc']),
			'range' => $data['range'],
            'grade_id'=>trim($data['grade_id']),
		);
	}
}
?>