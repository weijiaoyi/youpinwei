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
		<ul class="nav nav-tabs">
		   <li class="active"><a href="<?php echo U('Admin/Deliver/C_deliverList'); ?>">申领订单</a></li>
		   <li><a href="<?php echo U('Admin/Deliver/CardBindList'); ?>">绑卡订单</a></li>
		   <li><a href="<?php echo U('Admin/Deliver/UpGradeList'); ?>">升级订单</a></li>
		   <li><a href="<?php echo U('Admin/Deliver/RenewalsList'); ?>">续费订单</a></li>
		   <li><a href="<?php echo U('Admin/Order/orderListing'); ?>">充值订单</a></li>
		</ul>
		<form class="well form-search" method="post" action="<?php echo U('Admin/Deliver/C_deliverList'); ?>">
			
			关键字： 
			<input type="text" name="keyword"  style="height:34px; width: 150px;" value="<?php echo ($keyword); ?>" placeholder="请输入查询的订单ID或卡号">
			&nbsp;&nbsp;
			时间范围： 
			 <div class="layui-inline">
		      <div class="layui-input-inline">
		        <input type="text" class="layui-input" id="timeRange" placeholder=" - " style="height:34px; width: 180px;" name="timeRange">
		      </div>
		    </div>

			&nbsp;&nbsp;
			<input type="submit" class="btn btn-primary order-number-keyword" value="搜索">
			&nbsp;&nbsp;
			<a class="btn btn-primary " id="import" >导出待发货记录</a>

		</form> 
		<script type="text/javascript">
			layui.use('laydate', function(){
				var laydate = layui.laydate;
				//日期时间范围
				  laydate.render({
				    elem: '#timeRange'
				    ,range: true
				  });
			})
  		$('#import').click(function(){
            var timeRange = $('#timeRange').val();
            if(!timeRange){
                layer.msg('请选择时间范围!');
                return false;
            }
		    layer.confirm('确定导出'+timeRange+'的待发货记录？',['确定','取消'],function(){
		        window.location.href='<?php echo U("Deliver/inportExcel");?>&timeRange='+timeRange;
		        layer.msg('导出成功',{icon:1},function(){
		            window.location.reload();//页面刷新
		        });
		    })
		});
		</script>
		<!--<form style="display: block;" id="excel-import" method="post" action="<?php echo U('Admin/Goods/importFromExcel'); ?>" enctype="multipart/form-data">-->
			<!--<input type="file" name="file" style="display: none;" />-->
			<!--<input style="float: right;margin-top: -70px;margin-right: 20px;" id="import" type="submit" class="btn btn-primary" value="Excel导入">-->
		<!--</form>-->
		<form class="js-ajax-form" action="<?php echo U('Admin/Deliver/C_saveDeliverListOrder'); ?>" method="post" novalidate="novalidate">
			<table class="table table-hover table-bordered">
				<thead>
					<tr>
						<th width="50">排序</th>
						<th>订单编号</th>
						<th>申请人</th>
						<th>应发油卡</th>
						<th>绑定油卡</th>
						<th>应发货人</th>
						<th>生成时间</th>
						<th>订单类型</th>
						<!--<th>充值金额</th>-->
						<th>支付金额</th>
						<th>油卡押金</th>
						<th>邮寄费用</th>
						<!--<th>优惠金额</th>-->
						<th>订单状态</th>
						<th style="width:170px;">操作</th>
					</tr>
				</thead>
				<tbody id="show">
					<?php foreach ($data as $k => $v) { ?>
						<tr>
							<td style="text-align:center;"><?php echo $v['id']; ?></td>
							<td style="text-align:center;"><?php echo $v['serial_number']; ?></td>
							<td style="text-align:center;">
								<?php echo ($v['nickname']); ?><img src="<?php echo ($v['user_img']); ?>" alt="<?php echo ($v['user_img']); ?>" style="width: 40px;height: 40px;" />
							</td>
							<td style="text-align:center;">
								<?php if($v['send_card_no'] == ''){ ?>
									<span style="font-size: 14px;color: #9a161a;font-weight: bold;"><?php echo $v['send_card_no_message']; ?></span>
								<?php }else{?>
									<span style="font-size: 18px;color: #0C0C0C;font-weight: bold;"><?php echo $v['send_card_no']; ?></span>
								<?php }?>
							</td>
							<td style="text-align:center;">
								<?php if($v['card_no'] == ''){ ?>
								<span style="font-size: 14px;color: #9a161a;font-weight: bold;"><?php echo $v['card_no_message']; ?></span>
								<?php }else{?>
								<span style="font-size: 18px;color: #0C0C0C;font-weight: bold;"><?php echo $v['card_no']; ?></span>
								<?php }?>
							</td>
							<td style="text-align:center;">
								<?php if($v['agent_id'] == 0){ ?>
								<span style="font-size: 14px;color: #9a161a;font-weight: bold;"><?php echo $v['agent_id_message']; ?></span>
								<?php }else{?>
									<?php echo $v['agent_id_message']; ?>
								<img src="<?php echo $v['agent_img']; ?>" alt="<?php echo $v['agent_img']; ?>" style="width: 40px;height: 40px;" />
								<?php }?>
							</td>
							<td style="text-align:center;"><?php echo $v['createtime']; ?></td>
							<td style="text-align:center;">
								<?php if($v['order_type'] == ''){ ?>
								<span style="font-size: 14px;color: #9a161a;font-weight: bold;"><?php echo $v['order_type_message']; ?></span>
								<?php }else{?>
								<span style="font-size: 14px;color: #999999;font-weight: bold;"><?php echo $v['order_type_message']; ?></span>
								<?php }?>
							</td>
							<td style="text-align:center;">
								<span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($v['real_pay']); ?></span>元
							</td>
							<td style="text-align:center;">
								<span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($v['user_deposit']); ?></span>元
							</td>
							<td style="text-align:center;">
								<span style="font-size: 20px;color: red;font-weight: bold;"><?php echo ($v['postage']); ?></span>元
							</td>
							<td style="text-align:center;">
								<?php if($v['status'] == 1){?>
									<span style="font-size: 14px;color: #9a161a;font-weight: bold;"><?php echo $v['send_status']; ?></span>
								<?php }elseif($v['status'] == 2){?>
									<span style="font-size: 14px;color: #1dccaa;font-weight: bold;"><?php echo $v['send_status']; ?></span>
								<?php }elseif($v['status'] == 3){?>
									<span style="font-size: 14px;color: #999999;font-weight: bold;"><?php echo $v['send_status']; ?></span>
								<?php }?>
							</td>
							<td>
								<!--<input type="hidden" class="this_id" value="<?php echo $v['id']; ?>">
								<input type="hidden" id="uid" class="uid" value="<?php echo $v['user_id']; ?>">
								<input type="hidden" class="card_no" value="<?php echo $v['card_no']; ?>">-->

								<?php if($v['status'] == 1){?>
									<input type="button" class="delivers" data-id="<?php echo $v['id']; ?>" style="margin-left:30px; background: #2c3e50;border:0px; width: 100px; height: 36px; color: white; font-size: 8px;" value="立即发货">
								<?php }elseif($v['status'] == 2){?>
									<input type="button" disabled="disabled"  data-id="<?php echo $v['id']; ?>" style="margin-left:30px; background: #999999;border:0px; width: 100px; height: 36px; color: white; font-size: 8px;" value="待绑定">
								<?php }elseif($v['status'] == 3){?>
									<input type="button" disabled="disabled"  data-id="<?php echo $v['id']; ?>" style="margin-left:30px; background: #999999;border:0px; width: 100px; height: 36px; color: white; font-size: 8px;" value="已绑定">
								<?php }?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</form>
			<div class="modal-dialog">
			<!-- <div class="modal-content"> -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;padding-left: 0px;margin-left:500px;height:340px;width: 50%;">
		<div class="modal-header">
			<button type="button" class="close"  data-dismiss="modal" aria-hidden="true">
				<!--&times;-->
			</button>
			<h4 class="modal-title" align="center">
				确认发货
			</h4>
		</div>
		<div class="modal-body">
			<table class="table table-hover table-bordered">
				<thead>
				<tr>
					<th>订单编号</th>
					<th>应发卡号</th>
					<th>收货人</th>
					<th>联系方式</th>
					<th>收货地址</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<th id="serial_number"></th>
					<th id="send_card_no"></th>
					<th id="user_name"> </th>
					<th id="mobile"></th>
					<th id="address"></th>
				</tr>
				</tbody>
				<tr>
					<th>填写快递单号&nbsp&nbsp&nbsp&nbsp&nbsp</th>
					<th colspan="4"><input name="express_number" id="express_number" style="width:250px;height: 50px;" type="text"></th>
					<input name="serial_number" type="hidden" value="">
					<input name="order_id" type="hidden" value="">
				</tr>
			</table>
		</div>
		<div class="modal-footer" style="text-align:center">
			<button type="button" style="width:100px;" class="btn btn-default" data-dismiss="modal">取消
			</button>
			<button type="button" style="width:100px;" class="btn btn-primary send-out-goods" >
				发货
			</button>
		</div>
	</div>

	<div class="modal fade" id="myModal1" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;padding-left: 0px;margin-left:500px;height:340px;width: 50%;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				<!--&times;-->
			</button>
			<h4 class="modal-title"  align="center">
				发货详情
			</h4>
		</div>
		<div class="modal-body">
			<!--<table>
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
			</table>-->

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
	/*function test(){
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
	}*/

//点击发货
$(document).on('click','.send-out-goods',function(){
    var order_id = $('input[name=order_id]').val();
    var express_number = $('input[name=express_number]').val();
    var serial_number = $('input[name=serial_number]').val();
    if(order_id == '' || serial_number == ''){
        layer.msg('参数错误',{icon:2,time:500});return false;
	}
	if(express_number == '' ){
        layer.msg('请填写快递单号快递单号',{icon:2,time:500});return false;
	}
    $.ajax({
        type:"post",
        url:"<?php echo U('Deliver/C_deliverEnterSend');?>",
        data:{order_id:order_id,express_number:express_number,serial_number:serial_number},
        success:function(data){
            data = $.parseJSON(data);
			if(data.status == 200){
                layer.msg(data.message,{icon:1,time:500},function(){
                    window.location.reload();
                })
			}else{
			    layer.msg(data.message,{icon:2,time:500},function(){
			        window.location.reload();
				})
			}

        }
    });



	/*number = $('.express-number').val();
	card_no = $('.card_number').html();
	$.ajax({
        type:"post",//规定传输方式
        url:"<?php echo U('Deliver/C_deliverSendGoods');?>",//提交URL
		data:{number:number,card_no:card_no},
        success:function(res){
			test();             
        }
    });*/

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
//立即发货
$(document).on('click','.delivers',function(){
    var order_id = $(this).attr('data-id');
    $.ajax({
        type:"post",//规定传输方式
        url:"<?php echo U('Deliver/C_deliverdetail');?>",//提交URL
        data:{order_id:order_id},
        success:function(response){
            response=$.parseJSON(response);
            if(response.status == 200){
                var data=response.data;
                $('#serial_number').html(data.serial_number);
                $('#send_card_no').html(data.send_card_no);
                $('#user_name').html(data.user_name);
                $('#mobile').html(data.mobile);
                $('#address').html(data.address);
                $('input[name=serial_number]').val(data.serial_number);
                $('input[name=order_id]').val(data.order_id);
                $('#myModal').modal('show');//显示弹层
			}else{
                layer.msg(response.message,{icon:2,time:1000});return false;
			}

        }
    });

	/*var num = $(this).index();
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
   });*/

})
//查看发货信息
    $(document).on('click','.see',function(){
        var order_id = $(this).attr('data-id');
        $('#myModal1').modal('show');//显示弹层
        /*$.ajax({
            type:"post",//规定传输方式
            url:"<?php echo U('Deliver/C_deliverdetail');?>",//提交URL
            data:{order_id:order_id},
            success:function(response){
                response=$.parseJSON(response);
                if(response.status == 200){
                    var data=response.data;
                    $('#serial_number').html(data.serial_number);
                    $('#send_card_no').html(data.send_card_no);
                    $('#user_name').html(data.user_name);
                    $('#mobile').html(data.mobile);
                    $('#address').html(data.address);
                    $('input[name=serial_number]').val(data.serial_number);
                    $('input[name=order_id]').val(data.order_id);
                    $('#myModal').modal('show');//显示弹层
                }else{
                    layer.msg(response.message,{icon:2,time:1000});return false;
                }
            }
        });
*/
    })
</script>
</html>