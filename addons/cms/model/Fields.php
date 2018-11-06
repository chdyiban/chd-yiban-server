<?php

namespace addons\cms\model;

class Fields extends \think\Model
{

    // 表名
    protected $name = 'cms_fields';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'content_list',
    ];
    protected static $listField = ['select', 'selects', 'checkbox', 'radio', 'array'];

    protected static function init()
    {

    }

    public function getContentListAttr($value, $data)
    {
        return in_array($data['type'], self::$listField) ? \app\common\model\Config::decode($data['content']) : $data['content'];
    }

    public function model()
    {
        return $this->belongsTo('Modelx', 'model_id')->setEagerlyType(0);
    }

}
