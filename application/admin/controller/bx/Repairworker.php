<?php

namespace app\admin\controller\bx;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Repairworker extends Backend
{
    
    /**
     * RepairWorker模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('RepairWorker');

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
            $info = $this -> model -> get_worker($admin_id,$offset,$limit);
            $total = $info['count'];
            $data = $info['data'];
            $result = array("total" => $total, "rows" => $data);           
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill)
                {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $params['distributed_id'] = $this->auth->id;
                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false)
                    {
                        $this->success();
                    }
                    else
                    {
                        $this->error($this->model->getError());
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 查看未完成工作情况
     */
    public function workProgress()
    {
        $workerId = $this ->request -> param('ids');
        //获取未完成列表
        $notFinishList = $this -> model -> getWorkerNotFinishList($workerId);
        $this->view->assign('notFinishList',$notFinishList);
        return $this->view->fetch('workProgress');
    }
    /**
     * 查看已经完成订单
     */
    public function workResult()
    {
        $workerId = $this ->request -> param('ids');
        //获取未完成列表
        $finishList = $this -> model -> getWorkerFinishList($workerId);
        $this->view->assign('finishList',$finishList);
        return $this->view->fetch('workResult');
    }

}
