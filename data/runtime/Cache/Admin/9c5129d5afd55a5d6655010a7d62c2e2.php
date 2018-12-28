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
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo U('gradelist'); ?>">粉丝列表</a></li>
    </ul>
        <!-- 搜索 start by LEE  -->
        <form class="well form-search" method="post" action="<?php echo U('Admin/Grade/gradelist'); ?>">
            <!--当前状态： -->
            <!--<select class="select_1" name="status">-->
                <!--<option value="-1" <?php if($where['status'] == -1) echo 'selected="selected"';?>>所有</option>-->
                <!--<option value="0" <?php if(isset($where['status']) && $where['status'] == 0) echo 'selected="selected"';?>>正常</option>-->
                <!--<option value="1" <?php if($where['status'] == 1) echo 'selected="selected"';?>>禁用</option>-->
            <!--</select> &nbsp;&nbsp;-->
            关键字： 
            <input type="text" name="keyword" style="width: 200px;" value="" placeholder="请输入要查询联系人手机号...">
            <input type="button" id="selectUser" class="btn btn-primary" value="搜索">
        </form>

    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th style="text-align:center;">粉丝信息</th>
            <!--<th style="text-align:center;">油卡</th>-->
            <th style="text-align:center;">总充值额度</th>
            <th style="text-align:center;">总支付额度</th>
            <th style="text-align:center;">总省额度</th>
            <th style="text-align:center;">积分</th>
            <th style="text-align:center;">加入时间</th>
            <th style="text-align:center;">状态</th>
            <th style="width:200px; text-align:center;">操作</th>
        </tr>
        </thead>
        <tbody id="show">
        <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$val): $mod = ($i % 2 );++$i;?><tr>
            <td style="width:40px;text-align:center;">
                <?php echo ($val['id']); ?>
            </td>
            <td style="text-align:center;">
                <?php echo ($val['nickname']); ?>
            </td>
            <!--<td style="text-align:center;">-->
                <!--<?php echo ($val['count']); ?>张-->
            <!--</td>-->
            <td style="text-align:center;">
                <?php if($val['total_recharge'] == '0'): ?><font style="color:red">无</font>
                <?php else: ?>
                    <font style="color:red">￥</font><?php echo ($val['total_recharge']); endif; ?>
            </td>
            <td style="text-align:center;">
                <?php if($val['total_add_money'] == '0'): ?><font style="color:red">无</font>
                <?php else: ?>
                    <font style="color:red">￥</font><?php echo ($val['total_add_money']); endif; ?>
            </td>
            <td style="text-align:center;">
                <?php if($val['already_save_money'] == '0'): ?><font style="color:red">无</font>
                <?php else: ?>
                    <font style="color:red">￥</font><?php echo ($val['already_save_money']); endif; ?>
            </td>
            <td style="text-align:center;">
                <?php echo ($val['integral']); ?>
            </td>
            <td style="text-align:center;">
                <?php echo ($val['createtime']); ?>
            </td>
            <td style="text-align:center;">
                <?php if($val['is_notmal'] == '1'): ?>正常
                <?php elseif($val['is_notmal'] == '3'): ?>
                    注销
                <?php else: ?>
                	冻结<?php endif; ?>
            </td>
            <td style="text-align:center;">
                <input type="hidden" id="ThisID" value="<?php echo ($val['id']); ?>">
                <!--<button class="del" style="background: #2c3e50;border:2px; width: 70px; height: 45px;" ><a href="<?php echo U('del',['id'=>$val['id']]);?>"><span style="color: white; font-size: 8px;">删除</span></a></button>-->
                <button class="del" style="background: #2c3e50;border:2px; width: 70px; height: 40px;" ><a><span style="color: white; font-size: 8px;">注销</span></a></button>
                <button class="Frozen" style="background: #2c3e50;border:2px; width: 70px; height: 40px;" ><a><span style="color: white; font-size: 8px;">冻结</span></a></button>
                <!--<button style="background: #2c3e50;border:2px; width: 70px; height: 40px;" ><a href="<?php echo U('del',['id'=>$val['id'],'flag'=>1]);?>"><span style="color: white; font-size: 8px;">冻结</span></a></button>-->
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <div id="page" class="pagination"><?php echo $page; ?></div>
</div>
<script src="/public/js/common.js"></script>
</body>
</html>

