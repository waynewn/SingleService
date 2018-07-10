<?php
class AsyncTaskDispatcher extends \SingleService\GateWay\AsyncTaskDispatcher{
    /**
     * 
     * @param \SingleService\GateWay\QueData $data
     * @return bool 成功或失败
     */
    public function processOneData($data)
    {
        $data->handled = false;
        return $data;
    }
}