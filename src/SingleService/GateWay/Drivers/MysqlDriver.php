<?php
namespace SingleService\GateWay\Drivers;

/**
 * 需要的配置格式：
 *  dbid=DB.mysql&tbname=test.tb_mq
 * 需要的数据库表(注意数据json串的长度)：

create table tb_que (
autoid bigint not null auto_increment,
quename varchar(255) not null default '',
quedata varchar(2000) not null default '', 
handled tinyint not null default 0,
dtCreate time ,
dtUpdate time ,
handler varchar(200) not null default '',
primary key (autoid),
index questatus (handled,quename)
);
 */
class MysqlDriver extends \SingleService\GateWay\Driver{
    protected function initConfig($queConfig,$config)
    {
        $ret = parent::initConfig($queConfig, $config);
        $dbIni = $config->getIni($this->arrIni['dbid']);
        list ($dbIni['dbName'],$dbIni['tbName']) = explode('.', $this->arrIni['tbname']);
        $this->arrIni['dbIni']=$dbIni;
        return $this;
    }
    protected function getTb()
    {
        return $this->arrIni['dbIni']['tbName'];
    }
    /**
     * 
     * @return \Sooh\DBClasses\Interfaces\DB
     */
    protected function getDB()
    {
        return \Sooh\DB::getDB($this->arrIni['dbIni']);
    }

    /**
     * 加入队列（注意是否是消费过的消息）
     * @param string $queName 一次只能获取一个队列里的数据
     * @param json-string $data (如果不是数组，会被自动转换成array('data'=>$data))
     * @param bool $handled 是否已经处理过了
     * @return \SingleService\Ret
     */
    public function appendData($queName, $data,$handled=false)
    {
        try{
            $now = date('Y-m-d H:i:s');
            $ret = $this->getDB()->addRecord($this->getTb(),array(
                'quename'=>$queName,
                'quedata'=>(is_string($data)?$data: json_encode($data)),
                'handled'=>$handled?1:0,
                'dtCreate'=>$now,
                'dtUpdate'=>$now,
                'handler'=>'',
            ));
            if($ret){
                return \SingleService\Ret::factoryOk();
            }else{
                return \SingleService\Ret::factoryError("failed");
            }
        } catch (\ErrorException $ex){
            return \SingleService\Ret::factoryError($ex->getMessage());
        }
    }
 
    protected function ensureConnection($forceReconnect=false)
    {

    }

    protected $where=null;
    /**
     * 准备获取哪些队列的数据
     */    
    public function beforeHandleData($arrQues)
    {
        $tmp = is_string($arrQues)?explode(",", $arrQues):$arrQues;
        if(sizeof($tmp)==1){
            $this->where= array('quename'=> current($tmp));
        }else{
            $this->where= array('quename'=> $tmp);
        }
    }
    /**
     * 本轮获取结束，清理相关资源
     */    
    public function afterHandleData()
    {
        $this->where = null;
    }

    /**
     * 取出消息（不等待），交由callback处理，最多处理$limit条，最少0条
     * @param type $limit
     * @return array
     */
    public function getUnhandledDataArray($limit=10)
    {
        if(is_array($this->where)){
            $where = array_merge($this->where,['handled'=>0]);
        }else{
            $where = array('handled'=>0);
        }
        $returnArray = array();
        $now = date('Y-m-d H:i:s');
        $db = $this->getDB();
        $tbname = $this->getTb();
        $handler = getmypid.'@'.gethostname();
        $rs = $db->getRecords($tbname,'*',$where,null,$limit+5);
        foreach($rs as $r){
            $ret = $db->updRecords($tbname,array('handled'=>-1,'handler'=>$handler,'dtUpdate'=>$now),array('autoid'=>$r['autoid']));
            if($ret === 1){
                $data = new \SingleService\GateWay\QueData;
                $data->queName = $r['quename'];
                $data->queData = $r['quedata'];
                $data->handled = $r['handled'];
                $data->driverData = array('autoid'=>$r['autoid'],'handled'=>-1);
                $returnArray[]=$data;
            }
            if(sizeof($returnArray)>=$limit){
                break;
            }
        }
        return $returnArray;
    }
    /**
     * 确认结果，并清理data结构中相关资源
     * @param \SingleService\GateWay\QueData $data
     */
    public function confirmAddFreeData($data) {
        if($data->handled){
            $ret = $this->getDB()->updRecords($this->getTb(),array('handled'=>1),$data->driverData);
            throw new \ErrorException('update set handled=1 failed,'. json_encode($data->driverData));
        }else{
            throw new \ErrorException('not handled, keep handled = -1');
        }
    }
    
    /**
     * 关闭连接，释放资源
     */
    public function closeAndFree()
    {
        
    }
}