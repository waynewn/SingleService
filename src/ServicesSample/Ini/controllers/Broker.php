<?php
/**
 * Description of Broker
 *
 * @author wangning
 */
class BrokerController extends \SingleService\ServiceController{
    //get DB,Email
    //get str.msgcommon,
    //
    public function getiniAction()
    {
        $s = trim($this->_request->get('name'));
        $this->_log->app_trace('/ini/getIni: '.$s);
        if($s=='*'){
            $ret = $this->_Config->dump();
            $this->_view->assign('SoohIni',$ret['default']);
            return;
        }
        $names = explode(',',$s);

        if(empty($names)){
            $this->setReturnError('ini-missing',404);
            $this->_view->assign('SoohIni',array());
            return ;
        }
        $ret = array();
        foreach($names as $id){
            $ret[$id] = $this->_Config->getIni($id);
        }
        $this->_view->assign('SoohIni',$ret);
    }
    public function getlangAction()
    {
        $this->_view->assign('trace',$this->_request->getServerHeader());
    }

    public function dumpAction()
    {
        $this->_view->assign('all_ini',$this->_Config->dump());
    }
    public function envAction()
    {
        $trace = $this->_request->getServerHeader();
        $trace['cookie']=$this->_request->getCookie();
        $this->_view->assign('trace', $trace);
        $this->_view->assign('remoteIP', $this->getRemoteAddr());
        $this->setReturnOK();
    }
}
