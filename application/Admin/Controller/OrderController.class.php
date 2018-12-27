<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Lib\Constant;
use Common\Lib\Wechat;
use Admin\Controller\WechaController;


class OrderController extends AdminbaseController{

    /**
     * 根据卡号查询该卡的相信信息
     */
    public function R_cardKeyword(){
        $card_no = trim(I('post.card_no'));
        if(empty($card_no)){
            echo json_encode(['msg' => '请输入要查询的卡号','status' => 500]);
        }
        $Card = M('oil_card');
        $card_info = $Card -> where( ['card_no' => $card_no] ) -> find ();
        if($card_info['status'] == '1'){
            $card_info['status'] = '库存';
        }else if($card_info['status'] == '2'){
            $card_info['status'] = '启用';
        }else{
            $card_info['status'] = '下架';
        }

        if( $card_info ){
            $str = '';
            $str.="<tr>
						<td style='width:40px;text-align:center;'>
							{$card_info['id']}
						</td>
						<td style='text-align:center;'>
							{$card_info['system_id']}
						</td>
						<td style='text-align:center;'>
							{$card_info['card_no']}
						</td>
						<td style='text-align:center;'>
							{$card_info['discount']}
						</td>
						<td style='text-align:center;' title=''>
							{$card_info['createtime']}
						</td>
						<td style='text-align:center;'>
							{$card_info['apply_fo_time']}
						</td>
						<td style='text-align:center;'>
						    {$card_info['status']}
						</td>
						<td style='width:300px; height: 53px; text-align:center;'>
							<button style='background: #2c3e50;border:2px; width: 70px; height: 40px;'  value='{$card_info['id']}' class='xiajia'><span style='color: white; font-size: 8px;'>下架</span></button>
							<button style='background: #2c3e50;border:2px; width: 70px; height: 40px;'  value='{$card_info['id']}' class='chongzhi'><span style='color: white; font-size: 8px;'>充值</span></button>
							<button style='background: #2c3e50;border:2px; width: 70px; height: 40px;'  value='{$card_info['id']}' class='tuika'><span style='color: white; font-size: 8px;'>退卡</span></button>
						</td>
					</tr>";
            $data['str'] = $str;
            $data['page'] = '';
            $this -> ajaxReturn( $data );
        }



    }

    /**
     * （支付-订单编号）关键字搜索
     */
    public function R_orderNumberKeyword(){
        $keyword = I('post.keyword');
        if( empty($keyword) ){
            echo json_encode(['msg' => '请输入要查询的订单编号','status' => 500]);exit;
        }
        $where = [
            'serial_number' => $keyword,
            'order_type' => 3
        ];
        $OrderRecordModel = M('order_record');
        $order_info = $OrderRecordModel -> where( $where ) ->find();
        if(empty($order_info)){
            echo json_encode(['msg' => '暂无数据','status' => 500]);exit;
        }

        $str = '';
        $str .="<tr>
                <td style='width:40px;text-align:center;'>
                    {$order_info['id']}
                </td>
                <td style=\"width:40px;text-align:center;\">
                    {$order_info['serial_number']}
                </td>
                <td style=\"text-align:center;\">
                    {$order_info['createtime']}
                </td>
                <td style=\"text-align:center;\">
                    {$order_info['card_no']}
                </td>
                <td style=\"text-align:center;\">
                    <font style=\"color:red\"></font>Admin
                </td>
                <td style=\"text-align:center;\">
                    <font style=\"color:red\">￥</font>{$order_info['money']}
                </td>
                <td style=\"text-align:center;\">
                    <font style=\"color:red\">￥</font>{$order_info['real_pay']}
                </td>
            </tr>";

        $data['str'] = $str;
        $data['page'] = '';
        echo json_encode($data);
    }

    /**
     * 支付列表
     */
    public function orderListing(){
        $OrderRecordModel = M('order_record');
        $p = trim(I('get.p','1'));
        $order_info = $OrderRecordModel -> where( 'order_type = 3 ' ) -> page($p,'10') ->select();
        if(empty($order_info)){
            echo '暂无数据';exit;
        }
        $count = $OrderRecordModel -> where('order_type = 3') -> count();
        $page = new \Think\Page($count,10);
        $show = $page -> show();
        $this -> assign('page',$show);
        $this -> assign('data',$order_info);
        $this -> display();
    }

    /**
     * 单卡折扣
     */
    public function discountThis(){
        $data = I('post.');
        $OilCardModel = M('oil_card');
        $card_info= $OilCardModel ->where(['id' => $data['discountid']])->find();
        if($card_info['discount'] == '95'){
            $result = $OilCardModel ->where(['id' => $data['discountid']])->save( ['discount' => 93] );
            echo json_encode([
                'msg' => 'success',
                'status' => 1000
            ]);
        }else{
            $result = $OilCardModel ->where(['id' => $data['discountid']])->save( ['discount' => 95] );
            echo json_encode([
                'msg' => 'success',
                'status' => 1000
            ]);
        }
    }

