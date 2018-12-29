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


    /**
     * 批量导入油卡
     */
    public function addCard(){
        $data = I('post.');
        $flag=true;
        $arr=[];
        $install = [];
        for ($i=$data['start_card_no']; $i <= $data['end_card_no']; $i++) {
            $arr['card_no']=$i;
            $arr['card_note']=$data['card_note'];
            // $arr['discount']='96';updatetime
            $arr['createtime'] = date('Y-m-d H:i:s');
            $arr['updatetime'] = date('Y-m-d H:i:s');
            $install[] =$arr;
        }
        $res=M('oil_card')->addAll($install);
        if($flag===true){
            echo json_encode([
                'msg'=>1000,
                'status'=>'油卡导入成功'
            ]);exit;
        }else{
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
     * 系统禁用油卡
     * @Author 老王
     * @创建时间   2018-12-29
     */
    public function ProhibitCard(){
        $id = trim( I('post.id') );
        $desc = trim( I('post.desc') );
        $time = time();
        $OilCardModel = M('oil_card');
        $result = $OilCardModel -> where( ['id' => $id] ) -> save(['is_notmal' => 2]);
        $uid = $OilCardModel->where( ['id' => $id] ) -> getField('user_id');

        if ($result) {
          //添加到禁用日志
          $addLog = [
            'userid'     => $uid?:0,
            'cardid'     => $id,
            'addtime'    => $time,
            'updatetime' => $time,
            'desc'       => !empty($desc)?:'系统禁用',
            'type'       => 2,
            'status'     => 1,
            'adminid'    => session('ADMIN_ID'),
          ];
          M('oil_option_log')->add($addLog);
          echo json_encode([
                'msg' => 'success',
                'status' => 1000
            ]);
        }else{
            $this -> error('修改失败');
        }
    }

    /**
     * 系统启用油卡
     * @Author 老王
     * @创建时间   2018-12-29
     * @return [type]     [description]
     */
    public function deProhibitCard(){
        $id = trim( I('post.id') );
        $desc = trim( I('post.desc') );
        $time = time();
        $OilCardModel = M('oil_card');
        $result = $OilCardModel -> where( ['id' => $id] ) -> save(['is_notmal' => 1]);
        $uid = $OilCardModel->where( ['id' => $id] ) -> getField('user_id');

        if ($result) {
          //添加到禁用日志
          $addLog = [
            'userid'     => $uid?:0,
            'cardid'     => $id,
            'addtime'    => TIMESTAMP,
            'updatetime' => TIMESTAMP,
            'desc'       => !empty($desc)?:'系统启用',
            'type'       => 2,
            'status'     => 1,
            'adminid'    => session('ADMIN_ID'),
          ];
          M('oil_option_log')->add($addLog);
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
        $card_info = M('oil_card') 
              ->alias('o')
              ->join('__PACKAGES__ p ON o.pkgid = p.pid','LEFT')
              ->join('__USER__ u ON o.user_id = u.id','LEFT')
              ->field('o.*,p.pid,p.price,p.limits,p.scale,u.nickname,u.user_img')
              ->order('o.id desc')-> page($p,'10') ->select();
        $count = $Card -> count();
        $page = new \Think\Page($count,10);
        $show = $page -> show();
        $this -> assign('page',$show);
        $this -> assign('data',$card_info);
        $this -> display();
    }

    /**
     * 退卡申请列表
     * @Author 老王
     * @创建时间   2018-12-29
     */
    public function CardBack(){
        $p = trim(I('get.p','1'));
        $Card = M('oil_option');

        $card_info = $Card 
              ->alias('o')
              ->join('__OIL_CARD__ p ON o.cardid = p.id','LEFT')
              ->join('__USER__ u ON u.id = o.userid','LEFT')
              ->field('o.*,p.id,p.card_no,u.nickname,u.user_img')
              ->where( 'p.is_notmal=2 and o.type=1' ) ->order('o.id desc')-> page($p,'10') ->select();
        $count = $Card -> where( 'type=1' ) -> count();
        $page = new \Think\Page($count,10);
        $show = $page -> show();

        $this -> assign('page',$show);
        $this -> assign('data',$card_info);
        $this -> display();
    }

    /**
     * 挂失申请列表
     * @Author 老王
     * @创建时间   2018-12-29
     */
    public function CardLoss(){
        $p = trim(I('get.p','1'));
        $Card = M('oil_option');
        $card_info = $Card 
              ->alias('o')
              ->join('__OIL_CARD__ p ON o.cardid = p.id','LEFT')
              ->join('__USER__ u ON u.id = o.userid','LEFT')
              ->field('o.*,p.id,p.card_no,u.nickname,u.user_img')
              ->where( 'p.is_notmal=2 and o.type=2' ) ->order('o.id desc')-> page($p,'10') ->select();
        $count = $Card -> where( 'type=2' ) -> count();
        $page = new \Think\Page($count,10);
        $show = $page -> show();

        $this -> assign('page',$show);
        $this -> assign('data',$card_info);
        $this -> display();
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