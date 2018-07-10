<?php
namespace SingleService\GateWay;

class AsyncTaskDispatcher extends \SingleService\AsyncTaskDispatcher
{
    public function internalCmd_start($SingleSerivceServer) {
        //从加载的本地类中找出所有的事件ID
        $namespacePre = $this->_Config->getMainModuleConfigItem('QueClassNamespace');
        $len = strlen($namespacePre);
        $replace = array();
        foreach($SingleSerivceServer->_loadedClass as $f){
            if(substr($f, 0,$len)==$namespacePre){
                $replace[$f] = ucfirst(array_pop(explode('\\',$f)));
            }
        }
        parent::internalCmd_start($SingleSerivceServer);
        $SingleSerivceServer->_loadedClass = $replace;
    }    
    /**
     * 创建一个定时任务：每次获取最大指定数量的任务，由异步task执行任务
     * @param \SingleService\Server $SingleServer
     */
    public function onTimer($SingleServer)
    {

        $taskProcessNum = $this->_Config->getMainModuleConfigItem('SERVICE_MAX_TASK');
        $maxDur = $this->_Config->getMainModuleConfigItem('Timer_Interval_MS');
        if($taskProcessNum>5){
            $taskProcessNum = $taskProcessNum -2;
        }
        //找出所有需要处理的消息
//        $ques = explode(',', $this->_Config->getMainModuleConfigItem('QueNameList'));
//        //实例化对应的mq-driver
        $driver = \SingleService\GateWay\Driver::getInstance($this->_Config);
        $this->_log->app_trace("\n\n\n\n".'start with que:'. implode(',', $SingleServer->_loadedClass).'...');
        $driver->beforeHandleData($SingleServer->_loadedClass);
        $dt0 = microtime(true);
        $dur0=0;
        $this->_log->app_trace('task running check0:'.$SingleServer->taskRunning->get());
        for($i=0;$i<10000;$i++){
            $thisTurn=$driver->getUnhandledDataArray($taskProcessNum);
            if(empty($thisTurn)){
                break;
            }
            error_log("###### found ".count($thisTurn).' needs process');
            foreach($thisTurn as $i=>$data){
                $thisTurn[$i] = $SingleServer->packForSwooleTask('processOneData', $data);
            }
            error_log("###### pack");
            $thisTurn = $SingleServer->swoole->taskWaitMulti($thisTurn,5);
            ksort($thisTurn);
            error_log("###### wait end, ". var_export($thisTurn,true));
            foreach($thisTurn as $i=>$data){
                try{
                    $driver->confirmAddFreeData($data);
                }catch(\ErrorException $ex){
                    $this->_log->app_error("error-on-que:".$ex->getMessage());
                }
                unset($thisTurn[$i]);
            }
            
            $dur = ceil((microtime(true)-$dt0)*1000);
            error_log("###### confirm and freed dur = $dur dur0 = $dur0 max-dur=".($maxDur-$dur));
            if($dur0==0){
                $dur0 = $dur;
            }
            if($maxDur-$dur < $dur0+10){//剩余时间小于一个执行周期，本轮结束
                break;
            }
        }
        $this->_log->app_trace('task running check1:'.$SingleServer->taskRunning->get());
        $driver->afterHandleData();
        $driver->closeAndFree();
        $this->_log->app_trace("one turn down \n\n\n\n");
    }

    /**
     * 
     * @param \SingleService\GateWay\QueData $data
     * @return bool 成功或失败
     */
    public function processOneData($data)
    {
        $data->handled = false;
        return $data;
    }
}
