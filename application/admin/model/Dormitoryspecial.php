<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class Dormitoryspecial extends Model
{

    // 表名
    protected $name = 'dormitory_special';
    /**
     * 宿舍试读/复学/退学特殊情况登记表
     */
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

     //表关联获取个人姓名
    public function getstuname(){
        return $this->belongsTo('Studetail', 'XH')->setEagerlyType(0);
    }
    
    public function getadminname(){
        return $this->belongsTo('Admin', 'admin_id');
    }


}
