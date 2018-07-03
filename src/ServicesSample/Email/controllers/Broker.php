<?php
/**
 * 邮件发送服务
 *  方法： sendSync 同步  sendAsync 异步
 *  参数： users(英文逗号分割的邮件地址)，title，content
 * 
 * @author wangning
 */
class BrokerController extends \SingleService\ServiceController{
    public function sendSyncAction()
    {
        $users = $this->_request->get('users');
        $title = $this->_request->get('title');
        $content=$this->_request->get('content');
        
        if($this->send($users,$title,$content)){
            $this->setReturnOK('sent');
        }else{
            $this->setReturnError('send failed',500);
        }
    }
    public function sendAsyncAction()
    {
        $users = $this->_request->get( 'users');
        $title = $this->_request->get( 'title');
        $content=$this->_request->get( 'content');
        
        $data = array('users'=>$users,'title'=>$title,'content'=>$content,
                'initstr'=>'user='.$this->_Config->getIni('Email.MAILSERVICE_SENDER_USER')
                 .'&pass='.$this->_Config->getIni('Email.MAILSERVICE_SENDER_PASSWORD')
                 .'&server='.$this->_Config->getIni('Email.MAILSERVICE_MAILSERVER_ADDR')
                 .'&port='.$this->_Config->getIni('Email.MAILSERVICE_MAILSERVER_SSLPORT'));
        try{
            $this->_serverOfThisSingleService->createSwooleTask('sendMail',$data,null );
            $this->setReturnOK('task accepted');
        } catch (\ErrorException $ex) {
            $this->errlog($ex->getMessage().'@'.__CLASS__);
            $this->setReturnError('send failed',500);
        }
    }

    
    protected function send($users,$title,$content)
    {
        try{
            $s = 'user='.$this->_Config->getIni('Email.MAILSERVICE_SENDER_USER')
                 .'&pass='.$this->_Config->getIni('Email.MAILSERVICE_SENDER_PASSWORD')
                 .'&server='.$this->_Config->getIni('Email.MAILSERVICE_MAILSERVER_ADDR')
                 .'&port='.$this->_Config->getIni('Email.MAILSERVICE_MAILSERVER_SSLPORT');
            \Libs\SmtpSimple::factory($s)
                    ->sendSimple($users, $content, $title);
            return true;
        }catch(\ErrorException $ex){
            $this->errlog($ex->getMessage().'@'.__CLASS__);
            return false;
        }
    }

}
