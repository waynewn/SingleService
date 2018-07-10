<?php
/**
 * Description of Broker
 *
 * @author wangning
 */
class ManagerController extends \SingleService\ServiceController{
    
    public function createAction()
    {
        $sessionId = $this->_request->get('serssionId');
        $userId = $this->_request->get('userId');
        $deviceType = $this->_request->get('deviceType');
        $proxyRoute = $this->_request->get('proxyRoute');
        
        $this->setReturnOK();
    }
    
    public function getsAction()
    {
        
    }
    public function forServiceProxyAction()
    {
        $sess = $this->_request->get('sessionid');
        $this->_view->assign('UidSetBySerivceProxy', "");
        $this->_view->assign('RouteChoseBySerivceProxy', ((ord($sess)%2)?"gray":"default"));
        $this->setReturnOK();
    }
}
