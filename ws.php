<?php
/**
 * Created by PhpStorm.
 * User: fengdan
 * Date: 2019/6/11
 * Time: 14:56
 */
$server = new swoole_websocket_server("0.0.0.0", 9502);

$server->on('open', function($server, $req) {
    echo "connection open: {$req->fd}\n";
});

$server->on('message', function($server, $frame) {
//    echo "received message: {$frame->data}\n";
//    $server->push($frame->fd, json_encode(["hello", "world"]));
        foreach ($server->connections as $fd) {
        // 需要先判断是否是正确的websocket连接，否则有可能会push失败
        if ($server->isEstablished($fd)) {
            $server->push($fd, $frame->data);
        }
    }
});

$server->on('close', function($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();