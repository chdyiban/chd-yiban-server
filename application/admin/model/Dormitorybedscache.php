<?php

namespace app\admin\model;
use think\Db;
use think\Model;

class Dormitorybedscache extends Model
{
    // 表名
    //protected $name = 'dormitory_system';
    protected $name = 'dormitory_beds_cache';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('空床'),'1' => __('正常'),'2' => __('有损坏')];
    } 
    
    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    //表关联获取宿舍名称
    public function getrooms(){
        return $this->belongsTo('Dormitory', 'FYID')-> setEagerlyType(0);
    }
    //表关联获取院系名称
    public function getcollege(){
        return $this->belongsTo('College', 'YXDM')->setEagerlyType(0);
    }

    //表关联获取个人姓名
    public function getstuname(){
        return $this->belongsTo('Studetail', 'XH')->setEagerlyType(0);
    }
}
