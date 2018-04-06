<?php

namespace app\admin\model;

use think\Model;

class RepairList extends Model
{
    // 表名
    protected $name = 'repair_list';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];
    

    public function getname(){
        return $this->belongsTo('Admin', 'admin_id');
    }

    public function gettype(){
        return $this->belongsTo('ServiceType', 'service_id');
    }

    public function getaddress(){
        return $this->belongsTo('AddressInfo', 'address_id');
    }

    







}
