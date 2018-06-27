<?php
namespace SingleService;
include_once 'Ret.php';
include_once 'Config.php';
include_once 'Curl.php';
include_once 'Request.php';
include_once 'View.php';
include_once 'View.php';
include_once 'Plugin.php';
include_once 'Loger.php';
include_once 'ServiceController.php';
include_once 'AsyncTaskDispather.php';
if(!class_exists('\swoole_http_server',false)){
    dl("swoole.so");
}
class Server
{
    protected $ServiceModuleName;//modulename（used in file system）
    protected $serviceNameInUri;//servicename (used in uri)
    protected $baseDir;
    /**
     *
     * @var \Sooh\Ini 
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
    public function initSuccessCode($codeName='code',$value=0,$msgName='message',$msg='success',$defaultErrCode=-1)
    {
        \SingleService\Ret::init($codeName, $value, $msgName, $msg, $defaultErrCode);
        return $this;
    }
    protected $wwwroot=null;
    public function initWWW($dir)
    {
        $this->wwwroot = $dir;
        return $this;
    }
    public function initConfigPath($dirOrUrl)
    {
        if(empty($this->ServiceModuleName)){
            die('call initServiceModule() first');
        }
        try{
            if(substr($dirOrUrl,0,5)=='http:'){
                $this->config = \Sooh\Ini::getInstance()->initLoader(new \Sooh\IniClasses\Url($dirOrUrl,$this->ServiceModuleName));
            }else{
                $this->config = \Sooh\Ini::getInstance()->initLoader(new \Sooh\IniClasses\Files($dirOrUrl,$this->ServiceModuleName));
            }
            
            $this->serviceNameInUri = explode(',',$this->config->getIni($this->ServiceModuleName.'.SERVICE_MODULE_NAME'));
        }catch(\ErrorException $ex){
            die($ex->getMessage());
        }
        return $this;
    }

    protected $_rawDigName=null;
    public function initRawdataDigname($digname)
    {
        $this->_rawDigName = $digname;
        return $this;
    }
    protected $_includePath=null;
    /**
     * 设置自定义的view类
     * @param type $view
     */
    public function initView($view)
    {
        $this->_view=$view;
        return $this;
    }
    protected $_view=null;
    public function initIncludePath($path)
    {
        $this->_includePath = $path;
        spl_autoload_register(array($this,'autoload_locallibs'));
        return $this;
    }
    public function initSetCtrlIfMissing($ctrlname)
    {
        if(!is_string($ctrlname)){
            die('arg: ctrl_with_missctrlAction should be string');
        }
        $this->whenCtrlMiss = ucfirst($ctrlname);
        return $this;
    }
    protected $whenCtrlMiss=null;
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
        $this->reqRunning = new \Swoole\Atomic\Long();
        
