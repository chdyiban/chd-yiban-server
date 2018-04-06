<?php

namespace app\admin\model;

use think\Model;

class CompanyInfo extends Model
{
    // 表名
    protected $name = 'company_info';
    
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
        return ['shanghui' => __('Type shanghui'),'xiehui' => __('Type xiehui'),'xuehui' => __('Type xuehui'),'qiye' => __('Type qiye')];
    }     


    public function getTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['type'];
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    
    public function getname(){
        return $this->belongsTo('Admin', 'admin_id');
    }

    public function category(){
        return $this->belongsTo('\app\common\model\Category', 'category_id');
    }

    public function categorys(){
        return $this->belongsTo('\app\common\model\Category', 'category_ids');
    }



}
