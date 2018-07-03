<?php
namespace SingleService;

/**
 * 各类异步任务处理封装
 *
 * @author wangning
 */
class AsyncTaskDispather {
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
     * 获取当前模块专属配置可以使用 getModuleConfigItem()
     * @var \Sooh\Ini 
     */
    protected $_Config;
    
    /**
     * swoole 启动时通过curl内部命令方式触发该函数，做一些默认操作，慎改
     */
    public function internalCmd_start($SingleServer,$swooleRequest)
    {
        $this->onServerStart($Server, $swooleRequest);
        $timerMS = $this->getModuleConfigItem('Timer_Interval_MS');
        if($timerMS!=0){
            swoole_timer_tick(abs($timerMS), function ($timer_id, $tickCounter) use ($Server) 
            {
                error_log('[internal onTimer] counter='.$tickCounter);
                if($this->getModuleConfigItem('Timer_Interval_MS')<=0){
                    return;
                }
                if($this->_Config->permanent->gets('shuttingDown')===true){
                    return;
                }
                $this->onTimer($Server,$tickCounter);
            },null);
        }
    }

    
    /**
     * server启动时触发一次
     * @param Server $SingleServer
     * @param swoole\request ,$swooleRequest
     */
    protected function onServerStart($SingleServer,$swooleRequest)
    {
        $this->_log->app_trace('server start');
        //$this->startTimer($swoole);
    }
    /**
     * 定时任务， $server 传进来是 createSwooleTask 用的
     */
    protected function onTimer($Server,$tickCounter)
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
    protected function getModuleConfigItem($subname){
        return $this->_Config->getIni($this->_Config->getRuntime('CurServModName').'.'.$subname);
    }    
}
