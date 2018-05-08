<?php
/**
 * Description of Broker
 *
 * @author wangning
 */
class BrokerController extends \SingleService\ServiceController{
    public function getiniAction()
    {
        $names = explode(',',trim($this->_request->get('name')));

        if(empty($names)){
            $this->setReturnMsgAndCode('ini-missing',404);
            $this->_view->assign('ini_static',array());
            return ;
        }
        $ret = array();
        foreach($names as $id){
            $ret[$id] = $this->_Config->getIni($id);
        }
        $this->_view->assign('ini_static',$ret);
    }
    public function dumpAction()
    {
        $this->_view->assign('all_ini',$this->_Config->dump());
    }
}
