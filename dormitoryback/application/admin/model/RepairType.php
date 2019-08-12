<?php

namespace app\admin\model;

use think\Model;

class RepairType extends Model
{
    // 表名
    protected $name = 'repair_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];
    public function get_type($offset, $limit)
    {
        $list = $this -> limit($offset, $limit) -> select();
        $list = collection($list)->toArray();
        return ['data' => $list, 'count' => count($list)];
    }

    







}
