<?php
namespace GWLibs;
/* 
 * 执行结果
 */
class Ret {
    public function __construct($msg='success',$code=0) {
        $this->code=$code;
        $this->msg = $msg;
    }

    public $code = 0;
    public $msg = 'success';
}

