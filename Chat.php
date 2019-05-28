<?php

class Chat
{
    private $host = '0.0.0.0';//ip地址 0.0.0.0代表接受所有ip的访问
    private $part = 9090;//端口号
    private $server = null;//单例存放websocket_server对象
    private $connList = [];//客户端的id集合

    public function __construct()
    {
        $this->server = new swoole_websocket_server($this->host,$this->part);
        $this->server->on('open',[$this,'onOpen']);
        $this->server->on('message',[$this,'onMessage']);
        $this->server->on('close',[$this,'onClose']);
        $this->server->start();

    }

    public function onOpen($server,$request)
    {
           // echo "$request->fd 进来了".PHP_EOL;
            $this->connList[] = $request->fd;
        foreach ($this->connList as $fd)
        {
            $this->server->push($fd,json_encode(['on'=>$fd,'msg'=>'{"name":"系统消息","msg":"有人进来了"}','conns'=>count($this->connList)]));
        }
    }

    public function onMessage($server,$frame)
    {
            //echo $frame->fd."说".$frame->data;
            foreach ($this->connList as $fd)
            {
                $this->server->push($fd,json_encode(['on'=>$frame->fd,'msg'=>$frame->data,'conns'=>count($this->connList)]));
            }

    }

    public function onClose($server,$fd)
    {
        //echo $fd.'退出了'.PHP_EOL;
        $this->connList = array_diff($this->connList,[$fd]);
        foreach ($this->connList as $fd)
        {
            $this->server->push($fd,json_encode(['on'=>$fd,'msg'=>'{"name":"系统消息","msg":"有人退出群聊了"}','conns'=>count($this->connList)]));
        }

    }
}

$obj = new Chat();