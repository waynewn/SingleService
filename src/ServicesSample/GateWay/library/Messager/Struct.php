<?php
namespace GWLibs\Messager;

class MsgStruct{
    public $type = "email";//可以同mq的队列名
    public $users = "user1,user2";
    public $title = "";
    public $content = "";
    public $ext = "";
}