    /**
     * 批量折扣
     */
    public function discountMany(){
        $data = I('post.');
        $OilCardModel = M('oil_card');
        $OilCardModel -> startTrans();

        $flag=true;
        $res=true;
        for ($i=$data['start']; $i <=$data['over']; $i++) {
            $arr['card_no']=$i;
            $OilCardModel = M('oil_card');
            $card_data= $OilCardModel ->where("card_no='$i'")->find();
            if (empty($card_data)){
                $flag=false;
            }else{
                if ($card_data['status']==1){
                    $res=$OilCardModel ->where("card_no='$i'")->save(['discount'=>$data['discounts']]);
                    if ($res===false){
                        $res=false;
                    }
                }else{
                    $flag=false;
                }
            }
        }
        if ($res && $flag){
            $OilCardModel->commit();
            echo json_encode(['msg' => 'success','status' => 1000]);
        }else{
            $OilCardModel->rollback();
            echo json_encode(['msg' => 'success','status' => 1000]);
        }
    }

    public function addManyCard(){
        echo 123;exit;
    }

    /**
     * 批量导入油卡
     */
    public function addCard(){
        $data = I('post.');

        $flag=true;
        $arr=[];
        for ($i=$data['start_card_no']; $i <= $data['end_card_no']; $i++) {
            $arr['card_no']=$i;
            $arr['card_note']=$data['card_note'];
            $arr['discount']='96';
            $arr['createtime'] = date('Y-m-d H:i:s');
            $arr['system_id'] = 'SN'.rand(100000000,999999999);

            $res=M('oil_card')->add($arr);
            if (!$res) {
                $flag=false;
            }
        }
        if($flag===true){
            echo 123;
            echo json_encode([
                'msg'=>1000,
                'status'=>'油卡导入成功'
            ]);exit;
        }else{
            echo 234;
            echo json_encode([
                'msg'=>500,
                'status'=>'入库失败失败'
            ]);exit;
        }
    }

    /**
     * 给用户充值
     */
    public function rechargeMoney(){

        $data['card_no'] = I('post.card_no');
        $data['discount'] = '0.'.$discount = I('post.discount');
        $data['money'] = I('post.money');
        $data['message'] = I('post.message');
        //Array ( [card_no] => 1234567891011111 [discount] => 0.95 [money] => 2000 [message] => 鏄惁 )
        $after_discount = $data['money'] * $data['discount'];
        $discount_money = $data['money'] - $after_discount;

        $orderRecord = M('order_record');
        $order_record_data = $orderRecord -> where( ['card_no' => $data['card_no']] ) -> find();
        $save_where = [
            'money' => $order_record_data['money'] + $data['money'],
            'discount_money' => $order_record_data['discount_money'] + $discount_money,
            'real_pay' => $order_record_data['real_pay'] + $after_discount,
            'updatetime' => date('Y-m-d H:i:s')
        ];

        $result = $orderRecord -> where( ['card_no' => $data['card_no']] ) -> save( $save_where );
        if($result){
            $OilCardModel = M('oil_card');
            $oil_card_info = $OilCardModel -> where( ['card_no' => $data['card_no']] ) -> find();
            $result2 = $OilCardModel -> where( ['card_no' => $data['card_no']] ) -> save( ['card_total_add_money' => $oil_card_info['card_total_add_money'] + $data['money']] );
            if($result2){
                $callback_data = [
                    'card_no' => $data['card_no'],
                    'recharge' => $data['money'],
                    'expenditure' => $after_discount,
                    'infomation' => $data['message']
                ];

                $this -> ajaxReturn($callback_data);
            }
        }
    }

    /**
     * 下架
     */
    public function underGoods(){
        $id = trim( I('post.id') );
        $p = trim('get.p','1');
        $OilCardModel = M('oil_card');
        $result = $OilCardModel -> where( ['id' => $id] ) -> save(['status' => 3]);
        if( $result ){
            echo json_encode([
                'msg' => 'success',
                'status' => 1000
            ]);
        }else{
            $this -> error('修改失败');
        }
    }

    /**
     * 获取要充值的卡号
     */
    public function getCard(){
        $id = I('post.id');
        $OilCardModel = M('oil_card');
        $where = [ 'id' => $id ];
        $this_card = $OilCardModel -> where( $where ) -> find();
        $this -> ajaxReturn($this_card);
    }

