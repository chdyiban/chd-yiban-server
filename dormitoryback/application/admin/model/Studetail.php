<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class Studetail extends Model
{

    // 表名
    protected $name = 'stu_detail';
    /**
     * 用来使Dormitorylist来进行表关联的模型。
     */
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
}
