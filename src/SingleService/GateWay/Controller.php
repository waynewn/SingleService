<?php
namespace SingleService\GateWay;
/**
 * 基于MQ的网关
 * 三个主要方法：
 *   A）加入队列，登后台任务处理
 *      mod/ctrl/add?que=队列名&data=json格式的数据
 *   B）立即处理，不加入队列
 *      mod/ctrl/do?que=队列名&data=json格式的数据
 *   C）先处理，然后加入队列（根据选用队列，存在重复消费的可能）
 *      mod/ctrl/do?que=队列名&data=json格式的数据
 */
class Controller extends \SingleService\ServiceController
{
    public function checkBeforeAction()
    {
        \SingleService\GateWay\Driver::getInstance($this->_Config);
        $ret = parent::checkBeforeAction();
        return $ret;
    }

    /**
     * 在执行action之后调用，做些额外工作，无返回值
     * @param bool $actionExecuted action 执行过还是没执行过
     */
    public function doAfterAction($actionExecuted)
    {
        \SingleService\GateWay\Driver::getInstance()->closeAndFree();
        $ret = parent::doAfterAction($actionExecuted);
        if(class_exists("\\Sooh\\DBClasses\\KVObj",false)){
            \Sooh\DBClasses\KVObj::freeCopy(null);
        }        
        if(class_exists("\\Sooh\\DB",false)){
            \Sooh\DB::free();
        }
        return $ret;
    }    
    /**
     * @param string $name Description
     * 根据request，实例化QueStruct
     * @return \SingleService\GateWay\QueStruct
     */
    protected function getMsgQueRequest()
    {
        $o = new \SingleService\GateWay\QueData();
        $o->queName = ucfirst($this->_request->get('que'));
        $o->queData = $this->_request->get('data');
        $tmp = json_decode($o->queData,true);
        if(empty($o->queName) || empty($tmp)){
            $this->_log->app_trace('param error:'.$o->queName.' with data:'.$o->queData);
            $this->setReturnError('param error:'.$o->queName);
            return false;
        }
        return $o;
    }
    
    
    /**
     * @SWG\Post(
     *     path="/evtgw/broker/add",
     *     tags={"Platform"},
     *     summary="追加到消息队列",
     *     @SWG\Parameter(name="que",description="队列名称",type="string",in="formData"),
     *     @SWG\Parameter(name="data",description="json格式的数据",type="string",in="formData"),
     * )
     */    
    public function addAction()
    {
        if(($qData = $this->getMsgQueRequest())===false){
            return;
        }
        $ret = \SingleService\GateWay\Driver::getInstance()->appendData($qData->queName, $qData->queData ,false);
        $this->setReturn($ret);
    }
    /**
     * @SWG\Post(
     *     path="/evtgw/broker/do",
     *     tags={"Platform"},
     *     summary="事件处理(立即执行,执行完了加入队列,原/platform/api/doevt)",
     *     @SWG\Parameter(name="que",description="队列名称",type="string",in="formData"),
     *     @SWG\Parameter(name="data",description="json格式的数据",type="string",in="formData"),
     *
     * )
     */
    public function doAction()
    {
        if(($qData = $this->getMsgQueRequest())===false){
            return;
        }
        try{
            $sys = \SingleService\GateWay\Process\Broker::factory($qData->queName, $this->_Config);
            $ret = $sys->handle($qData);
            return $this->setReturn($ret);        
        } catch (Exception $ex) {
            return $this->setReturnError($ex->getMessage());
        }
    }
    
    /**
     * @SWG\Post(
     *     path="/evtgw/broker/do",
     *     tags={"Platform"},
     *     summary="事件处理并加入队列(立即执行,执行完了加入队列,根据driver可能会重复触发,重复触发执行结果可能改变)，反回执行结果",
     *     @SWG\Parameter(name="que",description="队列名称",type="string",in="formData"),
     *     @SWG\Parameter(name="data",description="json格式的数据",type="string",in="formData"),
     *
     * )
     */
    public function doaddAction()
    {
        if(($qData = $this->getMsgQueRequest())===false){
            return;
        }
        
        $retDo = \SingleService\GateWay\Process\Broker::factory($qData->queName, $this->_Config)->handle($qData->queData);
        $retAdd = \SingleService\GateWay\Driver::getInstance()->appendData($qData->queName, $qData->queData ,true);
        
        return $this->setReturn($retDo);//注意，这里用了 returnError，即使是成功的
    } 
}