        if($this->_view===null){
            $this->_view=new \SingleService\View;
        }
        \SingleService\ReqEnvCookie::init($signKey, $SessionName,array());
        $this->startSwoole($ip, $port, 
                $this->config->getIni($this->ServiceModuleName.'.SERVICE_MAX_REQUEST'), 
                $this->totalTaskProcess = $this->config->getIni($this->ServiceModuleName.'.SERVICE_MAX_TASK'));
        $this->log->initOnNewRequest('[start]', '127.0.0.1');
        $this->log->app_trace($this->ServiceModuleName." start listening on $port");
        echo $this->ServiceModuleName." start listening on $port";
        $this->swoole->start();        
    }
    public function runTaskOnly($taskFuncname,$paramJsonstring)
    {
        if($this->_view===null){
            $this->_view=new \SingleService\View;
        }
        \SingleService\ReqEnvCookie::init($signKey, $SessionName,array());
        $this->startSwoole('127.0.0.1', 0, 
                $this->config->getIni($this->ServiceModuleName.'.SERVICE_MAX_REQUEST'), 
                $this->config->getIni($this->ServiceModuleName.'.SERVICE_MAX_TASK'));
        $tmp = new \AsyncTaskDispatcher($this->config,$this->log);
        if(substr($paramJsonstring,0,1)=='{'||substr($paramJsonstring,0,1)=='['){
            $data = json_decode($paramJsonstring,true);
            if(!is_array($data)){
                die('json error:'.$paramJsonstring."\n");
            }
        }
        
        $this->log->initOnNewRequest('///'.$taskFuncname,'0.0.0.0');
        try{
            $tmp->doBeforeTask($taskFuncname,$data);
            $ret = $tmp->$taskFuncname($data);
            $tmp->doAfterTask($taskFuncname,$data);
            if(empty($ret)){
                $this->taskRunning_dec();
            }else{
                return $ret;
            }
        }catch(\ErrorException $ex){
            $ret = $tmp->onError($ex, $taskFuncname, $data);
            if(empty($ret)){
                $this->taskRunning_dec();
            }else{
                return $ret;
            }
        }
    }
    //------------------------加载项目专属的所有类库 --------------------开始
    public function initAutloadLocalLibrary($flg=true)
    {
        if($flg){
            if(is_dir($this->baseDir.'/library')){
                $this->preloadModuleLibrary($this->baseDir.'/library');
            }
        }
        return $this;
    }
    protected function preloadModuleLibrary($dir)
    {
        $parsed = $this->parseAllPHP($this->getAllFiles($dir));
        $loaded=array();
        for($i=0;$i<100000;$i++){
            foreach($parsed as $classname=>$r){
                $extendsFrom = $r['extends'];
                $filepath = $r['path'];
                if(empty($extendsFrom) || isset($loaded[$extendsFrom]) || !isset($parsed[$extendsFrom])){
                    include $filepath;
                    $loaded[$classname]=true;
                    unset($parsed[$classname]);
                }
            }
            if(sizeof($parsed)==0){
                break;
            }
        }
    }
    protected function parseAllPHP($allFiles)
    {
        $ret = array();
        foreach($allFiles as $f){
            $s = file_get_contents($f);
            
            if(strpos($s, 'interface ')){
                include $f;
                continue;
            }
            
            $pos1 = strpos($s, 'namespace');
            $pos2 = strpos($s, ';', $pos1+9);
            $namespace = trim(trim(trim(substr($s, $pos1+9,$pos2-$pos1-8)),';'));
            
            $pos3 = strpos($s, 'class');
            if($pos3===false){
                continue;
            }
            $pos4 = strpos($s, '{', $pos3+5);
            $tmp = str_replace(array("\r","\n","\t"), ' ', substr($s, $pos3,$pos4-$pos3));
            
            $arr = explode(' ', $tmp);
            $parts = array();
            $key = '';
            foreach($arr as $c){
                if($c!=''){
                    if($key==''){
                        $key=$c;
                    }else{
                        $parts[strtolower($key)]=$c;
                        $key='';
                    }
                }
            }
            $classWithNamespace = str_replace('\\\\','\\','\\'.$namespace.'\\'.$parts['class']);
            $extendsWithNamespace = '';
            if(empty($parts['extends'])){//没有 extends
                $extendsWithNamespace='';
            }elseif(substr($parts['extends'],0,1)=='\\'){//extends 完整路径
                $extendsWithNamespace=$parts['extends'];
            }else{
                $extendsWithNamespace = '\\'.$namespace.'\\'.$parts['extends'];
                $found = preg_match_all("/use (.*);/", substr($s,0,$pos3),$uses);
                if($found>0){//有use 的情况
                    foreach ($uses[1] as $s3){
                        $r3 = explode('\\', $s3);
                        if(array_pop($r3)==$parts['extends']){
                            $extendsWithNamespace='\\'.$s3;
                            break;
                        }
                    }
                }
            }
            $ret[strtolower($classWithNamespace)]= array('extends'=> strtolower(str_replace('\\\\','\\',$extendsWithNamespace)),'path'=>$f);
        }
        return $ret;
    }
    
    protected function getAllFiles($dir)
    {
        if(!is_dir($dir)){
            return array();
        }
        $files = array();        
        $tmp  = scandir($dir);
        foreach($tmp as $s){
            if(ord($s)==46){// . or ..
                continue;
            }
            if(is_dir($dir.'/'.$s)){
                $files = array_merge($files,$this->getAllFiles($dir.'/'.$s));
            }elseif(substr($s,-4)=='.php'){
                $files[]= $dir.'/'.$s;
            }
        }
        return $files;
    }
    //------------------------加载项目专属的所有类库 ----------------------结束

    /**
     * 当前正在运行的task数
     * @var \Swoole\Atomic\Long
     */
    public $taskRunning=0;
    protected $totalTaskProcess=0;
    public function getNumFreeTaskProcess()
    {
        return $this->totalTaskProcess - $this->taskRunning->get();
    }
    /**
     * 当前正在处理的请求数
     * @var \Swoole\Atomic\Long
     */
    public $reqRunning=0;

    protected $swoole;
    /**
     *
     * @var \SingleService\Loger 
     */
    protected $log;
    protected function doInternalCmd($cmd,$request, $response)
    {
        switch ($cmd){
            case 'onServerStart':
                $tmp = new \AsyncTaskDispatcher($this->config,$this->log);
                $tmp->onServerStart($this);

                $response->header("Content-Type", "application/json");
                $response->end(Ret::factoryOk($this->ServiceModuleName.' first called')->toJsonString());
                return;
            case 'shutdownThisNode':
                $response->header("Content-Type", "application/json");
                $response->end(Ret::factoryOk($this->ServiceModuleName.' shutting down')->toJsonString());
                $this->swoole->shutdown();
                return;
            case 'getNumProcessRunning':
                $response->header("Content-Type", "application/json");
                $tmp = Ret::factoryOk()->toArray();
                $tmp['numRequest']=$this->reqRunning->get();
                $tmp['numTask']=$this->taskRunning->get();
                $response->end(json_encode($tmp));
                return;
            case 'reloadConfig':
                $response->header("Content-Type", "application/json");
                $this->config->reload();
                $response->end(Ret::factoryOk($this->ServiceModuleName.' config reloaded')->toJsonString());
                return;
            case 'dumpConfig':
                $response->header("Content-Type", "application/json");
                $tmp = Ret::factoryOk()->toArray();
                $tmp['all_ini'] = $this->config->dump();
                $response->end(json_encode($tmp));
                return;
            default:
                $response->header("Content-Type", "application/json");
                $response->end(Ret::factoryError("unknown cmd: '.$cmd.' for '.$this->ServiceModuleName.'")->toJsonString());
                return;
        }
    }
    
    protected function dealwith_www($uri,$response)
    {
        $tmp1 = explode('#',$uri);
        $tmp2 = explode('?',$tmp1[0]);
        $file = '';
        if(substr($tmp2[0],-1)=='/'){
            $contentType="text/html";
            $file = $this->wwwroot.'/index.html';
        }elseif(is_dir($this->wwwroot.$tmp2[0])){
            $contentType="text/html";
            $file = $this->wwwroot.$tmp2[0].'/index.html';
        }else{
            $chk = strtolower(substr($tmp2[0],strrpos($tmp2[0],'.')+1));
            //$fullpath = $this->wwwroot.$tmp2[0];
            if($chk=='html' || $chk=='htm'){
                $contentType = "text/html";
                $file = $this->wwwroot.$tmp2[0];
            }elseif($chk=='js'){
                $contentType = "application/x-javascript";
                $file = $this->wwwroot.$tmp2[0];
            }elseif($chk=='css'){
                $contentType = "text/css";
                $file = $this->wwwroot.$tmp2[0];
            }elseif($chk=='jpg'){
                $contentType = "image/jpeg";
                $file = $this->wwwroot.$tmp2[0];
            }elseif($chk=='png'){
                $contentType = "image/png";
                $file = $this->wwwroot.$tmp2[0];
            }elseif($chk=='gif'){
                $contentType = "image/gif";
                $file = $this->wwwroot.$tmp2[0];
            }elseif($chk=='jpeg'){
                $contentType = "image/jpeg";
                $file = $this->wwwroot.$tmp2[0];
            }elseif($chk=='map'){
                $file = $this->wwwroot.$tmp2[0];
            }elseif($chk=='txt'){
                $contentType = "text/plain";
                $file = $this->wwwroot.$tmp2[0];
            }elseif($chk=='pdf'){
                $contentType = "application/pdf";
                $file = $this->wwwroot.$tmp2[0];
            }else{
                return '';
            }
        }
        $response->header("Content-Type", $contentType);
        return $file;
    }
    public function dispatch($request, $response)
    {
        $this->reqRunning->add(1);
        \SingleService\ReqEnvCookie::getInstance($request->cookie);
        if(!empty($request->header['x-forwarded-for'])){
            $this->log->initOnNewRequest($request->server['request_uri'],$request->server['remote_addr'].','.trim($request->header['x-forwarded-for'],'[]'));
        }else{
            $this->log->initOnNewRequest($request->server['request_uri'],$request->server['remote_addr']);
        }
        $this->config->onNewRequest();
        $this->config->setRuntime('REQUEST_SN',$this->log->getRequestSN());
        $this->config->setRuntime('CurServModName', $this->ServiceModuleName);
        $this->log->app_trace('['.$request->server['remote_addr'].']'.$request->server['request_uri']);
        if($this->wwwroot!==null){
            $file = $this->dealwith_www($request->server['request_uri'], $response);
            if(!empty($file)){
                if(is_file($file)){
                    $this->log->app_common("GET $file 200");
                    $response->end(file_get_contents($file));
                }else{
                    $this->log->app_common("GET $file 404");
                    $response->status(404);
                    $response->end();
                }
                $this->reqRunning->sub(1);
                return;
            }
        }
        
        $mca = explode('/', $request->server['request_uri']);//  /开头，mca[0]是空串
        if($mca[1]=='SteadyAsHill'){//这些系统命令只接受本地请求
            if($request->server['remote_addr']!='127.0.0.1'){
                $this->log->app_common("EXEC ". implode('/', $mca). " 403 not-interal-ip" );
                $response->status(404);
                $response->end();
                $this->reqRunning->sub(1);
                return;
            }else{
                
                try{
                    $this->doInternalCmd($mca[3], $request, $response);
                    $this->log->app_common("EXEC ". implode('/', $mca). " 200" );
                    $this->reqRunning->sub(1);
                    return;
                }catch(\ErrorException $ex){
                    $this->log->app_common("EXEC ". implode('/', $mca). " 503" );
                }
            }
        }

        if(!in_array($mca[1], $this->serviceNameInUri)){
            $this->log->app_common("EXEC ". implode('/', $mca). " 403 invalid-service-name" );
            $response->status(404);
            $response->end();
            $this->reqRunning->sub(1);
            return;
        }
        
        $mca[2]=ucfirst($mca[2]);
        $class_name = $mca[2].'Controller';
        $func = $mca[3].'Action';

        if(!class_exists($class_name,false)){
            if(is_readable($this->baseDir.'/controllers/'.$mca[2].'.php')){
                include $this->baseDir.'/controllers/'.$mca[2].'.php';
            }else{
                if($this->whenCtrlMiss==null){
                    $this->log->app_common("EXEC ". implode('/', $mca). " 503 invalid-controller-name" );
                    $response->status(404);
                    $response->end();
                    $this->reqRunning->sub(1);
                    return;
                }else{
                    $class_name = $this->whenCtrlMiss.'Controller';
                    if(!class_exists($class_name,false)){
                        include $this->baseDir.'/controllers/'.$this->whenCtrlMiss.'.php';
                    }
                }
            }
        }

        try{
            $view = $this->_view->cloneone();
            $view->setResult(\SingleService\Ret::factoryOk());
            $obj = new $class_name;
            $obj->initAllFromServer($this->prepareRequest($request,$mca),$view,$this->config,$this,$this->log);
//            $obj->initPrjEnv($this->successCode[0],$this->successCode[1],$this->successCode[2],$this->successCode[3],$this->successCode[4]);
            
            if($obj->checkBeforeAction()){
                if(!method_exists($obj, $func)){
                    $this->log->app_common("EXEC ". implode('/', $mca). " 503 invalid-action-name" );
                    $response->status(405);
                    $response->end();
                    $this->reqRunning->sub(1);
                    return;
                }else{
                    $obj->$func();
                }
                $obj->doAfterAction(true);
            }else{
                $obj->doAfterAction(false);
            }
            
            $view->renderJson4Swoole($response);
            $this->log->app_common("EXEC ". implode('/', $mca). " 200" );
        } catch (Exception $ex) {
            $this->log->app_common("EXEC ". implode('/', $mca). " 503 " .$ex->getMessage()."#".json_encode($ex->getTraceAsString()));
        }
        $this->reqRunning->sub(1);
    }
    protected function prepareRequest($request,$mca)
    {
        $req = new \SingleService\Request($request);
        $req->setMCA($mca[1], $mca[2], $mca[3]);
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
    protected $arrIpPort=array();
    protected function startSwoole($ipListen,$portListen,$workerNum,$taskNum)
    {
        $this->arrIpPort=array($ipListen,$portListen);
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
        $http->on("start", array($this, 'onSwooleStart'));

    }
    
    protected function checkTaskSetting($taskNum)
    {
        if($taskNum>0){
            if(!is_file($this->baseDir.'/AsyncTaskDispatcher.php')){
                die('MISSING '.$this->baseDir.'/AsyncTaskDispatcher.php');
            }else{
                include $this->baseDir.'/AsyncTaskDispatcher.php';
                $tmp = new \AsyncTaskDispatcher($this->config,$this->log);
                if(!method_exists($tmp, 'onError')){
                    die('MISSING onError() in AsyncTaskDispatcher.php');
                }
            }
        }
    }
    
    // ----------------------------------swoole task 相关
    public function createSwooleTask($func,$data,$callBackEnd=null)
    {
        $pack = array($func,$data);
        $s = $this;

        if(false === $this->swoole->task($pack,-1, function ($serv,$task_id, $data)use ($s,$callBackEnd){
            try{
                if($callBackEnd===null){

                }elseif(is_array($callBackEnd)){
                    call_user_func($callBackEnd, $data);
                }else{
                    $callBackEnd($data);
                }
            } catch (\ErrorException $e){

            }
            $s->taskRunning->sub(1);
        })){
            return false;
        }else{
            return true;
        }

    }
    public function onSwooleStart($server)
    {
        
        if(class_exists('\\AsyncTaskDispatcher',false)){
            error_log('ignore timeout error of: Operation timed out after 1000 milliseconds with 0 bytes received http://...../onServerStart');
            $ret = Curl::factory()->httpGet('http://127.0.0.1:'.$this->arrIpPort[1].'/SteadyAsHill/broker/onServerStart',null,null,1);
        }
    }
    public function onSwooleTask($serv, $task_id, $src_worker_id, $data)
    {
        //error_log("## server:onTaskStart:enter". json_encode($data));
        $this->taskRunning->add(1);
        $func = $data[0];
        $this->log->initOnNewRequest('///'.$func,'0.0.0.0');
        $this->config->onNewRequest($this->log->getRequestSN());
        $this->config->setRuntime('CurServModName', $this->ServiceModuleName);
        $tmp = new \AsyncTaskDispatcher($this->config,$this->log);

        $this->log->app_trace("task-process-info:task_id=$task_id, src_worker_id=$src_worker_id");
        try{
            $tmp->doBeforeTask($func,$data[1]);
            $ret = $tmp->$func($data[1]);
            $tmp->doAfterTask($func,$data[1]);
            if(empty($ret)){
                return "process returned nothing, so single-server-framework return this to trigger callback";
            }else{
                return $ret;
            }
        }catch(\ErrorException $ex){
            $ret = $tmp->onError($ex, $func, $data[1]);
            if(empty($ret)){
                return "process returned nothing, so single-server-framework return this to trigger callback";
            }else{
                return $ret;
            }
        }
    }
    public function onSwooleTaskEnd($serv,$task_id, $data)
    {

    }
}