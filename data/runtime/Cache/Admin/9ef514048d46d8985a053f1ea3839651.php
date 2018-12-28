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

<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">  
<meta name="apple-mobile-web-app-status-bar-style" content="black">  
<meta content="telephone=no" name="format-detection">

<link rel="stylesheet" href="/public/assets/style/css/common.css"/>
<link rel="stylesheet" href="/public/js/bootstrap.min.css">
<body>
<div id="wrapper">
	<div class="box">
		<div id="dialogBg"></div>
		<div id="dialog" class="animated" style="width: 500px">
			<img class="dialogIco" width="50" height="50" src="/public/assets/style/images/ico.png" alt="" />
			<div class="dialogTop">
				<a href="javascript:;" class="claseDialogBtn">关闭</a>
			</div>
			<!-- //http://ysy.edshui.com/index.php?g=admin&m=order&a=add_card -->
			<form action="" method="post" id="editForm">
				<ul class="editInfos">
					<li style="width: 100%">起始卡号：<input id="start_card_no" placeholder="起始卡号：" type="text" style="padding: 1px 0px;  width:300px" name="start_card_no" required  class="ipt" /></li>
					<li style="width: 100%">结尾卡号：<input id="end_card_no" placeholder="结尾卡号：" type="text" style="padding: 1px 0px;  width:300px" name="end_card_no" required  class="ipt" /></li>
					<li>折扣：
						<select name='discount' class="discount">
							<option selected="selected">请选择</option>
							<option value="95" >95</option>
							<option value="93" >93</option>
						</select>
					</li>
					<li>备注：<input type="text" placeholder="备注：" style="padding: 1px 0px;  width:180px" id='card_note' name="card_note" required class="ipt" /></li>
					<li><input type="button" value="确认提交" class="submitBtn" /></li>
				</ul>
			</form>
		</div>
	</div>
</div>

