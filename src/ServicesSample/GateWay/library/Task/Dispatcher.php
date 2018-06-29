<?php
namespace GWLibs\Task;
/* 
 * 网关定时后台处理任务分发处理
 */

class Dispatcher {
    /**
     * 执行一个任务
     * @param type $queName
     * @param type $queData
     * @param type $config Description
     * @param \SingleService\Loger  $loger Description
     * @return \GWLibs\Ret
     */
    public static function one($queName,$queData,$config,$loger)
    {
        try{
            $obj = self::getObjByQueName($queName,$config,$loger);
            $ret = $obj->run($queData);
            $loger->app_common("task:$queName ".(is_scalar($queData)?$queData:json_encode($queData))." done: ".json_encode($ret));
            return $ret;
        }catch(\ErrorException $ex){
            $loger->app_common("task:$queName ".(is_scalar($queData)?$queData:json_encode($queData))." error: ".$ex->getMessage());
            return new \GWLibs\Ret($ex->getMessage(),-1);
        }
    }
    /**
     * @return \GWLibs\Task\TaskBase
     */
    protected static function getObjByQueName($queName,$config,$loger)
    {
        $s = '\\GWLibs\\CustomTasks\\'.$queName;
        $o = new $s($config,$loger);
        $o->init();
        return $o;
    }
    
    public static function reportError($mqReportError, $queName, $queData)
    {
        
    }
}

