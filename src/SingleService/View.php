<?php
namespace SingleService;

/**
 * Description of Response
 *
 * @author wangning
 */
class View {
    protected $_arr=array();

    public function assign($k,$v)
    {
        $this->_arr[$k]=$v;
    }

    public function renderJson4Swoole($response)
    {
        $response->header("Content-Type", "application/json");
        $response->end(json_encode($this->_arr));
    }
}
