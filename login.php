<?php
/**
 * Created by PhpStorm.
 * User: fengdan
 * Date: 2019/6/15
 * Time: 10.26
 */
$server = new swoole_websocket_server("0.0.0.0", 9501);

//打开连接
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
    $data=json_decode($frame->data,true);
    $user_name=$data['text']['user_name'];
    $user_pwd=$data['text']['user_pwd'];

    if(empty($user_name)){
        $res=[
            'code'=>2,
            'msg'=>'用户名不能为空'
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

    //连接数据库
    $swoole_mysql = new Swoole\Coroutine\MySQL();
    $swoole_mysql->connect([
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '123456',
        'database' => 'app',
    ]);

    $sql="select * from user_reg where user_name='$user_name'";
    echo $sql;
    $dataInfo=$swoole_mysql->query($sql);

    var_dump($dataInfo);
    if($dataInfo){
        $user_id=$dataInfo[0]['user_id'];
        $pwd=$dataInfo[0]['user_pwd'];
        $user_name=$dataInfo[0]['user_name'];
        if(password_verify($user_pwd,$pwd)){
//            $token=substr(rand(10000,9999).md5($user_id),5,15);
            //连接redis
//            $swoole_redis = new Swoole\Coroutine\Redis();
//            $redis=$swoole_redis->connect('192.168.3.1',6379);
//            $aa=$redis->set('user_token',$token);
//            var_dump($aa);
            $arr=[
                'code'=>1,
                'user_id'=>$user_id,
                'user_name'=>$user_name,
                'msg'=>'登录成功,进入聊天室'
            ];
            $server->push($frame->fd, json_encode($arr,JSON_UNESCAPED_UNICODE));
            return;
        }else{
            $arr=[
                'code'=>2,
                'msg'=>'密码错误'
            ];
            $server->push($frame->fd, json_encode($arr,JSON_UNESCAPED_UNICODE));
            return;
        }
    }else{
        $arr=[
            'code'=>2,
            'msg'=>'此账号不存在'
        ];
        $server->push($frame->fd, json_encode($arr,JSON_UNESCAPED_UNICODE));
        return;
    }
});

//关闭连接诶
$server->on('close', function($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();