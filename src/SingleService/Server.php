<?php
namespace SingleService;
include_once 'Config.php';
include_once 'Curl.php';
include_once 'Request.php';
include_once 'View.php';
include_once 'Plugin.php';
include_once 'Loger.php';
include_once 'ServiceController.php';
if(!class_exists('\swoole_http_server',false)){
    dl("swoole.so");
}
define("KEEPWORD_TASKFUNC_NAME",'kw_task_func_name_trans');
class Server
{
    protected $ServiceModuleName;//modulename（used in file system）
    protected $serviceNameInUri;//servicename (used in uri)
    protected $baseDir;
    /**
     *
     * @var \SingleService\Config 
     */
    protected $config;
    public static function factory()
    {
        return new Server;
    }
    public function initServiceModule($dir,$name)
    {
        $this->ServiceModuleName = ucfirst($name);
        $this->baseDir = str_replace(array('//','\\'),'/',$dir.'/'.$this->ServiceModuleName);

        return $this;
    }
    public function initLog($log)
    {
        $this->log = $log;
        return $this;
    }
    protected $successCode=array('code',0,'msg','ok');
    public function initSuccessCode($codeName='code',$value=0,$msgName='message',$msg='success')
    {
        $this->successCode[0]=$codeName;
        $this->successCode[1]=$value;
        $this->successCode[2]=$msgName;
        $this->successCode[3]=$msg;
        return $this;
    }
    public function initConfigPath($dirOrUrl)
    {
        if(empty($this->ServiceModuleName)){
            die('call initServiceModule() first');
        }
        $this->config = \SingleService\Config::getInstance($dirOrUrl,$this->ServiceModuleName);
        $this->serviceNameInUri = $this->config->getIni($this->ServiceModuleName.'.SERVICE_MODULE_NAME');
        
        return $this;
    }

    protected $_rawDigName=null;
    public function initRawdataDigname($digname)
    {
        $this->_rawDigName = $digname;
        return $this;
    }
    protected $_includePath=null;
    public function initIncludePath($path)
    {
        $this->_includePath = $path;
        spl_autoload_register(array($this,'autoload_locallibs'));
        return $this;
    }
    
    public function getConfig()
    {
        return $this->config;
    }
    
    public function autoload_locallibs($class)
    {
        $tmp = explode('\\', $class);
        if(sizeof($tmp)==1){
            return false;
        }
        if($tmp[0]==''){
            array_shift($tmp);
        }
        $cmpFilename = implode('/', $tmp).'.php';
        
        //error_log(">>>>>autoload:>>>>>>>>>$cmp>>>>>>".$f);
        if(is_file($this->_includePath.'/'.$cmpFilename)){
            include $this->_includePath.'/'.$cmpFilename;
            return true;
        }else{
            return false;
        }
    }
    
    public function run($ip='0.0.0.0',$port=9501)
    {
        $this->taskRunning = new \Swoole\Atomic\Long();
        $this->preloadModuleLibrary($this->baseDir.'/library');
        $this->startSwoole($ip, $port, 
                $this->config->getIni($this->ServiceModuleName.'.MAILSERVICE_MAX_REQUEST'), 
                $this->config->getIni($this->ServiceModuleName.'.MAILSERVICE_MAX_TASK'));
    }
    
    protected function preloadModuleLibrary($dir)
    {
        if(!is_dir($dir)){
            return;
        }
        $tmp  = scandir($dir);
        foreach($tmp as $s){
            if(ord($s)==46){// . or ..
                continue;
            }
            if(is_dir($dir.'/'.$s)){
                $this->preloadModuleLibrary($dir.'/'.$s);
            }elseif(substr($s,-4)=='.php'){
                include $dir.'/'.$s;
            }
        }
    }


