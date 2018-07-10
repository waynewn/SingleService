<?php
namespace SingleService;

/**
 * 各类异步任务处理封装
 *
 * @author wangning
 */
class AsyncTaskDispatcher {
    public function __construct($config,$loger,$reqRunning=-1,$taskRunning=-1) {
        $this->_Config = $config;
        $this->_log=$loger;
        $this->_reqRunning = $reqRunning;
        $this->_taskRunning = $taskRunning;
    }
    /**
     * 当前请求数（参考用）
     */
    protected $_reqRunning=0;
    /**
     * 当前swoole-task任务数（参考用）
     */
    protected $_taskRunning=0;
    /**
     * 记录日志用的
     * @var \SingleService\Loger 
     */
    protected $_log;
    /**
     * 获取配置用的
     * 
     * @var \Sooh\Ini 
     */
    protected $_Config;
    
    /**
     * swoole 启动时通过curl内部命令方式触发该函数，做一些默认操作，慎改
     */
    public function internalCmd_start($SingleSerivceServer)
    {
        $this->onServerStart($SingleSerivceServer);
        $timerMS = $this->_Config->getMainModuleConfigItem('Timer_Interval_MS');
        if($timerMS!=0){
            swoole_timer_tick(abs($timerMS), function ($timer_id, $ignore) use ($SingleSerivceServer) 
            {
                if($this->_Config->getMainModuleConfigItem('Timer_Interval_MS')<=0){
                    return;
                }
                if($this->_Config->permanent->gets('shuttingDown')==='yes'){
                    return;
                }
                $this->onTimer($SingleSerivceServer);
            },null);
        }
        $SingleSerivceServer->_loadedClass=array();
    }

    
    /**
     * server启动时触发一次
     * @param Server $SingleServer
     * @param swoole\request ,$swooleRequest
     * @return bool 是否正常启动了
     */
    protected function onServerStart($SingleSerivceServer)
    {
        $this->_log->app_trace('server start');
    }
    /**
     * 定时任务， $SingleSerivceServer 传进来是 createSwooleTask 用的
     */
    protected function onTimer($SingleSerivceServer)
    {
    }
//    protected function startTimer($swoole)
//    {
//
//        swoole_timer_tick(500,function ($timer_id, $params) use ($swoole){
//                $swoole->task($params);
//        },array('some arg for timer'));
//    }    
    /**
     * 如果有等待的回调，必须要有返回值，且不能返回类
     * @param \ErrorException $ex
     * @param string $func task-function
     * @param type $data
     */
    public function onError(\ErrorException $ex,$func,$data)
    {
        $this->_log->app_error($ex->getMessage()."#$func(". json_encode($data).")\n".$ex->getTraceAsString());
        return $ex->getMessage();
    }
    /**
     * 执行task之前调用(不是task的callback)
     */
    public function doBeforeTask($func,$data)
    {
        
    }
    /**
     * 执行task之前调用(不是task的callback)
     */
    public function doAfterTask($func,$data)
    {
        
    }
   
}
