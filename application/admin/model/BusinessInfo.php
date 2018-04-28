<?php

namespace app\admin\model;

use think\Model;

class BusinessInfo extends Model
{
    // 表名
    protected $name = 'business_info';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'type_text'
    ];
    

    
    public function getTypeList()
    {
        return ['nongye' => __('Type nongye'),'gongye' => __('Type gongye'),'lvyou' => __('Type lvyou'),'fangchan' => __('Type fangchan')];
    }     


    public function getTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['type'];
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
