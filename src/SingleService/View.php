<?php
namespace SingleService;

/**
 * Description of Response
 *
 * @author wangning
 */
class View {
    protected $_arr=array();

    /**
     * 
     * @param \SingleService\Ret $ret
     */
    public function setResult($ret)
    {
        $arr = $ret->toArray();
        foreach($arr as $k=>$v){
            $this->_arr[$k]=$v;
        }
    }
    
    public function assign($k,$v)
    {
        $this->_arr[$k]=$v;
    }

    public function renderJson4Swoole($response)
    {
        foreach($this->newCookies as $r){
            call_user_func_array(array($response,'cookie'),$r);
        }        
        if(!empty($this->forceCodeAndLocation)){
            $response->status($this->forceCodeAndLocation[0]);
            if(!empty($this->forceCodeAndLocation[1])){
                $response->header("Location", $this->forceCodeAndLocation[1]);
            }
        }else{
            $response->header("Content-Type", "application/json");
            $s = json_encode($this->_arr);
            if($s===false){
                $response->end('["result can not convert to json"]');
            }else{
                $response->end($s);
            }
        }
    }
    /**
     * 设置或获取 要返回的httpCode和重定向地址
     * 使用示例：
     * ->httpCodeAndNewLocation(404) 设置返回404错误
     * ->httpCodeAndNewLocation(301, '/new/location') 设置重定向
     * ->httpCodeAndNewLocation(null) 获取当前的设置 null 或 array(code,uri-redir)
     * @param type $code
     * @param type $redir
     * @return type
     */
    public function httpCodeAndNewLocation($code,$redir=null)
    {
        if($code==null){
            return $this->forceCodeAndLocation;
        }else{
            $this->forceCodeAndLocation=array($code,$redir);
        }
    }
    protected $forceCodeAndLocation=null;
    /**
     * 
     * @return \SingleService\View
     */
    public function cloneone()
    {
        $c = get_called_class();
        return new $c;
    }
    
    protected $newCookies=array();
    /**
     * 设置或获取 要设置的cookie
     * @param type $key
     * @param type $value
     * @param type $expire
     * @param type $path
     * @param type $domain
     * @param type $secure
     * @param type $httponly
     * @return type
     */
    public function setcookie($key, $value = '', $expire = 0 , $path = '/', $domain  = '', $secure = false , $httponly = false)
    {
        $this->newCookies[]=[$key, $value, $expire, $path, $domain, $secure, $httponly];
    }
}
