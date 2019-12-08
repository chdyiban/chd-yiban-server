<?php

namespace app\admin\model\form;

use think\Model;

class FormQuestionnaireList extends Model
{
    // 表名
    protected $name = 'form_questionnaire';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'type_text',
        'extra_text',
        'status_text',
        'must_text'
    ];
    

    
    public function getTypeList()
    {
        return ['text' => __('Text'),'textarea' => __('Textarea'),'selector' => __('Selector'),'radio' => __('Radio'),'image' => __('Image'),'position' => __('Position'),'checkbox' => __('Checkbox')];
    }     

    public function getExtraList()
    {
        return ['selector' => __('Selector'),'multiSelector' => __('Multiselector'),'time' => __('Time'),'date' => __('Date'),'region' => __('Region')];
    }     

    public function getStatusList()
    {
        return ['0' => __('不可见'),'1' => __('可见')];
    }     

    public function getMustList()
    {
        return ['0' => __('非必填'),'1' => __('必填')];
    }     


    public function getTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['type'];
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getExtraTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['extra'];
        $list = $this->getExtraList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getMustTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['must'];
        $list = $this->getMustList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