<div style="text-align:center;margin:50px 0; font:normal 14px/24px 'MicroSoft YaHei';">
</div>

	<div class="wrap js-check-wrap" style="margin-top:-50px;">
		<ul class="nav nav-tabs">
			<li class="active"><a href="<?php echo U('Admin/Order/C_orderList'); ?>">油卡列表</a></li>
		</ul>
		<form class="well form-search" method="post" action="<?php echo U('Admin/Order/C_orderList'); ?>">
			<!--订单状态： -->
			<!--<select class="select_2" name="status" style="width:120px;">-->
				<!--<option value="-1" <?php if($where['status'] == -1) echo 'selected="selected"';?>>所有</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_CREATED; ?>" <?php if(isset($where['status']) && $where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_CREATED) echo 'selected="selected"';?>>下单成功</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_STATION_ACCEPT; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_STATION_ACCEPT) echo 'selected="selected"';?>>已接单</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_DELIVERING; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_DELIVERING) echo 'selected="selected"';?>>配送中</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_FINISHED; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_FINISHED) echo 'selected="selected"';?>>已完成</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_CANCELED; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_CANCELED) echo 'selected="selected"';?>>已取消</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_STATUS_CLOSED; ?>" <?php if($where['status'] == Common\Lib\Constant::B2C_ORDER_STATUS_CLOSED) echo 'selected="selected"';?>>已关闭</option>-->
			<!--</select> &nbsp;&nbsp;-->
			<!--<?php if($_isAdmin === true): ?>-->
			<!--服务号： -->
			<!--<select class="select_2" name="config" style="width:120px;">-->
				<!--<option value="-1">所有</option>-->
				<!--<?php if(is_array($wechatconfig)): foreach($wechatconfig as $key=>$vo): ?>-->
					<!--<option value="<?php echo ($vo['id']); ?>" <?php if(isset($where['config']) && $where['config'] == $vo['id']) echo 'selected="selected"';?>><?php echo ($vo['wechat_name']); ?></option>-->
				<!--<?php endforeach; endif; ?>-->
			<!--</select> &nbsp;&nbsp;-->
			<!--<?php endif; ?>-->
			<!--支付方式： -->
			<!--<select class="select_2" name="pay_type" style="width:120px;">-->
				<!--<option value="-1" <?php if($where['pay_type'] == -1) echo 'selected="selected"';?>>所有</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_TYPE_CASH; ?>" <?php if(isset($where['pay_type']) && $where['pay_type'] == Common\Lib\Constant::B2C_ORDER_PAY_TYPE_CASH) echo 'selected="selected"';?>>现金支付</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_TYPE_WECHAT; ?>" <?php if($where['pay_type'] == Common\Lib\Constant::B2C_ORDER_PAY_TYPE_WECHAT) echo 'selected="selected"';?>>微信支付</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_TYPE_TICKET; ?>" <?php if($where['pay_type'] == Common\Lib\Constant::B2C_ORDER_PAY_TYPE_TICKET) echo 'selected="selected"';?>>水票支付</option>-->
			<!--</select> &nbsp;&nbsp;-->
			<!--支付状态：-->
			<!--<select class="select_2" name="pay_status" style="width:120px;">-->
				<!--<option value="-1" <?php if($where['pay_status'] == -1) echo 'selected="selected"';?>>所有</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_NOPAY; ?>" <?php if(isset($where['pay_status']) && $where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_NOPAY) echo 'selected="selected"';?>>未支付</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_SUCCESS; ?>" <?php if($where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_SUCCESS) echo 'selected="selected"';?>>支付成功</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_FAILED; ?>" <?php if($where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_FAILED) echo 'selected="selected"';?>>支付失败</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKING; ?>" <?php if($where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKING) echo 'selected="selected"';?>>退款中</option>-->
				<!--<option value="<?php echo Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKED; ?>" <?php if($where['pay_status'] == Common\Lib\Constant::B2C_ORDER_PAY_STATUS_BACKED) echo 'selected="selected"';?>>已退款</option>-->
			<!--</select> &nbsp;&nbsp;-->
			<!--时间：-->
			<!--<input type="text" name="start_time" class="js-date date" value="<?php echo $where['start_time']; ?>" style="width: 100px;text-align:center;" autocomplete="off">- -->
			<!--<input type="text" class="js-date date" name="end_time" value="<?php echo $where['end_time']; ?>" style="width: 100px;text-align:center;" autocomplete="off"> &nbsp; &nbsp;-->
			关键字： 
			<input type="text" name="keyword" style="width: 190px; height:34px;" value="" placeholder="请输入要查询的油卡...">
			<input type="button" class="btn btn-primary CardKeyword " value="搜索">
			<a href="javascript:;" class="import-many btn">批量导入</a>
			<a href="javascript:;" class="discount-many btn">批量折扣</a>
		</form>

		<table class="table table-hover table-bordered">
			<thead>
				<tr>
					<th>ID</th>
					<th>系统ID</th>
					<th style="text-align:center;">卡号</th>
					<?php if($_isAdmin === true): ?><th style="text-align:center;">优惠折扣</th><?php endif; ?>
					<th style="text-align:center;">入库时间</th>
					<th style="text-align:center;">申领时间</th>
					<th style="text-align:center;">状态</th>
					<th style="text-align:center;">查看</th>
				</tr>
			</thead>
			<tbody id="show">
				<?php foreach ($data as $k => $v) { ?>
					<tr>
						<td style="width:40px;text-align:center;">
							<?php echo $v['id']; ?>
						</td>
						<td style="text-align:center;">
							<?php echo $v['system_id']; ?>
						</td>
						<td style="text-align:center;">
							<?php echo $v['card_no']; ?>
						</td>
						<td style="text-align:center;">
							<?php echo $v['discount'].'折'; ?>
						</td>
						<td style="text-align:center;" title="<?php echo $v['name']; ?>">
							<?php echo $v['createtime'];?>
						</td>
						<td style="text-align:center;">	
							<?php echo $v['apply_fo_time']; ?>
						</td>
						<td style="text-align:center;">
						<?php  if($v['status'] == '1'){ echo '库存'; }else if($v['status'] == '2'){ echo '启用'; }else{ echo '下架'; } ?>
						</td>
						<td style="width:300px; height: 53px; text-align:center;">
							<button style="background: #2c3e50;border:2px; width: 70px; height: 40px;"  value="<?php echo $v['id']; ?>" class="xiajia"><span style="color: white; font-size: 8px;">下架</span></button>
							<button style="background: #2c3e50;border:2px; width: 70px; height: 40px;"  value="<?php echo $v['id']; ?>" class="chongzhi"><span style="color: white; font-size: 8px;">充值</span></button>
							<button style="background: #2c3e50;border:2px; width: 70px; height: 40px;"  value="<?php echo $v['id']; ?>" class="tuika"><span style="color: white; font-size: 8px;">退卡</span></button>
						</td>
					</tr>
				<?php } ?>
				
			</tbody>
		</table>
			<div id="page" class="pagination"><span class="page"><?php echo $page; ?></span></div>
	</div>
	<!-- 充值弹出层 -->
	<div class="modal-dialog">
	<!-- <div class="modal-content"> -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: block;padding-left: 0px;margin-left:500px;height:340px;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				&times;
			</button>
			<h4 class="modal-title" id="myModalLabel" align="center">
				油卡充值
			</h4>
		</div>
		<div class="modal-body">
			<table>
				<tr>
					<th>充值卡号&nbsp&nbsp&nbsp&nbsp&nbsp<input id="sp" value="" type="text"></th>
					<input type="hidden" id="discount" value="">
				</tr>
				<tr>
					<th>请选择面额&nbsp&nbsp&nbsp&nbsp&nbsp</th>
				</tr>
				<tr>
					<th>
						<input type="button" class="clickmoney" value='200'>
						<input type="button" class="clickmoney" value='500'>
						<input type="button" class="clickmoney" value='1000'>
						<input type="button" class="clickmoney" value='2000'>
						<input type="button" class="clickmoney" value='5000'>
					</th>
				</tr>

				<tr>
					<th>自定义金额&nbsp&nbsp&nbsp&nbsp&nbsp<input type="text" id="shr"></th>
				</tr>
				<tr>
					<th>优惠后支付金额&nbsp&nbsp&nbsp&nbsp&nbsp<span id="lxfs"></span></th>
					<th>省油钱&nbsp&nbsp&nbsp&nbsp&nbsp<span id="lxfs"></span></th>
				</tr>
				<tr>
					<th>备注信息&nbsp&nbsp&nbsp&nbsp&nbsp<input class="remarks-infomation" type="text"></th>
				</tr>
			</table>
		</div>
		<div class="modal-footer" style="text-align:center">
			<button type="button" style="width:100px;" class="btn btn-default" data-dismiss="modal">返回
			</button>
			<button type="button" style="width:100px;" class="btn btn-primary go-pay" data-dismiss="modal" >
				去支付
			</button>
		</div>
	</div>

	<div class="modal-dialog">
	<!-- <div class="modal-content"> -->
	<div class="modal fade" id="modal12" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: block;padding-left: 0px;margin-left:500px;height:340px;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				&times;
			</button>
			<h4 class="modal-title" id="myModalLabel" align="center">
				油卡充值状态
			</h4>
		</div>
		<div class="modal-body">
			<table>
				<tr>
					<th>充值卡号&nbsp&nbsp&nbsp&nbsp&nbsp<span id="callback_card_no">123</span></th>
					
				</tr>
				<tr>
					<th>充值额&nbsp&nbsp&nbsp&nbsp&nbsp<span id="callback_recharge_money">123</span></th>
				</tr>
			
				<tr>
					<th>消费额&nbsp&nbsp&nbsp&nbsp&nbsp<span id="callback_expenditure">123</span></th>
				</tr>
				<tr>
					<th>备注&nbsp&nbsp&nbsp&nbsp&nbsp<span id="callback_infomation">123</span></th>
				</tr>
			</table>
		</div>
		<div class="modal-footer" style="text-align:center">
			<button type="button" style="width:100px;" class="btn btn-default" data-dismiss="modal">返回
			</button>
		</div>
	</div>
	
	<!-- 批量折扣弹出层 -->
	<div class="modal-dialog">
	<!-- <div class="modal-content"> -->
	<div class="modal fade" id="Discountmany" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: block;padding-left: 0px;margin-left:500px;height:340px;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				&times;
			</button>
			<h4 class="modal-title" id="myModalLabel" align="center">
				设置折扣
			</h4>
		</div>
		<div class="modal-body">
			<table>
				<tr>
					<th>起始卡号&nbsp&nbsp&nbsp&nbsp&nbsp<input type="text" placeholder="请输入起始卡号" style="height:30px; width: 200px;" class="start"></th>
					
				</tr>
				<tr>
					<th>结尾卡号&nbsp&nbsp&nbsp&nbsp&nbsp<input type="text" placeholder="请输入结尾卡号" style="height:30px; width: 200px;" class="over"></th>
				</tr>
			
				<tr>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp
					<select name='discounts' class="DiscountMany">
						<option selected="selected">请选择折扣</option>
						<option value="95" >95</option>
						<option value="93" >93</option>
					</select>
				</tr>
				<tr>
					<th>备注&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp <input type="text" placeholder="填写备注吧" style="height:60px; width: 200px;" class="infomations"></th>
				</tr>
			</table>
		</div>
		<div class="modal-footer" style="text-align:center">
			<button type="button" style="width:100px;" class="btn btn-default" data-dismiss="modal">返回
			</button>
			<button type="button" style="width:100px;" class="btn btn-primary keep" data-dismiss="modal" >
				保存
			</button>
		</div>
	</div>
		<!-- 成败获成功显示 -->
		<div class="mengban" style="position: fixed;width:100%;height: 100%;background: white;opacity: 0.5;display: none"></div>
		<div class="alert" style="width:200px;height: 110px;background: #8c8887;position: absolute;top:50%;left: 50%;margin-top: -80px;margin-left:-270px;display:none;line-height: 80px;text-align: center;font-size:20px;color: #000"></div>

	<!-- 批量导入弹出层 -->
	<div class="modal-dialog">
		<!-- <div class="modal-content"> -->
		<div class="modal fade" id="ImportMany" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: block;padding-right: 0px;margin-left:500px;height:340px;">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title" id="myModalLabel" align="center">
					批量导入
				</h4>
			</div>
			<div class="modal-body">
				<table>
					<tr>
						<th>起始卡号&nbsp&nbsp&nbsp&nbsp&nbsp<input type="text" placeholder="请输入起始卡号" value="" style="height:30px; width: 200px;" class="start_card_no"></th>
					</tr>
					<tr>
						<th>结尾卡号&nbsp&nbsp&nbsp&nbsp&nbsp<input type="text" placeholder="请输入结尾卡号" value="" style="height:30px; width: 200px;" class="end_card_no"></th>
					</tr>

					<tr>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp
						<select name='discount' class="ImportMany">
							<option selected="selected">请选择折扣</option>
							<option value="95" >96</option>
							<option value="95" >95</option>
							<option value="93" >93</option>
						</select>
					</tr>
					<tr>
						<th>备注&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp <input type="text" value="" placeholder="填写备注吧" style="height:60px; width: 200px;" class="card_note"></th>
					</tr>
				</table>
			</div>
			<div class="modal-footer" style="text-align:center">
				<button type="button" style="width:100px;" class="btn btn-default" data-dismiss="modal">返回
				</button>
				<button type="button" style="width:100px;" class="btn btn-primary keep" data-dismiss="modal" >
					立即导入
				</button>
			</div>
		</div>
