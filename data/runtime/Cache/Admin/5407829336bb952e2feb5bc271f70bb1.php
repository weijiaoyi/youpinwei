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
    <script type="text/javascript" src="/public/Api.js"></script>
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
        <!-- 搜索 start by LEE  -->
        <form class="well form-search" method="post" action="<?php echo U('Admin/Grade/gradelist'); ?>">
            <!--当前状态： -->
            <!--<select class="select_1" name="status">-->
                <!--<option value="-1" <?php if($where['status'] == -1) echo 'selected="selected"';?>>所有</option>-->
                <!--<option value="0" <?php if(isset($where['status']) && $where['status'] == 0) echo 'selected="selected"';?>>正常</option>-->
                <!--<option value="1" <?php if($where['status'] == 1) echo 'selected="selected"';?>>禁用</option>-->
            <!--</select> &nbsp;&nbsp;-->
            关键字： 
            <input type="text" name="keyword" style="width: 200px;" value="" placeholder="请输入要查询联系人名称...">
            <input type="button" id="selectUser" class="btn btn-primary order-number-pay-keyword" value="搜索">
        </form>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th style="text-align:center;">订单ID</th>
            <th style="text-align:center;">时间</th>
            <th style="text-align:center;">卡号</th>
            <th style="text-align:center;">操作人</th>
            <th style="text-align:center;">充值金额</th>
            <th style="text-align:center;">支付金额</th>
        </tr>
        </thead>
        <tbody id="show">
        <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$val): $mod = ($i % 2 );++$i;?><tr>
                <td style="width:40px;text-align:center;">
                    <?php echo ($val['id']); ?>
                </td>
                <td style="width:40px;text-align:center;">
                    <?php echo ($val['serial_number']); ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['createtime']); ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['card_no']); ?>
                </td>
                <td style="text-align:center;">
                    <font style="color:red"></font>Admin
                </td>
                <td style="text-align:center;">
                    <font style="color:red">￥</font><?php echo ($val['money']); ?>
                </td>
                <td style="text-align:center;">
                    <font style="color:red">￥</font><?php echo ($val['real_pay']); ?>
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <div id="page" class="pagination"><?php echo $page; ?></div>
</div>
<script src="/public/js/common.js"></script>
</body>
</html>

<script type="text/javascript">
    /** 订单编号(关键字搜索) */
    $(document).on('click','.order-number-pay-keyword',function(){
        var keyword = $('input[name=keyword]').val();
        $.ajax({
            type:'post',
            url:'http://ysy.edshui.com/index.php?g=admin&m=order&a=R_orderNumberKeyword',
            data:{keyword:keyword},
            dataType:'json',
            success:function(result){
                $('#show').html(result.str);
                $('#page').html(result.page);
            }
        })

    })
</script>