<?php
namespace SingleService;

/**
 * Description of Request
 *
 * @author wangning
 */
class Request {
    protected $_req;
    public function __construct($req) {
        $this->_req = $req;
    }
    protected $_params=array();
    public function setParam($k,$v)
    {
        $this->_params[$k]=$v;
    }
    public function getParam($k)
    {
        return $this->_params[$k];
    }
    public function get($key)
    {
        if(isset($this->_req->get[$key])){
            return $this->_req->get[$key];
        }elseif(isset($this->_req->post[$key])){
            return $this->_req->post[$key];
        }else{
            
            
            return $this->_params[$key];
        }
    }
    public function setMCA($m,$c,$a)
    {
        $this->_params['__AcT__']=$a;
        $this->_params['__CtrL__']=$c;
        $this->_params['__MdL__']=$m;
    }
    public function getActionName()
    {
        return $this->_params['__AcT__'];
    }
    public function getControllerName()
    {
        return $this->_params['__CtrL__'];
    }
    public function getModuleName()
    {
        return $this->_params['__MdL__'];
    }
    public function getCookie($key=null)
    {
        if($key!=null){
            if(isset($this->_req->cookie[$key])){
                return $this->_req->cookie[$key];
            }else{
                return null;
            }
        }else{
            return $this->_req->cookie;
        }
    }
    public function getServerHeader($key=null)
    {
        if($key==null){
            return array(
              'server'=>$this->_req->server,
              'header'=>$this->_req->header,
            );
        }
        if(isset($this->_req->server[$key])){
            return $this->_req->server[$key];
        }elseif(isset($this->_req->header[$key])){
            return $this->_req->header[$key];
        }else{
            return null;
        }
    }
    /**
     * 获取 $_FILES
     * @return array
     */
    public function getUploadFile()
    {
        return $this->_req->files;
    }
}
