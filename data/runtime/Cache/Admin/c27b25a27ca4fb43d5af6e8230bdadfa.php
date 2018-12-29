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
<!--<link rel="stylesheet" href="/public/js/bootstrap.min.css">-->
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo U('gradelist'); ?>">拥有油卡</a></li>
    </ul>
    <!-- 搜索 start by LEE  -->
    <form class="well form-search" method="post" action="<?php echo U('getMyCard',['user_id'=>$user['id']]); ?>">
        <p style="float: right;">操作用户名称：<img src="<?php echo ($user['user_img']); ?>" alt="<?php echo ($user['user_img']); ?>" style="height: 40px;" /><span style="margin-left: 10px;font-size: 16px;"><?php echo ($user['nickname']); ?></span></p>
        当前卡状态：
        <select class="select_1" name="status">
            <option value="" >所有</option>
            <option value="1" <?php if(isset($status) && $status == 1) echo 'selected="selected"';?>>正常</option>
            <option value="2" <?php if(isset($status) && $status == 2) echo 'selected="selected"';?>>冻结</option>
            <option value="3" <?php if(isset($status) && $status == 3) echo 'selected="selected"';?>>注销</option>
        </select> &nbsp;&nbsp;
        关键字：
        <input type="text" name="keywords" style="width: 200px;" value="<?php echo ($keywords); ?>" placeholder="请输入要查询卡号">
        <input type="submit" id="selectUser" class="btn btn-primary" value="搜索">
    </form>

    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th style="text-align:center;">系统ID</th>
            <th style="text-align:center;">卡号</th>
            <th style="text-align:center;">优惠折扣</th>
            <th style="text-align:center;">入库时间</th>
            <th style="text-align:center;">申领时间</th>
            <th style="text-align:center;">状态</th>
            <th style="text-align:center;">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$val): $mod = ($i % 2 );++$i;?><tr>
                <td style="text-align:center;">
                    <?php echo ($val['id']); ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['card_no']); ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['scale']); ?>折
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['createtime']); ?>
                </td>
                <td style="text-align:center;">
                    <?php echo ($val['apply_fo_time']); ?>
                </td>
                <td style="text-align:center;">
                    <?php if($val['is_notmal'] == '1'): ?>正常
                    <?php elseif($val['is_notmal'] == '2'): ?>
                        冻结
                        <?php else: ?>
                        注销<?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <!--<a href="<?php echo U('del',['id'=>$val['id']]);?>">删除</a>-->
                    <!--<a href="<?php echo U('del',['id'=>$val['id'],'flag'=>1]);?>">冻结</a>-->
                    <!--<button style="background: #2c3e50;border:2px; width: 70px; height: 40px;"  value="<?php echo $val['id']; ?>" class="xiajia"><span style="color: white; font-size: 8px;">下架</span></button>-->
                    <button style="background: #2c3e50;border:2px; width: 70px; height: 40px;"  value="<?php echo $val['id']; ?>" class="chongzhi"><span style="color: white; font-size: 8px;">充值记录</span></button>
                    <!--<button style="background: #2c3e50;border:2px; width: 70px; height: 40px;"  value="<?php echo $val['id']; ?>" class="tuika"><span style="color: white; font-size: 8px;">退卡</span></button>-->
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>

    <!-- 成败获成功显示 -->
    <div class="mengban" style="position: fixed;width:100%;height: 100%;background: white;opacity: 0.5;display: none"></div>
    <div class="alert" style="width:200px;height: 110px;background: #8c8887;position: absolute;top:50%;left: 50%;margin-top: -80px;margin-left:-200px;display:none;line-height: 114px;text-align: center;font-size:20px;color: #000"></div>

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

    <div class="pagination"><?php echo $page; ?></div>
</div>
<script src="/public/js/common.js"></script>
<script src="/public/js/jquery-1.9.1.min.js"></script>
<script src="/public/js/bootstrap.min.js"></script>
</body>
</html>
<script>
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
        })
    })

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

    //点击后将卡号回显到页面
    function card_on_input(data){
        $("#sp").val(data.card_no);
        $("#discount").val(data.discount);
        $('#myModal').modal('show');
        $(document).on('click','.clickmoney',function(){
            var clickmoney = $(this).val();
            $('#shr').val(clickmoney);
        });
    }

    //点击充值
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
</script>