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
    <script src="/public/js/layer/layer.js"></script>
    <link href="/public/js/layer/skin/layer.css" rel="stylesheet" />
    <script src="/public/simpleboot/bootstrap/js/bootstrap.min.js"></script>
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
        <li class="active"><a href="<?php echo U('agentlist'); ?>">发卡记录</a></li>
    </ul>

    <!-- 搜索 start by LEE  -->
    <form class="well form-search" method="post" action="<?php echo U('Admin/Grade/sendCardRecord',['id'=>$user['id']]); ?>">
        <p style="float: right;">操作用户名称：<img src="<?php echo ($user['user_img']); ?>" alt="<?php echo ($user['user_img']); ?>" style="height: 40px;" /><span style="margin-left: 10px;font-size: 16px;"><?php echo ($user['nickname']); ?></span></p>
        发卡日期：
        <input type="text" name="keyword" style="width: 200px;" value="" placeholder="暂无处理" disabled="disabled">
        <!--<input type="submit" id="selectUser" class="btn btn-primary" value="搜索">-->
    </form>



    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>ID</th>

            <th style="text-align:center;">开始卡号</th>
            <th style="text-align:center;">结束卡号</th>
            <th style="text-align:center;">拿卡价格/张</th>
            <th style="text-align:center;">总数</th>
            <th style="text-align:center;">拿卡类型</th>
            <th style="text-align:center;">发卡时间</th>
            <th style="text-align:center;">当前状态</th>
            <th style="text-align:center;">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$val): $mod = ($i % 2 );++$i;?><tr>
                <td style="width:40px;text-align:center;">
                    <?php echo ($val['id']); ?>
                </td>

                <td style="text-align:center;">
                    <?php echo ($val['start_card_no']); ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['end_card_no']); ?>
                </td>
                <td style="text-align:center;">
                    <span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($val['each_price']); ?></span>元
                </td>
                <td style="text-align:center;">
                    <span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($val['card_no_num']); ?></span>张
                </td>
                <td style="text-align:center;">
                    <?php if($val['card_mode'] == '1'): ?><span style="font-size: 14px;color: #00FF00;font-weight: bold;">正常</span>
                        <?php else: ?>
                        <span style="font-size: 14px;color: red;font-weight: bold;">赊账</span><?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['createtime']); ?>
                </td>
                <td style="text-align:center;">
                    <?php if($val['status'] == '1'): ?>未发货
                        <?php else: ?>
                        已发货<?php endif; ?>
                </td>
                <th style="text-align:center;">
                    <?php if($val['card_mode'] == '1'): ?><button style="border:0; width: 70px; height: 40px;" disabled="disabled"><span style="color: white; font-size: 8px;">操作完成</span></button>
                        <?php else: ?>
                        <button style="background: #1dccaa;border:0; width: 70px; height: 40px;" ><a href="<?php echo U('sendCard',['id'=>$val['id']]);?>"><span style="color: white; font-size: 8px;">清账</span></a></button><?php endif; ?>
                </th>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <?php if($page != ''): ?><div id="page" class="pagination"><span class="page"><?php echo $page; ?></span></div><?php endif; ?>
</div>
<script src="/public/js/common.js"></script>
</body>
</html>