    /**
     * 油卡列表信息
     */
    public function C_orderList(){
        $p = trim(I('get.p','1'));
        $Card = M('oil_card');
        $card_info = $Card -> where( 'is_sale=1' ) -> page($p,'10') ->select();

        $count = $Card -> where( 'is_sale=1' ) -> count();
        $page = new \Think\Page($count,10);
        $show = $page -> show();

        $this -> assign('page',$show);
        $this -> assign('data',$card_info);
        $this -> display();
          //        if($this->_isAdmin){
          //            $wechatConfigModel = M('wechat_config');
          //            $wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
          //            $this->assign('wechatconfig', $wechatConfig);
          //        }
          //        $where = '1=1';
          //        if(IS_POST){
          //            if(intval(I('post.status')) != -1) $where .= ' and o.order_status='.intval(I('post.status'));
          //            if(intval(I('post.pay_type')) != -1) $where .= ' and o.pay_type='.intval(I('post.pay_type'));
          //            if(intval(I('post.pay_status')) != -1) $where .= ' and o.pay_status='.intval(I('post.pay_status'));
          //
          //            if(0 != strlen(trim(I('post.start_time')))) $stime = strtotime(trim(I('post.start_time')).' 00:00:00');
          //            if(0 != strlen(trim(I('post.end_time')))) $etime = strtotime(trim(I('post.end_time')).' 23:59:59');
          //            if($stime) $where .= ' and o.create_time >= "'.$stime.'"';
          //            if($etime) $where .= ' and o.create_time <= "'.$etime.'"';
          //
          //            $keyword = trim(I('post.keyword'));
          //            // if(0 != strlen($keyword)) $where .= ' and (o.order_sn like "%'.$keyword.'%" or o.openid like "%'.$keyword.'%")';
          //            if($_SESSION['CONFIG_ID'] != 0){
          //                $where .= ' and o.config_id = '.$_SESSION['CONFIG_ID'];
          //            }else{
          //                if(intval(I('post.config')) != -1) $where .= ' and o.config_id = '.intval(I('post.config'));
          //            }
          //            $this->assign('where', $_POST);
          //        }
          //        if(IS_GET){
          //            $keyword = trim(I('get.openid'));
          //            if(0 != strlen($keyword)) $where .= ' and (o.openid like "%'.$keyword.'%")';
          //        }
          //        if($_SESSION['CONFIG_ID'] != 0){
          //            $where .= ' and o.config_id = '.$_SESSION['CONFIG_ID'];
          //        }
          //        $count = M('oil_card')->alias('o')
          //            ->field('o.*, u.nickname, u.headimgurl')
          //            ->join(C('DB_PREFIX').'wechat_user as u on u.openid=o.openid')
          //            ->where($where)
          //            ->count();
          //        $page = $this->page($count, $this->perpage);
          //
          //        // $where .= ' and (a.name like "%'.$keyword.'%")';// 姓名搜索
          //        // $where .= ' and (a.tel like "%'.$keyword.'%")';// 电话搜索
          //        // $where .= ' and (od.goods_name like "%'.$keyword.'%")';// 商品名搜索
          //        $where .= ' and (a.name like "%'.$keyword.'%" or a.tel like "%'.$keyword.'%" or od.goods_name like "%'.$keyword.'%" or o.order_sn like "%'.$keyword.'%")';
          //        // echo $where;die;
          //        $data['data'] = M('b2c_order')->alias('o')
          //            ->join(C('DB_PREFIX').'wechat_user_address as a on a.id=o.address_id')
          //            ->join(C('DB_PREFIX').'b2c_order_detail as od ON od.order_id=o.id')
          //            ->where($where)
          //            ->field('o.*,a.name,od.goods_name')
          //            ->limit($page->firstRow, $page->listRows)
          //            ->order('o.create_time desc')
          //            ->select();
          //
          //            // echo M('b2c_order')->getLastSql();
          //        // 加入微信服务号名称
          //        if($this->_isAdmin){
          //            foreach ($data['data'] as $key => &$value) {
          //                $value['wechat_name'] = M('wechat_config') -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
          //            }
          //            unset($value);
          //        }
          //        // echo "<pre>";
          //        // print_r($data);die;
          //        $data['show'] = $page->show('Admin');
          //        $this->assign('data', $data);

    }
    // 订单详情页
    public function orderDetail(){
        $id     = trim(I("get.oid",0,'intval'));
            if($this->_isAdmin){
                $wechatConfigModel = M('wechat_config');
                $wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
                $this->assign('wechatconfig', $wechatConfig);
            }
            $data = M('b2c_order')->alias('o')
                // ->join(C('DB_PREFIX').'wechat_user as u on u.openid=o.openid')
                ->join(C('DB_PREFIX').'wechat_user_address as a on a.id=o.address_id')
                ->join(C('DB_PREFIX').'b2c_order_detail as od ON od.order_id=o.id')
                ->where('o.id='.$id)
                ->field('o.*,a.name,a.tel,a.pcd,a.detail,od.goods_name')
                ->select();
                $data[0]['wechat_name'] = M('wechat_config') -> where(array('id' => $data[0]['config_id'])) -> getField('wechat_name');
            // echo "<pre>";
            // print_r($data);die;

            $this->assign('data', $data);
            $this->display();
    }

