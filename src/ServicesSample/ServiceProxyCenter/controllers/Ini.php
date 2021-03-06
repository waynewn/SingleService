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
        if($s=='*'){
            $ret = $this->_Config->dump();
            $this->_view->assign('ini_static',$ret['default']);
            return;
        }
        $names = explode(',',$s);

        if(empty($names)){
            $this->setReturnError('ini-missing',404);
            $this->_view->assign('ini_static',array());
            return ;
        }
        $ret = array();
        foreach($names as $id){
            $ret[$id] = $this->_Config->getIni($id);
        }
        $this->_view->assign('ini_static',$ret);
    }

}
