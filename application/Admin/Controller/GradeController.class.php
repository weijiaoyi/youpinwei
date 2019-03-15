<?php
/**
 * Created by PhpStorm.
 * User: 1006a
 * Date: 2018/3/28
 * Time: 13:31
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Think\Model;

class GradeController extends AdminbaseController
{

    /**
     * 普通用户列表
     */
    public function ordinarylist(){
        $AgentModel = M('agent');
        $p = trim(I('get.p','1'));
        $pageNum = 10;
        $status = trim(I('post.status'));
        $keywords = trim(I('post.keywords'));
        $where =' agent.openid = user.openid AND agent.role = 1 AND user.nickname != ""';
        if(!empty($status)){
            $where .= ' AND user.is_notmal = "'.$status.'"';
        }
        if(!empty($keywords)){
            $where .= ' AND user.nickname LIKE "%'.$keywords.'%"';
        }
        $ordinary_info = $AgentModel
            -> join('user ON user.openid=agent.openid',LEFT)
            -> where($where)
            ->order('agent.id DESC')
            -> page($p,$pageNum)
            ->select();
        if(!empty($ordinary_info)){
            foreach ($ordinary_info as $key=>$value){
                if(!empty($value['parentid'])){
                    $parentInfo = M('user')->where('id="'.$value["parentid"].'"')->field('nickname,user_img')->find();
                    $ordinary_info[$key]['parent_nickname']=$parentInfo['nickname'];
                    $ordinary_info[$key]['parent_img']=$parentInfo['user_img'];
                }else{
                    $ordinary_info[$key]['parent_nickname']='';
                    $ordinary_info[$key]['parent_img']='';
                }
            }
        }
        $count = $AgentModel
            -> join('user ON user.openid=agent.openid',LEFT)
            -> where($where)
            -> count();
        $Page = new \Think\Page($count,$pageNum);
        $show = $Page -> show();
        $this -> assign( 'count' , $count );
        $this -> assign( 'data' , $ordinary_info );
        $this -> assign( 'page' , $show );
        $this -> assign( 'status' , $status );
        $this -> assign( 'keywords' , $keywords );
        $this -> display();
    }

    /**
     * VIP列表信息
     */
    public function viplist(){
        $AgentModel = M('agent');
        $p = trim(I('get.p','1'));
        $status = trim(I('post.status'));
        $keywords = trim(I('post.keywords'));
        $pageNum = 10;
        $where =' agent.openid = user.openid AND agent.role = 2 AND user.nickname != ""';
        if(!empty($status)){
            $where .= ' AND user.is_notmal = "'.$status.'"';
        }
        if(!empty($keywords)){
            $where .= ' AND  user.nickname LIKE "%'.$keywords.'%" ';
        }
        $vip_info = $AgentModel
            -> join('user ON user.openid=agent.openid',LEFT)
            -> where($where)
            ->order('agent.id DESC')
            -> page($p,$pageNum)
            -> select();
        if(!empty($vip_info)){
            foreach ($vip_info as $key=>$value){
                if(!empty($value['parentid'])){
                    $parentInfo = M('user')->where('id="'.$value["parentid"].'"')->field('nickname,user_img')->find();
                    $vip_info[$key]['parent_nickname']=$parentInfo['nickname'];
                    $vip_info[$key]['parent_img']=$parentInfo['user_img'];
                }else{
                    $vip_info[$key]['parent_nickname']='';
                    $vip_info[$key]['parent_img']='';
                }
            }
        }
        $count = $AgentModel
            -> join('user ON user.openid=agent.openid',LEFT)
            -> where($where)
            -> count();

        $Page = new \Think\Page($count,$pageNum);
        $show = $Page -> show();
        $this -> assign( 'count' , $count );
        $this -> assign( 'data' , $vip_info );
        $this -> assign( 'page' , $show );
        $this -> assign( 'status' , $status );
        $this -> assign( 'keywords' , $keywords );
        $this -> display();
    }

    /*
     * 代理商列表信息
     */
    public function agentList(){
        $AgentModel = M('agent');
        $p = trim(I('get.p','1'));
        $status = trim(I('post.status'));
        $keywords = trim(I('post.keywords'));
        $pageNum = 10;
        /*$pagenum = 3;
        $limit = $pagenum * ( $p - 1 );
        $sql = "select agent.id,nickname,role,deposit,development,`status`,agent.createtime,expire_time from agent join user on agent.openid = user.openid where role = '3' limit $limit,$pagenum";
        $agent_info = $AgentModel -> query($sql);*/
        //        $count = $AgentModel -> where(['role' => 3]) -> count();
        $where =' agent.openid = user.openid AND agent.role = 3';
        if(!empty($status)){
            $where .= ' AND is_notmal = "'.$status.'"';
        }
        if(!empty($keywords)){
            $where .= ' AND ( user.nickname LIKE "%'.$keywords.'%" OR user.remarks LIKE "%'.$keywords.'%" )';
        }
        $agent_info = $AgentModel
            -> join('user ON user.openid=agent.openid',LEFT)
            -> where($where)
            ->order('agent.id desc')
            -> page($p,$pageNum)
            -> select();
        $count = $AgentModel
            -> join('user ON user.openid=agent.openid',LEFT)
            -> where($where)
            -> count();
        //已付押金总额   赊销押金总额   购买油卡总张数    未激活油卡总张数
        $already_pay = M('agent_library')->where('card_mode=1')->field('sum(count_price) as count_price')->find();
        $no_already_pay = M('agent_library')->where('card_mode=2')->field('sum(count_price) as no_count_price')->find();
        $number = M('oil_card')->where('agent_id != 0')->field('count(id) as number')->find();
        $no_number = M('oil_card')->where('activate=1 AND agent_id != 0')->field('count(id) as no_number')->find();

        $Page = new \Think\Page($count,$pageNum);
        $show = $Page -> show();
        $this -> assign( 'already_pay' , $already_pay['count_price'] );
        $this -> assign( 'no_already_pay' , $no_already_pay['no_count_price'] );
        $this -> assign( 'number' , $number['number'] );
        $this -> assign( 'no_number' , $no_number['no_number'] );
        $this -> assign( 'count' , $count );
        $this -> assign( 'status' , $status );
        $this -> assign( 'keywords' , $keywords );
        $this -> assign( 'data' , $agent_info );
        $this -> assign( 'page' , $show );
        $this -> display();
    }

    /**
     * 查看我的油卡
     */
    public function getMyCard(){
        $user_id = I('get.user_id','');
        $p = I('get.p',1);
        $status = trim(I('post.status'));
        $keywords = trim(I('post.keywords'));
        $pageNum = 10;
        if(empty($user_id)){
            echo '操作失败';exit;
        }
        $user = M('user')->where('id="'.$user_id.'"')->find();
        $OilCardModel = M('oil_card');
        $where = '`oil_card`.user_id="'.$user_id.'"';
        if(!empty($status)){
            $where .= ' AND `oil_card`.is_notmal = "'.$status.'"';
        }
        if(!empty($keywords)){
            $where .= ' AND `oil_card`.card_no LIKE "%'.$keywords.'%"';
        }
        $oil_card_data = $OilCardModel
            ->join('packages ON  `oil_card`.pkgid=`packages`.pid',LEFT)
            -> where($where)
            -> page($p,$pageNum )
            -> select();

        $count = $OilCardModel
            ->join('packages ON  `oil_card`.pkgid=`packages`.pid',LEFT)
            -> where($where)
            -> count();
        $Page = new \Think\Page($count,$pageNum);
        $show = $Page -> show();
        $this -> assign( 'data' , $oil_card_data );
        $this -> assign( 'page' , $show );
        $this -> assign( 'user' , $user );
        $this -> assign( 'status' , $status );
        $this -> assign( 'keywords' , $keywords );
        $this -> display();
    }

    /**
     * 下线列表信息
     */
    public function getOffline(){
        $id = trim( I('get.id') );
        $status = trim(I('post.status'));
        $keywords = trim(I('post.keywords'));
        if( empty($id) ){
            echo '操作失败';exit;
        }
        $p = trim(I('get.p','1'));
        $pageNum = 10;
        $AgentModel = M('agent');
        $role = $AgentModel
            -> where('agent.id = "'.$id.'"' )->getField('role');
        $user = M('user')->where('id="'.$id.'"')->find();



        if($role == 2){
            $where = ' user.parentid = "'.$id.'" AND agent.role<3 AND user.nickname != ""';
            if(!empty($status)){
                $where .= ' AND is_notmal = "'.$status.'"';
            }
            if(!empty($keywords)){
                $where .= ' AND nickname LIKE "%'.$keywords.'%"';
            }
            $agent_earnings_data = $AgentModel
                -> join('user ON agent.openid = user.openid ')
                -> where($where)
                -> page($p,$pageNum)
                -> select();
            $count = $AgentModel
                -> join('user ON agent.openid = user.openid ')
                -> where($where)
                -> count();
        }else if($role == 3){
            $where = ' user.agentid = "'.$id.'" AND agent.role<3 AND user.nickname != ""';
            if(!empty($status)){
                $where .= ' AND is_notmal = "'.$status.'"';
            }
            if(!empty($keywords)){
                $where .= ' AND nickname LIKE "%'.$keywords.'%"';
            }
            $agent_earnings_data = $AgentModel
                -> join('user ON agent.openid = user.openid ',LEFT)
                -> where($where)
                -> page($p,$pageNum)
                -> select();
            $count = $AgentModel
                -> join('user ON agent.openid = user.openid ',LEFT)
                -> where($where)
                -> count();
        }

        $Page = new \Think\Page($count,$pageNum);

        $show = $Page -> show();
        $this -> assign( 'count' , $count );
        $this -> assign( 'user' , $user );
        $this -> assign('data' , $agent_earnings_data);
        $this -> assign('page' , $show);
        $this -> assign( 'status' , $status );
        $this -> assign( 'keywords' , $keywords );
        $this -> display();
    }

    /**
     * 添加(添加代理商)
     */
    public function addAgent(){
        if(!empty($_POST)){
            $data = I('post.');
            $agentModel=M('agent');

            $agent = $agentModel->where('openid="'.$data["openid"].'"')->find();
            if(empty($agent)){
                echo json_encode(['msg' => '代理不存在，无法分配','status' => 100]);
            }
            $OilCardModel = M('oil_card');
            //判断用户是否是代理商如果是代理->agent_earnings添加卡区间信息
            $where['card_no'] = array('BETWEEN',array($data['start'],$data['end']));
            $where['status'] = 2;

//            $save_oilcard_where =' card_no <= "'.$data["end"].'" AND card_no >= "'.$data["start"].'"';
//
//            $res =$OilCardModel->where('card_no >= "'.$data["end"].'"')->getField('id');
//            if(!$res){
//                echo json_encode(['msg' => '结束卡号超出，请重新分配','status' => 100]);exit;
//            }
//            $res2 =$OilCardModel->where('card_no = "'.$data["start"].'"')->getField('agent_id');
//            if($res2['agent_id']){
//                echo json_encode(['msg' => '开始卡号已被分配，请重新分配','status' => 100]);exit;
//            }
            $is_card = $OilCardModel -> where( $where )->select();
//            print_r($OilCardModel->getLastSql());die;
            if($is_card){
                echo json_encode(['msg' => '卡号中有已启用的卡，请查验','status' => 100]);exit;
            }
            $save_oilcard_data = [
                'agent_create_time' => time(),
                'agent_id' => $data['user_id'],
                'agent_status' => 1,
            ];
            $where1['card_no'] = array('BETWEEN',array($data['start'],$data['end']));
            # 将此区间的卡状态更改为启用
            $result1 = $OilCardModel -> where( $where1 ) -> save( $save_oilcard_data );
            if($result1) {
                //添加卡附属信息（记录该代理拿卡区间）
                $card_no_num = $data['end']-$data['start']+1;
                $insert_agent_library_data = [
                    'user_id' => $data['user_id'],
                    'openid' => $data['openid'],
                    'start_card_no' => $data['start'],
                    'end_card_no' => $data['end'],
                    'card_no_num' => $card_no_num,
                    'each_price' => $data['each_price'],
                    'count_price' => $data['each_price']*$card_no_num,
                    'card_mode' => $data['mode'],
                    'address' => $data['address'],
                    'createtime' => date('Y-m-d H:i:s'),
                    'status' => 2,
                ];
                $AgentLibraryModel = M('agent_library');
                $result2 = $AgentLibraryModel->add($insert_agent_library_data);
                //修改押金和角色 过期时间
                $new_deposit = $card_no_num*$data['each_price'];
                $new_agent_oilcard_num = $card_no_num;
                $new_agent_oilcard_stock_num = $card_no_num;
                $old_deposit = $agent['agent_deposit'];
                $old_agent_oilcard_num= $agent['agent_oilcard_num'];
                $old_agent_oilcard_stock_num= $agent['agent_oilcard_stock_num'];
                $agent_deposit= $new_deposit+$old_deposit;
                $agent_oilcard_num= $new_agent_oilcard_num+$old_agent_oilcard_num;
                $agent_oilcard_stock_num= $new_agent_oilcard_stock_num+$old_agent_oilcard_stock_num;
                //修改agent
                $update_data=array(
                    'agent_deposit'=>$agent_deposit,
                    'agent_oilcard_num' =>$agent_oilcard_num,
                    'agent_oilcard_stock_num'=>$agent_oilcard_stock_num,
                    'role' =>3,
                    /*'vip_direct_scale' =>$data['vip_direct_scale'],
                    'user_direct_scale' =>$data['user_direct_scale'],
                    'vip_indirect_scale' =>$data['vip_indirect_scale'],
                    'user_indirect_scale' =>$data['user_indirect_scale'],*/
                    'expire_time' => date('Y-m-d H:i:s',strtotime('+1year')),
                );
                 $agentModel->where('openid="'.$data["openid"].'"')->save($update_data);
                 //修改USER表
                $update_user_data=array(
                    'parentid'=>0,
                    'agentid' =>0,
                    'agent_bind' =>0,
                    'agent_relation'=>3
                );
                $agentModel->where('openid="'.$data["openid"].'"')->save($update_user_data);
                if ($result2) {
                    echo json_encode(['msg' => '升级成功', 'status' => 200]);
                    exit;
                } else {
                    echo json_encode(['msg' => '升级失败', 'status' => 100]);
                    exit;
                }
            }


        }else{

            $user_id = I('get.user_id');
            $openid = I('get.openid');
            $user = M('user')->where('id="'.$user_id.'" AND openid="'.$openid.'"')->find();
            $user['nickname'] = base64_decode( $user['nickname']);

//            $OilCardModel = M('oil_card');
//            //查询
//            $card_id=$OilCardModel -> where('agent_id != 0')->order('id desc') -> getField('id');
////            print_r($card_id);die;
//            if(!empty($card_id)){
//                //如果该卡为总部卡 但是该卡已被总部用户绑定
//                $start_card_id = $OilCardModel -> where(' id>"'.$card_id.'" AND status=2')->order('id desc')->getField('id');
//                if(!empty($start_card_id)){
//                    $start_card_id = $start_card_id+1;
//                    $start_card = $OilCardModel -> where(' id="'.$start_card_id.'" ') ->getField('card_no');
//                }else{
//
//                    $card_id = $card_id+1;
//                    $start_card = $OilCardModel -> where(' id="'.$card_id.'" ') ->getField('card_no');
//                }
//            }else{
//                //未分配代理商
//                $start_card_id = $OilCardModel -> where(' id>0 AND status=2')->order('id desc') ->getField('id');
//                if(!empty($start_card_id)){
//                    $start_card_id = $start_card_id+1;
//                    $start_card = $OilCardModel -> where(' id="'.$start_card_id.'" ') ->getField('card_no');
//                }else{
//                    $start_card = $OilCardModel -> where(' id=1 ') ->getField('card_no');
//                }
//            }
//            if(!empty($start_card)){
//                $start_card = $start_card;
//            }else{
//                $start_card = '总部已无卡发布，请及时补充';
//            }
//            $this -> assign( 'start_card' , $start_card );
            $this -> assign( 'user' , $user );
            $this -> assign( 'user_id' , $user_id );
            $this -> assign( 'openid' , $openid );
            $this -> display('add_grade');
        }
    }

    /**
     * 给代理发卡（查询卡信息和用户信息）
     */
    public function sendCard(){
        $id = trim( I('get.id','') );
        //查询为库存的第一张卡 准备发卡
        $OilCardModel = M('oil_card');
        /*$card_id=$OilCardModel -> where('agent_id != 0')->order('id desc') -> getField('id');
        $card_id = $card_id+1;
        $start_card = $OilCardModel -> where(['status' => 1,'id'=>$card_id]) -> getField('card_no');*/
        //
//        $card_id=$OilCardModel -> where('agent_id != 0')->order('id desc') -> getField('id');
//        if(!empty($card_id)){
//            $start_card_id = $OilCardModel -> where(' id>"'.$card_id.'" AND status=2')->order('id desc')->getField('id');
//            if(!empty($start_card_id)){
//                $start_card_id = $start_card_id+1;
//                $start_card = $OilCardModel -> where(' id="'.$start_card_id.'" ') ->getField('card_no');
//            }else{
//                $card_id = $card_id+1;
//                $start_card = $OilCardModel -> where(' id="'.$card_id.'" ') ->getField('card_no');
//            }
//        }else{
//            //未分配代理商
//            $start_card_id = $OilCardModel -> where(' id>0 AND AND status=2')->order('id desc') ->getField('id');
//            if(!empty($start_card_id)){
//                $start_card_id = $start_card_id+1;
//                $start_card = $OilCardModel -> where(' id="'.$start_card_id.'" ') ->getField('card_no');
//            }else{
//                $start_card = $OilCardModel -> where(' id=1 ') ->getField('card_no');
//            }
//        }
//
//
//
//        if(!empty($start_card)){
//            $sendCardData['card_no'] = $start_card;
//        }else{
//            $sendCardData['card_no'] = '总部已无卡发布，请及时补充';
//        }

        $AgentModel = M('agent');
        $agent_info = $AgentModel -> where( ['id' => $id,'role'=>3] ) -> find();
        $user = M('user')->where('id="'.$id.'" AND openid="'.$agent_info['openid'].'"')->find();
//
        $sendCardData['uid'] = $agent_info['id'];
        $sendCardData['openid'] = $agent_info['openid'];

        $this -> assign('user',$user);
        $this -> assign('data',$sendCardData);

        $this -> display();
    }
    /**
     * 确认发卡(确认发卡)
     */
    public function confirmSendCard(){
        //开启事务
        $things = M();
        $things->startTrans();
        $data = I('post.');
        $agentModel=M('agent');
        $agent = $agentModel->where('openid="'.$data["openid"].'"')->find();
        if(empty($agent)){
            echo json_encode(['msg' => '代理不存在，无法分配','status' => 100]);
        }
        $OilCardModel = M('oil_card');
        $where['card_no'] = array('BETWEEN',array($data['start'],$data['end']));
        $where['status'] = 2;

//        $res =$OilCardModel->where('card_no >= "'.$data["end"].'"')->getField('id');
//        if(!$res){
//            echo json_encode(['msg' => '结束卡号超出，请重新分配','status' => 100]);exit;
//        }
//        $res2 =$OilCardModel->where('card_no = "'.$data["start"].'"')->getField('agent_id');
//        if($res2['agent_id']){
//            echo json_encode(['msg' => '开始卡号已被分配，请重新分配','status' => 100]);exit;
//        }

        $is_card = $OilCardModel -> where( $where )->select();
//            print_r($OilCardModel->getLastSql());die;
        if($is_card){
            echo json_encode(['msg' => '卡号中有已启用的卡，请查验','status' => 100]);exit;
        }
        $save_oilcard_data = [
            'agent_create_time' => time(),
            'agent_id' => $data['user_id'],
            'agent_status' => 1,
        ];
        $where1['card_no'] = array('BETWEEN',array($data['start'],$data['end']));
        # 将此区间的卡状态更改为启用
        $result1 = $OilCardModel -> where( $where1 ) -> save( $save_oilcard_data );
        if($result1){
            //添加卡附属信息（记录该代理拿卡区间）
            $card_no_num = $data['end']-$data['start']+1;
            $insert_agent_library_data = [
                'user_id' => $data['user_id'],
                'openid' => $data['openid'],
                'start_card_no' => $data['start'],
                'end_card_no' => $data['end'],
                'card_no_num' => $card_no_num,
                'each_price' => $data['each_price'],
                'count_price' => $data['each_price']*$card_no_num,
                'card_mode' => $data['mode'],
                'address' => $data['address'],
                'createtime' => date('Y-m-d H:i:s'),
                'status' => 2,
            ];
            $AgentLibraryModel = M('agent_library');
            $result2 = $AgentLibraryModel -> add( $insert_agent_library_data );

            if($result2){
                //修改押金 库存
                $new_deposit = $card_no_num*$data['each_price'];
                $new_agent_oilcard_num = $card_no_num;
                $new_agent_oilcard_stock_num = $card_no_num;
                $old_deposit = $agent['agent_deposit'];
                $old_agent_oilcard_num= $agent['agent_oilcard_num'];
                $old_agent_oilcard_stock_num= $agent['agent_oilcard_stock_num'];
                $agent_deposit= $new_deposit+$old_deposit;
                $agent_oilcard_num= $new_agent_oilcard_num+$old_agent_oilcard_num;
                $agent_oilcard_stock_num= $new_agent_oilcard_stock_num+$old_agent_oilcard_stock_num;
                $update_data=array(
                    'agent_deposit'=>$agent_deposit,
                    'agent_oilcard_num' =>$agent_oilcard_num,
                    'agent_oilcard_stock_num'=>$agent_oilcard_stock_num
                );
                $result3 = $agentModel->where('openid="'.$data["openid"].'"')->save($update_data);
                if($result3){
                    //修改订单send_card_no
                    $orderRecordModel=M('order_record');
                    $order = $orderRecordModel->where('send_card_no="" AND agent_id="'.$agent['id'].'" AND order_status=2 AND order_type=1')->select();
                    if(!empty($order)){
                        foreach ($order as $key=>$val){
                            $agentInfo = $agentModel->where('openid="'.$data["openid"].'"')->find();
                            $SendCard = M('oil_card')->where('agent_id="'.$agentInfo["id"].'" AND status=1 AND chomd=1 AND is_notmal=1 AND activate=1')->getField('card_no');
                            if($SendCard){
                                //修改订单
                                $orderRecordModel->where('id="'.$val["id"].'"')->save(array('send_card_no'=>$SendCard));
                                //修改卡信息
                                $OilCardModel->where('card_no = "'.$SendCard.'" AND status=1')->save(array('status'=>2,'updatetime'=>date('Y-m-d H:i:s',time())));
                                if($agentInfo['agent_oilcard_stock_num']>0){
                                    //修改代理商库存
                                    $agentModel->where('openid="'.$data["openid"].'"')->setDec('agent_oilcard_stock_num');
                                }
                            }
                        }
                    }
                    $things->commit();
                    echo json_encode(['msg' => '发卡成功','status' => 200]);exit;
                }else{
                    $things->rollback();
                    echo json_encode(['msg' => '发卡失败','status' => 100]);exit;
                }
            }else{
                $things->rollback();
                echo json_encode(['msg' => '发卡失败','status' => 100]);exit;
            }
        }else{
            $things->rollback();
            echo json_encode(['msg' => '修改卡状态失败','status' => 100]);
        }
    }

    /**
     * 查看该代理的发卡记录
     */
    public function sendCardRecord(){
        $id = trim( I('get.id') );
        $p = I('get.p',1);
        $pageNum = 10;
        if( empty($id) ){
            echo '操作失败';exit;
        }
        $AgentModel = M('agent');
        $agent_info = $AgentModel -> where( ['id' => $id] ) -> find();
        $user = M('user')->where('id="'.$id.'"')->find();
        if( empty($agent_info) ){
            echo '操作失败';exit;
        }
        $AgentLibraryModel = M('agent_library');
        $select_where ='agent_library.user_id="'.$id.'" ';

        $agent_library_data = $AgentLibraryModel
            -> where( $select_where )
            -> page( $p , $pageNum )
            ->order('id desc')
            -> select();
        $count = $AgentLibraryModel
            -> where( $select_where )
            -> count();
        //已付押金总额   赊销押金总额   购买油卡总张数    未激活油卡总张数
        $already_pay = M('agent_library')->where('card_mode=1 AND user_id="'.$id.'"')->field('sum(count_price) as count_price')->find();
        $no_already_pay = M('agent_library')->where('card_mode=2 AND user_id="'.$id.'"')->field('sum(count_price) as no_count_price')->find();
        $number = M('oil_card')->where('agent_id="'.$id.'"')->field('count(id) as number')->find();
        $no_number = M('oil_card')->where('activate=1 AND agent_id="'.$id.'"')->field('count(id) as no_number')->find();

        $Page = new \Think\Page($count,$pageNum);
        $show = $Page -> show();
        $this -> assign( 'already_pay' , $already_pay['count_price'] );
        $this -> assign( 'no_already_pay' , $no_already_pay['no_count_price'] );
        $this -> assign( 'number' , $number['number'] );
        $this -> assign( 'no_number' , $no_number['no_number'] );
        $this -> assign('data' , $agent_library_data);
        $this -> assign('page' , $show);
        $this -> assign('user' , $user);
        $this -> display();
    }

    /**
     * 查看该代理所有购买的油卡
     */
    public function payCard(){
        $id = trim( I('get.id') );
        $status = trim(I('post.status'));
        $keywords = trim(I('post.keywords'));
        $p = I('get.p',1);
        $pageNum = 10;
        if( empty($id) ){
            echo '操作失败';exit;
        }
        $AgentModel = M('agent');
        $agent_info = $AgentModel -> where( ['id' => $id] ) -> find();
        $user = M('user')->where('id="'.$id.'"')->find();
        if( empty($agent_info) ){
            echo '操作失败';exit;
        }
        $oilCardModel = M('oil_card');
        $where = ' agent_id = "'.$id.'"';
        if(!empty($status)){
            if($status>3){
                $where .= ' AND oil_card.status = 1';
            }else{
                $where .= ' AND oil_card.status = 2 AND oil_card.is_notmal = "'.$status.'"';
            }

        }
        if(!empty($keywords)){
            $where .= ' AND oil_card.card_no LIKE "%'.$keywords.'%"';
        }
        $agent_library_data = $oilCardModel
            ->join('user ON user.id=oil_card.user_id',LEFT)
            ->join('agent ON agent.id=oil_card.user_id',LEFT)
            ->field('oil_card.*,user.nickname,user.user_img,agent.role')
            ->where($where)
            ->page( $p , $pageNum )
            ->order('oil_card.id desc')
            ->select();

        $count = $oilCardModel
            ->join('user ON user.id=oil_card.user_id',LEFT)
            -> where($where)
            -> count();
        $Page = new \Think\Page($count,$pageNum);
        $show = $Page -> show();
        $this -> assign('data' , $agent_library_data);
        $this -> assign('page' , $show);
        $this -> assign('user' , $user);
        $this -> assign( 'status' , $status );
        $this -> assign( 'keywords' , $keywords );
        $this -> display();
    }

    /**
     * @desc 还账
     * @author langzhiyao
     *
     */
    public function Repayment(){
        $id = trim( I('post.id') );
        $user_id = trim( I('post.user_id') );
        $openid = trim( I('post.openid') );
        //查看记录是否存在
        $agentLibraryModel=M('agent_library');
        $where = 'id="'.$id.'" AND user_id="'.$user_id.'" AND openid="'.$openid.'"';
        $result = $agentLibraryModel->where($where)->find();
        if($result && $result['card_mode'] ==2){
            $agentLibrary_data=array(
                    'card_mode'=>1,
                    'repaymenttime'=>time()
            );
            $res=$agentLibraryModel->where($where)->save($agentLibrary_data);
            if($res){
                echo json_encode(array('status'=>200,'msg'=>'还账成功'));exit;
            }else{
                echo json_encode(array('status'=>100,'msg'=>'操作失败，请重新操作'));exit;
            }
        }else{
            echo json_encode(array('status'=>100,'msg'=>'该记录已还账，无需重复操作'));exit;
        }
    }

    /**
     * @desc 退还押金
     * @author langzhiyao
     *
     */
    public function ReturnDeposit(){
        $id = trim( I('post.id') );
        $user_id = trim( I('post.user_id') );
        $openid = trim( I('post.openid') );
        $money = trim( I('post.money') );

        //查看记录是否存在
        $agentLibraryModel=M('agent_library');
        $where = 'id="'.$id.'" AND user_id="'.$user_id.'" AND openid="'.$openid.'"';
        $result = $agentLibraryModel->where($where)->find();

        if($result && $result['card_mode'] ==2){
            echo json_encode(array('status'=>100,'msg'=>'该记录处于赊账，无法进行退还操作'));exit;
        }else{
            $count_Money = $result['count_price'];
            $return_deposit = $result['return_deposit'];
            $sy_return_deposit = $count_Money-$return_deposit;
            if($money > $sy_return_deposit){
                echo json_encode(array('status'=>100,'msg'=>'还款金额大于剩余还款金额，无法进行退还操作'));exit;
            }else{
                $agentLibrary_data=array(
                    'return_deposit'=>$return_deposit+$money,
                );
                $res=$agentLibraryModel->where($where)->save($agentLibrary_data);
                if($res){
                    echo json_encode(array('status'=>200,'msg'=>'退还成功'));exit;
                }else{
                    echo json_encode(array('status'=>100,'msg'=>'操作失败，请重新操作'));exit;
                }
            }
        }
    }

    /**
     * 根据关键字查询相关数据
     */
    public function userKeyword(){
        $keyword = I('post.keyword','');
        $UserModel = M('user');
        $where['phone'] = ['like' , "%$keyword%"];
        $user_info = $UserModel -> where( $where ) -> find();
        $count=M('oil_card')->where( ['user_id' => $user_info['id']] )->count();
        # 计算的充值次数,暂时不展示
        # $num_count = M('order_record') -> where( ['user_id' => $user_info['id']] ) -> count();
        # $num_count['num_count'] = $num_count;

        # 页码处理
        $count = $UserModel -> where( $where ) -> count();
        $Page = new \Think\Page($count,3);
        $show = $Page -> show();

        $user_info['count'] = $count;
        $user_info['total_recharge'] = $user_info['already_save_money'] + $user_info['total_add_money'];

        if( $user_info['total_recharge'] == '0' ){
            $user_info['total_recharge'] = "<font style=\"color:red\">无</font>";
        }else{
            $user_info['total_recharge'] = " <font style=\"color:red\">￥</font>".$user_info['total_recharge'];
        }
        if( $user_info['already_save_money'] == '0'){
            $user_info['already_save_money'] = "<font style=\"color:red\">无</font>";
        }else{
            $user_info['already_save_money'] = "<font style=\"color:red\">￥</font>".$user_info['already_save_money'];
        }
        if( $user_info['total_add_money'] == '0' ){
            $user_info['total_add_money'] = "<font style=\"color:red\">无</font>";
        }else{
            $user_info['total_add_money'] = $user_info['total_add_money'];
        }
        if( $user_info['integral'] == '0' ){
            $user_info['integral'] = "<font style=\"color:red\">无</font>";
        }else{
            $user_info['integral'] = $user_info['integral'];
        }
        if( $user_info['is_notmal'] == '1' ){
            $user_info['is_notmal'] = '正常';
        }else if( $user_info['is_notmal'] == '3' ){
            $user_info['is_notmal'] = '注销';
        }else{
            $user_info['is_notmal'] = '冻结';
        }
        $info['data'] = $user_info;
        $info['page'] = $show;
        echo json_encode($info);
    }

    /**
     * 获取用户的的信息(添加代理商) and 卡号信息
     */
    public function getThisUser(){
        $keyword = I('post.nickname');
        $UserModel = M('user');
        $where = [
            'nickname' => $keyword
        ];
        /**
        $where = [
        'nickname' =>['like', '%'.$keyword.'%']
        ];
        $user_info = $UserModel -> where($where) -> select();
        foreach ($user_info as $k => $v) {
        $user_info[$k]['nickname']=$v['nickname'].$v['phone'];
        }
        $card_no = M('oil_card') -> where(['status' => 1,'chomd'=>1]) -> getField('card_no');
        $this -> ajaxReturn(['users'=>$user_info,'startCardNo'=>$card_no]);
         */
        $user_info = $UserModel -> where($where) -> find();
        $user_info['nickname'] = $user_info['nickname'].$user_info['phone'];
        $OilCardModel = M('oil_card');
        $user_info['start_card'] = $OilCardModel -> where(['status' => 1,'chomd'=>1]) -> getField('card_no');
        $this -> ajaxReturn($user_info);

    }

    /**
     * 删除/冻结:
     * 删除flag为空  冻结flag为1
     */
    public function del(){
        $id = I('post.id','');

        $flag = I('post.flag','');
        if ( empty($id) ){
            echo json_encode(['status'=>500,'msg'=>'未选中用户']);exit;
        }
        if ( empty($flag) ){
            $res = M('user')->where("id='$id'")->save(['is_notmal'=>'3']);
        }else{
            $res = M('user')->where("id='$id'")->save(['is_notmal'=>'2']);
        }
        if ($res!==false){
            $p = I('get.p','1');
            $UserModel = M('user');
            $user_info = $UserModel -> page($p,'3') ->select();
            if( empty($user_info) ){
                echo '暂无数据';
            }
            $where = [ 'user_id' => $id ];
            foreach ($user_info as $k=>$v){
                $id = $v['id'];
                $count = M('oil_card') -> where("user_id='$id'") -> count();
                $num_count = M('order_record') -> where( $where ) -> count();
                $user_info[$k]['count'] = $count;
                $user_info[$k]['num_count'] = $num_count;
                $user_info[$k]['total_recharge'] = $user_info[$k]['already_save_money'] + $user_info[$k]['total_add_money'];
            }

            $count = $UserModel -> where( $where ) -> count();
            $Page = new \Think\Page($count,3);
            $show = $Page -> show();
            $info['data'] = $user_info;
            $info['page'] = $show;
            echo json_encode($info);
        }else{
            $this->error('操作失败', 'Grade/gradelist');
        }
    }

    /**
     * 粉丝数据替换页面  注销 and 冻结
     */
    public function fansReplace(){
        $id = I('get.id','');
        $flag = I('get.flag','');
        if ( empty($id) ){
            echo json_encode(['status'=>500,'msg'=>'未选中用户']);exit;
        }
        if ( empty($flag) ){
            $res = M('user')->where("id='$id'")->save(['is_notmal'=>'3']);
        }else{
            $res = M('user')->where("id='$id'")->save(['is_notmal'=>'2']);
        }
        if ($res!==false){
            $UserModel = M('user');

            $user_info = $UserModel -> where( ['id' => $id] ) -> find();

            $count=M('oil_card')->where( ['user_id' => $id] )->count();

            if( empty($user_info) ){
                echo '暂无数据';exit;
            }

            $user_info['count'] = $count;
            $user_info['total_recharge'] = $user_info['already_save_money'] + $user_info['total_add_money'];

            $this -> assign('data',$user_info);
            $this -> display();
        }else{
            $this->error('操作失败', 'Grade/gradelist');
        }
    }

    /**
     * 平台粉丝列表
     */
    public function gradelist()
    {
        $p = trim(I('get.p','1'));
        $User = M('user');
        $user_info = $User -> page($p,'10') ->select();
        if(empty($user_info)){
            echo '暂无数据';exit;
        }
        foreach ($user_info as $k=>$v){
            $id = $v['id'];
            $count=M('oil_card')->where("user_id='$id'")->count();
            $where = [
                'user_id' => $id,
            ];
            $num_count = M('order_record') -> where( $where ) -> count();
            $user_info[$k]['count']=$count;
            $user_info[$k]['num_count'] = $num_count;
            $user_info[$k]['total_recharge'] = $user_info[$k]['already_save_money'] + $user_info[$k]['total_add_money'];
        }

        foreach( $user_info as $k => $v ){
            if( $v['nickname'] == '' ){
                $user_info[$k]['nickname'] = $v['openid'];
            }
            if( $v['integral'] == '' ){
                $user_info[$k]['integral'] = '暂无积分';
            }else{
                $user_info[$k]['integral'] = $v['integral'].'积分';
            }
        }
        $count = $User -> count();
        $Page = new \Think\Page($count,10);
        $show = $Page -> show();
        $this -> assign('page',$show);
        $this -> assign('data',$user_info);
        $this -> display();

    }

    /**
     * 添加代理商渲染页面
     */
    public function add_grade(){
        $this->display();
    }


    public function ajaxGetPrev()
    {
        $lev = I('post.lev');
        $prevs = M('grade')->alias('g')->join('__GRADE_DETAIL__ gd on g.id=gd.grade_id')->where('g.level_id='.$lev)->select();
        if($prevs)
        {
            $this->ajaxReturn(['code'=>404,'data'=>$prevs]);
        }
    }

    public function editGrade()
    {
        $grade_id = I('get.grade_id');
        $grade_info =  M('grade')->alias('g')->join('__GRADE_DETAIL__ gd on g.id=gd.grade_id')->where('g.id='.$grade_id)->find();
        $this->assign('grade',$grade_info);
        $this->display();
    }

    public function do_editGrade()
    {
        $args = I('post.');
        foreach ($args as $val)
        {
            if(empty($val))
            {
                $this->error('不能为空');
            }
        }
        if($_FILES['photoimage']['size'] != 0)
        {
            $data = date('YmdHis',time());
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     3145728 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath  =     './Uploads/'.$data.'/'; // 设置附件上传根目录
            if (!file_exists($upload->rootPath)) {
                mkdir($upload->rootPath, 0777, true);
            }
            //$upload->savePath  =     ''; // 设置附件上传（子）目录
            // 上传文件
            $info   =   $upload->uploadOne($_FILES['photoimage']);
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }else{// 上传成功 获取上传文件信息
                $imageinfo =  './Uploads/'.$data.'/'.$info['savepath'].$info['savename'];
            }
            $args['bussiness_img'] = $imageinfo;
        }
        $res = M('grade_detail')->where('grade_id='.$args['grade_id'])->save($args);
        if($res)
        {
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }

    public function doAddGrade()
    {

        $args = I('post.');

//        foreach ($args as $val)
//        {
//            if(empty($val))
//            {
//                $this->error('不能为空');
//            }
//        }
//        $res = M('grade')->where('tel='.$args['tel'])->find();
//        if($res)
//        {
//            $this->error('不可添加相同代理商');
//        }
//        $args['password'] = md5(substr($args['tel'],-6,6));
//        $data = date('YmdHis',time());
//        $upload = new \Think\Upload();// 实例化上传类
//        $upload->maxSize   =     3145728 ;// 设置附件上传大小
//        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
//        $upload->rootPath  =     './Uploads/'.$data.'/'; // 设置附件上传根目录
//        if (!file_exists($upload->rootPath)) {
//            mkdir($upload->rootPath, 0777, true);
//        }
//        //$upload->savePath  =     ''; // 设置附件上传（子）目录
//        // 上传文件
//        $info   =   $upload->uploadOne($_FILES['photoimage']);
//        if(!$info) {// 上传错误提示错误信息
//            $this->error($upload->getError());
//        }else{// 上传成功 获取上传文件信息
//            $imageinfo =  './Uploads/'.$data.'/'.$info['savepath'].$info['savename'];
//        }
//        $grade = [
//            'tel'=>$args['tel'],
//            'password'=>$args['password'],
//            'status'=>0,
//            'config_id'=>session('CONFIG_ID'),
//            'level_id'=>$args['prev_lev']+1,
//            'parent_id'=>'0',
//            'create_time'=>date('Y-m-d H:i:s',time()),
//        ];
//
//        M()->startTrans();
//        $grade_id = M('grade')->add($grade);
//        if($grade_id){
//            $grade_detail = [
//                'grade_name'=>$args['serverName'],
//                'credit_code'=>$args['code'],
//                'abb'=>$args['abb'],
//                'adress'=>$args['address'],
//                'contacts'=>$args['tel_name'],
//                'bussiness_img'=>$imageinfo,
//                'grade_id'=>$grade_id,
//                'create_time'=>date('Y-m-d H:i:s',time()),
//            ];
//            $detail_id = M('grade_detail')->add($grade_detail);
//            if(!$detail_id)
//            {
//                M()->rollback();
//                $this->error('添加代理商详细失败');
//            }else{
//                M()->commit();
//                $this->success('添加成功');
//            }
//        }else{
//            M()->rollback();
//            $this->error('添加代理商失败');
//        }
    }

    public function switchChars($grade)
    {
        foreach ($grade as $k=>$val)
        {
            switch($val['status'])
            {
                case 0: $val['statu'] = '使用中';break;
                case 1:$val['statu'] = '禁用';break;
                default:$val['statu'] = '未知';
            }
            $grade[$k]  = $val;
        }
        return $grade;
    }


    public function get_draw()
    {
       $draw_record =  M('grade_draw_reply')->select();
       $this->assign();
       $this->display();
    }

    public function enableGrade()
    {
        $grade_id = I('get.grade_id');
        $res = M('grade')->where('id='.$grade_id)->save(['status'=>0]);
        if($res)
        {
            $this->success('修改成功');
        }else
        {
            $this->error('修改失败');
        }
    }

    public function disAbleGrade()
    {
        $grade_id = I('get.grade_id');
        $res = M('grade')->where('id='.$grade_id)->save(['status'=>1]);
        if($res)
        {
            $this->success('修改成功');
        }else
        {
            $this->error('修改失败');
        }
    }

    public function deleteGrade()
    {
        $grade_id = I('get.grade_id');
        $res = M('grade')->where('id='.$grade_id)->save(['status'=>3]);
        if($res)
        {
            $this->success('修改成功');
        }else
        {
            $this->error('修改失败');
        }
    }
    /**
     * @author langzhiyao
     * @desc 添加代理商备注
     * @time 20190110
     */
    public function addRemarks(){
        $id=trim(I('post.id'));
        $remarks = trim(I('post.remarks'));
        if(!empty($id)){
            $user = M('user')->where('id="'.$id.'"')->find();
            if(!empty($user)){
                M('user')->where('id="'.$id.'"')->save(array('remarks'=>$remarks));
                echo json_encode(array('status'=>'200','message'=>'更改备注成功'));exit;
            }else{
                echo json_encode(array('status'=>'100','message'=>'操作失败'));exit;
            }
        }else{
            echo json_encode(array('status'=>'100','message'=>'操作失败'));exit;
        }

    }

    /**
     * @author langzhiyao
     * @desc 导出所有代理商的当前收益
     * @time 20190110
     */
    public function importExcel(){
        //开启事务
        $things=M();
        $things->startTrans();
        $title = ['代理商微信openid','代理商微信昵称','代理商备注','当前收益'];

        $where =' agent.role = 3';
        $AgentModel = M('agent');
        $AgentEarningModel = M('agent_earnings');
        $agent_info = $AgentModel
            -> join('user ON user.openid=agent.openid',LEFT)
            ->field('user.openid,user.nickname,user.remarks,agent.currt_earnings')
            -> where($where)
            ->order('agent.id desc')
            -> select();
        if(!empty($agent_info)){
            foreach ($agent_info as $key=>$value){
                $res = $AgentEarningModel-> add(array('openid'=>$value["openid"],'createtime'=>date('Y-m-d H:i:s',time()),'order_type'=>4,'earning_body'=>0,'updatetime'=>date('Y-m-d H:i:s',time()),'log_type'=>2));
                if($res){
                    $AgentModel-> where('openid="'.$value["openid"].'" AND role=3')-> save(array('currt_earnings'=>0));
                    if($agent_info)inportExcelLog($agent_info,3,'代理商当前收益Excel');
                    $things->commit();
                }else{
                    $things->rollback();
                }
            }
        }
        createExcel($title,$agent_info,'代理商当前收益Excel');
        exit;
    }


}