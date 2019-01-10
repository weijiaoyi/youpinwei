<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Controller\ExcelController;
class DeliverController extends AdminbaseController{


    /**
     * 订单列表信息
     */
	public function C_deliverList(){

        $p = trim(I('get.p','1'));
        $keyword = trim(I('post.keyword'));
        $Order = M('order_record');
        $Apply = M('user_apply');
        $where = [
            'R.order_type' =>1 ,
            'R.order_status' =>2,
        ];
        $timeRange = trim(I('timeRange',''));
        if ($timeRange) {
            $timeRange = explode(' - ', $timeRange);
            $where['R.createtime'] = ['between',[$timeRange[0],$timeRange[1]]];
        }
        if (!empty($keyword)) {
            $where['R.card_no'] = ['like','%'.$keyword.'%'];
        }
        $order_info = $Apply
            ->alias('A')
            ->field('R.*,A.id as apply_id,A.status,U.nickname,U.user_img')
            ->join('__ORDER_RECORD__ R ON A.serial_number=R.serial_number',LEFT)
            ->join('user U ON U.id=R.user_id',LEFT)
            ->where($where)
            ->order('R.id DESC')
            ->page($p,'10')
            ->select();

        foreach( $order_info as $k => $v ){
            if( $v['order_type'] == '1' ){
                $order_info[$k]['order_type_message'] = '已申领';
            }else {
                $order_info[$k]['order_type_message'] = '已绑定';
            }
            if( $v['status'] == '1' ){
                $order_info[$k]['send_status'] = '待发货';
            }else if( $v['status'] == '2' ){
                $order_info[$k]['send_status'] = '已发货';
            }else {
                $order_info[$k]['send_status'] = '已绑定';
            }

            if( $v['send_card_no'] == '' ){
                $order_info[$k]['send_card_no_message'] = '等待代理购买油卡';
            }
            if( $v['card_no'] == '' ){
                $order_info[$k]['card_no_message'] = '未绑定';
            }
            if($v['agent_id'] != 0){
                $agentInfo=M('user')->where('id="'.$v["agent_id"].'"')->field('nickname,user_img')->find();
                $order_info[$k]['agent_id_message'] = $agentInfo['nickname'];
                $order_info[$k]['agent_img'] = $agentInfo['user_img'];
            }else{
                $order_info[$k]['agent_id_message'] = '总部';
            }


        }
        $count = $Apply
            ->alias('A')
            ->join('__ORDER_RECORD__ R ON A.serial_number=R.serial_number',LEFT)
            ->join('__USER__ U ON U.id=R.user_id',LEFT)
            ->where($where)
            -> count();
        $Page = new \Think\Page($count,10);
        $show = $Page -> show();
        $this->assign('keyword',$keyword);
        $this -> assign('page',$show);
        $this -> assign('data',$order_info);
        $this -> display();

	}

    /**
     * 油卡绑定列表
     * @Author 老王
     * @创建时间   2019-01-08
     */
    public function CardBindList(){
        $p = trim(I('get.p','1'));
        $keyword = trim(I('keyword'));
        $online = intval(I('online'),0);
        $Order = M('order_record');
        $Apply = M('user_apply');
        $where = [
            'R.order_type' =>2 ,
            'R.order_status' =>2,
            'A.status' =>3
        ];
        $timeRange = trim(I('timeRange',''));
        if ($timeRange) {
            $timeRange = explode(' - ', $timeRange);
            $where['R.createtime'] = ['between',[$timeRange[0],$timeRange[1]]];
        }
        if (!empty($keyword)) {
            $where['R.card_no'] = ['like','%'.$keyword.'%'];
        }
        if ($online) {
            $where['R.online'] = $online;
            $this->assign('online',$online);

        }
        $order_info = $Apply
            ->alias('A')
            ->field('R.*,A.id as apply_id,A.status,U.nickname,U.user_img,UA.nickname as agent_nickname,UA.user_img as agent_user_img')
            ->join('__ORDER_RECORD__ R ON A.serial_number=R.serial_number',LEFT)
            ->join('user U ON U.id=R.user_id',LEFT)
            ->join('user UA ON UA.id=R.agent_id')
            ->where($where)
            ->order('R.id DESC')
            ->page($p,'10')
            ->select();
        
        $count = $Apply
            ->alias('A')
            ->join('__ORDER_RECORD__ R ON A.serial_number=R.serial_number',LEFT)
            ->join('__USER__ U ON U.id=R.user_id',LEFT)
            ->join('user UA ON UA.id=R.agent_id',LEFT)
            ->where($where)
            -> count();
        $Page = new \Think\Page($count,10);
        $show = $Page ->show();
        $this->assign('keyword',$keyword);
        $this -> assign('page',$show);
        $this -> assign('data',$order_info);
        $this->display();
    }

