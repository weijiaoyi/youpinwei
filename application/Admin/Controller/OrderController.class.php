<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Lib\Constant;
use Common\Lib\Wechat;
use Admin\Controller\WechaController;


class OrderController extends AdminbaseController{


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
        $keyword = trim(I('post.keyword'));
        $where='o.order_type = 3 AND o.order_status = 2 ';
        if(!empty($keyword)){
            $where.=' AND (a.card_no LIKE "%'.$keyword.'%" OR o.serial_number LIKE "%'.$keyword.'%")';
        }
        $order_info=$OrderRecordModel
            ->alias('o')
            ->join('add_money a ON a.order_no=o.serial_number',LEFT)
            ->join('user u ON u.id=o.user_id',LEFT)
            ->join('agent_earnings e ON e.sn=o.serial_number AND e.log_type=1',LEFT)
            ->join('user us ON us.id=o.agent_id',LEFT)
            ->field('o.id,o.user_id,o.serial_number,o.order_type,o.order_status,o.coupon_money,o.discount_money as zk_money,o.is_import,a.*,e.earnings,u.nickname,u.user_img,us.nickname as agent_name,us.user_img as agent_img')
            ->where($where)
            ->order('o.id DESC')
            -> page($p,'10')
            ->select();
//        echo '<pre>';var_dump($order_info);exit;
       /* $order_info = $OrderRecordModel
            -> where( 'order_type = 3 ' )
            -> page($p,'10')
            ->select();
        if(empty($order_info)){
            echo '暂无数据';exit;
        }*/
        $count = $OrderRecordModel
            ->alias('o')
            ->join('add_money a ON a.order_no=o.serial_number',LEFT)
            ->join('user u ON u.id=o.user_id',LEFT)
            ->join('agent_earnings e ON e.sn=o.serial_number AND e.log_type=1',LEFT)
            ->join('user us ON us.id=o.agent_id',LEFT)
            ->field('o.id,o.user_id,o.serial_number,o.order_type,o.order_status,o.coupon_money,o.discount_money as zk_money,a.*,u.nickname,u.user_img,us.nickname as agent_name,us.user_img as agent_img')
            ->where($where)
            -> count();
        $page = new \Think\Page($count,10);
        $show = $page -> show();
        $this->assign('keyword',$keyword);
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
        $start_card_no = I('post.start_card_no');
        $end_card_no = I('post.end_card_no');
        $card_note = I('post.card_note');
        $flag=true;
        $arr=[];
        $install = [];
        $end = ($end_card_no - $start_card_no) +1 ;
        $time = date('Y-m-d H:i:s');
        for ($i=0; $i < $end; $i++) { 
            $No = bigDataAdd($start_card_no,(string)$i);
            $arr['card_no'] =$No;
            $arr['card_note'] =$card_note;
            $arr['createtime'] =$time;
            $arr['updatetime'] =$time;
            $install[] =$arr;
        }
        $res=M('oil_card')->addAll($install);
        if($res){
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
        $OilCardModel = M('oil_card');
        $result = $OilCardModel -> where( ['id' => $id] ) -> save(['is_notmal' => 2,'desc'=>!empty($desc)?:'系统禁用']);
        $uid = $OilCardModel->where( ['id' => $id] ) -> getField('user_id');

        if ($result) {
          //添加到禁用日志
          $addLog = [
            'userid'     => $uid?:0,
            'cardid'     => $id,
            'addtime'    => TIMESTAMP,
            'updatetime' => TIMESTAMP,
            'desc'       => !empty($desc)?:'系统禁用',
            'type'       => 3,
            'hand'       => 2,
            'status'     => 1,
            'adminid'    => session('ADMIN_ID'),
          ];
          M('oil_option')->add($addLog);
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
        $OilCardModel = M('oil_card');
        $result = $OilCardModel -> where( ['id' => $id] ) -> save(['is_notmal' => 1,'desc'=>!empty($desc)?:'系统启用']);
        $uid = $OilCardModel->where( ['id' => $id] ) -> getField('user_id');

        if ($result) {
          //添加到禁用日志
          $addLog = [
            'userid'     => $uid?:0,
            'cardid'     => $id,
            'addtime'    => TIMESTAMP,
            'updatetime' => TIMESTAMP,
            'desc'       => !empty($desc)?:'系统启用',
            'type'       => 3,
            'hand'       => 2,
            'status'     => 1,
            'adminid'    => session('ADMIN_ID'),
          ];
          M('oil_option')->add($addLog);
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
        $card_no = trim(I('post.card_no',''));
        $Card = M('oil_card');
        $where = [];
        $condition=[];
        if (!empty($card_no)) {
          $where['o.card_no'] = ['LIKE','%'.$card_no.'%'];
          $condition = ['card_no'=>$card_no];
        }
        $card_info = M('oil_card') 
              ->alias('o')
              ->join('__PACKAGES__ p ON o.pkgid = p.pid','LEFT')
              ->join('__USER__ u ON o.user_id = u.id','LEFT')
              ->field('o.*,p.pid,p.price,p.limits,p.scale,u.nickname,u.user_img')
              ->where($where)
              ->order('o.id desc')
            ->page($p,'10')
            ->select();
        $count = $Card ->where($condition)-> count();
        $page = new \Think\Page($count,10);
        $show = $page -> show();
        //查询总部可发油卡数量
        $card_id=$Card -> where('agent_id != 0')->order('id desc') -> getField('id');
        $send_count = $Card -> where('id>"'.$card_id.'" AND status=1') -> count();
        $this -> assign('send_count',$send_count);
        $this -> assign('page',$show);
        // p($card_info);
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
              ->join('__OIL_CARD__ p ON o.cardid = p.id',LEFT)
              ->join('__USER__ u ON u.id = o.userid',LEFT)
              ->field('o.*,p.card_no,u.nickname,u.user_img')
              ->where( 'p.is_notmal=2 and o.type=1' )
              ->order('o.id desc')
              -> page($p,'10')
              ->select();
        $count = $Card
            ->alias('o')
            ->join('__OIL_CARD__ p ON o.cardid = p.id','LEFT')
            ->join('__USER__ u ON u.id = o.userid','LEFT')
            ->field('o.*,p.card_no,u.nickname,u.user_img')
            ->where( 'p.is_notmal=2 and o.type=1' )
            -> count();
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
              ->field('o.*,p.card_no,u.nickname,u.user_img')
              ->where( 'p.is_notmal=2 and o.type=2' )
            ->order('o.id desc')
            -> page($p,'10')
            ->select();
        $count = $Card
            ->alias('o')
            ->join('__OIL_CARD__ p ON o.cardid = p.id','LEFT')
            ->join('__USER__ u ON u.id = o.userid','LEFT')
            ->field('o.*,p.card_no,u.nickname,u.user_img')
            ->where( 'p.is_notmal=2 and o.type=2' )
            -> count();
        $page = new \Think\Page($count,10);
        $show = $page -> show();

        $this -> assign('page',$show);
        $this -> assign('data',$card_info);
        $this -> display();
    }

    /**
     * @author langzhiyao
     * @desc 处理退卡/挂失
     * @time20190109
     */
    public function HandleCard(){
        $id = trim(I('post.id'));
        if(!empty($id)){
            //查询记录
            $result = M('oil_option')->where('id="'.$id.'"')->find();
            if(!empty($result)){
                if($result['status'] == 1){
                    echo json_encode(array('status'=>100,'message'=>'该记录已处理，无需重复操作'));exit;
                }else{
                    $data=array(
                        'status'=>1,
                        'updatetime'=>time(),
                        'adminid'=>$_SESSION['ADMIN_ID']
                    );
                    $res = M('oil_option')->where('id="'.$id.'"')->save($data);
                    if(!empty($res)){
                        echo json_encode(array('status'=>200,'message'=>'已处理'));exit;
                    }else{
                        echo json_encode(array('status'=>100,'message'=>'操作失败'));exit;
                    }
                }
            }else{
                echo json_encode(array('status'=>100,'message'=>'该记录不存在'));exit;
            }
        }else{
            echo json_encode(array('status'=>100,'message'=>'参数错误'));exit;
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

    /**
     * @author langzhiyao
     * @desc 导出所有充值的记录
     * @time 20190110
     */
    public function rechargeImportExcel(){
        //开启事务
        $things=M();
        $things->startTrans();
        $title = ['订单ID','充值用户','充值卡号','充值金额','支付金额','使用加油券','折扣金额','上级代理商','代理分润','充值时间'];
        $where='o.order_type = 3 AND o.order_status = 2 ';
        $OrderRecordModel = M('order_record');
        $order_info=$OrderRecordModel
            ->alias('o')
            ->join('add_money a ON a.order_no=o.serial_number',LEFT)
            ->join('user u ON u.id=o.user_id',LEFT)
            ->join('agent_earnings e ON e.sn=o.serial_number AND e.log_type=1',LEFT)
            ->join('user us ON us.id=o.agent_id',LEFT)
            ->field('o.serial_number,u.nickname,a.money,a.real_pay,o.coupon_money,o.discount_money,e.earnings,us.nickname as agent_name')
            ->where($where)
            ->select();
        if($order_info)foreach ($order_info as $key => $value) {
            if(empty($order_info[$key]['agent_name']))$order_info[$key]['agent_name']='总部';
            if(empty($order_info[$key]['earnings']))$order_info[$key]['earnings']='0.00';
        }
        $OrderRecordModel->alias('o')->where($where)->save(array('is_import'=>2));
        if($order_info)inportExcelLog($order_info,2,'用户充值记录Excel');
         createExcel($title,$order_info,'用户充值记录Excel');
        exit;
    }

}