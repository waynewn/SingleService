<?php
namespace GWLibs\CustomTasks;
class Email extends \GWLibs\Task\TaskBase
{
    /** 
     * 邮件消息处理
     * @param array $array 数据
     * @return \GWLibs\Ret
     */
    public function run($array)
    {
        $users = $array['users'];
        $title = $array['title'];
        $content = $array['content'];
        \GWLibs\Misc\Funcs::trace(__CLASS__.'->run:'.$title.'# '. json_encode($users).' # '.$content);
        $this->_log->app_trace(__CLASS__.'->run:'.$title);
        return new \GWLibs\Ret();
    }
}

