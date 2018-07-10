<?php
namespace Email\Libs;

class Data{
    public $from;
    public $to;
    public $title;
    public $content;
    /**
     * 
     * @param type $config
     * @return \Email\Libs\SmtpSimple
     */
    public function getMail($arrConfBySender)
    {
        return \Email\Libs\SmtpSimple::factory(http_build_query($arrConfBySender));
    }
    
    public function send()
    {
        
    }
}