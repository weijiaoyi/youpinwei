<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/3/21
 * Time: 18:08
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;


class PromptController extends AdminbaseController
{
    /**
     * 判断是否有新的订单
     */
    public function  siNewOrder(){
        $OrderRecordModel = M('order_record');
        $where='order_type = 3 AND order_status = 2 AND is_import = 1 ';
        $news = $OrderRecordModel->where($where)-> count();
        if($news>0){
            exit( json_encode(['status'=>1,'msg'=>'有新的订单']) );
        }else{
            exit( json_encode(['status'=>0,'msg'=>'有新的订单']) );
        }
    }



}