</body>
</html>

<script src="http://www.jq22.com/jquery/jquery-1.10.2.js"></script>
<script src="/public/js/jquery-1.9.1.min.js"></script>
<script src="/public/js/bootstrap.min.js"></script>

<script>
	/** 根据卡号查询该油卡信息 */
	$(document).on('click','.CardKeyword',function(){
	    var card_no = $('input[name=keyword]').val();
	    $.ajax({
			type:'post',
			url:'http://ysy.edshui.com/index.php?g=admin&m=order&a=R_cardKeyword',
			dataType:'json',
			data:{card_no:card_no},
			success:function(result){
				$('#show').html(result.str);
				$('#page').html(result.page);
			}
		});
	});

	/** 批量导入 */
	$(document).on('click','.import-many',function(){
		$('#ImportMany').modal('show');
		$(document).on('click','.keep',function(){
			var start_card_no = $('.start_card_no').val();
			var end_card_no = $('.end_card_no').val();
			var discount=$('.ImportMany').val();
			var card_note=$('.card_note').val();
			$.ajax({
				type:'post',
				url:'http://ysy.edshui.com/index.php?g=admin&m=order&a=addCard',
				dataType:'json',
				data:{start_card_no:start_card_no,end_card_no:end_card_no,discount:discount,card_note:card_note},
				async:false,
				success:function(res){
					console.log(res);
					// if (res.status==500) {
					// 	$('.alert').text('导入失败啦');
					// 	$('.alert').show().delay (1000).fadeOut();
					// 	$('.alert').show().delay (1000).fadeOut();
					// }else{
					// 	$('.alert').text('导入成功');
					// 	$('.alert').show().delay (1000).fadeOut();
					// 	$('.alert').show().delay (1000).fadeOut();
					// }
				}
			});
		});
	});

	/** 下架 */
	$(document).on('click','.xiajia',function(){
		var id = $(this).val();
		$.ajax({
			type:'post',
			url:'http://ysy.edshui.com/index.php?g=admin&m=order&a=underGoods',
			dataType:'json',
			data:{id:id},
			// async:false,
			success:function(res){
				if(res.msg == 'success'){
                    window.location.reload();
				}else{
                    $('.alert').text('下架失败啦');
                    $('.alert').show().delay (1000).fadeOut();
                    $('.alert').show().delay (1000).fadeOut();
				}

			}
		});
	});

	/** 去支付 */
	$(document).on('click','.go-pay',function(){
		var card_no = $("#sp").val();
		var money = $("#shr").val();
		var discount = $("#discount").val();
		var message = $(".remarks-infomation").val();
		$.ajax({
			type:'post',
			url:'http://ysy.edshui.com/index.php?g=admin&m=order&a=rechargeMoney',
			dataType:'json',
			data:{card_no:card_no,discount:discount,money:money,message:message},
			success:function(res){
				$('#callback_card_no').html(res.card_no);
				$('#callback_recharge_money').html(res.recharge);
				$('#callback_expenditure').html(res.expenditure);
				$('#callback_infomation').html(res.infomation);
				$('#modal12').modal('show');
			}
		});
	});

	/** 点击后将卡号回显到页面 */
	function card_on_input(data){
		$("#sp").val(data.card_no);
		$("#discount").val(data.discount);
		$('#myModal').modal('show');
		$(document).on('click','.clickmoney',function(){
			var clickmoney = $(this).val();
			$('#shr').val(clickmoney);
		});
	}

	/** 点击充值 */
	$(document).on('click','.chongzhi',function(){
		var this_id = $(this).val();
		$.ajax({
	        type:"post",//规定传输方式
	        url:"http://ysy.edshui.com/index.php?g=admin&m=order&a=getCard",//提交URL
			data:{id:this_id},
	        success:function(data){
				card_on_input(data);
            }
        });
	});

	/** 退卡 */
	$(document).on('click','.tuika',function(){
		var discountid = $(this).val();
		$.ajax({
			type:'post',
			url:'http://ysy.edshui.com/index.php?g=admin&m=order&a=withdrawCard',
			dataType:'json',
			data:{discountid:discountid},
			success:function(res){
                if(res.msg == 'success'){
                    $('.alert').text(res.data);
                    $('.alert').show().delay (1000).fadeOut();
                    $('.alert').show().delay (1000).fadeOut();
                }else{
                    $('.alert').text(res.data);
                    $('.alert').show().delay (1000).fadeOut();
                    $('.alert').show().delay (1000).fadeOut();
                }
			}
		});
	});

	/** 单卡折扣 */
	$(document).on('click','.zhekou',function(){
		var discountid = $(this).val();
		$.ajax({
			type:'post',
			url:'http://ysy.edshui.com/index.php?g=admin&m=order&a=discountThis',
			dataType:'json',
			data:{discountid:discountid},
			success:function(res){
				console.log(res);
			}
		});
	});

	/** 批量折扣 */
	$(document).on('click','.discount-many',function(){
		$('#Discountmany').modal('show');
		$(document).on('click','.keep',function(){
			var start = $('.start').val();
			var over = $('.over').val();
			var discounts = $('.DiscountMany').val();
			var infomations = $('.infomations').val();
			$.ajax({
				type:'post',
				url:'http://ysy.edshui.com/index.php?g=admin&m=order&a=discountMany',
				dataType:'json',
				data:{start:start,over:over,discounts:discounts,infomations:infomations},
				success:function(res){
					if(res.msg == 'success'){
						$('.alert').text('操作成功');
						$('.alert').show().delay (1000).fadeOut();
						$('.alert').show().delay (1000).fadeOut();
					}else{
						$('.alert').text('操作失败啦');
						$('.alert').show().delay (1000).fadeOut();
						$('.alert').show().delay (1000).fadeOut();
					}
				}
			});
		});
	});

