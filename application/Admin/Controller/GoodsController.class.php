<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Think\Upload;

class GoodsController extends AdminbaseController{
	public function C_goodsList(){
		 $_SESSION['CONFIG_ID']='7';
		if(IS_POST){
			$where = '1=1';
			if(intval(I('post.status')) != -1){
				$where .= ' and g.status='.intval(I('post.status'));
			}else{
				$where .= ' and g.status<>2';
			}
			
			$keyword = trim(I('post.keyword'));
			if(0 != strlen($keyword)) $where .= ' and (g.name like "%'.$keyword.'%" or c.name like "%'.$keyword.'%")';
			$where .= ' and g.config_id = '.intval($_SESSION['CONFIG_ID']);
			$this->assign('where', $_POST);
 		}else{
 			$where = 'g.status <> 2';
			$where .= ' and g.config_id = '.intval($_SESSION['CONFIG_ID']);
 		}
 		$cateModel = M('goods');
		$count = $cateModel
			->alias('g')
			->where($where)
			->join('yds_goods_cate as c on c.id=g.cate_id')
			->count();

		$page = $this->page($count, $this->perpage);
		$data['data'] = $cateModel
			->alias('g')
			->field('g.*, c.name as cate_name')
			->where($where)
			->join('yds_goods_cate as c on c.id=g.cate_id')
			->limit($page->firstRow, $page->listRows)
			->order('g.list_order')
			->select();
		$data['show'] = $page->show('Admin');
		$this->assign('data', $data);
		$this->display();
	}

	public function C_saveGoodsListOrder(){
		$goodsModel = M('goods');
		$transModel = M();
		$transModel->startTrans();
		foreach ($_POST['listorders'] as $goodsId => $order) {
			$where = array(
				'config_id' => $_SESSION['CONFIG_ID'],
				'id' => intval($goodsId),
			);
			$data = array('list_order' => intval($order));
			try{
				$saveGoodsOrder = $goodsModel->where($where)->save($data);
			}catch(\Exception $e){
				$this->error("排序更新失败！");
			}
			if(false === $saveGoodsOrder){
				$transModel->rollback();
				$this->error("排序更新失败！");
			}
		}
		$transModel->commit();
		$this->success("排序更新成功 ！");
	}

	
	public function C_saveGoodsCateListOrder(){
		$goodsCateModel = M('goods_cate');
		$transModel = M();
		$transModel->startTrans();
		foreach ($_POST['listorders'] as $goodsCateId => $order) {
			$where = array(
				'config_id' => $_SESSION['CONFIG_ID'],
				'id' => intval($goodsCateId),
			);
			$data = array('list_order' => intval($order));
			try{
				$saveGoodsCateOrder = $goodsCateModel->where($where)->save($data);
			}catch(\Exception $e){
				$this->error("排序更新失败！");
			}
			if(false === $saveGoodsCateOrder){
				$transModel->rollback();
				$this->error("排序更新失败！");
			}
		}
		$transModel->commit();
		$this->success("排序更新成功 ！");
	}

	public function C_addGoods(){
		$cates = M('goods_cate')->where(array('status'=>0, 'config_id'=>$_SESSION['CONFIG_ID']))->select();
		$this->assign('cates', $cates);
		$this->display();
	}