<script>
    /** 冻结该用户 */
    $(document).on('click','.Frozen',function(){
        var this_id = $(this).parent().children().eq(0).val();
        var flag = 1;
        $.ajax({
            type: 'post',
            url: 'http://ysy.edshui.com/index.php?g=admin&m=grade&a=del',
            dataType: 'json',
            data:{id:this_id,flag:flag},
            success:function(res){
                for( var i in res['data'] ){
                    var str="";
                    console.log(res.data[i].id);
                    $('#show').innerHTML+="<tr>" +
                        "<td style=\"width:40px;text-align:center;\">"+res['data'][i]['id']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['nickname']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['count']+"</td>" +
                        // "<td style=\"text-align:center;\">"+res.num_count+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['total_recharge']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['total_add_money']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['already_save_money']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['integral']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['createtime']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['is_notmal']+"</td>" +
                        "<td style=\'text-align:center;\'>"+
                        "<button style='background: #2c3e50;border:2px; width: 70px; height: 45px;' ><a href='del?id="+res['data'][i]['id']+"'><span style='color: white; font-size: 8px;'>删除</span></a></button>"+"&nbsp;"+
                        "<button style='background: #2c3e50;border:2px; width: 70px; height: 45px;' ><a href='del?id="+res['data'][i]['id']+"&flag=1'><span style='color: white; font-size: 8px;'>冻结</span></a></button>"+
                        "</td>"+
                        "</tr>";
                }
                document.getElementById('show').innerHTML = $('#show').html();
                document.getElementById('page').innerHTML = res['page'];
            }
        });
        window.location.reload();
    });

    /** 注销该用户 */
    $(document).on('click','.del',function(){
        var this_id = $(this).parent().children().eq(0).val();
        $.ajax({
            type: 'post',
            url: 'http://ysy.edshui.com/index.php?g=admin&m=grade&a=del',
            dataType: 'json',
            data:{id:this_id},
            success:function(res){
                for( var i in res['data'] ){
                    var str="";
                    console.log(res.data[i].id);
                    $('#show').innerHTML+="<tr>" +
                        "<td style=\"width:40px;text-align:center;\">"+res['data'][i]['id']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['nickname']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['count']+"</td>" +
                        // "<td style=\"text-align:center;\">"+res.num_count+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['total_recharge']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['total_add_money']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['already_save_money']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['integral']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['createtime']+"</td>" +
                        "<td style=\"text-align:center;\">"+res['data'][i]['is_notmal']+"</td>" +
                        "<td style=\'text-align:center;\'>"+
                        "<button style='background: #2c3e50;border:2px; width: 70px; height: 45px;' ><a href='del?id="+res['data'][i]['id']+"'><span style='color: white; font-size: 8px;'>删除</span></a></button>"+"&nbsp;"+
                        "<button style='background: #2c3e50;border:2px; width: 70px; height: 45px;' ><a href='del?id="+res['data'][i]['id']+"&flag=1'><span style='color: white; font-size: 8px;'>冻结</span></a></button>"+
                        "</td>"+
                        "</tr>";
                }
                document.getElementById('show').innerHTML = $('#show').html();
                document.getElementById('page').innerHTML = res['page'];
            }
        });
        window.location.reload();
    });

    /** 根据手机号搜索粉丝 */
    $(document).on('click','#selectUser',function(){
        var keyword = $('input[name=keyword]').val();
        $.ajax({
            type:'post',
            url:'http://ysy.edshui.com/index.php?g=admin&m=grade&a=userKeyword',
            dataType:'json',
            data:{keyword:keyword},
            success:function(res){
                for( var i in res['data'] ) {
                    var this_id = res.data.id;
                    var str = "";
                    str += "<tr>" +
                        "<td style=\"width:40px;text-align:center;\">" + res.data.id + "</td>" +
                        "<td style=\"text-align:center;\">" + res.data.nickname + '(' + res.data.phone + ')' + "</td>" +
                        "<td style=\"text-align:center;\">" + res.data.count + "</td>" +
                        // "<td style=\"text-align:center;\">"+res.num_count+"</td>" +
                        "<td style=\"text-align:center;\">" + res.data.total_recharge + "</td>" +
                        "<td style=\"text-align:center;\">" + res.data.total_add_money + "</td>" +
                        "<td style=\"text-align:center;\">" + res.data.already_save_money + "</td>" +
                        "<td style=\"text-align:center;\">" + res.data.integral + "</td>" +
                        "<td style=\"text-align:center;\">" + res.data.createtime + "</td>" +
                        "<td style=\"text-align:center;\">" + res.data.is_notmal + "</td>" +
                        "<td style=\'text-align:center;\'>" +
                        "<button style='background: #2c3e50;border:2px; width: 70px; height: 45px;' >" +
                        "<a href='Grade/fansReplace?id="+this_id+"'><span style='color: white; font-size: 8px;'>删除</span></a>" +
                        "</button>" + "&nbsp;" +
                        "<button style='background: #2c3e50;border:2px; width: 70px; height: 45px;' ><a href='del?id=" + res.data.id + "&flag=1'><span style='color: white; font-size: 8px;'>冻结</span></a></button>" +
                        "</td>"+
                        "</tr>";
                }
                document.getElementById('show').innerHTML = str;
                document.getElementById('page').innerHTML = res.page;
            }
        })
    })
</script>