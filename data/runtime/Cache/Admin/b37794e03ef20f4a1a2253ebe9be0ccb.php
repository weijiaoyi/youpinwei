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
<link rel="stylesheet" href="/public/js/bootstrap.min.css">
</head>
<body>
	<div class="wrap js-check-wrap">
		<ul class="nav nav-tabs">
			<li class="active"><a href="<?php echo U('Admin/Deliver/C_deliverList'); ?>">订单列表</a></li>
		</ul>
		<form class="well form-search" method="post" action="<?php echo U('Admin/Deliver/C_deliverList'); ?>">
			<!--分类： -->
			<!--<select class="select_2" name="status" style="width:120px;">-->
				<!--<option value="0">申领</option>-->
				<!--<option value="1">绑定</option>-->
				<!--<option value="2">充值</option>-->
			<!--</select> &nbsp;&nbsp;-->
			关键字： 
			<input type="text" name="keyword" style="height:34px; width: 200px;" value="" placeholder="请输入要查询的订单ID...">
			<input type="button" class="btn btn-primary order-number-keyword" value="搜索">
		</form> 
		<!--<form style="display: block;" id="excel-import" method="post" action="<?php echo U('Admin/Goods/importFromExcel'); ?>" enctype="multipart/form-data">-->
			<!--<input type="file" name="file" style="display: none;" />-->
			<!--<input style="float: right;margin-top: -70px;margin-right: 20px;" id="import" type="submit" class="btn btn-primary" value="Excel导入">-->
		<!--</form>-->
		<form class="js-ajax-form" action="<?php echo U('Admin/Deliver/C_saveDeliverListOrder'); ?>" method="post" novalidate="novalidate">
			<table class="table table-hover table-bordered">
				<thead>
					<tr>
						<th width="50">ID</th>
						<th>订单ID</th>
						<th>油卡</th>
						<th>时间</th>
						<th>分类</th>
						<th>充值金额</th>
						<th>支付金额</th>
						<th>优惠金额</th>
						<th style="width:170px;">操作</th>
					</tr>
				</thead>
				<tbody id="show">
					<?php foreach ($data as $k => $v) { ?>
						<tr>
							<td style="text-align:center;"><?php echo $v['id']; ?></td>
							<td style="text-align:center;"><?php echo $v['serial_number']; ?></td>
							<td style="text-align:center;"><?php echo $v['card_no']; ?></td>
							<td style="text-align:center;"><?php echo $v['createtime']; ?></td>
							<td style="text-align:center;"><?php echo $v['order_type']; ?></td>
							<td style="text-align:center;"><?php echo '<font style="color:red">￥</font>'.$v['money']; ?></td>
							<td style="text-align:center;"><?php echo '<font style="color:red">￥</font>'.$v['real_pay']; ?></td>
							<td style="text-align:center;"><?php echo '<font style="color:red">￥</font>'.$v['discount_money']; ?></td>
							<td>
								<input type="hidden" class="this_id" value="<?php echo $v['id']; ?>">
								<input type="hidden" id="uid" class="uid" value="<?php echo $v['user_id']; ?>">
								<input type="hidden" class="card_no" value="<?php echo $v['card_no']; ?>">
								<input type="button" class="delivers" style="margin-left:30px; background: #2c3e50;border:0px; width: 100px; height: 36px; color: white; font-size: 8px;" value="立即发货">
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</form>
			<div class="modal-dialog">
			<!-- <div class="modal-content"> -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: block;padding-left: 0px;margin-left:500px;height:340px;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				&times;
			</button>
			<h4 class="modal-title" id="myModalLabel" align="center">
				发货
			</h4>
		</div>
		<div class="modal-body">
			<table>
				<tr>
					<th>商品&nbsp&nbsp&nbsp&nbsp&nbsp<span id="sp"></span></th>
				</tr>
				<tr>
					<th>充值&nbsp&nbsp&nbsp&nbsp&nbsp<span id="cz"></span></th>
				</tr>
				<tr>
					<th>收货人&nbsp&nbsp&nbsp&nbsp&nbsp<span id="shr"></span></th>
				</tr>
				<tr>
					<th>联系方式&nbsp&nbsp&nbsp&nbsp&nbsp<span id="lxfs"></span></th>
				</tr>
				<tr>
					<th>收货地址&nbsp&nbsp&nbsp&nbsp&nbsp<span id="shdz"></span></th>
				</tr>
				<tr>
					<th>选择快递&nbsp&nbsp&nbsp&nbsp&nbsp<span id="kd"></span></th>
				</tr>
				<tr>
					<th><input type="hidden" style="display:none" id="uuid"></th>
				</tr>
				<tr>
					<th>请录入快递单号&nbsp&nbsp&nbsp&nbsp&nbsp<input class="express-number" type="text"></th>
				</tr>
			</table>
		</div>
		<div class="modal-footer" style="text-align:center">
			<button type="button" style="width:100px;" class="btn btn-default" data-dismiss="modal">返回
			</button>
			<button type="button" style="width:100px;" class="btn btn-primary send-out-goods" >
				发货
			</button>
		</div>
	</div>

	<div class="modal fade" id="myModal1" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: block;padding-left: 0px;margin-left:500px;height:340px;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				&times;
			</button>
			<h4 class="modal-title" id="myModalLabel" align="center">
				发货详情
			</h4>
		</div>
		<div class="modal-body">
			<table>
				<tr>
					<th>商品&nbsp&nbsp&nbsp&nbsp&nbsp<span id="q"></span></th>
				</tr>
				<tr>
					<th>充值&nbsp&nbsp&nbsp&nbsp&nbsp<span id="u"></span></th>
				</tr>
				<tr>
					<th>收货人&nbsp&nbsp&nbsp&nbsp&nbsp<span id="t"></span></th>
				</tr>
				<tr>
					<th>联系方式&nbsp&nbsp&nbsp&nbsp&nbsp<span id="a"></span></th>
				</tr>
				<tr>
					<th>收货地址&nbsp&nbsp&nbsp&nbsp&nbsp<span id="n"></span></th>
				</tr>
				<tr>
					<th>选择快递&nbsp&nbsp&nbsp&nbsp&nbsp<span id="c"></span></th>
				</tr>
				
			</table>
		</div>
		<div class="modal-footer" style="text-align:center">
			
			<button type="button" style="width:100px;" class="btn btn-primary push-message"  >
				推送消息
			</button>
			<button type="button" style="width:100px;" class="btn btn-default" data-dismiss="modal">返回
			</button>
		</div>
	</div>

	</div>

		<div id="page" class="pagination"><?php echo $page; ?></div>
	</div>
	<script src="/public/js/common.js"></script>
	<script src="/public/js/jquery-1.9.1.min.js"></script>
	<script src="/public/js/bootstrap.min.js"></script>

</body>
<script type="text/javascript">
	/** 关键字搜索 */
	$(document).on('click','.order-number-keyword',function(){
	    var keyword = $('input[name=keyword]').val();
		$.ajax({
			type:'post',
			url:"<?php echo U('Deliver/orderKeywordList');?>",
			data:{keyword:keyword},
			dataType:'json',
			success:function(result){
			    $('#show').html(result.str);
			    $('#page').html(result.page);
			}
		});
	});

	//推送消息
	$(document).on('click','.push-message',function(){
		card_no = $('.card_number').html();
		user_id = $('#uuid').val();
		$.ajax({
	        type:"post",
	        url:"<?php echo U('Deliver/C_deliverPushMessage');?>",
			data:{card_no:card_no,user_id:user_id},
	        success:function(result){
				//判断推送成功
	            console.log(result);
	        }
	    });
	});

	//确认发货
	function test(){
		card_no = $('.card_number').html();
		user_id = $('#uid').val();
		$.ajax({
		        type:"post",
		        url:"<?php echo U('Deliver/C_deliverEnterSend');?>",
				data:{card_no:card_no,user_id:user_id},
		        success:function(data){
		            $("#q").html(data.card_names);//交互成功回调
		            $("#i").html(data.card_number);//交互成功回调
		            $("#u").html(data.recharge_money);//交互成功回调
		            $("#t").html(data.consignee_name);//交互成功回调
		            $("#a").html(data.consignee_phone);//交互成功回调
		            $("#n").html(data.consignee_address);//交互成功回调
		            $("#c").html(data.which_express);//交互成功回调
					$('#myModal1').modal('show');//显示弹层
		        }
		    });
	}

//点击发货
$(document).on('click','.send-out-goods',function(){
	number = $('.express-number').val();
	card_no = $('.card_number').html();
	$.ajax({
        type:"post",//规定传输方式
        url:"<?php echo U('Deliver/C_deliverSendGoods');?>",//提交URL
		data:{number:number,card_no:card_no},
        success:function(res){
			test();             
        }
    });

});

$(function(){
	var imported = false;
	$('#import').click(function(){
		if(imported === false){
			$('input[type="file"]').trigger('click');
			imported = true;
			return false;
		}else{
			imported = false;
		}
	});
});

$(document).on('click','.delivers',function(){
	var num = $(this).index();
	var this_id = $(this).siblings().val();
	var user_id = $(this).parent().children().eq(1).val();
	var card_no = $(this).parent().children().eq(2).val();
	$.ajax({
        type:"post",//规定传输方式
        url:"<?php echo U('Deliver/C_deliverdetail');?>",//提交URL
		data:{id:this_id,uid:user_id,card_no:card_no},
        success:function(data){
            $("#sp").html(data.card_name);//交互成功回调
            $("#kh").html(data.card_number);//交互成功回调
            $("#cz").html(data.recharge_money);//交互成功回调
            $("#shr").html(data.consignee_name);//交互成功回调
            $("#lxfs").html(data.consignee_phone);//交互成功回调
            $("#shdz").html(data.consignee_address);//交互成功回调
            $("#kd").html(data.which_express);//交互成功回调
			$("#uuid").val(data.user_id);
			$('#myModal').modal('show');//显示弹层
       }
   });

})


</script>
</html>