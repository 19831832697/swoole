<?php
/**
 * Created by PhpStorm.
 * User: fengdan
 * Date: 2019/6/15
 * Time: 14:56
 */
$server = new swoole_websocket_server("0.0.0.0", 9503);

$server->on('open', function($server, $req) {
    echo "connection open: {$req->fd}\n";
    //连接数据库
    $swoole_mysql = new Swoole\Coroutine\MySQL();
    $swoole_mysql->connect([
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '123456',
        'database' => 'app',
    ]);
});

$server->on('message', function($server, $frame) {
//    echo "received message: {$frame->data}\n";
    $arrInfo=json_decode($frame->data,true);
    $user_name=$arrInfo['text']['user_name'];
    $user_email=$arrInfo['text']['user_email'];
    $user_pwd=$arrInfo['text']['user_pwd'];

    //连接数据库
    $swoole_mysql = new Swoole\Coroutine\MySQL();
    $swoole_mysql->connect([
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '123456',
        'database' => 'app',
    ]);
    if(empty($user_name)){
        $res=[
            'code'=>2,
            'msg'=>'用户名不能为空'
        ];
        $server->push($frame->fd, json_encode($res,JSON_UNESCAPED_UNICODE));
        return;
    }
    if(empty($user_email)){
        $res=[
            'code'=>2,
            'msg'=>'邮箱不能为空'
        ];
        $server->push($frame->fd, json_encode($res,JSON_UNESCAPED_UNICODE));
        return;
    }
    if(empty($user_pwd)){
        $res=[
            'code'=>2,
            'msg'=>'密码不能为空'
        ];
        $server->push($frame->fd, json_encode($res,JSON_UNESCAPED_UNICODE));
        return;
    }

    //验证用户名是否唯一
    $name_sql="select * from user_reg where user_name='$user_name'";
    $dataInfo=$swoole_mysql->query($name_sql);
    if($dataInfo){
        $res=[
            'code'=>2,
            'msg'=>'用户名已存在'
        ];
        $server->push($frame->fd, json_encode($res,JSON_UNESCAPED_UNICODE));
        return;
    }
    //验证邮箱是否唯一
    $email_sql="select * from user_reg where user_email=".$user_email;
    $data=$swoole_mysql->query($email_sql);

    if($data){
        $res=[
            'code'=>2,
            'msg'=>'此邮箱已存在'
        ];
        $server->push($frame->fd, json_encode($res,JSON_UNESCAPED_UNICODE));
        return;
    }

    $pwd=password_hash($user_pwd,PASSWORD_BCRYPT);
    $res_sql="insert into user_reg(user_name,user_pwd,user_email) values('$user_name','$pwd','$user_email')";
    $res=$swoole_mysql->query($res_sql);
    if($res){
        $res=[
            'code'=>1,
            'msg'=>'注册成功,前往登录'
        ];
        $server->push($frame->fd, json_encode($res,JSON_UNESCAPED_UNICODE));
        return;
    }else{
        $res=[
            'code'=>2,
            'msg'=>'注册失败'
        ];
        $server->push($frame->fd, json_encode($res,JSON_UNESCAPED_UNICODE));
        return;
    }
});

$server->on('close', function($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();