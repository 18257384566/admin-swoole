<?php

class AdminlogTask extends \App\Core\AppBaseTask
{

    public function delAction()
    {
        //删除100天以前的数据
        $time = time() - 8640000;
        $this->getModel('AdminLog')->delByTime($time);
    }

}