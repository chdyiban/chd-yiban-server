<?php

namespace app\admin\controller\bx;
use think\Db;
use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Repaircount extends Backend
{
    
    /**
     * RepairList模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('RepairCount');
        $this -> control_id = Db::view('auth_group') 
                    -> view('auth_group_access','uid,group_id','auth_group.id = auth_group_access.group_id')
                    -> where('name','报修管理员') 
                    -> find()['uid'];
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index()
    {
       //设置过滤方法
       $this->request->filter(['strip_tags']);
       if ($this->request->isAjax())
       {
           //如果发送的来源是Selectpage，则转发到Selectpage
           if ($this->request->request('pkey_name'))
           {
               return $this->selectpage();
           }

           list($where, $sort, $order, $offset, $limit) = $this->buildparams();
           //获取管理员id
           $admin_id = $this->auth->id;
           $info = model('repairWorker')-> get_worker($admin_id,$offset,$limit);
           $total = $info['count'];
           $data = $info['data'];
           $result = array("total" => $total, "rows" => $data);           
           return json($result);
       }
       return $this->view->fetch();
    }

    /**
     * ajax获取每个工人的工作统计
     * @param key:idlist
     */
    public function getworkercount()
    {
        $param = $this->request->param();
        $workerId = $param['key'];
        $workerCount = $this -> model -> getWorkerCount($workerId);

        return json($roomDetailInfo); 
    }
}
