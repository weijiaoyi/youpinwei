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

<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">  
<meta name="apple-mobile-web-app-status-bar-style" content="black">  
<meta content="telephone=no" name="format-detection">

<link rel="stylesheet" href="/public/assets/style/css/common.css"/>
<link rel="stylesheet" href="/public/js/bootstrap.min.css">
<body>
<div id="wrapper">
	
</div>

<div style="text-align:center;margin:50px 0; font:normal 14px/24px 'MicroSoft YaHei';">
</div>

	<div class="wrap js-check-wrap" style="margin-top:-50px;">
		<ul class="nav nav-tabs">
			<li class="active"><a href="<?php echo U('Admin/Order/C_orderList'); ?>">油卡列表</a></li>
			<!-- <li><a href="<?php echo U('Admin/Order/CardApply'); ?>">申领列表</a></li> -->
			<li><a href="<?php echo U('Admin/Order/CardBack'); ?>">退卡申请</a></li>
			<li><a href="<?php echo U('Admin/Order/CardLoss'); ?>">挂失申请</a></li>
		</ul>
		<thead>

		<p style="text-align:center;font-size: 20px;font-weight: bold;margin-top: 20px;">
			总部可发油卡：<span style="color: red;font-weight: bold;font-size: 28px;"><?php echo $send_count;?></span>张
		</p>
		</thead>
		<form class="well form-search" method="post" action="<?php echo U('order/C_orderList');?>">

			关键字： 
			<input type="text" name="card_no" style="width: 190px; height:34px;" value="" placeholder="请输入要查询的油卡...">
			<input type="submit" class="btn btn-primary CardKeyword " value="搜索">
			<a href="javascript:;" class="import-many btn">批量导入</a>

			<!-- <a href="javascript:;" class="discount-many btn">批量折扣</a> -->
		</form>

		<table class="table table-hover table-bordered">
			<thead>
				<tr>
					<th>ID</th>
					<th>用户</th>
					<th style="text-align:center;">卡号</th>
					<?php if($_isAdmin === true): ?><th style="text-align:center;">优惠折扣</th><?php endif; ?>
					<th style="text-align:center;">入库时间</th>
					<th style="text-align:center;">申领时间</th>
					<th style="text-align:center;">油卡所属</th>
					<th style="text-align:center;">发货状态</th>
					<th style="text-align:center;">激活</th>
					<th style="text-align:center;">油卡状态</th>
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
							<?php echo empty($v['nickname'])?'未绑定':$v['nickname'].'&nbsp;&nbsp;'.'<img src="'.$v['user_img'].'" width="35" hight="35">'; ?>
						</td>
						<td style="text-align:center;">
							<?php echo $v['card_no']; ?>
						</td>
						<td style="text-align:center;">
							<?php echo number_format($v['scale']);?>折
						</td>
						<td style="text-align:center;" title="">
							<?php echo $v['createtime'];?>
						</td>
						<td style="text-align:center;">	
							<?php echo $v['apply_fo_time']; ?>
						</td>
						<td style="text-align:center;">
						<?php  if($v['agent_id'] ==0 ){ echo "总部卡"; }else{ echo GetAgentInfo($v['agent_id']); } ?>
						</td>
						<td style="text-align:center;">
						<?php  switch ($v['chomd']) { case '1': echo '未发放'; break; case '2': echo '已发放'; break; } ?>
						</td>
						<td style="text-align:center;">
						<?php  switch ($v['activate']) { case '1': echo '未激活'; break; case '2': echo '已激活'; break; } ?>
						</td>
						<td style="text-align:center;">
						<?php  switch ($v['is_notmal']) { case '1': echo '<span style="color: green;">正常</span>'; break; case '2': echo '<span style="color: red;">冻结使用</span>'; break; case '3': echo '<span style="color: gray;">注销油卡</span>'; break; default: echo '<span style="color: green;">正常</span>'; break; } ?>
						</td>
						<td style="width:300px; height: 53px; text-align:center;">
							<?php if ($v['is_notmal'] == 2){?>
							<button style="background: #1dccaa;border:2px; width: 70px; height: 40px;"  value="<?php echo $v['id']; ?>" class="depro"><span style="color: white; font-size: 8px;">启用</span></button>
							<?php }else{?>
								<button style="background: #2c3e50;border:2px; width: 70px; height: 40px;"  value="<?php echo $v['id']; ?>" class="edpro"><span style="color: white; font-size: 8px;">禁用</span></button>
							<?php }?>
							<!-- <button style="background: #2c3e50;border:2px; width: 70px; height: 40px;"  value="<?php echo $v['id']; ?>" class="chongzhi"><span style="color: white; font-size: 8px;">充值</span></button>
							<button style="background: #2c3e50;border:2px; width: 70px; height: 40px;"  value="<?php echo $v['id']; ?>" class="tuika"><span style="color: white; font-size: 8px;">退卡</span></button> -->
						</td>
					</tr>
				<?php } ?>
				
			</tbody>
		</table>
			<div id="page" class="pagination"><span class="page"><?php echo $page; ?></span></div>
	</div>
	<!-- 充值弹出层 -->
	

	<!-- <div class="modal-content"> -->
	
	<!-- 批量折扣弹出层 -->
	<!-- <div class="modal-content"> -->
		<!-- 成败获成功显示 -->
		
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
						<th>起始卡号&nbsp&nbsp&nbsp&nbsp&nbsp<input type="text" placeholder="请输入起始卡号" value="" style="height:30px; width: 200px;" maxlength="16" class="start_card_no"></th>
					</tr>
					<tr>
						<th>结尾卡号&nbsp&nbsp&nbsp&nbsp&nbsp<input type="text" placeholder="请输入结尾卡号" value="" style="height:30px; width: 200px;" maxlength="16" class="end_card_no"></th>
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