    /**
     * 油卡升级列表
     * @Author 老王
     * @创建时间   2019-01-08
     */
    public function UpGradeList(){
        $p = trim(I('get.p','1'));
        $keyword = trim(I('post.keyword'));
        $Order = M('order_record');
        $Apply = M('user_apply');
        $where = [
            'R.order_type' => 4,
            'R.order_status' =>2,
        ];
        if (!empty($keyword)) {
            $where['R.card_no'] = ['like','%'.$keyword.'%'];
        }
        $timeRange = trim(I('timeRange',''));
        if ($timeRange) {
            $timeRange = explode(' - ', $timeRange);
            $where['R.createtime'] = ['between',[$timeRange[0],$timeRange[1]]];
        }
        $M = M('order_record');
        $OrderList = $M
            ->alias('R')
            ->field('R.*,U.nickname,U.user_img')
            ->join('__USER__ U ON U.id=R.user_id',LEFT)
            ->where($where)
            ->order('R.id DESC')
            ->page($p,'10')
            ->select();
        foreach( $order_info as $k => $v ){
            

        }
        $count = $M
            ->alias('R')
            ->field('R.*,U.nickname,U.user_img')
            ->join('__USER__ U ON U.id=R.user_id',LEFT)
            ->where($where)
            -> count();
        $Page = new \Think\Page($count,10);
        $show = $Page -> show();
        $this->assign('keyword',$keyword);
        $this -> assign('page',$show);
        $this -> assign('data',$OrderList);
        $this->display();
    }

    /**
     * 油卡续费
     * @Author 老王
     * @创建时间   2019-01-08
     */
    public function RenewalsList(){
        $p = trim(I('get.p','1'));
        $keyword = trim(I('post.keyword'));
        $Order = M('order_record');
        $Apply = M('user_apply');
        $where = [
            'R.order_type' => 5,
            'R.order_status' =>2,
        ];
        if (!empty($keyword)) {
            $where['R.card_no'] = ['like','%'.$keyword.'%'];
        }
        $timeRange = trim(I('timeRange',''));
        if ($timeRange) {
            $timeRange = explode(' - ', $timeRange);
            $where['R.createtime'] = ['between',[$timeRange[0],$timeRange[1]]];
        }
        $M = M('order_record');
        $OrderList = $M
            ->alias('R')
            ->field('R.*,U.nickname,U.user_img')
            ->join('__USER__ U ON U.id=R.user_id',LEFT)
            ->where($where)
            ->order('R.id DESC')
            ->page($p,'10')
            ->select();
        foreach( $order_info as $k => $v ){
            

        }
        $count = $M
            ->alias('R')
            ->field('R.*,U.nickname,U.user_img')
            ->join('__USER__ U ON U.id=R.user_id',LEFT)
            ->where($where)
            -> count();
        $Page = new \Think\Page($count,10);
        $show = $Page -> show();
        $this->assign('keyword',$keyword);
        $this -> assign('page',$show);
        $this -> assign('data',$OrderList);
        $this->display();
    }

	public function createExcel() {
		header("content-type:text/html;charset=gb2312");
		$excel_obj = new ExcelController();
		$excel_data = array();
		$data=M('order_record')->where( ['order_type' => 3])->select();
		//设置样式
		$excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
		//header

		$excel_data[0][] = array('styleid' => 's_title', 'data' => "订单ID");
		$excel_data[0][] = array('styleid' => 's_title', 'data' => "油卡");
		$excel_data[0][] = array('styleid' => 's_title', 'data' => "时间");
		$excel_data[0][] = array('styleid' => 's_title', 'data' => "分类");
		$excel_data[0][] = array('styleid' => 's_title', 'data' => "充值金额");
		$excel_data[0][] = array('styleid' => 's_title', 'data' => "支付金额");
		$excel_data[0][] = array('styleid' => 's_title', 'data' => "优惠金额");
		//data
		foreach ((array) $data as $k => $v) {
			$tmp = array();
			$tmp[] = array('data' => ($v['serial_number']));
			$tmp[] = array('data' =>($v['card_no']));
			$tmp[] = array('data' => $v['createtime']);
			$tmp[] = array('data' => $v['order_type']==1?'绑定':'充值');
			$tmp[] = array('data' => $v['money']);
			$tmp[] = array('data' => $v['real_pay']);
			$tmp[] = array('data' => $v['discount_money']);
			$excel_data[] = $tmp;
		}
		define('CHARSET','UTF-8');
		$excel_data = $excel_obj->charset($excel_data, CHARSET);
		$excel_obj->addArray($excel_data);
		$excel_obj->addWorksheet($excel_obj->charset("订单信息", CHARSET));
		$excel_obj->generateXML($excel_obj->charset("订单信息", CHARSET) . '-' . date('Y-m-d-H', time()));
	}
    /**
     * 油卡发货页面
     */
	public function C_deliverDetail(){
        /*$data['id'] = I('post.id');
        $data['uid'] = I('post.uid');
        $data['card_no'] = I('post.card_no');*/
        $order_id = I('post.order_id');

        # 查询卡信息（发货）
        $orderRecordModel = M('order_record');
//        $where = ['user_id' => $data['uid']];
//        $card_find = $ApplyModel -> where( $where ) -> find();
//        if( $card_find['shop_name'] == 1 ){ $card_find['shop_name'] = '中石油加油卡'; }
        $orderInfo = $orderRecordModel
            ->alias('a')
            ->join('user_apply u ON u.serial_number=a.serial_number AND u.status=1',LEFT)
            ->field('a.*,u.id as apply_id,u.phone,u.receive_person,u.address')
            ->where('a.id="'.$order_id.'" AND a.order_status=2')
            ->find();
        if($orderInfo){
            $data=[
                'send_card_no' => $orderInfo['send_card_no'],
                'user_name' => $orderInfo['receive_person'],
                'mobile' => $orderInfo['phone'],
                'address' => $orderInfo['address'],
                'serial_number'=>$orderInfo['serial_number'],//订单编号
                'order_id'=>$orderInfo['id'],//订单ID
            ];
            echo json_encode(array('status'=>200,'message'=>'准备发货发货','data'=>$data));exit;
        }else{
            echo json_encode(array('status'=>100,'message'=>'订单已发货或不存在'));exit;
        }


    }

