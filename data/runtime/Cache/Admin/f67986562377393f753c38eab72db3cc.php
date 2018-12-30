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
            <li class="active"><a href="<?php echo U('add_grade'); ?>">添加代理商</a></li>
        </ul>
        <div class="row-fluid">
            <div class="span9">
                <table class="table table-bordered">
                    <tr>
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
                            <span><?php echo ($start_card); ?></span>
                            <!--<input type="text" name="start_card" id="start_card"  disabled="disabled"  placeholder="开始卡号">-->
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
                            <input type="text" name="each_price" value="" class="each_price" placeholder="请输入拿卡价格">
                        </td>
                    </tr>
                    <tr>
                        <th>送货地址<span class="true">*</span>：</th>
                        <td>
                            <input type="text" id="address" name="address" size="20" placeholder="送货地址" style="width:205px;" />
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
                    <tr>
                        <th>VIP直属会员充值分成<span class="true">*</span>：</th>
                        <td>
                            <input type="text" name="vip_direct_scale" id="vip_direct_scale"   placeholder="VIP直属会员充值分成"><span style="font-size: 24px;color: red;font-weight: normal">%</span>
                        </td>
                    </tr>
                    <tr>
                        <th>普通直属会员充值分成<span class="true">*</span>：</th>
                        <td>
                            <input type="text" name="user_direct_scale" id="user_direct_scale"   placeholder="普通直属会员充值分成"><span style="font-size: 24px;color: red;font-weight: normal">%</span>
                        </td>
                    </tr>
                    <tr>
                        <th>VIP间接会员充值分成<span class="true">*</span>：</th>
                        <td>
                            <input type="text" name="vip_indirect_scale" id="vip_indirect_scale"   placeholder="VIP间接会员充值分成"><span style="font-size: 24px;color: red;font-weight: normal">%</span>
                        </td>
                    </tr>
                    <tr>
                        <th>普通间接会员充值分成<span class="true">*</span>：</th>
                        <td>
                            <input type="text" name="user_indirect_scale" id="user_indirect_scale"   placeholder="普通间接会员充值分成"><span style="font-size: 24px;color: red;font-weight: normal">%</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="form-actions">
            <button style="margin-left:400px" class="btn btn-primary js-ajax-submit" id="addagent" type="submit">添加</button>
            <a class="btn" href="javascript:history.go(-1);">返回</a>
        </div>
        <div id="shows"></div>
    </div>

<!-- 成败获成功显示 -->
<div class="mengban" style="position: fixed;width:100%;height: 100%;background: white;opacity: 0.5;display: none"></div>
<div class="alert" style="width:200px;height: 110px;background: #8c8887;position: absolute;top:50%;left: 50%;margin-top: -80px;margin-left:-270px;display:none;line-height: 80px;text-align: center;font-size:20px;color: #000"></div>

<!-- </form> -->
<script type="text/javascript" src="/public/js/common.js"></script>
<script type="text/javascript" src="/public/js/jquery.min.js"></script>
<script>
    //点击代理商名称
  /*  $(document).on('click','.server-keyword',function(){
        var nickname = $('.nickname').val();
        if(!nickname){alert('会员名称不能为空！');$('.nickname').focus();return false;}
        $.ajax({
            url:"<?php echo U('Grade/getThisUser');?>",
            type:'post',
            data:{nickname:nickname},
            success:function (res) {
                $('.nickname').val(res.nickname);
                $('.openid').val(res.openid);
                $('#start_card').val(res.start_card);
                $('.uid').val(res.id);
            }
        })
    });*/

    //添加代理商
    $(document).on('click','#addagent',function(){
        $('#sendCard').attr('disabled','disabled');
        var openid = '<?php echo $user["openid"];?>';
        var user_id = '<?php echo $user["id"];?>';
        var start_card = '<?php echo $start_card;?>';
        var end_card = $('#end_card').val();
        var mode = $('.mode').find('option:selected').val();
        var each_price = $('.each_price').val();
        var address = $('#address').val();
        var name = $('#detail').val();
        var phone = $('#peopel').val();
        var vip_direct_scale = $('#vip_direct_scale').val();
        var user_direct_scale = $('#user_direct_scale').val();
        var vip_indirect_scale = $('#vip_indirect_scale').val();
        var user_indirect_scale = $('#user_indirect_scale').val();

        if(end_card == '' || mode == '' || each_price == '' || address == '' || name == '' || phone == '' || vip_direct_scale == '' || user_direct_scale == '' || vip_indirect_scale == '' || user_indirect_scale == ''){
            $('#addagent').removeAttr('disabled');
            layer.msg('请补全完整的信息内容！！！',{icon:2});return false;
        }

        $.ajax({
            url:"<?php echo U('grade/addAgent');?>",
            type:'post',
            data:{start:start_card,end:end_card,each_price:each_price,mode:mode,
                address:address,name:name,phone:phone,openid:openid,user_id:user_id,
                vip_direct_scale:vip_direct_scale,user_direct_scale:user_direct_scale,
                vip_indirect_scale:vip_indirect_scale,user_indirect_scale:user_indirect_scale},
            success:function (res) {
                res = $.parseJSON(res);
                if(res.status == 200){
                    layer.msg(res.msg,{icon:1,time:3000},function(){
                        window.location.href="<?php echo U('Grade/agentlist');?>";
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