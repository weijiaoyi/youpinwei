<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<!-- Set render engine for 360 browser -->
	<meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- HTML5 shim for IE8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <![endif]-->

	<link href="/public/simpleboot/themes/<?php echo C('SP_ADMIN_STYLE');?>/theme.min.css" rel="stylesheet">
    <link href="/public/simpleboot/css/simplebootadmin.css" rel="stylesheet">
    <link href="/public/js/artDialog/skins/default.css" rel="stylesheet" />
    <link href="/public/simpleboot/font-awesome/4.4.0/css/font-awesome.min.css"  rel="stylesheet" type="text/css">
    <!-- <script type="text/javascript" src="/public/Api.js"></script> -->
    <style>
		form .input-order{margin-bottom: 0px;padding:3px;width:40px;}
		.table-actions{margin-top: 5px; margin-bottom: 5px;padding:0px;}
		.table-list{margin-bottom: 0px;}
	</style>
	<!--[if IE 7]>
	<link rel="stylesheet" href="/public/simpleboot/font-awesome/4.4.0/css/font-awesome-ie7.min.css">
	<![endif]-->
	<script type="text/javascript">
	//全局变量
	var GV = {
		DIMAUB: "/",
	    ROOT: "/",
	    WEB_ROOT: "/",
	    JS_ROOT: "public/js/",
	    APP:'<?php echo (MODULE_NAME); ?>'/*当前应用名*/
	};
	</script>
    <script src="/public/js/jquery.js"></script>
    <script src="/public/js/wind.js"></script>
    <script src="/public/simpleboot/bootstrap/js/bootstrap.min.js"></script>
    <script src="/public/js/layer/layui.all.js"></script>
    <script src="/public/js/layer/layui.js"></script>
    <script src="/public/js/layer/layer.js"></script>
 	<link rel="stylesheet" href="/public/js/layer/css/layui.css">       

    <script>
    	// $(function(){
    	// 	$("[data-toggle='tooltip']").tooltip();
    	// });
    </script>
<?php if(APP_DEBUG): ?><style>
		#think_page_trace_open{
			z-index:9999;
		}
	</style><?php endif; ?>
<style type="text/css">
    .pagination{float: right;margin-right: 20px;}
    .pagination a, .pagination span{padding: 3px 10px;margin-left: 3px;border-radius: 3px;}
    .pagination a{background-color: #dadada;border: 1px solid #d1d1d1;color: black;text-decoration: none;}
    .pagination span{background-color: orangered;border: 1px solid orangered;color: white;cursor: default;}
</style>
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo U('gradelist'); ?>">普通会员列表</a></li>
        <!--<li><a href="<?php echo U('add_grade'); ?>">添加代理商</a></li>-->
    </ul>

    <!-- 搜索 start by LEE  -->
    <form class="well form-search" method="post" action="<?php echo U('Admin/Grade/ordinarylist'); ?>">
        当前用户状态：
        <select class="select_1" name="status">
            <option value="" >所有</option>
            <option value="1" <?php if(isset($status) && $status == 1) echo 'selected="selected"';?>>正常</option>
            <option value="2" <?php if(isset($status) && $status == 2) echo 'selected="selected"';?>>冻结</option>
            <option value="3" <?php if(isset($status) && $status == 3) echo 'selected="selected"';?>>注销</option>
        </select> &nbsp;&nbsp;
        关键字：
        <input type="text" name="keywords" style="width: 200px;" value="<?php echo ($keywords); ?>" placeholder="请输入要查询联系人名称">
        <input type="submit" id="selectUser" class="btn btn-primary" value="搜索">
    </form>

    <table class="table table-hover table-bordered">
        <thead>
        <p style="text-align:center;font-size: 20px;font-weight: bold;margin-top: 20px;">
            普通会员：<span style="color: red;font-weight: bold;font-size: 28px;"><?php echo $count;?></span>人
        </p>
        </thead>
        <thead>
        <tr>
            <th>ID</th>
            <th style="text-align:center;">用户名称</th>
            <th style="text-align:center;">头像</th>
            <th style="text-align:center;">代理称号</th>
            <th style="text-align:center;">总押金</th>
            <th style="text-align:center;">上级用户</th>
            <!--<th style="text-align:center;">发展来源</th>-->
            <th style="text-align:center;">状态</th>
            <th style="text-align:center;">创建时间</th>
            <!--<th style="text-align:center;">过期时间</th>-->
             <th style="text-align:center;">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$val): $mod = ($i % 2 );++$i;?><tr>
                <td style="width:40px;text-align:center;">
                    <?php echo ($key+1); ?>
                </td>
                <td style="text-align:center;">
                    <?php if($val['nickname'] == ''): echo ($val['openid']); ?>
                    <?php else: ?>
                        <?php echo ($val['nickname']); endif; ?>
                </td>
                <td style="width:40px;text-align:center;">
                    <img src="<?php echo ($val['user_img']); ?>" alt="<?php echo ($val['user_img']); ?>" />
                </td>
                <td style="text-align:center;">
                    <?php if($val['role'] == '1'): ?>普通用户<?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($val['deposit']); ?></span>元
                </td>
                <td style="text-align:center;">
                    <?php if($val['parentid'] != ''): echo ($val['parent_nickname']); ?>
                        <img src="<?php echo ($val['parent_img']); ?>" alt="<?php echo ($val['parent_img']); ?>" style="width: 40px;height: 40px;" />
                        <?php else: ?>
                        <span style="font-size: 14px;color: #999999;font-weight: bold;">无上级</span><?php endif; ?>
                </td>
                <!--<td style="text-align:center;">
                    <?php if($val['development'] == '1'): ?>总部发展
                        <?php elseif($val['development'] == '2'): ?>
                        代理商发展
                        <?php else: ?>
                        业务员发展<?php endif; ?>
                </td>-->
                <td style="text-align:center;">
                    <?php if($val['is_notmal'] == '1'): ?><span style="font-size: 14px;color: #1dccaa;font-weight: bold;">正常</span>
                        <?php elseif($val['is_notmal'] == '2'): ?>
                            <span style="font-size: 14px;color: #9a161a;font-weight: bold;">冻结</span>
                        <?php elseif($val['is_notmal'] == '3'): ?>
                            <span style="font-size: 14px;color: #999999;font-weight: bold;">注销</span><?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['createtime']); ?>
                </td>
                <!--<td style="text-align:center;">-->
                    <!--<?php echo ($val['expire_time']); ?>-->
                <!--</td>-->
                <td style="text-align:center;">
                    <button style="background: #2c3e50;border:2px; width: 110px; height: 40px;" ><a href="<?php echo U('getMyCard',['user_id'=>$val['id']]);?>"><span style="color: white; font-size: 8px;">查看我的油卡</span></a></button>
                    <button style="background: #1dccaa;border:2px; width: 110px; height: 40px;" ><a href="<?php echo U('addAgent',['openid'=>$val['openid'],'user_id'=>$val['id']]);?>"><span style="color: white; font-size: 8px;">升级为代理商</span></a></button>
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <?php if($page != ''): ?><div id="page" class="pagination"><span class="page"><?php echo $page; ?></span></div><?php endif; ?>
</div>
<script src="/public/js/common.js"></script>
</body>
</html>