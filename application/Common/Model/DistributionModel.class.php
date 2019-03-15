<?php
namespace Common\Model;
use Common\Model\CommonModel;
class DistributionModel extends CommonModel
{	
	protected $tableName = 'wechat_user';
	public function getDistributionList($keyword='', $level=0, $configId){
		$userModel = M('wechat_user');
		$where = array();
		if($configId != 0) $where['config_id'] = $configId;
		if(strlen($keyword) > 0) $where["from_base64(nickname)"] = array('like', '%'.$keyword.'%');
		if(intval($level) > 0) $where['deeps'] = $level;

		$count = $userModel -> where($where) -> count();
		$page = new \Think\Page($count, 15);
		// $page = $this->page($count, $this->perpage);
		$data['show'] = $page->show('Admin');
		$data['data'] = $userModel -> where($where) -> field('*,from_base64(nickname) as nickname') -> order('deeps asc') -> limit($page->firstRow, $page->listRows) ->select();
		return $data;
	}
	public function getUserDistributionList($keyword='', $level=0, $openid=''){
		$userModel = M('wechat_user');
		$where = array();
		if(strlen(trim($openid)) <= 0) return false;
		$data['user'] = $userModel -> where(array('openid' => $openid)) -> find();
		if(empty($data['user'])) return false;
		$data['user']['nickname'] = base64_decode($data['user']['nickname']);
		$where['deeps'] = array('gt', $data['user']['deeps']);
		if(intval($level) > 0){
			$path = $data['user']['path'];
			for($i=1;$i<=$level;$i++){
				$path.='-%';
			}
			$where['path'] = array('like', $path);
		}
		
		if(strlen($keyword) > 0)  $where["from_base64(nickname)"] = array('like', '%'.$keyword.'%');
		$count = $userModel -> where($where) -> count();
		$page = new \Think\Page($count, 15);
		$data['show'] = $page->show('Admin');
		$data['data'] = $userModel -> where($where) -> field('*,from_base64(nickname) as nickname') -> order('deeps asc') -> limit($page->firstRow, $page->listRows) -> select();
		return $data;
	}
}
	