<?php

namespace app\admin\model;
use think\Db;
use think\Model;

class RepairConfig extends Model
{
    // 表名
    protected $name = 'repair_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
}
