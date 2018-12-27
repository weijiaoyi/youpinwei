<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\oil\controller;

use cmf\controller\AdminBaseController;
// use app\oil\model\PortalPostModel;
use app\oil\service\PostService;
// use app\oil\model\PortalCategoryModel;
use think\Db;

/**
 * 所有需要登陆后才能放访问的接口，需要继承此控制器
 */
class AdminBaseController extends AdminBaseController
{
    public function _initialize(){
        parent::_initialize();
        $key = input('key');
        if ($key) {
            echo 1111;
        }else{
            echo 2222;
        }


        
    }    


}
