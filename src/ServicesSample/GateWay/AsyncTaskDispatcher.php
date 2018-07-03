<?php
/**
 * 网关任务
 *
 * @author wangning
 */
class AsyncTaskDispatcher extends \SingleService\AsyncTaskDispather{
    /**
     * 创建一个定时任务：每次获取最大指定数量的任务，由异步task执行任务
     * @param \SingleService\Server $SingleServer
     */
    public function onTimer($Server,$tickCounter)
    {
        $modName = $this->_Config->getRuntime('CurServModName');
        $max = $this->getModuleConfigItem('Task_Per_Interval');
        \GWLibs\Misc\Funcs::trace("on timer start ----------$tickCounter");
        if($max==0){//设置Task_Per_Interval=0，可以临时暂停后台处理逻辑（计时器继续跑）
            return;
        }
        $finished=0;
        //找出所有需要处理的消息
        $ques = explode(',', $this->_Config->getIni($modName.'.QueNameList'));
        //实例化对应的mq-driver
        $queDriver=array();
        foreach($ques as  $queName){
            $queName = ucfirst($queName);
            $driverId = $this->_Config->getIni('MQUsed.'.$queName.'.driver');
            \GWLibs\Misc\Funcs::trace(">>driverId = $driverId");
            if(!empty($driverId)){
                if(isset($queDriver[$driverId])){
                    $driver = $queDriver[$driverId]['driver'];
                }else{
                    \GWLibs\Misc\Funcs::trace(">>driver = ". json_encode($this->_Config->getIni('MQDriver.'.$driverId)));
                    try{
                        $driver = \GWLibs\MsgQue\Broker::factory($this->_Config->getIni('MQDriver.'.$driverId));
                        $driver->ensureConnection();
                    } catch (\ErrorException $ex){
                        $this->_log->app_error("driver invalid: queName: $queName -> driver: $driverId ".$ex->getMessage());
                        $driver = null;
                    }
                }
            }else{
                $driver = null;
            }
            if(empty($driver)){
                \GWLibs\Misc\Funcs::trace("driver invalid: queName: $queName -> driver: $driverId ");
                $this->_log->app_error("driver invalid: queName: $queName -> driver: $driverId ");
            }else{
                $queDriver['driver'][$driverId] = $driver;
                $queDriver['ques'][$driverId][] = $queName;
            }
        }
        if(empty($queDriver['driver'])){//没找到有效的mq
            \GWLibs\Misc\Funcs::trace("no mq-driver found in timer");
            $this->_log->app_error("no mq-driver found in timer");
            return;
        }
        //通知所有的mq准备处理
        foreach($queDriver['driver'] as $driverId=>$driver){
            \GWLibs\Misc\Funcs::trace("driver prepare handle: $driverId :  ". json_encode($queDriver['ques'][$driverId]));
            $driver->beforeHandleData($queDriver['ques'][$driverId]);
        }

        while($finished<$max){//没达到一轮最多处理数量之前，只要有积压的任务就处理
            //遍历所有消息队列，发现有任务的创建后台任务处理，没有任务的，从本轮查询队列里移除，等下一轮timer

            foreach($queDriver['driver'] as $driverId=>$driver){
                $done = $driver->handleData(function ($data) use ($SingleServer){
                    $SingleServer->createSwooleTask('doOneJob', $data);
                },10);

                if($done==0){//该队列空，本轮不再查询处理
                    \GWLibs\Misc\Funcs::trace(" $driverId empty this round, skip ");
                    $driver->endHandleData($queDriver['ques'][$driverId]);
                    $driver->closeAndFree();
                    unset($queDriver['driver'][$driverId]);
                }else{
                    \GWLibs\Misc\Funcs::trace("处理了   $done  个 ");
                    $finished += $done;
                }
            }
            if(empty($queDriver['driver'])){
                break;
            }
        }
    }
    /**
     * 由后台task处理一个任务
     * @param \GWLibs\MsgQue\Data $data 
     */
    public function doOneJob($data)
    {

        try{
            $ret = \GWLibs\Task\Dispatcher::one($data->fromQue, $data->strData,$this->_Config,$this->_log);
            if($ret->code!=0){
                
                throw new \ErrorException($ret->msg);
            }else{
                $this->_log->app_common('事件消息处理成功 '.$data->fromQue.' data ' . $data->strData. ' ret '. json_encode($ret) );
            }
        } catch (\ErrorException $ex) {
            $reportIni = $this->_Config->getIni($this->_Config->getRuntime('CurServModName').'.ReportFailedTask');
            
            if (strtoupper($reportIni['method'])=='POST'){
                $realParam = str_replace(array(), array(), $reportIni['uri']);
                \Sooh\Curl::getInstance()->httpPost($reportIni['uri'], $realParam);
            }else{
                $realParam = str_replace(array(), array(), $reportIni['uri']);
                \Sooh\Curl::getInstance()->httpPost($reportIni['uri'], $realParam);
            }
            $this->_log->app_error('事件消息处理失败 '.$data->fromQue.' data:' . $data->strData.' err'.$ex->getMessage() );
        }
    }

}
