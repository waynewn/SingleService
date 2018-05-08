<?php
/**
 * Description of Task
 *
 * @author wangning
 */
class AsyncTaskDispatcher {
    public function sendMail($data)
    {        
        \Libs\SmtpSimple::factory($data['initstr'])
                    ->sendSimple($data['users'], $data['content'], $data['title']);
        //return true;
    }
    /**
     * 如果有等待的回调，必须要有返回值，且不能返回类
     * @param \ErrorException $ex
     * @param type $data
     */
    public function onError(\ErrorException $ex,$data)
    {
        
    }
}
