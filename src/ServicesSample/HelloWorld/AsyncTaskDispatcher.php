<?php
/**
 * Description of Task
 *
 * @author wangning
 */
class AsyncTaskDispatcher extends \SingleService\AsyncTaskDispatcher{
    /**
     * 
     * @param \SingleService\Server $SingleServer
     */
    protected function onServerStart($SingleServer)
    {
        $this->_log->app_trace('AsyncTaskDispatcher server start:'. json_encode($this->_Config->dump()));
    }
    
    protected function onTimer($Server) {
        error_log('------onTick: '.$dt);
        //$SingleServer->createSwooleTask('onTimer00', $dt);
    }


    public function onSwooleTaskStart1($data)
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
