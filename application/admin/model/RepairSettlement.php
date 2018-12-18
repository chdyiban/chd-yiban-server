<?php

namespace app\admin\model;
use think\Db;
use think\Model;

class RepairSettlement extends Model
{
    // 表名
    protected $name = 'repair_settlement';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];
    //获取维修单位名称
    public function getcompanyname(){
        return $this->belongsTo('Admin', 'repair_company_id');
    }
    
    //获取维修内容名称
    public function getrepairname(){
        return $this->belongsTo('repair_type', 'repair_content_id');
    }


}
