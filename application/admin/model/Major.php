<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class Major extends Model
{
    // 表名
    protected $name = 'fdy_major';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];
  
}
