<?php
/**
 * Description of Task
 *
 * @author wangning
 */
class AsyncTaskDispatcher extends \SingleService\AsyncTaskDispather{
    /**
     * 
     * @param \SingleService\Server $SingleServer
     */
    public function onServerStart($SingleServer)
    {
        $this->_log->app_trace('AsyncTaskDispatcher server start:'. json_encode($this->_Config->dump()));
        //$this->startTimer($swoole);
        
//        swoole_timer_tick(1000,function ($timer_id, $tickCounter) use ($SingleServer){
//            $dt = date('H:i:s');
//            error_log('------onTick: '.$dt);
//                $SingleServer->createSwooleTask('onTimer00', $dt);
//        },null);

    }
    public function onSwooleTaskStart1($data)
    {        
        for($i=0;$i<100000000;$i++){
            
        }
        $this->_log->app_trace("TTAASSKK # AsyncTaskDispatcher:".__FUNCTION__.':'. json_encode($data));
    }
    public function onSwooleTaskStart2($data)
    {       
        for($i=0;$i<100000000;$i++){
            
        }
        $this->_log->app_trace("TTAASSKK # AsyncTaskDispatcher:".__FUNCTION__.':'. json_encode($data));
    }
    
    public function onTimer00($data)
    {
        error_log('-----------onTimer: '.$data);
    }
}