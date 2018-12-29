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
        <div class="well">
            年费套餐列表 --暂只读
        </div>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th style="text-align:center;">VIP年费(元)</th>
            <th style="text-align:center;">时间轴</th>
            <th style="text-align:center;">优惠比例(折扣)</th>
            <th style="text-align:center;">年度可用额(元)</th>
            <th style="text-align:center;">增加时间</th>
            <th style="text-align:center;">修改时间</th>
        </tr>
        </thead>
        <tbody id="show">
        <?php if(is_array($packages)): $i = 0; $__LIST__ = $packages;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$val): $mod = ($i % 2 );++$i;?><tr>
                <td style="width:40px;text-align:center;">
                    <?php echo ($val['pid']); ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ncPriceFormatb($val['price']);?>
                </td>
                <td style="text-align:center;">
                    <?php echo AxisFomat($val['axis']);?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['scale']); ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ncPriceFormatb($val['limits'],'limit');?>
                </td>
                <td style="text-align:center;">
                    <?php echo dateFomat($val['addtime']);?>
                </td>
                <td style="text-align:center;">
                    <?php echo dateFomat($val['updatetime']);?>
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <!-- <div id="page" class="pagination"><?php echo $page; ?></div> -->
</div>
<script src="/public/js/common.js"></script>
</body>
</html>