<?php
/**
 * Description of Broker
 *
 * @author wangning
 */
class AgentController extends \SingleService\ServiceController{
    public function sayhiAction()
    {
        $this->_log->app_common(__CLASS__.'->'.__FUNCTION__.'() called');
        $this->_view->assign("cookies", $this->_request->getCookie());
        $this->setReturnOK('hi,'.$this->_request->get('name'));

    }
    
    public function taskAction()
    {
        $this->_log->app_common(__CLASS__.'->'.__FUNCTION__.'() called');
        $this->_serverOfThisSingleService->createSwooleTask(array('taskFrom'=>'broker','seq'=>1), array($this,'onSwooleTaskStart1'), array($this,'onSwooleTaskEnd1'));

        $this->setReturnOK('run background and return accept');
    }
    
    public function proxyAction()
    {
        $this->_log->app_common(__CLASS__.'->'.__FUNCTION__.'() called');
        $serviceProxy = $this->_Config->getIni('ServiceProxy.LocalProxyIPPort');
        $ret = \Sooh\Curl::getInstance()->httpGet($serviceProxy."/".$this->getModuleConfigItem('SERVICE_MODULE_NAME').'/agent/sayhi?name='. urlencode($this->_request->get('name')));
        $this->_view->assign('proxy_result', $ret->body);
        $this->setReturnOK("proxy done");
    }
    
    public function proxy2Action()
    {
        $this->_log->app_common(__CLASS__.'->'.__FUNCTION__.'() called');
        $serviceProxy = $this->_Config->getIni('ServiceProxy.LocalProxyIPPort');
        $baseUri = $serviceProxy."/".$this->getModuleConfigItem('SERVICE_MODULE_NAME').'/agent/';
        $paramPart = '?name='. urlencode($this->_request->get('name'));
        $this->_view->assign('proxy_result', \Sooh\Curl::getInstance()->httpGet($baseUri.'/sayhi'.$paramPart)->body);
        $this->_view->assign('proxy_result2', \Sooh\Curl::getInstance()->httpGet($baseUri.'/proxy'.$paramPart)->body);
        $this->setReturnOK("proxy done");
    }
    
    public function getUsrSettingAction()
    {
        $this->_view->assign('forServiceProxy', array('UID'=>$this->_request->get('sessionId'),'ROUTE'=>'default'));
        $this->setReturnOK("proxy done");
    }
    
    public function flgAction()
    {
//        $o = \Prj\Session::getCopy(array("SessionId"=>'asdfgdfhjh'));
//        $o->load();
//        $o->setField('uid',123);
//        $ret = $o->saveToDB();
//        $this->_view->assign('ret', $ret);
//        $this->setReturnOK('Msg.common.server_busy');
//        $this->_view->assign('extendInfo', array('flgA'=>time().'#'.$this->_request->get('flg').'@u:'.$this->_request->get('uid')));
    }

}
