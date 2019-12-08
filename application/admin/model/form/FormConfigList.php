<?php

namespace app\admin\model\form;

use think\Model;

class FormConfigList extends Model
{
    // 表名
    protected $name = 'form_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'start_time_text',
        'end_time_text',
        'status_text'
    ];
    
    //表关联获取院系名称
    public function getcollege(){
        return $this->belongsTo('app\admin\model\College', 'YXDM')->setEagerlyType(0);
    }

    //表关联获取个人姓名
    public function getform(){
        return $this->belongsTo('Formlist', 'form_id')->setEagerlyType(0);
    }
    
    public function getStatusList()
    {
        return ['0' => __('关闭'),'1' => __('开启')];
    }     


    public function getStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['start_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['end_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setStartTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setEndTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


}
