<?php

$server = new swoole_websocket_server("0.0.0.0",9090);

$server->on('open',function(swoole_websocket_server $server,$request){
    echo "server: handshake success with fd{$request->fd}\n";
});

$server->on('message',function(swoole_websocket_server $server,$frame){
   echo "receive from {$frame->fd}:{$frame->data}";
   echo "opcode:{$frame->opcode}";
   echo "fin: {$frame->finish}\n";
    foreach($server->connections as $key => $fd) {
        $user_message = $frame->data;
        $server->push($fd, $user_message);
    }
   //$server->push($frame->fd,"$frame->data");
});

$server->on('close',function($ser,$fd){
   echo "client {$fd} closed\n";
});

$server->start();


