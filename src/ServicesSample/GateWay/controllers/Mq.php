<?php
/**
 * 网关，主要接口
 *       add 加入消息队列，等待后台任务执行
 * 
 * @author wangning
 */
class MqController extends \SingleService\ServiceController{
    protected $queName;
    protected $queData;
    protected function getMsgQueRequest()
    {

        $this->queName = ucfirst($this->_request->get('quename'));
        if(empty($this->queName)){
            $this->setReturnError("这里只接收：");
            return false;
        }
        $this->queData = $this->_request->get('quedata');
        if($this->queData[0]!='{' || $this->queData[strlen($this->queData)-1]!='}'){
            $this->setReturnError("数据的格式不是json的");
            return false;
        }
        return true;
    }
    
    /**
     * @SWG\Post(
     *     path="/evtgw/broker/add",
     *     tags={"Platform"},
     *     summary="事件通知(/platform/api/addevt)",
     *     @SWG\Parameter(name="quename",description="队列名称",type="string",in="formData"),
     *     @SWG\Parameter(name="quedata",description="json格式的数据",type="string",in="formData"),
     * )
     */    
    public function addAction()
    {
        if($this->getMsgQueRequest()==false){
            return;
        }
        $driverId = $this->_Config->getIni('MQUsed.'.$this->queName.'.driver');
        if(!empty($driverId)){
            $driver = \GWLibs\MsgQue\Broker::factory($this->_Config->getIni('MQDriver.'.$driverId));
            $ret = $driver->sendData($this->queName, $this->queData);
            if($ret->code==0){
                return $this->setReturnError("sent");
            }else{
                return $this->setReturnError("send failed:".$ret->msg);
            }
        }else{
            return $this->setReturnError("driver not found");
        }
    }
    /**
     * @SWG\Post(
     *     path="/evtgw/broker/do",
     *     tags={"Platform"},
     *     summary="事件处理(立即执行,执行完了加入队列,原/platform/api/doevt)",
     *     @SWG\Parameter(name="quename",description="队列名称",type="string",in="formData"),
     *     @SWG\Parameter(name="quedata",description="json格式的数据",type="string",in="formData"),
     *
     * )
     */
    public function doAction()
    {
        if($this->getMsgQueRequest()==false){
            return;
        }
        
        $ret = \GWLibs\Task\Dispatcher::one($this->queName, $this->queData,$this->_Config,$this->_log);
        return $this->setReturnError($ret['msg'],$ret['code']);
    }
    
    /**
     * @SWG\Post(
     *     path="/evtgw/broker/do",
     *     tags={"Platform"},
     *     summary="事件处理(立即执行,执行完了加入队列,原/platform/api/doevt)",
     *     @SWG\Parameter(name="quename",description="队列名称",type="string",in="formData"),
     *     @SWG\Parameter(name="quedata",description="json格式的数据",type="string",in="formData"),
     *
     * )
     */
    public function doaddAction()
    {
        if($this->getMsgQueRequest()==false){
            return;
        }
        
        $ret = \GWLibs\Task\Dispatcher::one($this->queName, $this->queData,$this->_Config,$this->_log);
        $this->addAction();
        
        return $this->setReturnError($ret->msg,$ret->code);//注意，这里用了 returnError，即使是成功的
    }    
}
