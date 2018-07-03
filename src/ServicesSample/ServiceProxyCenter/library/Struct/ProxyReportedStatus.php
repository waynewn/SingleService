<?php
namespace Sooh\ServiceProxy\Struct;

class ProxyReportedStatus {
    public $ErrMsg = '';//如果有错误，这里给出错误说明
    public $ProxyIP="127.0.0.1";
    public $TimeStartup = "";
    public $ConfigVersion = '1.0.0';
    public $CurrentRequesting = array(/*ipport=>num*/);
    public $NodesStatus = array(/*nodename=>ping cmd result*/);
}