<!-- <script src="http://www.jq22.com/jquery/jquery-1.10.2.js"></script> -->
<script src="/public/js/jquery-1.9.1.min.js"></script>
<script src="/public/js/bootstrap.min.js"></script>

<script>
	/** 根据卡号查询该油卡信息 */
	// $(document).on('click','.CardKeyword',function(){
	//     var card_no = $('input[name=keyword]').val();
	//     $.ajax({
	// 		type:'post',
	// 		url:"<?php echo U('order/C_orderList');?>",
	// 		dataType:'json',
	// 		data:{card_no:card_no},
	// 		success:function(result){
	// 			$('#show').html(result.str);
	// 			$('#page').html(result.page);
	// 		}
	// 	});
	// });

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
				url:"<?php echo U('order/addCard');?>",
				dataType:'json',
				data:{start_card_no:start_card_no,end_card_no:end_card_no,discount:discount,card_note:card_note},
				async:false,
				success:function(res){
					layer.msg(res.status);
					var url = "<?php echo U('order/C_orderList');?>";
					setTimeout(function(){

						location.href=url;
					}, 2000);

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
	$(document).on('click','.edpro',function(){
		var id = $(this).val();
		$.ajax({
			type:'post',
			url:"<?php echo U('order/ProhibitCard');?>",
			dataType:'json',
			data:{id:id},
			// async:false,
			success:function(res){
				if(res.msg == 'success'){
                    window.location.reload();
				}else{
                    $('.alert').text('禁用失败');
                    $('.alert').show().delay (1000).fadeOut();
                    $('.alert').show().delay (1000).fadeOut();
				}

			}
		});
	});

	$(document).on('click','.depro',function(){
		var id = $(this).val();
		$.ajax({
			type:'post',
			url:"<?php echo U('order/deProhibitCard');?>",
			dataType:'json',
			data:{id:id},
			// async:false,
			success:function(res){
				if(res.msg == 'success'){
                    window.location.reload();
				}else{
                    $('.alert').text('启用失败');
                    $('.alert').show().delay (1000).fadeOut();
                    $('.alert').show().delay (1000).fadeOut();
				}

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


	/** 退卡 */
	$(document).on('click','.tuika',function(){
		var discountid = $(this).val();
		$.ajax({
			type:'post',
			url:"<?php echo U('order/withdrawCard');?>",
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