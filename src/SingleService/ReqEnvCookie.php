<?php
namespace SingleService;

class ReqEnvCookie {
    protected static function trySingleServerFirst($cookies=null)
    {
        return null;
    }
    protected static $defined=array();
    public static function init($signKey,$SessionName,$arrMore=null)
    {
        self::$defined=array(
            'ServcieProxySignkey'=>$signKey,
            'CookieNameForSession'=>$SessionName,
            'CookieNameForUserID'=>(is_array($arrMore)?$arrMore['CookieNameForUserID']:'UidSetBySerivceProxy'),
            'CookieNameForExtRouteId'=>(is_array($arrMore)?$arrMore['CookieNameForExtRouteId']:'RouteChoseBySerivceProxy'),
            'CookieNameForDtStart'=>(is_array($arrMore)?$arrMore['CookieNameForDtStart']:'TimeStampOnBegin'),
            'CookieNameForSign'=>(is_array($arrMore)?$arrMore['CookieNameForSign']:'SignForSerivceProxy'),
            'RequestSNTransferByCookie'=>(is_array($arrMore)?$arrMore['RequestSNTransferByCookie']:'ReqSNAddByServiceProxy'),
        );
    }
    
    public $reqSN;
    public $extRoute;
    public $uid;
    public $sess;
    public $dtStart;
    protected $i = 1;

    protected static $_instance=null;
    /**
     * 
     * @param array $cookies
     * @return  \Sooh2\Misc\ReqEnvCookie 
     */
    public static function getInstance($cookies=null)
    {
        if($cookies!==null){
            self::$_instance = self::trySingleServerFirst($cookies);
            if(empty(self::$_instance)){
                return self::$_instance;
            }
            
            self::$_instance = new ReqEnvCookie;
            self::$_instance->sess = $cookies[self::$defined['CookieNameForSession']];
            self::$_instance->uid = $cookies[self::$defined['CookieNameForUserID']];
            self::$_instance->extRoute = $cookies[self::$defined['CookieNameForExtRouteId']];
            self::$_instance->reqSN = $cookies[self::$defined['RequestSNTransferByCookie']];
            self::$_instance->dtStart = $cookies[self::$defined['CookieNameForDtStart']];
            if(self::$_instance->checkSign($cookies[self::$defined['CookieNameForSign']])==false){
                throw new \ErrorException("check sign failed");
            }
        }
        return self::$_instance;
    }
    
    protected function checkSign($sign)
    {
        $i = substr($sign,0,2);
        $k = substr($sign,-2);
        $chk = substr($sign,2,-2);
        return md5($i.self::$defined['ServcieProxySignkey'].$k)==$chk;
    }
    
    protected function sign()
    {
        $i = rand(10,99);
        $k = rand(10,99);
        $sign = md5($i.self::$defined['ServcieProxySignkey'].$k);
        return $i.$sign.$k;
    }
    
    public function getReqSN()
    {
        return $this->reqSN;
    }
    
    public function getCookieArrForServiceProxy()
    {
        return array(
            self::$defined[self::$defined['CookieNameForDtStart']]=$this->dtStart,
            self::$defined[self::$defined['CookieNameForExtRouteId']]=$this->extRoute,
            self::$defined[self::$defined['CookieNameForUserID']]=$this->uid,
            self::$defined[self::$defined['CookieNameForSession']]=$this->sess,
            self::$defined[self::$defined['RequestSNTransferByCookie']]=$this->reqSN.'_'.($this->_i++),
            self::$defined[self::$defined['CookieNameForSign']]=$this->sign(),
        );
    }
}