	public function C_addGoodsPost(){
		$name = strval(I('post.name'));
		if(0 == strlen($name)) $this->error('商品名称不能为空！');
		$price = sprintf('%0.2f', floatval(I('post.price')));
		if(0 >= $price) $this->error('商品原价不能为小于0.01！');
		$standard = strval(I('post.standard'));
		if(0 == strlen($standard)) $this->error('商品规格不能为空！');
		$unit = strval(I('post.unit'));
		if(0 == strlen($unit)) $this->error('商品单位不能为空！');
		$imgPath = $this->getUploadFileLocalPath(strval(I('post.img')));
		if(!$imgPath || !file_exists(SITE_PATH.$imgPath)){
			$this->error('商品图片不能为空！'.SITE_PATH.$imgPath);
		}
		$desc = strval(I('post.desc'));
		if(0 == strlen($desc)) $this->error('商品描述不能为空！');
		//多图
		if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
                foreach ($_POST['photos_url'] as $key=>$url){
                    $photourl=sp_asset_relative_url($url);
                    $_POST['smeta'][]=array("url"=>"/data/upload/".$photourl,"alt"=>$_POST['photos_alt'][$key]);
                }
            }
		$data = array(
			'config_id' => intval($_SESSION['CONFIG_ID']),
			'cate_id' => intval(I('post.cate_id')),
			'name' => $name,
			'img' => $imgPath,
			'album' => json_encode($_POST['smeta']),
			'price' => $price,
			'price_l1' => $pricel1,
			'price_l2' => $pricel2,
			'price_l3' => $pricel3,
			'price_l4' => $pricel4,
			'price_l5' => $pricel5,
			'standard' => $standard,
			'unit' => $unit,
			'desc' => $desc,
			'create_time' => date('Y-m-d H:i:s', time()),
			'status' => 0,
			'is_allowticket' => intval(I('post.is_allowticket')),
		);
		// 活动时间
        // $startTime = I('post.start_time');
        // $endTime = I('post.end_time');
        // if($startTime){
        // 	$data['start_time'] = strtotime($startTime);
        // }
        // if($endTime){
        // 	$data['end_time'] = strtotime($endTime);
        // }
		$addGoods = M('goods')->add($data);
		if($addGoods){
			$this->success('新建商品成功！', U('Admin/Goods/C_goodsList'));
		}else{
			$this->error('系统错误，请稍候再试...');
		}
	}

	// public function importFromExcel(){
	// 	$upload = new Upload();
	//     $upload->maxSize   =     100000000 ;// 设置附件上传大小
	//     $upload->exts      =     array('xls', 'xlsx');// 设置附件上传类型
	//     $upload->rootPath  =     SITE_PATH.'public/upfile/Excel/'; // 设置附件上传根目录
	//     $info   =   $upload->upload();
	//     if(!$info) {// 上传错误提示错误信息
	//         $this->error($upload->getError(),'',10);
	//     }else{// 上传成功
	//     	$filePath = $upload->rootPath.$info['file']['savepath'].'/'.$info['file']['savename'];
	//     	$fileContentArray = $this->_import($filePath);
	//     	$goods = [];
	//     	for($i=6; $i<count($fileContentArray);){
	//     		$good = array(
	//     			'cate_id' => 1,
	//     			'goods_no' => $fileContentArray[$i],
	//     			'name' => $fileContentArray[$i+1],
	//     			'standard' => $fileContentArray[$i+2],
	//     			'source' => $fileContentArray[$i+3],
	//     			'unit' => $fileContentArray[$i+4]
	//     		);
	//     		if(count($good)){
	//     			$goods[] = $good;
	//     		}
	//     		$i += 5;
	//     	}
	//     	if(count($goods) == 0){
	//     		$this->error('文件内并不包含商品信息！');
	//     	}else{
	//     		if(count($goods) == 1){
	//     			$addGoods = M('goods')->add($goods);
	//     		}else{
	//     			$addGoods = M('goods')->addAll($goods);
	//     		}
	//     		if($addGoods){
	//     			$this->success('导入商品成功！');
	//     		}else{
	//     			$this->error('导入商品失败！');
	//     		}
	//     	}
	//     }
	// }

	// private function _import($filePath){
	// 	vendor('PHPExcel.PHPExcel');
	// 	$PHPExcel = new \PHPExcel(); 
	// 	$PHPReader = new \PHPExcel_Reader_Excel2007(); 
	// 	if(!$PHPReader->canRead($filePath)){ 
	// 		$PHPReader = new \PHPExcel_Reader_Excel5(); 
	// 		if(!$PHPReader->canRead($filePath)){ 
	// 			echo 'no Excel'; 
	// 			return; 
	// 		} 
	// 	} 

	// 	$PHPExcel = $PHPReader->load($filePath); 
	// 	$currentSheet = $PHPExcel->getSheet(0);
	// 	$allColumn = $currentSheet->getHighestColumn();
	// 	$allRow = $currentSheet->getHighestRow();
	// 	$erp_orders_id = array();

	// 	for($currentRow = 1;$currentRow <= $allRow;$currentRow++){ 

	// 		for($currentColumn= 'A';$currentColumn<= $allColumn; $currentColumn++){ 

	// 			$val = $currentSheet->getCellByColumnAndRow(ord($currentColumn) - 65,$currentRow)->getValue();
	// 			if($val!=''){
	// 				$erp_orders_id[] = $val; 
	// 			}
	// 		} 
	// 	} 
	// 	return $erp_orders_id;
	// }


	private function getUploadFileLocalPath($uploadPath){
		$dataIndex = strripos($uploadPath, '/data');
		if(0 === $dataIndex || 0 < $dataIndex){
			return substr($uploadPath, $dataIndex);
		}else{
			return "/data/upload/".$uploadPath;
		}
	}

	/**
	 * 推荐商品至首页
	 * @return [type] [description]
	 */
	public function C_recommendGoods(){
		$goodsModel = M('goods');
		$saveGoods = $goodsModel->where(array('id'=>intval(I('get.gid')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('is_recommended'=>1));
		if(false !== $saveGoods){
			$this->success('商品推荐至首页成功！');
		}else{
			$this->error('商品推荐至首页失败！'.$goodsModel->getLastSql());
		}
	}

	/**
	 * 取消推荐商品至首页
	 * @return [type] [description]
	 */
	public function C_unrecommendGoods(){
		$goodsModel = M('goods');
		$saveGoods = $goodsModel->where(array('id'=>intval(I('get.gid')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('is_recommended'=>0));
		if(false !== $saveGoods){
			$this->success('商品取消推荐至首页成功！');
		}else{
			$this->error('商品取消推荐至首页失败！'.$goodsModel->getLastSql());
		}
	}

	/**
	 * 下架商品
	 * @return string json
	 */	
	public function C_closeGoods(){
		$goodsModel = M('goods');
		$goodsStrategyModel = M('goods_strategy');
		$goodsStrategyDetailModel = M('goods_strategy_detail');
		//判断当前商品是否存在水票或者套餐
		$goodsStrategy = $goodsStrategyModel -> where(array('status' => array('lt',2), 'goods_id' => intval(I('get.gid')), 'config_id'=>$_SESSION['CONFIG_ID'])) -> getField('id');
		if(!empty($goodsStrategy)) $this -> error('该商品下存在水票无法下架');
		$goodsStrategyDetail = $goodsStrategyModel
			->alias('s')
			->join(C('DB_PREFIX')."goods_strategy_detail as g on s.id = g.sid")
			->where("s.status < 2 and s.type = 2 and g.goods_id = ".intval(I('get.gid')))
			->field('s.id')
			->find();
		if(!empty($goodsStrategyDetail)) $this -> error('该商品下存在套餐无法下架');

		$saveGoods = $goodsModel->where(array('id'=>intval(I('get.gid')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('status'=>1));
		if(false !== $saveGoods){
			$this->success('下架商品成功！');
		}else{
			$this->error('下架商品失败！'.$goodsModel->getLastSql());
		}
	}

	/**
	 * 上架商品
	 * @return string json
	 */	
	public function C_openGoods(){
		$goodsModel = M('goods');
		$saveGoods = $goodsModel->where(array('id'=>intval(I('get.gid')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('status'=>0));
		if(false !== $saveGoods){
			$this->success('上架商品成功！');
		}else{
			$this->error('上架商品失败！'.$goodsModel->getLastSql());
		}
	}

	/**
	 * 删除商品
	 * @return string json
	 */	
	public function C_deleteGoods(){
		$goodsModel = M('goods');
		$goodsStrategyModel = M('goods_strategy');
		$goodsStrategyDetailModel = M('goods_strategy_detail');
		//判断当前商品是否存在水票或者套餐
		$goodsStrategy = $goodsStrategyModel -> where(array('status' => array('lt',2), 'goods_id' => intval(I('get.gid')), 'config_id'=>$_SESSION['CONFIG_ID'])) -> getField('id');
		if(!empty($goodsStrategy)) $this -> error('该商品下存在水票无法删除');
		$goodsStrategyDetail = $goodsStrategyModel
			->alias('s')
			->join(C('DB_PREFIX')."goods_strategy_detail as g on s.id = g.sid")
			->where("s.status < 2 and s.type = 2 and g.goods_id = ".intval(I('get.gid')))
			->field('s.id')
			->find();
		if(!empty($goodsStrategyDetail)) $this -> error('该商品下存在套餐无法删除');

		$saveGoods = $goodsModel->where(array('id'=>intval(I('get.gid')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('status'=>2));
		if(false !== $saveGoods){
			$this->success('删除商品成功！');
		}else{
			$this->error('删除商品失败！');
		}
	}

	/**
	 * 编辑商品
	 * @return [type] [description]
	 */
	public function C_editGoods(){
		$goodsModel = M('goods');
		$goods = $goodsModel->find(intval(I('get.gid')));
		$this->assign('goods', $goods);
		$type = intval(I('get.type'));
		$cates = M('goods_cate')->where(array('status'=>0, 'config_id'=>$_SESSION['CONFIG_ID']))->select();
		$this->assign('cates', $cates);
		$album = json_decode($goods['album'],true);
		$this->assign("album",$album);
		$this->display();
	}

	/**
	 * 编辑商品提交
	 * @return [type] [description]
	 */
	public function C_editGoodsPost(){
		$goodsModel = M('goods');

		$name = strval(I('post.name'));
		if(0 == strlen($name)) $this->error('商品名称不能为空！');
		$price = sprintf('%0.2f', floatval(I('post.price')));
		if(0 >= $price) $this->error('商品原价不能为小于0.01！');
		$standard = strval(I('post.standard'));
		if(0 == strlen($standard)) $this->error('商品规格不能为空！');
		$unit = strval(I('post.unit'));
		if(0 == strlen($unit)) $this->error('商品单位不能为空！');
		$imgPath = $this->getUploadFileLocalPath(strval(I('post.img')));
		if(!file_exists(SITE_PATH.$imgPath)) $this->error('商品图片不能为空！'.SITE_PATH.$imgPath);
		$desc = strval(I('post.desc'));
		if(0 == strlen($desc)) $this->error('商品描述不能为空！');
		//多图
		if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
                foreach ($_POST['photos_url'] as $key=>$url){
                    $photourl=sp_asset_relative_url($url);
                    $_POST['smeta'][]=array("url"=>"/data/upload/".$photourl,"alt"=>$_POST['photos_alt'][$key]);
                }
            }
		$data = array(
			'cate_id' => intval(I('post.cate_id')),
			'name' => $name,
			'img' => $imgPath,
			'album' => json_encode($_POST['smeta']),
			'price' => $price,
			'price_l1' => $pricel1,
			'price_l2' => $pricel2,
			'price_l3' => $pricel3,
			'price_l4' => $pricel4,
			'price_l5' => $pricel5,
			'standard' => $standard,
			'unit' => $unit,
			'desc' => $desc,
			'is_allowticket' => intval(I('post.is_allowticket')),
		);
		// 活动时间
        // $startTime = I('post.start_time');
        // $endTime = I('post.end_time');
        // if($startTime){
        // 	$data['start_time'] = strtotime($startTime);
        // }
        // if($endTime){
        // 	$data['end_time'] = strtotime($endTime);
        // }
		$saveGoodsStore = $goodsModel->where(array('id'=>intval(I('post.id')), 'config_id'=>$_SESSION['CONFIG_ID']))->save($data);
		if(false !== $saveGoodsStore){
			$this->success('保存商品成功！');
		}else{
			$this->error('保存商品失败！');
		}
	}

	/**
	 * 商品分类列表
	 * @return [type] [description]
	 */
	public function C_goodsCatesList(){
		if(IS_POST){
			$where = '1=1';
			if(intval(I('post.status')) != -1){
				$where .= ' and status='.intval(I('post.status'));
			}else{
				$where .= ' and status<>2';
			}
			
			$keyword = trim(I('post.keyword'));
			if(0 != strlen($keyword)) $where .= ' and (name like "%'.$keyword.'%")';
			$where .= ' and config_id = '.intval($_SESSION['CONFIG_ID']);
			$this->assign('where', $_POST);
 		}else{
 			$where = 'status <> 2';
			$where .= ' and config_id = '.intval($_SESSION['CONFIG_ID']);
 		}
 		$cateModel = M('goods_cate');
		$count = $cateModel->where($where)->count();

		$page = $this->page($count, $this->perpage);
		$data['data'] = $cateModel->where($where)
			->limit($page->firstRow, $page->listRows)
			->order('list_order asc')
			->select();
		$data['show'] = $page->show('Admin');	
		$this->assign('data', $data);
		$this->display();
	}

	/**
	 * 新建商品分类
	 */
	public function C_createGoodsCate(){
		$cates = M('goods_cate')->where(array('status'=>0, 'config_id'=>$_SESSION['CONFIG_ID']))->select();
		$this->assign('cates', $cates);
		$this->display();
	}

	/**
	 * 新建商品分类提交
	 */
	public function C_createGoodsCatePost(){
		$name = trim(strval(I('post.name')));
		if(0 == strlen($name)){
			$this->error('商品分类名称不能为空！');
		}
		$saveCate = M('goods_cate')->add(array(
			'config_id' => $_SESSION['CONFIG_ID'],
			'pid' => intval(I('post.pid')),
			'name' => $name,
			'create_time' => date('Y-m-d H:i:s', time()),
			'status' => 0
		));
		if($saveCate){
			$this->success('新建商品分类成功！', U('Admin/Goods/C_goodsCatesList'));
		}else{
			$this->error('新建商品分类失败！');
		}
	}

	/**
	 * 编辑商品分类
	 * @return string json
	 */
	public function C_editGoodsCate(){
		$cateModel = M('goods_cate');
		$cate = $cateModel->find(intval(I('get.cid')));
		$this->assign('cate', $cate);

		$cates = $cateModel->where(array('status'=>0, 'config_id'=>$_SESSION['CONFIG_ID']))->select();
		$this->assign('cates', $cates);

		$this->display();
	}

	/**
	 * 编辑商品分类提交
	 * @return string json
	 */
	public function C_editGoodsCatePost(){
		$cateModel = M('goods_cate');
		$name = strval(I('post.name'));
		if(0 == strlen($name)) $this->error('分类名称不能为空！');
		$data = array(
			'pid' => intval(I('post.pid')),
			'name' => $name,
		);

		$saveCate = $cateModel->where(array('id'=>intval(I('post.id')), 'config_id'=>$_SESSION['CONFIG_ID']))->save($data);
		if(false !== $saveCate){
			$this->success('保存商品分类成功！',  U('Admin/Goods/C_goodsCatesList'));
		}else{
			$this->error('保存商品分类失败！'.$cateModel->getLastSql());
		}
	}

	/**
	 * 禁用商品库分类
	 * @return string json
	 */	
	public function C_closeGoodsCate(){
		$cateModel = M('goods_cate');
		$saveCate = $cateModel->where(array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('status'=>1));
		if(false !== $saveCate){
			$this->success('禁用商品库分类成功！');
		}else{
			$this->error('禁用商品库分类失败！'.$cateModel->getLastSql());
		}
	}

	/**
	 * 启用商品库分类
	 * @return string json
	 */	
	public function C_openGoodsCate(){
		$cateModel = M('goods_cate');
		$saveCate = $cateModel->where(array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('status'=>0));
		if(false !== $saveCate){
			$this->success('启用商品库分类成功！');
		}else{
			$this->error('启用商品库分类失败！'.$cateModel->getLastSql());
		}
	}

	/**
	 * 删除商品库分类
	 * @return string json
	 */	
	public function C_deleteGoodsCate(){
		$cateModel = M('goods_cate');
		$saveCate = $cateModel->where(array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('status'=>2));
		if(false !== $saveCate){
			$this->success('删除商品库分类成功！');
		}else{
			$this->error('删除商品库分类失败！'.$cateModel->getLastSql());
		}
	}
	/**
	 * 组合商品列表
	 */
	public function C_goodsStrategyList(){
		$goodsModel = M('goods');
		$goodsStrategyModel = M('goodsStrategy');
		$goodsStrategyDetailModel = M('goodsStrategy_detail');
		if(IS_POST){
			$where = '1=1 and s.status < 2';
			if(intval(I('post.type')) >= 1){
				$where .= ' and s.type='.intval(I('post.type'));
			}
			$keyword = trim(I('post.keyword'));
			if(0 != strlen($keyword)) $where .= ' and (s.name like "%'.$keyword.'%")';
			$where .= ' and s.config_id = '.intval($_SESSION['CONFIG_ID']);
			$this->assign('where', $_POST);
 		}else{
			$where = 's.status < 2 and s.config_id = '.intval($_SESSION['CONFIG_ID']);
 		}
		$count = $goodsStrategyModel
			->alias('s')
			->where($where)
			->count();

		$page = $this->page($count, $this->perpage);
		
		$data['data'] = $goodsStrategyModel
			->alias('s')
			->where($where)
			->limit($page->firstRow, $page->listRows)
			->order('s.type asc, s.list_order asc')
			->select();
		
		foreach ($data['data'] as $key => &$value) {
			if($value['type'] == 1){
				$goodsStrategy = $goodsStrategyDetailModel ->where(array('sid' => $value['id'])) -> field('num, is_give, givenum, goods_name, goods_price, goods_img') -> find();
				$value['goods_strategy'] = $goodsStrategy;
			}
		}
		unset($value);
		$data['show'] = $page->show('Admin');	
		$this->assign('data', $data);
		$this->display();
	}
	/**
	 * 水票添加
	 */
	public function C_addGoodsStrategyTicket(){
		$goodsModel = M('goods');
		$goodsId = I('goods_id', 0, 'intval');
		if($goodsId <= 0) $this->error('访问错误！');
		$goods = $goodsModel ->where(array('status' => 0, 'config_id' => intval($_SESSION['CONFIG_ID']))) -> find($goodsId);
		if(empty($goods)) $this->error('商品不存在');
		if($goods['is_allowticket'] != 1) $this->error('所选商品不允许添加水票');

		$this->assign('goods',$goods);
		$this->display('C_addGoodsStrategyTicket');
	}
	/**
	 * 水票添加提交
	 */
	public function C_addGoodsStrategyTicketPost(){
		$goodsModel = M('goods');
		$goodsId = I('goods_id', 0, 'intval');
		if($goodsId <= 0) $this->error('访问错误！');
		$goods = $goodsModel ->where(array('status' => 0, 'config_id' => intval($_SESSION['CONFIG_ID']))) -> find($goodsId);
		if(empty($goods)) $this->error('商品不存在');
		if($goods['is_allowticket'] != 1) $this->error('所选商品不允许添加水票');
		$num = I('num', 0, 'intval');
		if($num <= 0) $this->error('请填写商品数量');
		$givenum = I('givenum', 0, 'intval');
		$is_give = $givenum > 0 ? 1 : 0;
		$name = I('name', '', 'trim');
		if($name == '') $this->error('请填写水票内容');
		//$price = $num * $goods['price'];
		$price  = sprintf('%.2f', I('post.price'));
		if($price <= 0){
			$this->error('请填写水票价格');
		}
		$status = I('status', 0, 'intval');
		$goodsStrategyData = array(
			'goods_id' => $goodsId,
			'config_id' => intval($_SESSION['CONFIG_ID']),
			'name' => $name,
			'num' => $num,
			'price' => $price,
			'type' => 1,
			'status' => $status,
			);
		// 活动
        $startTime = I('post.start_time');
        $endTime = I('post.end_time');
        if($startTime){
        	$goodsStrategyData['start_time'] = strtotime($startTime);
        }
        if($endTime){
        	$goodsStrategyData['end_time'] = strtotime($endTime);
        }
        $condition = array('use_num' => '', 'only' =>0);
        $use_num = I('post.use_num', 0, 'intval');
        if($use_num < 0){
        	$this->error('请填写大于0的数值');
        }
        $code = I('post.code');
        if(!empty($code)){
        	$condition['use_num'] = $code.','.$use_num;
        }
        $only = I('post.only');
        if($only >= 0){
        	$condition['only'] = $only;
        }
        $goodsStrategyData['condition'] = json_encode($condition);

		$transModel = M();
		$transModel -> startTrans();
		$goodsStrategy = M('goodsStrategy') -> add($goodsStrategyData);
		if(!$goodsStrategy){
			$transModel -> rollback();
			$this->error('添加水票失败！');
		}
		$goodsStrategyDetailData = array(
			'sid' => $goodsStrategy,
			'goods_id' => $goodsId,
			'num' => $num,
			'is_give' => $is_give,
			'givenum' => $givenum,
			'goods_name' => $goods['name'],
			'goods_img' => $goods['img'],
			'goods_price' => $goods['price'],
			'type' => 1,
			);
		if(!M('goodsStrategy_detail')->add($goodsStrategyDetailData)){
			$transModel -> rollback();
			$this->error('添加水票失败！');
		}
		$transModel -> commit();
		$this->success('添加水票成功！', U('Admin/Goods/C_goodsList'));
	}
	/**
	 * 水票修改
	 */
	public function C_editGoodsStrategyTicket(){
		$goodsStrategyModel = M('goodsStrategy');
		$id = I('id', 0, 'intval');
		if($id <= 0) $this->error('访问错误！');
		$goodsStrategy = $goodsStrategyModel ->where(array('config_id' => intval($_SESSION['CONFIG_ID']))) -> find($id);
		if(empty($goodsStrategy)) $this->error('水票不存在');
		if($goodsStrategy['type'] != 1) $this->error('当前组合非水票');
		if($goodsStrategy['status'] == 2) $this->error('水票不存在');
		//如果商品删除，水票不显示
		$goods = M('goods') ->where(array('status' => 0, 'config_id' => intval($_SESSION['CONFIG_ID']))) -> find($goodsStrategy['goods_id']);
		if(empty($goods)) $this->error('水票对应的商品不存在');
		//查询detail表
		$goodsStrategyDetail = M('goodsStrategy_detail') -> where(array('sid' => $id)) -> find();
		$goodsStrategy['num'] = $goodsStrategyDetail['num'];
		$goodsStrategy['givenum'] = $goodsStrategyDetail['givenum'];

		$condition = json_decode($goodsStrategy['condition'], true);
		$condition['code'] = explode(',', $condition['use_num'])[0];
		$condition['use_num'] = explode(',', $condition['use_num'])[1];
		$this->assign('condition',$condition);
		$this->assign('goodsStrategy',$goodsStrategy);
		$this->assign('goods',$goods);
		$this->display();
	}
	/**
	 * 水票修改提交
	 */
	public function C_editGoodsStrategyTicketPost(){
		$goodsStrategyModel = M('goodsStrategy');
		$id = I('id', 0, 'intval');
		$status = I('status', 0, 'intval');
		$goodsStrategy = $goodsStrategyModel ->where(array('id' => $id, 'config_id' => intval($_SESSION['CONFIG_ID']))) -> find();
		if(empty($goodsStrategy)) $this->error('请水票不存在！');
		if($goodsStrategy['status'] >= 2) $this->error('水票不存在');
		$num = I('num', 0, 'intval');
		if($num <= 0) $this->error('请填写商品数量');
		$givenum = I('givenum', 0, 'intval');
		$is_give = $givenum > 0 ? 1 : 0;
		$name = I('name', '', 'trim');
		if($name == '') $this->error('请填写水票内容');
		$goods = M('goods') ->where(array('id' => $goodsStrategy['goods_id'], 'status' => 0, 'config_id' => intval($_SESSION['CONFIG_ID']))) -> find();
		if(empty($goods)) $this->error('水票对应的商品不存在');
		// $price = $num * $goods['price'];
		$price  = sprintf('%.2f', I('post.price'));
		if($price <= 0){
			$this->error('请填写水票价格');
		}
		$goodsStrategyData = array(
			'name' => $name,
			'num' => $num,
			'price' => $price,
			'id' => $id,
			'status' => $status,
			'use_num' => I('post.use_num', 0, 'intval'),
			);
		// 活动时间
        $startTime = I('post.start_time');
        $endTime = I('post.end_time');
        if($startTime){
        	$goodsStrategyData['start_time'] = strtotime($startTime);
        }
        if($endTime){
        	$goodsStrategyData['end_time'] = strtotime($endTime);
        }
        $condition = array('use_num' => '', 'only' =>0);
        $use_num = I('post.use_num', 1, 'intval');
        if($use_num <= 0){
        	$this->error('请填写大于0的数值');
        }
        $code = I('post.code');
        if(!empty($code)){
        	$condition['use_num'] = $code.','.$use_num;
        }
        $only = I('post.only');
        if($only >= 0){
        	$condition['only'] = $only;
        }
        $goodsStrategyData['condition'] = json_encode($condition);

		$transModel = M();
		$transModel -> startTrans();
		$goodsStrategyModel = M('goodsStrategy') -> save($goodsStrategyData);
		if(!$goodsStrategy){
			$transModel -> rollback();
			$this->error('更新水票失败！');
		}
		$goodsStrategyDetailData = array(
			'num' => $num,
			'is_give' => $is_give,
			'givenum' => $givenum,
			);
		if(M('goodsStrategy_detail') -> where(array('sid' => $id)) -> save($goodsStrategyDetailData) === false){
			$transModel -> rollback();
			$this->error('更新水票失败！');
		}
		$transModel -> commit();
		$this->success('更新水票成功！', U('Admin/Goods/C_goodsList'));
	}
	/**
	 * 套餐添加
	 */
	public function C_addGoodsStrategyPackage(){
		$goodsModel = M('goods');
		$goods = $goodsModel ->field('id, name, price') -> where(array('status' => 0, 'config_id' => intval($_SESSION['CONFIG_ID']))) -> select();
		$tickets = M('goods_strategy') -> field('id, name, price') -> where(array('type' => 1, 'status' => 0, 'config_id' => intval($_SESSION['CONFIG_ID']))) -> select();
		$this->assign('goods', $goods);
		$this->assign('tickets', $tickets);
		$this->display();
	}
	/**
	 * 套餐添加提交(任意普通商品版)
	 */
	// public function C_addGoodsStrategyPackagePost(){
	// 	$goodsStrategyModel = M('goodsStrategy');
	// 	$goodsStrategyDetailModel = M('goodsStrategy_detail');
	// 	$name = I('name', '', 'trim');
	// 	$status = I('status', 0, 'intval');
	// 	if($name == '') $this->error('请输入套餐名称');
	// 	$goods = $_POST['goods'];
	// 	if(empty($goods)) $this->error('请选择商品');
	// 	$num = $_POST['num'];
	// 	$goodsStrategyData = array(
	// 		'config_id' => $_SESSION['CONFIG_ID'],
	// 		'name' => $name,
	// 		'type' => 2,
	// 		'status' => $status,
	// 		);
	// 	$goodsStrategyDetailData =array();
	// 	$price = 0;
	// 	foreach($goods as $key => $vo){
	// 		$goodsStrategyDetailData[$key]['goods_id'] = $vo;
	// 		$goodsStrategyDetailData[$key]['num'] = $num[$key];
	// 		$goodsDetail = M('goods')->where(array('id'=>$vo))->field('price, name, img')->find();
	// 		$goodsStrategyDetailData[$key]['goods_name'] = $goodsDetail['name'];
	// 		$goodsStrategyDetailData[$key]['goods_img'] = $goodsDetail['img'];
	// 		$goodsStrategyDetailData[$key]['goods_price'] = $goodsDetail['price'];
	// 		$price += $goodsDetail['price'] * $num[$key];
	// 	}
	// 	$goodsStrategyData['price'] = $price;
	// 	$transModel = M();
	// 	$transModel -> startTrans();
	// 	$goodsStrategy = $goodsStrategyModel -> add($goodsStrategyData);
	// 	if(!$goodsStrategy){
	// 		$transModel -> rollback();
	// 		$this->error('添加套餐失败');
	// 	}
	// 	foreach($goodsStrategyDetailData as $vo){
	// 		$vo['sid'] = $goodsStrategy;
	// 		if(!$goodsStrategyDetailModel->add($vo)){
	// 			$transModel -> rollback();
	// 			$this->error('添加套餐失败');
	// 		}
	// 	}
	// 	$transModel -> commit();
	// 	$this->success('添加套餐成功！', U('Admin/Goods/C_goodsList'));

	// }
	/**
	 * 套餐添加提交
	 */
	public function C_addGoodsStrategyPackagePost(){
		$goodsStrategyModel = M('goodsStrategy');
		$goodsStrategyDetailModel = M('goodsStrategy_detail');
		$name = I('name', '', 'trim');
		$status = I('status', 0, 'intval');
		if($name == '') $this->error('请输入套餐名称');
		$goods = $_POST['goods'];

		if(empty($goods[0])) $this->error('请选择商品');
		$ticket = $_POST['ticket'];
		if(empty($ticket[0])) $this->error('请选择水票');
		$num = $_POST['num'];
		if(intval($num[0]) <= 0){
			$this->error('请填写水票数量');
		}
		if(intval($num[1]) <= 0){
			$this->error('请填写商品数量');
		}
		$goodsStrategyData = array(
			'config_id' => $_SESSION['CONFIG_ID'],
			'name' => $name,
			'type' => 2,
			'status' => $status,
			);
		$goodsStrategyDetailData =array();
		//$price = 0;
		//处理普通商品
		foreach($goods as $key => $vo){
			$goodsStrategyDetailDataGoods[$key]['goods_id'] = $vo;
			$goodsStrategyDetailDataGoods[$key]['num'] = $num[1];
			$goodsDetail = M('goods')->where(array('id'=>$vo))->field('price, name, img')->find();
			$goodsStrategyDetailDataGoods[$key]['goods_name'] = $goodsDetail['name'];
			$goodsStrategyDetailDataGoods[$key]['goods_img'] = $goodsDetail['img'];
			$goodsStrategyDetailDataGoods[$key]['goods_price'] = $goodsDetail['price'];
			//$price += $goodsDetail['price'] * $num[1];
		}
		//处理水票
		foreach($ticket as $key => $vo){
			$goodsStrategyDetailDataTicket[$key]['goods_id'] = $vo;
			$goodsStrategyDetailDataTicket[$key]['num'] = $num[0];
			$goodsDetail = M('goods_strategy')->where(array('id'=>$vo))->field('id, price, name, goods_id')->find();
			$ticketImg = $goodsStrategyDetailModel -> where(array('sid' => $goodsDetail['id'])) -> getField('goods_img');
			$goodsStrategyDetailDataTicket[$key]['goods_name'] = $goodsDetail['name'];
			$goodsStrategyDetailDataTicket[$key]['goods_price'] = $goodsDetail['price'];
			$goodsStrategyDetailDataTicket[$key]['goods_img'] = $ticketImg;
			$goodsStrategyDetailDataTicket[$key]['type'] = 1;
			//$price += $goodsDetail['price'] * $num[0];
		}

		$goodsStrategyData['price'] = I('post.price');
		$transModel = M();
		$transModel -> startTrans();
		$goodsStrategy = $goodsStrategyModel -> add($goodsStrategyData);
		if(!$goodsStrategy){
			$transModel -> rollback();
			$this->error('添加套餐失败');
		}
		$goodsStrategyDetailData = array_merge($goodsStrategyDetailDataGoods,$goodsStrategyDetailDataTicket);
		foreach($goodsStrategyDetailData as $vo){
			$vo['sid'] = $goodsStrategy;
			if(!$goodsStrategyDetailModel->add($vo)){
				$transModel -> rollback();
				$this->error('添加套餐失败');
			}
		}
		$transModel -> commit();
		$this->success('添加套餐成功！', U('Admin/Goods/C_goodsList'));

	}
	/**
	 * 套餐修改
	 */
	public function C_editGoodsStrategyPackage(){
		$goodsModel = M('goods');
		$goodsStrategyModel = M('goodsStrategy');
		$goodsStrategyDetailModel = M('goodsStrategy_detail');
		$id = I('id', 0, 'intval');
		if($id <= 0) $this -> error('访问错误');
		$goodsStrategy = $goodsStrategyModel -> find($id);
		if(empty($goodsStrategy)) $this -> error('访问错误');
		if($goodsStrategy['type'] != 2) $this -> error('当前组合非套餐');
		if($goodsStrategy['status'] >= 2) $this -> error('当前套餐不存在');
		$goodsStrategyDetailGoods = $goodsStrategyDetailModel -> where(array('sid' => $id, 'type' => 0)) -> field('goods_id, num') -> select();
		$goodsStrategy['goods'] = $goodsStrategyDetailGoods;
		$goodsStrategyDetailTicket = $goodsStrategyDetailModel -> where(array('sid' => $id, 'type' => 1)) -> field('goods_id, num') -> select();
		$goodsStrategy['ticket'] = $goodsStrategyDetailTicket;
		$goods = $goodsModel -> field('id, name, price') -> where(array('status' => 0, 'config_id' => intval($_SESSION['CONFIG_ID']))) -> select();
		$tickets = M('goods_strategy') -> field('id, name, price') -> where(array('type' => 1, 'status' => 0, 'config_id' => intval($_SESSION['CONFIG_ID']))) -> select();
		$this->assign('tickets', $tickets);
		$this->assign('goods', $goods);
		$this->assign('goodsStrategy', $goodsStrategy);
		$this->display();
	}
	/**
	 * 套餐修改提交
	 */
	public function C_editGoodsStrategyPackagePost(){
		$goodsModel = M('goods');
		$goodsStrategyModel = M('goodsStrategy');
		$goodsStrategyDetailModel = M('goodsStrategy_detail');
		$id = I('id', 0, 'intval');
		$status = I('status', 0, 'intval');
		if($id <= 0) $this -> error('访问错误');
		$goodsStrategy = $goodsStrategyModel -> find($id);
		if(empty($goodsStrategy)) $this -> error('访问错误');
		if($goodsStrategy['type'] != 2) $this -> error('当前组合非套餐');
		if($goodsStrategy['status'] >= 2) $this -> error('当前套餐不存在');
		$name = I('name', '', 'trim');
		if($name == '') $this->error('请输入套餐名称');
		$goods = $_POST['goods'];
		if(empty($goods[0])) $this->error('请选择商品');
		$ticket = $_POST['ticket'];
		if(empty($ticket[0])) $this->error('请选择水票');
		$num = $_POST['num'];
		if(intval($num[0]) <= 0){
			$this->error('请填写水票数量');
		}
		if(intval($num[1]) <= 0){
			$this->error('请填写商品数量');
		}
		$goodsStrategyData = array(
			'name' => $name,
			'id' => $id,
			'status' => $status,
			);
		$goodsStrategyDetailData =array();
		// $price = 0;
		foreach($goods as $key => $vo){
			$goodsStrategyDetailDataGoods[$key]['goods_id'] = $vo;
			$goodsStrategyDetailDataGoods[$key]['num'] = $num[1];
			$goodsDetail = M('goods')->where(array('id'=>$vo))->field('price, name, img')->find();
			$goodsStrategyDetailDataGoods[$key]['goods_name'] = $goodsDetail['name'];
			$goodsStrategyDetailDataGoods[$key]['goods_img'] = $goodsDetail['img'];
			$goodsStrategyDetailDataGoods[$key]['goods_price'] = $goodsDetail['price'];
			// $price += $goodsDetail['price'] * $num[1];
		}
		//处理水票
		foreach($ticket as $key => $vo){
			$goodsStrategyDetailDataTicket[$key]['goods_id'] = $vo;
			$goodsStrategyDetailDataTicket[$key]['num'] = $num[0];
			$goodsDetail = M('goods_strategy')->where(array('id'=>$vo))->field('id, price, name, goods_id')->find();
			$ticketImg = $goodsStrategyDetailModel -> where(array('sid' => $goodsDetail['id'])) -> getField('goods_img');
			$goodsStrategyDetailDataTicket[$key]['goods_name'] = $goodsDetail['name'];
			$goodsStrategyDetailDataTicket[$key]['goods_price'] = $goodsDetail['price'];
			$goodsStrategyDetailDataTicket[$key]['goods_img'] = $ticketImg;
			$goodsStrategyDetailDataTicket[$key]['type'] = 1;
			// $price += $goodsDetail['price'] * $num[0];
		}
		$goodsStrategyData['price'] = I('post.price');
		$transModel = M();
		$transModel -> startTrans();
		if($goodsStrategyModel -> save($goodsStrategyData) === false){
			$transModel -> rollback();
			$this->error('更新套餐失败');
		}
		//删除detil
		if(!$goodsStrategyDetailModel -> where(array('sid' => $id)) -> delete()){
			$transModel -> rollback();
			$this->error('更新套餐失败');
		}
		$goodsStrategyDetailData = array_merge($goodsStrategyDetailDataGoods,$goodsStrategyDetailDataTicket);
		foreach($goodsStrategyDetailData as $vo){
			$vo['sid'] = $id;
			if(!$goodsStrategyDetailModel -> add($vo)){
				$transModel -> rollback();
				$this->error('更新套餐失败');
			}
		}
		$transModel -> commit();
		$this->success('更新套餐成功！', U('Admin/Goods/C_goodsList'));
	}
	/**
	 * 禁用商品组合
	 * @return string json
	 */	
	public function C_closeGoodsStrategy(){
		$goodsStrategyModel = M('goodsStrategy');
		$saveGoodsStrategy = $goodsStrategyModel->where(array('id'=>intval(I('get.id')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('status'=>1, 'end_time' => time()-1));
		if(false !== $saveGoodsStrategy){
			$this->success('禁用商品组合成功！');
		}else{
			$this->error('启用商品组合失败！'.$goodsStrategyModel->getLastSql());
		}
	}

	/**
	 * 启用商品组合
	 * @return string json
	 */	
	public function C_openGoodsStrategy(){
		$goodsStrategyModel = M('goodsStrategy');
		$saveGoodsStrategy = $goodsStrategyModel->where(array('id'=>intval(I('get.id')), 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('status'=>0,'end_time' => 2000000000));
		if(false !== $saveGoodsStrategy){
			$this->success('启用商品组合成功！');
		}else{
			$this->error('启用商品组合失败！'.$goodsStrategyModel->getLastSql());
		}
	}

	/**
	 * 删除商品组合
	 * @return string json
	 */	
	public function C_deleteGoodsStrategy(){
		$goodsStrategyModel = M('goodsStrategy');
		$goodsStrategyDetailModel = M('goodsStrategy_detail');
		$id = intval(I('get.id'));
		$transModel = M();
		$transModel -> startTrans();
		try{
			$saveGoodsStrategy = $goodsStrategyModel->where(array('id'=>$id, 'config_id'=>$_SESSION['CONFIG_ID']))->save(array('status'=>2));
			// if(false !== $saveGoodsStrategy){
			// 	if($goodsStrategyDetailModel -> where(array('sid' => $id)) ->delete()){
			// 		$transModel -> commit();
			// 		$this->success('删除商品成功！');
			// 	}
			// }
			// $transModel -> rollback();
			// $this->error('删除商品失败！');
			if(false !== $saveGoodsStrategy){
				$transModel -> commit();
				$this->success('删除组合商品成功！');
				die();
			}
			$transModel -> rollback();
			$this->error('删除组合商品失败！');
		}catch(\Exception $e){
			$this->error('删除组合商品失败！');
		}
	}
	public function C_saveStragegyGoodsListOrder(){
		$trategyModel = M('goods_strategy');
		$transModel = M();
		$transModel->startTrans();
		foreach ($_POST['listorders'] as $trategyId => $order) {
			$where = array(
				'config_id' => $_SESSION['CONFIG_ID'],
				'id' => intval($trategyId),
			);
			$data = array('list_order' => intval($order));
			try{
				$saveGoods = $trategyModel->where($where)->save($data);
			}catch(\Exception $e){
				$this->error("排序更新失败！");
			}
			if(false === $saveGoods){
				$transModel->rollback();
				$this->error("排序更新失败！");
			}
		}
		$transModel->commit();
		$this->success("排序更新成功 ！");
	}

	/**
	 * 商品库
	 */
	public function C_goodsWarehouse(){
		if($this->_isAdmin){
			$this->error('超级管理员不具备从商品库选择商品功能',U('Admin/Goods/C_goodsList'));
		}
		//获取根分类
		$goodsList = M('goods_cate')
			-> where(array('config_id' => 0, 'status' => 0, 'pid' => 0))
			-> field('id, name')
			-> order('list_order asc')
			-> select();
		if(!empty($goodsList)){
			foreach ($goodsList as $key => &$value) {
				$goods = M('goods') -> where(array(
					'config_id' => 0,
					'status' => array('neq','2'),
					'cate_id' => $value['id'],
					))
					-> order('list_order asc')
					-> select();

				if(!empty($goods)){
					foreach ($goods as $k => &$v) {
						// 水票
						$goodsTicket = M('goods_strategy') -> where(array(
							'config_id' => 0,
							'goods_id' => $v['id'],
							'type' => 1,
							'status' => 0,
							))
							-> select();
						$v['ticket'] = $goodsTicket;
						// 套餐
						$goodsStrategy = M('goods_strategy')
							-> alias('s')
							-> join(C('DB_PREFIX').'goods_strategy_detail d on s.id = d.sid', 'left')
							-> field('s.*')
							-> where(array(
								's.config_id' => 0,
								's.type' => 2,
								's.status' => array('lt', '2'),
								'd.goods_id' => $v['id'],
								))
							-> order('s.list_order asc')
							-> select();
						if(!empty($goodsStrategy)){
							foreach ($goodsStrategy as $y => $e) {
								$goodsStrategy[$y]['detail'] = M('goods_strategy_detail')
									-> where(array(
										'sid' => $e['id'],
										))
									-> select();
							}
						}
						$v['strategy'] = $goodsStrategy;
					}
				}
				unset($v);
				$value['goods'] = $goods;
			}
			unset($value);
		}
		$this -> assign('goodsList', $goodsList);
		$this->display();
	}
	public function C_goodsWarehousePost(){
		if(IS_POST){
			$goods = I('post.goods') ? I('post.goods') : array();
			$goodsCate = I('post.cate') ? I('post.cate') : array();
			$ticket = I('post.ticket') ? I('post.ticket') : array();
			$strategy = I('post.strategy') ? I('post.strategy') : array();
			// 检查所选套餐是否选择普通商品和水票
			$strategyModel = M('goods_strategy');
			$detailModel = M('goods_strategy_detail');
			$goodsModel = M('goods');
			//(查询防止非套餐id)
			$newStrategy = array();
			if(!empty($strategy)){
				$goodsStrategy = $strategyModel -> field('id, goods_id') -> where(array('type' =>2 ,'config_id' => 0, 'id' => array('in', trim(implode(',', $strategy), ',')))) -> select();
				foreach ($goodsStrategy as $key => $value) {
					$strategyDetail = $detailModel -> where(array('sid' => $value['id'])) -> select();
					if(!empty($strategyDetail)){
						foreach ($strategyDetail as $key1 => $value1) {
							if($value1['type'] == 0){
								array_push($goods, $value1['goods_id']);
								$newStrategy[$key]['goods_id']=$value1['goods_id'];
								//$strategyGoods[$value1['goods_id']]['strategy'] = $value;
								//$strategyGoodsCateId = $goodsModel -> where(array('config_id' => 0, 'id' => $value1['goods_id'])) -> getField('cate_id');
								//$strategyData[$strategyGoodsCateId]['goods'] = $strategyGoods;
							}else if($value1['type'] == 1){
								array_push($ticket, $value1['goods_id']);
								$newStrategy[$key]['ticket_id']=$value1['goods_id'];
							}
						}
					}
					$newStrategy[$key]['id']=$value['id'];
				}				
			}
			// 拼装套餐添加信息
			// 检查所选水票对应商品(查询防止非套餐id)
			$newTicket = array();
			if(!empty($ticket)){
				$ticket = array_unique($ticket);
				$ticket = $strategyModel -> where(array('type' =>1 ,'config_id' => 0, 'id' => array('in', trim(implode(',', $ticket), ',')))) -> select();
				foreach ($ticket as $key => $value) {
					if(!in_array($value['goods_id'], $goods)){
						array_push($goods, $value['goods_id']);
					}
					$newTicket[$key] = $value;
				}
			}
			// 检查所选商品是否选择商品分类
			$newGoods = array();
			if(!empty($goods)){
				$goods = array_unique($goods);
				$goodsList = $goodsModel -> where(array('config_id' => 0, 'id' => array('in', trim(implode(',', $goods), ',')))) -> select();
				foreach ($goodsList as $key => $value) {
					if(!in_array($value['cate_id'], $goodsCate)){
						array_push($goodsCate, $value['cate_id']);
					}
					$newGoods[$key]= $value;
				}
			}
			// 检查商品分类
			$cateModel = M('goods_cate');
			$goodsCate = array_unique($goodsCate);
			$goodsCateData = $cateModel -> where(array('config_id' => 0, 'id' => array('in', trim(implode(',', $goodsCate), ',')))) -> select();
			if(!empty($goodsCateData)){
				foreach ($goodsCateData as $key => $value) {
					$data[$value['id']] = $value;
					$data[$value['id']]['goods'] = array();
				}
			}
			// 拼装goods信息
			foreach ($newGoods as $key => $value) {
				$ticketData = array();
				// 拼装ticket信息
				foreach ($newTicket as $k => $v) {
					if($value['id'] == $v['goods_id']){
						$ticketData[$v['id']] = $v;
						unset($newTicket[$k]);
					}
				}
				$data[$value['cate_id']]['goods'][$value['id']] = $value;
				$data[$value['cate_id']]['goods'][$value['id']]['ticket'] = $ticketData;
			}

			$transModel = M();
			$transModel -> startTrans();
			//循环添加数据，cate->goods->ticket  套餐参数(goods,ticket)肯定有 继续添加，不错验证
			try{
				foreach ($data as $key => $value) {
					$cateData = array(
						'config_id' => $_SESSION['CONFIG_ID'],
						'pid' => 0,
						'create_time' => date('Y-m-d H:i:s', time()),
						'name' => $value['name'],
						'status' => $value['status'],
						'list_order' => $value['list_order'],
						);
					$cateResId = $cateModel -> add($cateData);
					if(!$cateResId){
						$transModel -> rollback();
						throw new \Exception('添加商品分类失败');
					}
					if(!empty($value['goods'])){
						foreach ($value['goods'] as $goodsKey => $goodsValue) {
							$goodsData = array(
								'config_id' => $_SESSION['CONFIG_ID'],
								'cate_id' => $cateResId,
								'name' => $goodsValue['name'],
								'img' => $goodsValue['img'],
								'album' => $goodsValue['album'],
								'price' => $goodsValue['price'],
								'unit' => $goodsValue['unit'],
								'standard' => $goodsValue['standard'],
								'sales' => $goodsValue['sales'],
								'diy_sales' => $goodsValue['diy_sales'],
								'amount' => $goodsValue['amount'],
								'desc' => $goodsValue['desc'],
								'is_recommended' => $goodsValue['is_recommended'],
								'create_time' => date('Y-m-d H:i:s', time()),
								'source' => $goodsValue['source'],
								'is_allowticket' => $goodsValue['is_allowticket'],
								'start_time' => $goodsValue['start_time'],
								'end_time' => $goodsValue['end_time'],
								'list_order' => $goodsValue['list_order'],
								'status' => $goodsValue['status'],
								);
							$goodsResId = $goodsModel -> add($goodsData);
							if(!$goodsResId){
								$transModel -> rollback();
								throw new \Exception('添加商品失败');
							}
							// 拼装套餐普通商品
							foreach ($newStrategy as $newStrategyKey => &$newStrategyValue) {
								if($newStrategyValue['goods_id'] == $goodsValue['id']){
									$newStrategyValue['goods_id'] = $goodsResId;
								}
							}
							unset($newStrategyValue);
							if(!empty($goodsValue['ticket'])){
								foreach ($goodsValue['ticket'] as $ticketKey => $ticketValue) {
									$ticketData = array(
										'config_id' => $_SESSION['CONFIG_ID'],
										'goods_id' => $goodsResId,
										'type' => $ticketValue['type'],
										'name' => $ticketValue['name'],
										'price' => $ticketValue['price'],
										'status' => $ticketValue['status'],
										'list_order' => $ticketValue['list_order'],
										'start_time' => $ticketValue['start_time'],
										'end_time' => $ticketValue['end_time'],
										'condition' => $ticketValue['condition'],
										'goods_price' => $ticketValue['goods_price'],
										);
									$ticketResId = $strategyModel -> add($ticketData);
									if(!$ticketResId){
										$transModel -> rollback();
										throw new \Exception('添加水票失败');
									}
									$ticketDetail = $detailModel -> where(array('sid' => $ticketValue['id'])) -> find();
									$ticketDetailData = array(
										'sid' => $ticketResId,
										'goods_id' => $goodsResId,
										'num' => $ticketDetail['num'],
										'is_give' => $ticketDetail['is_give'],
										'givenum' => $ticketDetail['givenum'],
										'goods_name' => $ticketDetail['goods_name'],
										'goods_img' => $ticketDetail['goods_img'],
										'goods_price' => $ticketDetail['goods_price'],
										'type' => $ticketDetail['type'],
										);
									$ticketDetailResId = $detailModel -> add($ticketDetailData);
									if(!$ticketDetailResId){
										$transModel -> rollback();
										throw new \Exception('添加水票详情失败');
									}
									// 拼装套餐普通商品
									foreach ($newStrategy as $newStrategyKey => &$newStrategyValue) {
										if($newStrategyValue['ticket_id'] == $ticketValue['id']){
											$newStrategyValue['ticket_id'] = $ticketResId;
										}
									}
									unset($newStrategyValue);
								}
							}
						}
					}
				}
				// 循环插入套餐
				foreach ($newStrategy as $key => $value) {
					$strategy = $strategyModel -> find($value['id']);
					$strategyData = array(
						'config_id' => $_SESSION['CONFIG_ID'],
						'goods_id' => 0,
						'type' => 2,
						'name' => $strategy['name'],
						'price' => $strategy['price'],
						'status' => $strategy['status'],
						'list_order' => $strategy['list_order'],
						'start_time' => $strategy['start_time'],
						'end_time' => $strategy['end_time'],
						'condition' => $strategy['condition'],
						'goods_price' => $strategy['goods_price'],
						);
					$strategyResId = $strategyModel -> add($strategyData);
					if(!$strategyResId){
						$transModel -> rollback();
						throw new \Exception('添加套餐失败');
					}
					$strategyDetail = $detailModel -> where(array('sid' => $value['id'])) -> select();
					foreach ($strategyDetail as $k => $v) {
						$addData = array(
							'sid' => $strategyResId,
							'num' => $v['num'],
							'is_give' => $v['is_give'],
							'givenum' => $v['givenum'],
							'goods_name' => $v['goods_name'],
							'goods_img' => $v['goods_img'],
							'goods_price' => $v['goods_price'],
							);
						if($v['type'] == 0){
							$addData['goods_id'] = $value['goods_id'];
							$addData['type'] = 0;
						}else{
							$addData['goods_id'] = $value['ticket_id'];
							$addData['type'] = 1;
						}
						$detailResId = $detailModel -> add($addData);
						if(!$detailResId){
							$transModel -> rollback();
							throw new \Exception('添加套餐详情失败');
						}
					}

				}
			}catch(\Exception $e){
				$this->error("添加失败！");
			}
			$transModel -> commit();
			$this->success("添加成功！", U('Admin/Goods/C_goodsList'));
		}
	}

	public function C_goodsImageCut(){
		if(IS_AJAX){
			$base64 = I('post.base64');
			if(empty($base64)){
				$this -> ajaxReturn(array('info' => 2));
			}
			$base64_image_content = $_POST['base64'];
			//匹配出图片的格式
			if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
				$type = $result[2];
				$savepath=strtolower(MODULE_NAME).'/'.date('Ymd').'/';
				$new_file = C("UPLOADPATH").$savepath;
				if(!file_exists(SITE_PATH.$new_file))
				{
				//检查是否有该文件夹，如果没有就创建，并给予最高权限
				mkdir(SITE_PATH.$new_file, 0700);
				}
				$path = time().".".$type;
				$new_file = $new_file.$path;
				
				if(file_put_contents(SITE_PATH.$new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
					$this -> ajaxReturn(array('info' => 1, 'posturl' => $savepath.$path, 'showurl' => $new_file));
				}else{
					$this -> ajaxReturn(array('info' => 2));
				}
			}
		}
	}
}
?>