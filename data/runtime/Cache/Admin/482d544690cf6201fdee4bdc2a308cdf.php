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
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
        <style type="text/css">
            body, html{width: 100%;height: 100%;margin:0;font-family:"微软雅黑";font-size:14px;}
            .true{
                color: red;
            }
        </style>
        <title></title>
    </head>
<body>
<!-- <form action="" enctype="multipart/form-data" method=""> -->
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo U('sendCard'); ?>">发卡</a></li>
    </ul>

    <div class="row-fluid">
        <div class="span9">
            <table class="table table-bordered">
                <tr>
                    <th>代理商名称：</th>
                    <td style="font-size: 16px;line-height: 39px;">
                        <span><?php echo ($user['nickname']); ?>&nbsp;&nbsp;&nbsp;&nbsp;<img src="<?php echo ($user['user_img']); ?>" alt="<?php echo ($user['user_img']); ?>" style="height: 39px;" /></span>
                        <!--<input type="text" name="nickname" class="nickname" value="" style="width:150px" disabled="disabled" placeholder="请输入代理商名称">-->
                        <!--<input type="button" value="搜索" style="width:60px;height: 36px; margin-bottom:10px;" class="server-keyword">-->
                    </td>
                </tr>
                <tr>
                    <th>开始卡号：</th>
                    <td style="font-size: 16px;line-height: 39px;">
                        <span><?php echo ($data['card_no']); ?></span>
                        <!--<input type="text" name="start_card" id="start_card" value="<?php echo $data['card_no'];?>"  disabled="disabled"  placeholder="开始卡号">-->
                    </td>
                </tr>
                <tr>
                    <th>结尾卡号<span class="true">*</span>：</th>
                    <td>
                        <input type="text" name="end_card" id="end_card"  placeholder="请输入结束卡号">
                    </td>
                </tr>
                <tr>
                    <th>拿卡状态<span class="true">*</span>：</th>
                    <td>
                        <select name='mode' class="mode">
                            <option selected="selected">请选择</option>
                            <option value="1" >正常</option>
                            <option value="2" >赊账</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>拿卡价格<span class="true">*</span>：</th>
                    <td>
                        <input type="text" name="each_price" class="each_price" placeholder="请输入拿卡价格">
                    </td>
                </tr>

                <tr>
                    <th>送货地址<span class="true">*</span>：</th>
                    <td>
                        <textarea style="width:300px;" id="address" name="address"  placeholder="送货地址"></textarea>
                    </td>
                </tr>
                <tr>
                    <th>联系人<span class="true">*</span>：</th>
                    <td>
                        <input type="text" name="tel_name" id="detail"   placeholder="联系人">
                    </td>
                </tr>
                <tr>
                    <th>联系电话<span class="true">*</span>：</th>
                    <td>
                        <input type="text" name="tel" id="peopel"   placeholder="联系电话">
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="form-actions" >
        <button style="margin-left:400px;" class="btn btn-primary js-ajax-submit" id="sendCard" type="submit">确认发卡</button>
        <a class="btn" href="javascript:history.go(-1);">返回</a>
    </div>
</div>

<!-- 成败获成功显示 -->
<div class="mengban" style="position: fixed;width:100%;height: 100%;background: #337ab7;opacity: 0.5;display: none"></div>
<div class="alert" style="width:200px;height: 110px;background: white;position: absolute;top:50%;left: 50%;margin-top: -80px;margin-left:-300px;display:none;line-height: 80px;text-align: center;font-size:20px;color: #000"></div>
<!-- </form> -->
<script type="text/javascript" src="/public/js/common.js"></script>
<script type="text/javascript" src="/public/js/jquery.min.js"></script>
<script>
    /** 给代理商发卡(确认发卡) */
    $(document).on('click','#sendCard',function(){
        $('#sendCard').attr('disabled','disabled');
        var start_card = "<?php echo $data['card_no'] ?>";
        var openid = "<?php echo $data['openid'] ?>";
        var user_id = "<?php echo $data['uid'] ?>";
        var end_card = $('#end_card').val();
        var mode = $('.mode').find('option:selected').val();
        var each_price = $('.each_price').val();
        var address = $('#address').val();
        var name = $('#detail').val();
        var phone = $('#peopel').val();
        if(end_card == '' || mode == '' || each_price == '' || address == '' || name == '' || phone == ''){
            $('#sendCard').removeAttr('disabled');
            layer.msg('请补全完整的信息内容！！！',{icon:2});return false;
        }
        $.ajax({
            url:"<?php echo U('confirmSendCard');?>",
            type:'post',
            data:{start:start_card,end:end_card,each_price:each_price,mode:mode,address:address,name:name,phone:phone,openid:openid,user_id:user_id},
            success:function (res) {
                res = $.parseJSON(res);
                if(res.status == 200){
                    layer.msg(res.msg,{icon:1,time:3000},function(){
                        window.location.reload();
                    });
                }else{
                    layer.msg(res.msg,{icon:2,time:3000},function(){
                        window.location.reload();
                    });
                }
            }
        })
    });
</script>
</body>
</html>