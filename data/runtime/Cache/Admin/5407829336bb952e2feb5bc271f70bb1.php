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
<link rel="stylesheet" href="/public/js/bootstrap.min.css">
</head>
<body>

<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs" style="margin-bottom: 20px;">
        <li><a href="<?php echo U('Admin/Deliver/C_deliverList'); ?>">申领订单</a></li>
        <li><a href="<?php echo U('Admin/Deliver/CardBindList'); ?>">绑卡订单</a></li>
        <li><a href="<?php echo U('Admin/Deliver/UpGradeList'); ?>">升级订单</a></li>
        <li><a href="<?php echo U('Admin/Deliver/RenewalsList'); ?>">续费订单</a></li>
        <li class="active"><a href="<?php echo U('Admin/Order/orderListing'); ?>">充值订单</a></li>
    </ul>
        <!-- 搜索 start by LEE  -->
        <form class="well form-search" method="post" action="<?php echo U('Admin/Order/orderListing'); ?>">
            关键字： 
            <input type="text" name="keyword" style="width: 300px;height: 40px;" value="<?php echo ($keyword); ?>" placeholder="请输入查询订单的订单ID或卡号">
            &nbsp;&nbsp;
            时间范围：
            <div class="layui-inline">
                <div class="layui-input-inline">
                    <input type="text" class="layui-input" id="timeRange" placeholder=" - " style="height:34px; width: 180px;" name="timeRange">
                </div>
            </div>
            <input type="submit" id="selectUser" class="btn btn-primary order-number-pay-keyword" value="搜索">
            <a class="btn btn-primary " id="import">导出充值记录</a>
        </form>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th style="text-align:center;">订单ID</th>
            <th style="text-align:center;">充值用户</th>
            <th style="text-align:center;">充值卡号</th>
            <th style="text-align:center;">充值金额</th>
            <th style="text-align:center;">支付金额</th>
            <th style="text-align:center;">使用加油券</th>
            <th style="text-align:center;">折扣金额</th>
            <th style="text-align:center;">上级代理商</th>
            <th style="text-align:center;">代理分润</th>
            <th style="text-align:center;">充值时间</th>
            <th style="text-align:center;">操作</th>
        </tr>
        </thead>
        <tbody id="show">
        <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$val): $mod = ($i % 2 );++$i;?><tr>
                <td style="width:40px;text-align:center;">
                    <?php echo ($key+1); ?>
                </td>
                <td style="width:40px;text-align:center;">
                    <?php echo ($val['serial_number']); ?>
                </td>
                <td style="text-align:center;">
                    <img src="<?php echo ($val['user_img']); ?>" alt="<?php echo ($val['user_img']); ?>" style="width: 40px;height: 40px;" /><?php echo ($val['nickname']); ?>
                </td>

                <td style="text-align:center;">
                    <?php echo ($val['card_no']); ?>
                </td>

                <td style="text-align:center;">
                    <span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($val['money']); ?></span>元
                </td>
                <td style="text-align:center;">
                    <span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($val['real_pay']); ?></span>元
                </td>
                <td style="text-align:center;">
                    <span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($val['coupon_money']); ?></span>元
                </td>
                <td style="text-align:center;">
                    <span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($val['zk_money']); ?></span>元
                </td>
                <td style="text-align:center;">
                    <?php if($val['agent_name'] == ''): ?><span style="font-size: 20px;color: red;font-weight: bold;">总部</span>
                        <?php else: ?>
                        <img src="<?php echo ($val['agent_img']); ?>" alt="<?php echo ($val['agent_img']); ?>" style="width: 40px;height: 40px;" /><?php echo ($val['agent_name']); endif; ?>

                </td>
                <td style="text-align:center;">
                    <?php if($val['earnings'] == ''): ?><span style="font-size: 20px;color: red;font-weight: bold;">0.00</span>元
                        <?php else: ?>
                        <span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($val['earnings']); ?></span>元<?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['createtime']); ?>
                </td>
                <td style="text-align:center;">
                    <?php if($val['is_import'] == '1'): ?><span style="font-size: 20px;color: red;font-weight: bold;">未处理</span>
                        <?php else: ?>
                        <span style="font-size: 20px;color: red;font-weight: bold;">已处理</span><?php endif; ?>
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <?php if($page != ''): ?><div id="page" class="pagination"><span class="page"><?php echo $page; ?></span></div><?php endif; ?>
</div>
<script src="/public/js/common.js"></script>
</body>
</html>

<script type="text/javascript">
    layui.use('laydate', function(){
        var laydate = layui.laydate;
        //日期时间范围
        laydate.render({
            elem: '#timeRange'
            ,range: true
        });
    });
    $('#import').click(function(){
        var timeRange = $('#timeRange').val();
        if(!timeRange){
            layer.msg('请选择时间范围!');
            return false;
        }
        layer.confirm('确定导出'+timeRange+'的用户的充值记录吗？',['确定','取消'],function(){
            window.location.href='<?php echo U("Deliver/rechargeImportExcel");?>&timeRange='+timeRange;
            layer.msg('导出成功',{icon:1},function(){
                window.location.reload();//页面刷新
            });
        })
    });
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