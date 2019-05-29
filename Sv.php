<?php

class Sv
{
    private $serv;
    private $conn = null;
    private static $fd = null;

    public function __construct()
    {
        $this->initDb();
        $this->serv = new swoole_websocket_server("0.0.0.0", 9502);
        $this->serv->set(array(
            'worker_num' => 8,
            'daemonize' => false,
            'max_request' => 10000,
            'dispatch_mode' => 2,
            'debug_mode' => 1
        ));

        $this->serv->on('Open', array($this, 'onOpen'));
        $this->serv->on('Message', array($this, 'onMessage'));
        $this->serv->on('Close', array($this, 'onClose'));

        $this->serv->start();

    }

    function onOpen($server, $req)
    {
        // $server->push($req->fd, json_encode(33));
    }

    public function onMessage($server, $frame)
    {
        //$server->push($frame->fd, json_encode(["hello", "world"]));
        $pData = json_decode($frame->data);
        $data = array();
        if (isset($pData->content)) {
            $tfd = $this->getFd($pData->tid); //获取绑定的fd
            $data = $this->add($pData->fid, $pData->tid, $pData->content); //保存消息
            $server->push($tfd, json_encode($data)); //推送到接收者
        } else {
            $this->unBind(null,$pData->fid); //首次接入，清除绑定数据
            if ($this->bind($pData->fid, $frame->fd)) {  //绑定fd
                $data = $this->loadHistory($pData->fid, $pData->tid); //加载历史记录
            } else {
                $data = array("content" => "无法绑定fd");
            }
        }
        $server->push($frame->fd, json_encode($data)); //推送到发送者

    }


    public function onClose($server, $fd)
    {
        $this->unBind($fd);
        echo "connection close: " . $fd;
    }


    /*******************/
    function initDb()
    {
        $conn = mysqli_connect("106.12.139.157", "root", "44eb3c00b1599ed8");
        if (!$conn) {
            die('Could not connect: ' . mysql_error());
        } else {
            mysqli_select_db($conn, "chat_room");
        }
        $this->conn = $conn;
    }

    public function add($fid, $tid, $content)
    {
        $sql = "insert into msg (fid,tid,content) values ($fid,$tid,'$content')";
        if ($this->conn->query($sql)) {
            $id = $this->conn->insert_id;
            $data = $this->loadHistory($fid, $tid, $id);
            return $data;
        }
    }

    public function bind($uid, $fd)
    {
        $sql = "insert into fd (uid,fd) values ($uid,$fd)";
        if ($this->conn->query($sql)) {
            return true;
        }
    }

    public function getFd($uid)
    {
        $sql = "select * from fd where uid=$uid limit 1";
        $row = "";
        if ($query = $this->conn->query($sql)) {
            $data = mysqli_fetch_assoc($query);
            $row = $data['fd'];
        }
        return $row;
    }

    public function unBind($fd, $uid = null)
    {
        if ($uid) {
            $sql = "delete from fd where uid=$uid";
        } else {
            $sql = "delete from fd where fd=$fd";
        }
        if ($this->conn->query($sql)) {
            return true;
        }
    }

    public function loadHistory($fid, $tid, $id = null)
    {
        $and = $id ? " and id=$id" : '';
        $sql = "select * from msg where ((fid=$fid and tid = $tid) or (tid=$fid and fid = $tid))" . $and;
        $data = [];
        if ($query = $this->conn->query($sql)) {
            while ($row = mysqli_fetch_assoc($query)) {
                $data[] = $row;
            }
        }
        return $data;
    }
}

// 启动服务器
$server = new Sv();