    public function C_deliverSendGoods(){

	    $data['order_number'] = I('post.number');
	    $data['card_no'] = I('post.card_no');

	    $UserApplyModel = M('user_apply');
	    $where = [
	        'card_no' => $data['card_no']
        ];
	    $user_apply_find = $UserApplyModel -> where( $where ) -> find();

	    $result = $UserApplyModel -> where( $where ) -> save( ['express_number' => $data['order_number']] );
        if($result){
            echo json_encode('1000');
        }
	}

    /**
     * 确认发货
     */
	public function C_deliverEnterSend(){
//	    $data = I('post.');
//	    $data1['card_no'] = I('post.card_no');
//	    $data1['user_id'] = I('post.user_id');
        $order_id = I('post.order_id');
        $express_number = I('post.express_number');
        $serial_number = I('post.serial_number');
        # 查询订单信息（确认发货）
        $orderRecordModel=M('order_record');
        $userApplyModel = M('user_apply');

        $res = $orderRecordModel->where('id="'.$order_id.'" AND serial_number="'.$serial_number.'"')->find();
        if($res){
            $result = $userApplyModel->where('serial_number="'.$serial_number.'" AND status=1')->find();
            p($result);exit;
            if($result){
                    $update_apply = $userApplyModel->where('id="'.$order_id.'" AND serial_number="'.$serial_number.'"')->save(array('express_number'=>$express_number,'status'=>2,'updatetime'=>date('Y-m-d H:i:s',time())));
                    if($update_apply){
                        echo json_encode(array('status'=>200,'message'=>'发货成功'));exit;
                    }else{
                        echo json_encode(array('status'=>100,'message'=>'发货失败'));exit;
                    }
            }else{
                echo json_encode(array('status'=>100,'message'=>'订单已发货，重复操作'));exit;
            }
        }else{
            echo json_encode(array('status'=>100,'message'=>'订单不存在'));exit;
        }

    }

    //推送消息
    public function C_deliverPushMessage(){

        $data1['card_no'] = I('post.card_no');
        $data1['user_id'] = I('post.user_id');
        # 获取用户的openid
        $User = M('user');
        $user_info = $User -> where( ['id' => $data1['user_id']] ) -> find();

        # 查询卡信息（确认发货）
        $ApplyModel = M('user_apply');
        $where = ['card_no' => $data1['card_no']];
        $card_find = $ApplyModel -> where( $where ) -> find();

        # 查询该卡拥有几折优惠
        $OilCardModel = M('oil_card');
        $discount_data = $OilCardModel -> where( $where ) -> find();

        # 查询该卡充值金额、优惠金额、实付金额
        $OrderRecordModel = M('order_record');

        $OrderRecord_data = $OrderRecordModel -> where( $where ) -> find();

        $push_data = [
            'card_names' => $card_find['shop_name'],#商品名称（中石油卡）
            'card_number' => $OrderRecord_data['card_no'],#卡号
            'recharge_money' => $OrderRecord_data['money'],#充值金额
            'consignee_name' => $card_find['receive_person'],#收货人名称
            'consignee_phone' => $card_find['phone'],#收获人联系方式
            'consignee_address' => $card_find['address'],#收获地址
            'courier_company' => $card_find['courier_company'],#快递（单号）
            'serial_number' => $card_find['express_number']
        ];


        $a=new \Oilcard\Controller\WechatController;
        $res=$a->templateMessage($user_info['openid'],$push_data,5);
        echo json_encode($res);

        //print_R($data);exit;
        //$this -> ajaxReturn($data);
    }