</script>
<script type="text/javascript">
    // partial
var w,h,className;
function getSrceenWH(){
	w = $(window).width();
	h = $(window).height();
	$('#dialogBg').width(w).height(h);
}

window.onresize = function(){  
	getSrceenWH();
}
$(window).resize();

$(function(){
	getSrceenWH();

	//显示弹框
	$('.partial').click(function(){
		className = $(this).attr('class');
		$('#dialogBg').fadeIn(300);
		$('#dialog').removeAttr('class').addClass('animated '+className+'').fadeIn();
	});

	//关闭弹窗
	$('.claseDialogBtn').click(function(){
		$('#dialogBg').fadeOut(300,function(){
			$('#dialog').addClass('bounceOutUp').fadeOut();
		});
	});
});
</script>

<script type="text/javascript">
	
	$('.submitBtn').click(function(){
		var start_card_no=$('#start_card_no').val();
		var end_card_no=$('#end_card_no').val();
		var discount=$('.discount').val();
		var card_note=$('#card_note').val();
		$.ajax({
			type:'post',
			url:'http://ysy.edshui.com/index.php?g=admin&m=order&a=addCard',
			dataType:'json',
			data:{start_card_no:start_card_no,end_card_no:end_card_no,discount:discount,card_note:card_note},
			async:false,
			success:function(res){
				if (res.status==500) {

					alert(res.msg);
				}else{
					var aa='卡号区间： '+start_card_no+' 至 '+end_card_no+'导入成功----折扣：'+discount+'折----备注'+card_note;
					$("#dialog").show().delay(1000).hide(100);
					$('#dialogBg').css('display','none');
					alert(aa);
				}
			}
		})
	})


	
</script>