    public function C_addOrder()
    {
        $goods=M('goods')->select();
        $stations = M('station')->select();
        $arr = [];
        foreach($stations as &$val){
            array_push($arr, array(
                'desc' => strval(trim($val['desc'])),
                'range' => json_decode($val['range']),
                'station_id' => intval($val['id']),
            ));
        }
        unset($val);
        $this->assign('station',json_encode($arr));
        $this->assign('goods',$goods);
        $this->display();
    }

    public function doAddOrder()
    {
        if(IS_POST)
        {
            $order_sn = I('post.serverNum');
            $user = I('post.user');
            $tel = I('post.tel');
            $address = I('post.address');
            $num = I('post.nums');
            $good_id = I('post.commity');
            $detail=I('post.detail');
            $station=I('post.stations');
            $point=I('post.point');


            $commity = M('goods')->where('id='.$good_id)->find();
            $addressData = [
                'config_id'=>6,
                'name'=>$user,
                'tel'=>$tel,
                'pcd'=>$address,
                'detail'=>$detail,
                'station_id'=>$station,
                'gps'=>$point,
                'create_time'=>time(),
                'status'=>1
            ];
            $transModel = M();
            $transModel->startTrans();
            $address_id = M('wechat_user_address')->add($addressData);
            if($address_id > 0)
            {
                $orderData = [
                    'order_sn'=>$order_sn,
                    'station_id'=>$station,
                    'address_id'=>$address_id,
                    'config_id'=>6,
                    'order_price'=>$commity['price']*$num,
                    'order_original_price'=>$commity['price']*$num,
                    'pay_type'=>1,
                    'pay_status'=>1,
                    'pay_id' => '',
                    'bucket' => 0,
                    'type'=>0,
                    'order_status'=>0,
                    'create_time'=>time()
                ];
               $order_id =  M('b2c_order')->add($orderData);
               if($order_id>0)
               {
                   $detailData = [
                       'goods_id'=>$commity['id'],
                       'order_id'=>$order_id,
                       'goods_name'=>$commity['name'],
                       'goods_price'=>$commity['price'],
                       'goods_num'=>$num,
                       'sub_total'=>$commity['price']*$num,
                       'goods_img'=>$commity['img'],
                   ];
                   $detail_id = M('b2c_order_detail')->add($detailData);
                   $openids = M('boss')->where('config_id=6 and status=1')->field('openid')->select();
                   if($detail_id >0)
                   {
                       $deliver = new WechaController();
                       foreach ($openids as $val)
                       {
                           $deliver->_sendWechatBossOrderTplMsg('6',$val['openid'],$orderData,$detailData);
                       }
                       $transModel->commit();
                       $this->success('录入成功',U('C_addOrder'));
                   }else{
                       $transModel->rollback();
                       $this->error('录入失败',U('C_addOrder'));
                   }
               }else{
                   $transModel->rollback();
                   $this->error('添加订单失败',U('C_addOrder'));
               }
            }else{
                $transModel->rollback();
                $this->error('添加地址失败',U('C_addOrder'));
            }
        }
    }



    /**
     * 退卡操作
     */
    public function withdrawCard(){

        $card_id = I('post.discountid','');

        $card_no = M('oil_card')->where(['id' => $card_id ]) -> getField('card_no');

        if (empty($card_no)){
            echo json_encode([
                'status'=>500,
                'msg'=>'error',
                'data'=>'未选中卡号'
            ]);
        }
        $order_data=[
            'card_no'=>$card_no,
            'order_type'=>'4',
        ];
        M('order_record')->add($order_data);

        $user_id=M('oil_card')->where("card_no='$card_no'")->getField('user_id');
        $openid=M('user')->where("id='$user_id'")->getField('openid');
        $deposit=M('agent')->where("openid='$openid'")->getField('deposit');
        M('agent')->where("openid='$openid'")->save(['deposit'=>(string)$deposit-(string)20]);

        $res=M('oil_card')->where("card_no='$card_no'")->save(['is_sale'=>'3']);
        if ($res!==false){
            echo json_encode([
                'status'=>1000,
                'msg'=>'success',
                'data'=>'退卡成功'
            ]);
        }else{
            echo json_encode([
                'status'=>500,
                'msg'=>'error',
                'data'=>'退卡失败啦'
            ]);
        }

    }

}