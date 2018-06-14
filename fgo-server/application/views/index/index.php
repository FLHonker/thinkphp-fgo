<!DOCTYPE html>
<html lang="zh">
<head>
    <title>
        <?php echo $title;?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <link rel="stylesheet" href="/css/bootstrap.min.css">    
    <link rel="stylesheet" href="/css/index.css">
</head>

<body>
<div id = 'bg2' class = 'div-body-backround'></div>
<div id = 'bg1' class = 'div-body-backround' style = "background-image:url(/img/bodybg<?php echo $defualt_img;?>.jpg);" defualt_img = "<?php echo $defualt_img;?>"></div>

<div class = 'div-body'>

    <div class="div-bg">
    <div class="mytable">
        <form action="/index/login" method='post'>
        <span class="heading">LOGIN</span>
        <div class="input-group">
          <span class="input-group-addon" id="basic-addon1"><i class="glyphicon glyphicon-user"></i></span>
          <input name="user_realname" type="text" class="form-control" placeholder="姓名">
          <input name="user_password" type="text" class="form-control" placeholder="密码">
        </div >
        <!--下面是登陆按钮,包括颜色控制-->
        <div class="input-group"><button  class="btn btn-success" type = 'submit'>登 录</button></div>

        <div class="input-group">
            <p class="text-default">
            <?php if(isset($user_info['user_realname']))
                echo '当前用户：' . $user_info['user_realname'];
             else
                echo '未登录';   
            ?>                  
            </p>                
            <a href= "/index/loginout" ><button class="btn btn-danger" type="button">注销用户</button></a>
        </div>

        <div class="div-bottom">Design By: Jerry Xiong</div>
        </form>
    </div>
    </div>
    <div class="div-stop"><button class="btn btn-default"><span class="glyphicon glyphicon-pause"></span></button></div>
</div>
</body>
<script src = "/js/jquery.min.js"></script>
<script src = "/js/index.js"></script>
</html>
    