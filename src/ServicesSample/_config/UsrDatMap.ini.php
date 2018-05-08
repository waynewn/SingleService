<?php
return array(
    'flgA'=>array('expire'=>30,'url'=>'http://127.0.0.1:8010/ignore/agent/flg?flg={f}&uid={u}','post'=>'','jsonLoc'=>'extendInfo.flgA'),
    'flgB'=>array('expire'=>60,'url'=>'http://127.0.0.1:8010/ignore/broker/flg','post'=>'{"data":{"flg":"{f}","uid":"{u}"}}','jsonLoc'=>'extendInfo.flgB'),
    'UserBasicInfo'=>array('expire'=>3600,'url'=>'http://127.0.0.1:123/platform1/asdf/donothing?flg={f}&uid={u}','post'=>'','jsonLoc'=>'extendInfo'),
    'NewbieStep'=>array(),
    "NewLetter","CouponUnSendCount","CurrentSwitch","UserPoint","CreditNotice","AppLabel",
    "Alert",
);

