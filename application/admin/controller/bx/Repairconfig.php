<?php

namespace app\admin\controller\bx;
use think\Db;
use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Repairconfig extends Backend
{
    
    /**
     * Repairconfig
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('RepairConfig');

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
        $row = $this->model->get(1);
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill)
                {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try{
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($row)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result =  $row->allowField(true)->save($params);
                    if ($result !== false)
                    {
                        $this->success("配置修改成功");
                    }
                    else
                    {
                        $this->error( $row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        } else {
            $param = Db::name('repair_config') -> where('ID','1') -> find();
            $this ->view -> assign([
                'configinfo' => $param, 
            ]);
            return $this->view->fetch();
        }
    }
    

}
