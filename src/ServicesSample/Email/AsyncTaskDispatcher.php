<?php
/**
 * Description of Task
 *
 * @author wangning
 */
class AsyncTaskDispatcher extends \SingleService\AsyncTaskDispatcher{

    public function sendMail($data)
    {        
        \Libs\SmtpSimple::factory($data['initstr'])
                    ->sendSimple($data['users'], $data['content'], $data['title']);
        //return true;
    }

}
