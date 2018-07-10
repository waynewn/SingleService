<?php
/**
 * 邮件发送服务
 *  方法： sendSync 同步  sendAsync 异步
 *  参数： users(英文逗号分割的邮件地址)，title，content
 * 
 * @author wangning
 */
class BrokerController extends \SingleService\ServiceController{
    protected function parseReq()
    {
        $tmp = new \Email\Libs\Data;
        $tmp->from = $this->_request->get('from');
        $tmp->to = $this->_request->get('to');
        $tmp->title = $this->_request->get('title');
        $tmp->content= $this->_request->get('content');
        $conf = $this->_Config->getMainModuleConfigItem('BySender');
        if(!isset($conf[$tmp->from])){
            $this->setReturnError('error sender address');
            return null;
        }
        if(empty($tmp->title)){
            $this->setReturnError('title is empty');
            return null;
        }
        if(empty($tmp->content)){
            $this->setReturnError('content is empty');
            return null;
        }
        if(empty($tmp->to) || strpos($tmp->to, '@')===false){
            $this->setReturnError('email address invalid');
            return null;
        }
        return $tmp;
    }
    
    public function sendSyncAction()
    {
        $mail = $this->parseReq();
        if($mail==null){
            return;
        }
        $ret = $mail->getMail($this->_Config->getMainModuleConfigItem('BySender')[$mail->from])
                ->sendSimple($mail->to, $mail->content, $mail->title);
        
        if($ret){
            $this->setReturnOK('sent');
        }else{
            $this->setReturnError('send failed');
        }
    }
    public function sendAsyncAction()
    {
        $mail = $this->parseReq();
        if($mail==null){
            return;
        }
        
        try{
            $this->_serverOfThisSingleService->createSwooleTask('sendMail',$data,null );
            $this->setReturnOK('task accepted');
        } catch (\ErrorException $ex) {
            $this->errlog($ex->getMessage().'@'.__CLASS__);
            $this->setReturnError('send failed',500);
        }
    }

}
