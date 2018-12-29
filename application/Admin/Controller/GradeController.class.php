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
        $where =' agent.openid = user.openid AND agent.role = 1';
        if(!empty($status)){
            $where .= ' AND is_notmal = "'.$status.'"';
        }
        if(!empty($keywords)){
            $where .= ' AND nickname LIKE "%'.$keywords.'%"';
        }
        $ordinary_info = $AgentModel
            -> join('user')
            -> where($where)
            -> page($p,$pageNum)
            ->select();
        $count = $AgentModel
            -> join('user')
            -> where($where)
            -> count();
        $Page = new \Think\Page($count,$pageNum);
        $show = $Page -> show();
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
        $where =' agent.openid = user.openid AND agent.role = 2';
        if(!empty($status)){
            $where .= ' AND is_notmal = "'.$status.'"';
        }
        if(!empty($keywords)){
            $where .= ' AND nickname LIKE "%'.$keywords.'%"';
        }
        $vip_info = $AgentModel
            -> join('user')
            -> where($where)
            -> page($p,$pageNum)
            -> select();
        $count = $AgentModel
            -> join('user')
            -> where($where)
            -> count();

        $Page = new \Think\Page($count,$pageNum);
        $show = $Page -> show();
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
            $where .= ' AND nickname LIKE "%'.$keywords.'%"';
        }
        $agent_info = $AgentModel
            -> join('user')
            -> where($where)
            -> page($p,$pageNum)
            -> select();
        $count = $AgentModel
            -> join('user')
            -> where($where)
            -> count();

        $Page = new \Think\Page($count,$pageNum);
        $show = $Page -> show();
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
            $where = ' agent.openid = user.openid AND user.parentid = "'.$id.'"';
            if(!empty($status)){
                $where .= ' AND is_notmal = "'.$status.'"';
            }
            if(!empty($keywords)){
                $where .= ' AND nickname LIKE "%'.$keywords.'%"';
            }
            $agent_earnings_data = $AgentModel
                -> join('user')
                -> where($where)
                -> page($p,$pageNum)
                -> select();
            $count = $AgentModel
                -> join('user')
                -> where($where)
                -> count();
        }else if($role == 3){
            $where = ' agent.openid = user.openid AND user.agentid = "'.$id.'"';
            if(!empty($status)){
                $where .= ' AND is_notmal = "'.$status.'"';
            }
            if(!empty($keywords)){
                $where .= ' AND nickname LIKE "%'.$keywords.'%"';
            }
            $agent_earnings_data = $AgentModel
                -> join('user')
                -> where($where)
                -> page($p,$pageNum)
                -> select();
            $count = $AgentModel
                -> join('user')
                -> where($where)
                -> count();
        }

        /*$AgentEarningsModel = M('agent_earnings');
        $agent_earnings_data = $AgentEarningsModel -> join('user on agent_earnings.openid=user.openid') -> where("agent_id = '$id'") -> page( $p , $pageNum ) -> select();
        $count=$AgentEarningsModel -> join('user on agent_earnings.openid=user.openid') -> where("agent_id = '$id'") -> count();*/

        $Page = new \Think\Page($count,$pageNum);

        $show = $Page -> show();
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
        $data = I('post.');

        $where = [ 'openid' => $data['openid'] ];
        $AgentModel = M('agent');
        $agent_info = $AgentModel -> where( $where ) -> find();
        //判断用户是否是代理商如果是代理->agent_earnings添加卡区间信息
        if( $agent_info ){
            //记录该代理拿卡信息
            $AgentLibraryModel = M('agent_library');
            $insert_agent_library_data = [
                'user_id' => $data['user_id'],
                'start_card_no' => $data['start'],
                'end_card_no' => $data['end'],
                'openid' => $data['openid'],
                'each_price' => $data['each_price'],
                'card_mode' => $data['mode'],
                'createtime' => date('Y-m-d H:i:s')
            ];

            $AgentLibraryModel = M('agent_library');
            $result1 = $AgentLibraryModel -> add( $insert_agent_library_data );



            //代理拿到卡后修改卡状态
            for( $i = $data['start'] ; $i <= $data['end'] ; $i++ ){
                //拿卡的数量
                $arr[] = $i;
                //修改卡状态和入手时间
                $save_data = [
                     'status' => 1,
                    'agent_create_time' => date('Y-m-d H:i:s'),
                    'agent_id' => $agent_info['id'],
                    'chomd'=>2
                ];
                $OilCardModel = M('oil_card');
                $result2 = $OilCardModel -> where( ['card_no' => $i ] ) -> save( $save_data );
            }

            //记录代理申领的信息
            $UserApplyModel = M('user_apply');
            $insert_user_apply_data = [
                'user_id' => $data['user_id'],
                'card_number' => count($arr),
                'shop_name' => '中石油加油卡',
                'receive_person' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'deliver_number' => count($arr),
                'serial_number' => '20181130'.rand(100000,999999),
                'status' => 1,
                'createtime' => date('Y-m-d H:i:s')
            ];

            $result3 = $UserApplyModel -> add( $insert_user_apply_data );

            //修改代理的过期时间
            $save_expire_time = [
                'expire_time' => date('Y-m-d H:i:s',strtotime('+1year')),
            ];
            $result4 = $AgentModel -> where( $where ) -> save( $save_expire_time );
            $AgentModel = M('agent');
            $insert_agent_where =[
                'openid'=>$data['openid']
            ];
            $result5 = $AgentModel -> where( $insert_agent_where )->save(['role'=>3]);

            if( $result1 && $result2 && $result3 && $result4 && $result5 ){
                echo json_encode([
                    'msg' => 1000,
                    'status' => '添加成功'
                ]);exit;
            }else{
                echo json_encode([
                    'msg' => 500,
                    'status' => '添加失败'
                ]);exit;
            }
        }else{
            //添加代理

//            $AgentModel = M('agent');
//            $insert_agent_data = [
//                'openid' => $data['openid'],
//                'role' => 3,
//                'expire_time' => date('Y-m-d H:i:s',strtotime('+1year')),
//                'status' => 1,
//                'deposit' => '100',
//                'createtime' => date('Y-m-d H:i:s'),
//                'development' => 1
//            ];
//            $result1 = $AgentModel -> add( $insert_agent_data );
//
//            //记录该代理拿卡信息
//            $AgentLibraryModel = M('agent_library');
//            $insert_agent_library_data = [
//                'user_id' => $data['user_id'],
//                'start_card_no' => $data['start'],
//                'end_card_no' => $data['end'],
//                'openid' => $data['openid'],
//                'each_price' => $data['each_price'],
//                'card_mode' => $data['mode'],
//                'createtime' => date('Y-m-d H:i:s')
//            ];
//
//            $AgentLibraryModel = M('agent_library');
//            $result2 = $AgentLibraryModel -> add( $insert_agent_library_data );
//            //代理拿到卡后修改卡状态
//            for( $i = $data['start'] ; $i <= $data['end'] ; $i++ ){
//                //拿卡的数量
//                $arr[] = $i;
//                //修改卡状态和入手时间
//                $save_data = [
//                    //'status' => 2,
//                    'agent_create_time' => date('Y-m-d H:i:s')
//                ];
//                $OilCardModel = M('oil_card');
//                $result3 = $OilCardModel -> where( ['card_no' => $i ] ) -> save( $save_data );
//            }
//            //记录代理申领的信息
//            $UserApplyModel = M('user_apply');
//            $insert_user_apply_data = [
//                'user_id' => $data['user_id'],
//                'card_number' => count($arr),
//                'shop_name' => '中石油加油卡',
//                'receive_person' => $data['name'],
//                'phone' => $data['phone'],
//                'address' => $data['address'],
//                'deliver_number' => count($arr),
//                'serial_number' => '20181130'.rand(100000,999999),
//                'status' => 1,
//                'createtime' => date('Y-m-d H:i:s')
//            ];
//            $result4 = $UserApplyModel -> add( $insert_user_apply_data );
//
//            if( $result1 && $result2 && $result3 && $result4 ){
//                echo json_encode([
//                    'msg' => 1000,
//                    'status' => '添加成功'
//                ]);
//            }else{
//                echo json_encode(['msg' => 500,'status' => '添加失败']);
//            }

            $user_id = I('get.user_id');
            $openid = I('get.openid');
            $user = M('user')->where('id="'.$user_id.'" AND openid="'.$openid.'"')->find();

            $OilCardModel = M('oil_card');
            $start_card = $OilCardModel -> where(['status' => 1,'chomd'=>1]) -> getField('card_no');

            $this -> assign( 'start_card' , $start_card );
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
        $frist_card = $OilCardModel -> where(['status' => 1,'chomd'=>1]) -> find();

        $AgentModel = M('agent');
        $agent_info = $AgentModel -> where( ['id' => $id,'role'=>3] ) -> find();
        $user = M('user')->where('id="'.$id.'" AND openid="'.$agent_info['openid'].'"')->find();

        $sendCardData['card_no'] = $frist_card['card_no'];
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
        $data = I('post.');

        $UserModel = M('user');
        $user_info = $UserModel -> where( ['openid' => $data['openid']] ) -> find();

        // 获取发卡卡段区间（操作修改oil_Card）
        for( $i = $data['start']; $i <= $data['end']; $i++ ){
            $arr[] = $i;
            $save_oilcard_where = [ 'card_no' => $i ];
            $save_oilcard_data = [
                // 'status' => 2,
                'agent_create_time' => date('Y-m-d H:i:s'),
                'agent_id' => $data['user_id'],
                'agent_status' => 1,
                'user_id' => $user_info['id'],
                'chomd' => 2
            ];
            # 将此区间的卡状态更改为启用
            $OilCardModel = M('oil_card');
            $result1 = $OilCardModel -> where( $save_oilcard_where ) -> save( $save_oilcard_data );
        }

        //添加卡附属信息（记录该代理拿卡区间）
        $insert_agent_library_data = [
            'user_id' => $data['user_id'],
            'openid' => $data['openid'],
            'start_card_no' => $data['start'],
            'end_card_no' => $data['end'],
            'each_price' => $data['each_price'],
            'card_mode' => $data['mode'],
            'createtime' => date('Y-m-d H:i:s'),
        ];
        $AgentLibraryModel = M('agent_library');
        $result2 = $AgentLibraryModel -> add( $insert_agent_library_data );

        //记录代理的附属信息
        $insert_user_apply_data = [
            'user_id' => $data['user_id'],
            'receive_person' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'card_number' => count($arr),
            'shop_name' => '中石油加油卡',
            'deliver_number' => count($arr),
            'serial_number' => '20180313'.rand(100000,999999)
        ];
        $UserApplyModel = M('user_apply');
        $result3 = $UserApplyModel -> add($insert_user_apply_data);

        if( $result1 && $result2 && $result3 ){
            echo json_encode(['msg' => 'success','status' => 1000]);
        }else{
            echo json_encode(['msg' => 'error','status' => 500]);
        }
    }

    /**
     * 查看该代理的发卡记录
     */
    public function sendCardRecord(){
        $id = trim( I('get.id') );
        $p = I('get.p',1);
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
        $select_where = [
            'openid' => $agent_info['openid']
        ];
        $agent_library_data = $AgentLibraryModel -> where( $select_where ) -> page( $p , 10 ) -> select();
        foreach( $agent_library_data as $k => $v ){
            $agent_library_data[$k]['nickname'] = M('user') -> where(['openid' => $v['openid']] ) -> getField('nickname');
        }
        $count = $AgentLibraryModel -> where( $select_where ) -> count();
        $Page = new \Think\Page($count,10);
        $show = $Page -> show();
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
                $where .= ' AND oil_card.is_notmal = "'.$status.'"';
            }

        }
        if(!empty($keywords)){
            $where .= ' AND oil_card.card_no LIKE "%'.$keywords.'%"';
        }
        $agent_library_data = $oilCardModel
            ->join('user ON user.id=oil_card.user_id',LEFT)
            ->where($where)
            ->page( $p , $pageNum )
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


}