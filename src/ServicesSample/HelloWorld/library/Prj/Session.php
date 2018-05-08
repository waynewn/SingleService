<?php
namespace Prj;

/**
 * Description of Session
 *
 * @author wangning
 */
class Session extends \Sooh2\DB\KVObj{
    protected function onInit()
    {
        parent::onInit();
        $this->_tbName = 'tb_cachesimple_{i}';//表名的默认模板
        $this->cacheExpiredIn = 15;
    }
    
}
