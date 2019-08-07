<?php

namespace App\Models;

use Phalcon\Mvc\Model;

class BaseModel extends Model
{
    public function initialize()
    {

    }

    protected function setTableName($tableName)
    {
        if(strpos($tableName,'system')===false){
            $prefix = $this->getDI()->get('config')['database']['dbprefix'];
            $this->setSource($prefix.$tableName);
        }else{
            $this->setSource('homepage_'.$tableName);
        }

    }
}