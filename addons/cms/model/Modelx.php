<?php

namespace addons\cms\model;

use think\Model;

/**
 * 模型
 */
class Modelx Extends Model
{

    protected $name = "cms_model";
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
    ];

    public function getFieldsAttr($value, $data)
    {
        return (array)json_decode($value, TRUE);
    }

    public function getSettingAttr($value, $data)
    {
        return (array)json_decode($value, TRUE);
    }

    public function getFieldsListAttr($value, $data)
    {
        return Fields::where('model_id', $data['id'])->where('status', 'normal')->select();
    }

}
