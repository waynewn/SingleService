<?php
namespace SingleService;
class Ret {
    protected static $preDefined=array('code',0,"message","success",-1);
    public static function init($codeName='code',$value=0,$msgName='message',$msg='success',$defaultErrCode=-1)
    {
        self::$preDefined = array($codeName,$value,$msgName,$msg,$defaultErrCode );
    }
    protected $code;
    protected $msg;
    public function __construct($msg,$code) {
        $this->code = $code;
        $this->msg = $msg;
    }
    /**
     * 生成成功的结果
     * @param string $msg null表示使用默认的
     */
    public static function factoryOk($msg=null)
    {
        if($msg===null){
            return new Ret(self::$preDefined[3],self::$preDefined[1]);
        }else{
            return new Ret($msg,self::$preDefined[1]);
        }
    }
    /**
     * 生成失败的结果
     * @param string $msg
     * @param string $code null表示使用默认的
     */
    public static function factoryError($msg, $code=null)
    {
        if($code===null){
            return new Ret($msg,self::$preDefined[4]);
        }else{
            return new Ret($msg,$code);
        }
    }

    public static function factoryFromArray($arr)
    {
        return new Ret($arr[self::$preDefined[2]],$arr[self::$preDefined[0]]);
    }
    
    public static function factoryFromJsonString($str)
    {
        $tmp = json_decode($str,true);
        if(is_array($tmp)){
            return self::factoryFromArray($tmp);
        }else{
            throw new \ErrorException('unknown struct for Ret ('.$str.')');
        }
    }
    
    /**
     * 返回消息（不论成功的还是失败的）
     */
    public function getMessage()
    {
        return $this->msg;
    }
    /**
     * 获取错误码，没错误，返回null
     */
    public function getErrorCode()
    {
        if($this->code==self::$preDefined[1]){
            return null;
        }else{
            return $this->code;
        }
    }
    
    public function toArray()
    {
        return array(
            self::$preDefined[0]=>$this->code,
            self::$preDefined[2]=>$this->msg,
        );
    }
    public function toJsonString()
    {
        return json_encode($this->toArray());
    }
}