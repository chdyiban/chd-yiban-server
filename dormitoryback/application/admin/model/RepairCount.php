<?php

namespace app\admin\model;
use think\Db;
use think\Model;

class RepairCount extends Model
{
    // 表名
    protected $name = '';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];
    /**
     * 获取所有工人工作统计情况
     */
    public function getWorkerCount($workerIdList)
    {
        $workerCountResult = array();
        
    }
    /**
     * 获取单个工人工作情况统计
     * @param int id
     */
    public function getSingleCount($id)
    {
       
    }

    







}
