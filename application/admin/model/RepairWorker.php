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
        foreach ($list as $k => $v) {
            $checkBind = Db::name('repair_bind') -> where('type',2) -> where('user_id',$v['id'])->find();
            $list[$k]['isBind'] = !empty($checkBind) ? true : false ;
            $list[$k]['needRepairCount'] = count($this->getWorkerNotFinishList($v['id']));
            $list[$k]['allRepairCount'] = count($this->getWorkerList($v['id']));
            $list[$k]['star'] = $this->getWorkerStar($v['id'])['data']['star'];
            $list[$k]['person'] = $this->getWorkerStar($v['id'])['data']['person'];
        }
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
    /**
     * 获取工人完成工作的列表
     * 
     */

    public function getWorkerFinishList($workerId)
    {
        $list = Db::name('repair_list')
                    -> where('dispatched_id',$workerId)
                    -> where('status','finished')
                    -> select();
        return $list;

    }
    /**
     * 获取工人所有工作列表
     * 
     */

    public function getWorkerList($workerId)
    {
        $list = Db::name('repair_list')
                    -> where('dispatched_id',$workerId)
                    -> select();
        return $list;
    }
    /**
     * 获取工人满意度
     * 
     */

    public function getWorkerStar($workerId)
    {
        $total = 0;
        $list = Db::name('repair_list')
                    -> where('dispatched_id',$workerId)
                    -> where('status','finished')
                    -> field('star')
                    -> select();
        $personNumber = 0;
        if (empty($list)) {
            return [
                'status' => true,
                'data' => [
                    'star' => "未进行评价",
                    'person' => $personNumber
                    ]
            ];
        } 
        foreach ($list as $k => $v) {
            if (!empty($v['star'])) {
                $personNumber += 1;
                $total += $v['star'];
            }
        }
        if ($total == 0) {
            return [
                'status' => true,
                'data' => [
                    'star' => "未进行评价",
                    'person' => $personNumber
                    ]
            ];
        } else {
            return [
                'status' => true,
                'data' => [
                    'star' => round($total/$personNumber,2),
                    'person' => $personNumber
                    ]
            ];
        }

    }
    
}
