<?php

namespace app\admin\model\form;

use think\Model;
use think\Db;

class Formlist extends Model
{

    // 表名
    protected $name = 'form';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

}
