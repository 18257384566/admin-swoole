<?php
/**
 * Created by ws优化
 * User: dlab-xsy
 * Date: 2019/8/23
 * Time: 1:49 PM
 */


class Ws {

    const HOST = '0.0.0.0';
    const PORT = 8811;

    public $ws = null;
    public function __construct()
    {
        //判断redis中是否有上一次的fd 如果有，则删除（重启）

        $this->ws = new swoole_websocket_server(self::HOST,self::PORT);

//        $this->ws->set([
//            'enable_static_handler' => true,
//            'document_root' => '/www/swoole/thinkphp/public/static',
//            'worker_num' => 4,
//            'task_worker_num' => 4,
//        ]);

        $this->ws->on("start", [$this, 'onStart']);
        $this->ws->on("open", [$this, 'onOpen']);
        $this->ws->on("message", [$this, 'onMessage']);
//        $this->ws->on("workerstart", [$this, 'onWorkerStart']);
        $this->ws->on("request", [$this, 'onRequest']);
        $this->ws->on("task", [$this, 'onTask']);
        $this->ws->on("finish", [$this, 'onFinish']);
        $this->ws->on("close", [$this, 'onClose']);

        $this->ws->start();
    }

    public function onStart($server){
        swoole_set_process_name('chat_swoole');
    }


//    public function onWorkerStart($server,$worker_id){
//        include './swooleBootstrap.php';
//    }

    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response){
//        var_dump('test');
        // 跨域OPTIONS返回
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, DELETE, PUT, PATCH, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Authorization, User-Agent, Keep-Alive, Content-Type, X-Requested-With');
        if ($request->server['request_method'] == 'OPTIONS') {
            $response->status(http_response_code());
            $response->end();
            return;
        }

        //过滤多余的请求
        if ($request->server['request_uri'] == '/favicon.ico'){
            $response->status(200);
            $response->end();
            return;
        }


        $_SERVER = [];
        if(isset($request->server)){
            foreach ($request->server as $k => $v){
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        if(isset($request->header)){
            foreach ($request->header as $k => $v){
                $_SERVER[strtoupper($k)] = $v ;
            }
        }

        $_GET = [];
        if(isset($request->get)){
            foreach ($request->get as $k => $v){
                $_GET[$k] = $v;
            }
        }

        $_POST = [];
        if(isset($request->post)){
            foreach ($request->post as $k => $v){
                $_POST[$k] = $v;
            }
        }

        $_FILES = [];
        if(isset($request->files)){
            foreach ($request->files as $k => $v){
                $_FILES[$k] = $v;
            }
        }

        $_POST['http_server'] = $this->ws;
        echo 'nnnn';
        var_dump($_POST['http_server']);

        ob_start();
        try{
            \think\App::run()->send();
        }catch (\Exception $e){
            // todo
        }
        $res = ob_get_contents();
        ob_end_clean();

        $response->end($res);

    }

    //监听ws连接事件
    public function onOpen($ws, $request){
        var_dump($this->redis->get('backend'));
        //将fd放入redis有序集合
        var_dump('fd='.$request->fd);
    }

    //监听ws消息事件
    public function onMessage($ws, $frame){
        echo "ser-push-message:{$frame->data}\n";
        $ws->push($frame->fd, "server-push:".date('Y-m-d H:i:s'));
    }

    public function onTask($serv, $taskId, $workerId, $data){
        //分发task任务机制，让不同的任务走不同的逻辑
        $obj = new app\common\lib\task\Task;

        $method = $data['method'];
        $flag = $obj->$method($data['data'],$serv);

        return $flag;
    }

    public function onFinish($serv, $taskId, $data){
        echo "taskId:{$taskId}";
        echo "finish-data-sucess:{$data}";
    }

    //关闭
    public function onClose($ws, $fd){
        //将fd移除有序集合
        echo "clientid:{$fd}\n";
    }

}

$obj = new Ws();