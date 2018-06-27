<?php
namespace SingleService;

/**
 * 各类异步任务处理封装
 *
 * @author wangning
 */
class AsyncTaskDispather {
    public function __construct($config,$loger) {
        $this->_Config = $config;
        $this->_log=$loger;
    }
    /**
     *
     * @var \SingleService\Loger 
     */
    protected $_log;
    /**
     *
     * @var \Sooh\Ini 
     */
    protected $_Config;
    /**
     * 
     * @param Server $SingleServer
     */
    public function onServerStart($SingleServer)
    {
        $this->_log->app_trace('server start');
        //$this->startTimer($swoole);
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
