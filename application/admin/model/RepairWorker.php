<?php

namespace app\admin\model;
use think\Db;
use think\Model;

class RepairWorker extends Model
{
    // 表名
    protected $name = 'repair_worker';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    /**
     * 管理员维护自己单位的人员名单
     */
    public function get_worker($admin_id,$offset, $limit)
    {
        
        $list = Db::view('repair_worker')
                    ->view('admin','id,nickname','repair_worker.distributed_id = admin.id')
                    ->where('distributed_id',$admin_id)
                    ->limit($offset, $limit)
                    ->field('repair_worker.id,mobile,name')
                    ->select();
        $list = collection($list)->toArray();
        return ['data' => $list, 'count' => count($list)];
    }
    /**
     * 获取外协以及后勤的工人列表
     */
    public function getCompanyWorker($companyId,$offset,$limit)
    {
        $list = Db::name('repair_worker')
                    ->where('distributed_id',$companyId)
                    ->limit($offset, $limit)
                    ->select();

        $list = collection($list)->toArray();
        return ['data' => $list, 'count' => count($list)];
    }

    /**
     * 获取工人未完成的列表
     * 
     */

    public function getWorkerNotFinishList($workerId)
    {
        $list = Db::name('repair_list')
                    -> where('dispatched_id',$workerId)
                    -> where('status','dispatched')
                    -> select();
        return $list;

    }
    
}
