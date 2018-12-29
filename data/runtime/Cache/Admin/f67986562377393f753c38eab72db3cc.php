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
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <style type="text/css">
        body, html{width: 100%;height: 100%;margin:0;font-family:"微软雅黑";font-size:14px;}
        #l-map{height:300px;width:100%;}
        #r-result{width:100%;}
        #shows{
            display: none;
            position: fixed;
            z-index: 10000;
            left: 50%;
            top: 50%;
            width: 260px;
            height: 210px;
            background-color: white;
            opacity: 1;
            margin-top: -80px;
            margin-left: -130px;
            border-radius: 3px;
        }

        #bgs{
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0.6;
            background-color: black;
        }
        #btnGroupss{
            font-size: 0.9rem;
            margin-top: 0.5rem;
            margin-left: 1rem;
        }
        .dizhi{
            margin-top: 0.5rem;
            background: #58a4ff;
            color: #fff;
            text-align: center;
            width: 50%;
            margin-left: 20%;
        }
        #shuizhan{margin-top: 0.2rem;margin-left: 1rem}

        #cancelBtnPage{
            float: left;
            width: 50%;
            height: 50px;
            line-height: 50px;
            border-right: 1px solid #666666;
            color: #adadad;
            text-align: center;
        }

        .tishi{
            background: #58a4ff;
            height: 2rem;
            text-align: center;
            line-height: 2rem;
            color: #fff;
        }
        .anniu{
            background: #58a4ff;
            margin-top: 2rem;
            text-align: center;
            height: 2rem;
            line-height: 2rem;
            color: #fff;
            width: 50%;
            margin-left: 4rem;
        }
    </style>
    <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=B1fe242ae7bea1edfd0a8d3b39d291eb"></script>
    <script type="text/javascript" src="http://api.map.baidu.com/library/GeoUtils/1.2/src/GeoUtils_min.js"></script>
    <title>关键字输入提示词条</title>
</head>
<body>
<!-- <form action="" enctype="multipart/form-data" method=""> -->
    <div class="wrap js-check-wrap">
        <ul class="nav nav-tabs">
            <!--<li><a href="<?php echo U('agentlist'); ?>">代理商列表</a></li>-->
            <li class="active"><a href="<?php echo U('add_grade'); ?>">添加代理商</a></li>
        </ul>
        <!--<form action="<?php echo U('Admin/Order/C_doAddOrder'); ?>" method="post" class="form-horizontal js-ajax-forms" enctype="multipart/form-data">-->
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
                        <th>结尾卡号：</th>
                        <td>
                            <input type="text" name="end_card" id="end_card"  placeholder="请输入结束卡号">
                        </td>
                    </tr>
                    <tr>
                        <th>拿卡状态：</th>
                        <td>
                            <select name='mode' class="mode">
                                <option selected="selected">请选择</option>
                                <option value="1" >正常</option>
                                <option value="2" >赊账</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>拿卡价格：</th>
                        <td>
                            <input type="text" name="each_price" class="each_price" placeholder="请输入拿卡价格">
                        </td>
                    </tr>
                   
                    <input type="hidden" value="<?php echo ($user['openid']); ?>" class="openid">
                    <input type="hidden" value="<?php echo ($user['id']); ?>" class="uid">
                    <tr>
                        <th>送货地址：</th>
                        <td>
                            <input type="text" id="address" name="address" size="20" placeholder="送货地址" style="width:205px;" />
                        </td>
                    </tr>
                    <tr>
                        <th>联系人：</th>
                        <td>
                            <input type="text" name="tel_name" id="detail"   placeholder="联系人">
                        </td>
                    </tr>
                    <tr>
                        <th>联系电话：</th>
                        <td>
                            <input type="text" name="tel" id="peopel"   placeholder="联系电话">
                        </td>
                    </tr>
                    <tr>
                        <th>VIP直属会员充值分成：</th>
                        <td>
                            <input type="text" name="vip_direct_scale" id="vip_direct_scale"   placeholder="VIP直属会员充值分成">
                        </td>
                    </tr>
                    <tr>
                        <th>普通直属会员充值分成：</th>
                        <td>
                            <input type="text" name="user_direct_scale" id="user_direct_scale"   placeholder="普通直属会员充值分成">
                        </td>
                    </tr>
                    <tr>
                        <th>VIP间接会员充值分成：</th>
                        <td>
                            <input type="text" name="vip_indirect_scale" id="vip_indirect_scale"   placeholder="VIP间接会员充值分成">
                        </td>
                    </tr>
                    <tr>
                        <th>普通间接会员充值分成：</th>
                        <td>
                            <input type="text" name="user_indirect_scale" id="user_indirect_scale"   placeholder="普通间接会员充值分成">
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
        <!--</form>-->
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
        var serverTel = $('.serverTel').val();
        var start_card = $('#start_card').val();
        var end_card = $('#end_card').val();
        var mode = $('.mode').val();
        var each_price = $('.each_price').val();
        var address = $('#address').val();
        var name = $('#detail').val();
        var phone = $('#peopel').val();
        var openid = $('.openid').val();
        var user_id = $('.uid').val();
        $.ajax({
            url:"http://ysy.edshui.com/index.php?g=admin&m=grade&a=addAgent",
            type:'post',
            data:{serverTel:serverTel,start:start_card,end:end_card,each_price:each_price,mode:mode,address:address,name:name,phone:phone,openid:openid,user_id:user_id},
            success:function (res) {
                rel=eval('('+res+')');
                if (rel.msg==500) {
                    $('.alert').text('添加失败');
                    $('.alert').show().delay (1000).fadeOut();
                    $('.alert').show().delay (1000).fadeOut();
                }else{
                    $('.alert').text('添加成功');
                    $('.alert').show().delay (1000).fadeOut();
                    $('.alert').show().delay (1000).fadeOut();
                }
            }
        })
    });


    $("#uploadImage").on("change", function(){
        // Get a reference to the fileList
        var files = !!this.files ? this.files : [];

        // If no files were selected, or no FileReader support, return
        if (!files.length || !window.FileReader) return;

        // Only proceed if the selected file is an image
        if (/^image/.test( files[0].type)){

            // Create a new instance of the FileReader
            var reader = new FileReader();

            // Read the local file as a DataURL
            reader.readAsDataURL(files[0]);

            // When loaded, set image data as background of div
            reader.onloadend = function(){
                $("#image").attr('src',this.result);
            }
        }

    });

    $("#prev_lev").on('change',function () {
        var lev = $("#prev_lev option:selected").val();
        $.ajax({
            url:"<?php echo U('Admin/grade/ajaxGetPrev');?>",
            type:'POST',
            data:{lev:lev},
            success:function (res) {
                if(res.data)
                {
                    var data = res.data;
                    var str = '';
                    for(var i=0;i<data.length;i++)
                    {
                        str += '<option value="'+data[i].id+'">'+data[i].grade_name+'</option>'
                    }
                    $("#prev_name").html(str);
                }else {
                    $("#prev_name").html('<option value="0">无</option>');
                }
            }
        })
    });
</script>
</body>
</html>