<?php
/**
 * Description of Broker
 *
 * @author wangning
 */
class BrokerController extends \SingleService\ServiceController{
    public function sayhiAction()
    {
        $msgtpl = $this->_Config->getIni('HelloWorld.hello_msg_tpl');
        $msgtpl = 'hi, %s!';
        $this->_serverOfThisSingleService->createSwooleTask('onSwooleTaskStart1', array('taskFrom'=>'broker','seq'=>1),array($this,'onSwooleTaskEnd1'));
        $this->_serverOfThisSingleService->createSwooleTask('onSwooleTaskStart2', array('taskFrom'=>'broker','seq'=>2),array($this,'onSwooleTaskEnd2'));
        $this->_serverOfThisSingleService->createSwooleTask('onSwooleTaskStart1', array('taskFrom'=>'broker','seq'=>3),array($this,'onSwooleTaskEnd1'));
        $this->_serverOfThisSingleService->createSwooleTask('onSwooleTaskStart2', array('taskFrom'=>'broker','seq'=>4),array($this,'onSwooleTaskEnd2'));
        $this->setReturnMsgAndCode(sprintf($msgtpl,$this->_request->get('name')));

    }
    
    public function longreqAction()
    {
        for($i=0;$i<100000000;$i++){
            
        }
        $this->setReturnMsgAndCode('done');
    }
    
    public function flgAction()
    {
        $o = \Prj\Session::getCopy(array("SessionId"=>'asdfgdfhjh'));
        $o->load();
        $o->setField('uid',123);
        $ret = $o->saveToDB();
        $this->_view->assign('ret', $ret);
        $this->setReturnMsgAndCode('Msg.common.server_busy');
        $this->_view->assign('extendInfo', array('flgB'=>time().'#'.$this->_request->get('flg').'@u:'.$this->_request->get('uid')));
        $this->_log->app_error("aaaaaaaaaaaaaaaaaaa",array('bb'=>1));
    }

    public function onSwooleTaskStart1($serv, $task_id, $src_worker_id, $data)
    {
        error_log("TTAASSKK # broker:".__FUNCTION__.':'. json_encode($data));
    }
    
    public function onSwooleTaskStart2($serv, $task_id, $src_worker_id, $data)
    {
        error_log("TTAASSKK # broker:".__FUNCTION__.':'. json_encode($data));
    }
}