    public function C_editDeliver(){
		$id = I('get.did', 0, 'intval');
		if($id <= 0) $this->error('访问错误');

		$where = array('id' => $id, 'status' => array('lt', '2'));
		if($_SESSION['CONFIG_ID'] != 0) $where['config_id'] = $_SESSION['CONFIG_ID'];
		$deliver = M('station_deliver') -> where($where) -> find();
		if(empty($deliver)) $this->error('访问的水工不存在');

		$this -> assign('deliver', $deliver);
		$this -> display();
	}

	public function C_editDeliverPost(){
		$name = I('post.name', '', 'trim');
		if(0 == strlen($name)){
			$this->error('水工姓名不能为空！');
		}
		$tel = I('post.tel', 0);
		if(empty($tel)) $this->error('水工电话不能为空！');
		if(!preg_match("/^1[34578]{1}\d{9}$/",strval($tel))) $this->error('水工电话格式不正确！');
		$configId = I('post.config_id', 0, 'intval');
		$deliverId = I('post.id', 0, 'intval');
		$deliverData = array(
			'id' => $deliverId,
			'config_id' => $configId,
			'name' => $name,
			'tel' => $tel,
		);

		
		if(M('station_deliver')->save($deliverData) === false){
			$this->error('系统错误，更新水工信息失败！');
		}else{
			$this->success('更新水工信息成功！');
		}
	}

	/**
	 * 禁用水工
	 * @return string json
	 */	
	public function C_closeDeliver(){
		$deliverModel = M('station_deliver');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$saveDeliver = $deliverModel->where($where)->save(array('status'=>0));
		if(false !== $saveDeliver){
			$this->success('禁用水工成功！');
		}else{
			$this->error('禁用水工失败！');
		}
	}

	/**
	 * 启用水工
	 * @return string json
	 */	
	public function C_openDeliver(){
		$deliverModel = M('station_deliver');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$saveDeliver = $deliverModel->where($where)->save(array('status'=>1));
		if(false !== $saveDeliver){
			$this->success('启用水工成功！');
		}else{
			$this->error('启用水工失败！');
		}
	}

	/**
	 * 删除水工
	 * @return string json
	 */	
	public function C_deleteDeliver(){
		$deliverModel = M('station_deliver');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		// 软删除，有影响，改为硬删
		// $saveDeliver = $deliverModel->where($where)->save(array('status'=>2));
		$saveDeliver = $deliverModel->where($where)->delete();
		if(false !== $saveDeliver){
			$this->success('删除水工成功！');
		}else{
			$this->error('删除水工失败！');
		}
	}




    public function inportExcel(){

        $title = ['订单号','申请人微信昵称','姓名','手机号','联系地址','应发卡号','应发货人','支付金额','邮费','押金'];
        $field ="R.serial_number,U.nickname,A.receive_person,A.phone,A.address,R.send_card_no,G.nickname as agent_nickname,R.real_pay,R.postage,R.user_deposit,A.id as aid,R.id as rid";
        $where = [
            'R.order_type' =>1,
            'R.order_status' =>2,
            'R.applyfinish' =>1,
            'A.status' =>1
        ];
        $timeRange = trim(I('timeRange',''));
        if ($timeRange) {
            $timeRange = explode(' - ', $timeRange);
            $where['R.createtime'] = ['between',[$timeRange[0],$timeRange[1]]];
        }
        $data = M('order_record')
                ->alias('R')
                ->join('__USER_APPLY__ as A ON A.serial_number=R.serial_number','LEFT')
                ->join('__USER__ as U ON U.id=R.user_id','LEFT')
                ->join('__USER__ as G ON G.id=R.agent_id','LEFT')
                ->where($where)
                ->field($field)
                ->order('R.id desc')
                ->select();
        $aids = '';
        $rids = '';
        if($data)foreach ($data as $key => $value) {
            if(empty($data[$key]['agent_nickname']))$data[$key]['agent_nickname']='总部发卡';
            $aids .= $value['aid'].',';
            $rids .= $value['rid'].',';
            unset($data[$key]['aid']);
            unset($data[$key]['rid']);

        }
        $aids = trim($aids,',');
        $rids = trim($rids,',');
        $res = false;
        $res = M('user_apply')->where(['id'=>['in',$aids]])->save(['status'=>2]);
        $res = M('order_record')->where(['id'=>['in',$rids]])->save(['is_import'=>2]);
        if($data)inportExcelLog($data,1,'发货记录导出');
        createExcel($title,$data,'订单Excel');
        echo 1;
        exit;
        echo 111;exit;
    }
}