    /**
     * 当前正在运行的任务书数
     * @var \Swoole\Atomic\Long
     */
    protected $taskRunning=0;
    public function taskRunning_inc()
    {
        $this->taskRunning->add(1);
    }
    public function taskRunning_dec()
    {
        $this->taskRunning->sub(1);
    }
    public function httpCodeAndNewLocation($code,$redir=null)
    {
        $this->forceCodeAndLocation=array($code,$redir);
    }
    protected $forceCodeAndLocation;
    protected $swoole;
    /**
     *
     * @var \SingleService\Loger 
     */
    protected $log;
    public function dispatch($request, $response)
    {
        $this->log->initOnNewRequest($request->server['request_uri']);
        $mca = explode('/', $request->server['request_uri']);//  /开头，mca[0]是空串
        if($mca[1]=='shutdownThisNode' || $mca[2]=='shutdownThisNode'){
            $response->header("Content-Type", "application/json");
            $response->end('{"'.$this->successCode[0].'":'.$this->successCode[1].',"'.$this->successCode[2].'":"'.$this->ServiceModuleName.' shutting down"}');
            $this->swoole->shutdown();
            return;
        }elseif($mca[1]=='getNumProcessRunning' || $mca[2]=='getNumProcessRunning'){
            $response->header("Content-Type", "application/json");
            $response->end('{"'.$this->successCode[0].'":'.$this->successCode[1].',"'.$this->successCode[2].'":"'.$this->ServiceModuleName.' numProcessRunning='.$this->taskRunning->get().'"}');
            return;
        }

        if($mca[1]!=$this->serviceNameInUri){
            $response->status(404);
            $response->end();
            return;
        }

        $this->taskRunning_inc();
        
        
        $mca[2]=ucfirst($mca[2]);
        $class_name = $mca[2].'Controller';
        $func = $mca[3].'Action';

        if(!class_exists($class_name,false)){
            include $this->baseDir.'/controllers/'.$mca[2].'.php';
        }

        try{
            $view = new \SingleService\View;
            $obj = new $class_name;
            if(!method_exists($obj, $func)){
                $this->log->app_error('method:'.$func .' not found on:'.$this->ServiceModuleName.' / '. get_class($obj));
                $response->status(405);
                $response->end();
                return;
            }else{
                $obj->initAllFromServer($this->prepareRequest($request),$view,$this->config,$this,$this->log);
                $obj->initPrjEnv($this->successCode[0],$this->successCode[1],$this->successCode[2],$this->successCode[3]);
                if($obj->checkBeforeAction()){
                    $obj->$func();
                    $obj->doAfterAction(true);
                }else{
                    $obj->doAfterAction(false);
                }
            }
            if(!empty($this->forceCodeAndLocation)){
                $response->status($this->forceCodeAndLocation[0]);
                if(!empty($this->forceCodeAndLocation[1])){
                    $response->header("Location", $this->forceCodeAndLocation[1]);
                }
                $this->forceCodeAndLocation=array();
            }
            
            $view->renderJson4Swoole($response);
        } catch (Exception $ex) {
            $this->log->app_error($ex->getMessage()."\n".$ex->getTraceAsString());
        }
        $this->taskRunning_dec();
    }
    protected function prepareRequest($request)
    {
        $req = new \SingleService\Request($request);
        $tmp = $request->rawContent();
        
        if(empty($tmp)){
            return $req;
        }
        
        $_raw = json_decode($tmp,true);
        if(is_array($_raw)){
            if(isset($_raw[$this->_rawDigName])){
                foreach ($_raw[$this->_rawDigName] as $k=>$v){
                    $req->setParam($k, $v);
                }
                unset($_raw[$this->_rawDigName]);
            }
            foreach($_raw as $k=>$v){
                $req->setParam($k, $v);
            }
        }else{
            $this->log->app_error("rawdata is not json:".$tmp);
        }
        return $req;
    }
    protected function startSwoole($ipListen,$portListen,$workerNum,$taskNum)
    {
        $this->checkTaskSetting($taskNum);
        $http = new \swoole_http_server($ipListen,$portListen);
        $http->set(array(
            'worker_num' =>$workerNum,
            'task_worker_num'=>$taskNum,//因为主要是处理报警任务，所以100个足够了
            'daemonize' => true,
        ));
        if($ipListen!='127.0.0.1'){
            $http->listen('127.0.0.1',$portListen,SWOOLE_SOCK_TCP);
        }
        $this->swoole = $http;
        $http->on("request", array($this,'dispatch'));
        $http->on("task", array($this, 'onSwooleTask')); 
        $http->on("finish", array($this, 'onSwooleTaskEnd')); 
        echo $this->ServiceModuleName." start listening on $portListen";
        $http->start();
    }
    
    protected function checkTaskSetting($taskNum)
    {
        if($taskNum>0){
            if(!is_file($this->baseDir.'/AsyncTaskDispatcher.php')){
                die('MISSING '.$this->baseDir.'/AsyncTaskDispatcher.php');
            }else{
                include $this->baseDir.'/AsyncTaskDispatcher.php';
                $tmp = new \AsyncTaskDispatcher;
                if(!method_exists($tmp, 'onError')){
                    die('MISSING onError() in AsyncTaskDispatcher.php');
                }
            }
        }
    }
    
    // ----------------------------------swoole task 相关
    public function createSwooleTask($func,$data,$callBackEnd)
    {
        $data[KEEPWORD_TASKFUNC_NAME]=$func;
        $s = $this;
        if(empty($callBackEnd)){
            $this->swoole->task($data,-1, array($this,'onSwooleTaskEnd'));
        }else{
            $this->swoole->task($data,-1, function ($serv,$task_id, $data)use ($s,$callBackEnd){
                try{
                    if(is_array($callBackEnd)){
                        call_user_func($callBackEnd, $data);
                    }else{
                        $callBackEnd($data);
                    }
                } catch (\ErrorException $e){
                    
                }
                //error_log("## server:onTaskStart:end". json_encode($data));
                $s->taskRunning_dec();
            });
        }
    }
    public function onSwooleTask($serv, $task_id, $src_worker_id, $data)
    {
        //error_log("## server:onTaskStart:enter". json_encode($data));
        $this->taskRunning_inc();
            
        $tmp = new \AsyncTaskDispatcher;
        $func = $data[KEEPWORD_TASKFUNC_NAME];
        unset($data[KEEPWORD_TASKFUNC_NAME]);
        try{
            $ret = $tmp->$func($data);
            if(empty($ret)){
                $this->taskRunning_dec();
            }else{
                return $ret;
            }
        }catch(\ErrorException $ex){
            $ret = $tmp->onError($ex, $data);
            if(empty($ret)){
                $this->taskRunning_dec();
            }else{
                return $ret;
            }
        }

    }

    public function onSwooleTaskEnd($serv,$task_id, $data)
    {
        $this->taskRunning_dec();
    }
    
   
}