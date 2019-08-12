<?php

namespace app\admin\controller\adviser;
use think\Db;
use think\Config;
use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{
    
    /**
     * Dormitorylist模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Adviser');
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
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill)
                {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                //是否采用模型验证
                if ($this->modelValidate)
                {
                    $name = basename(str_replace('\\', '/', get_class($this->model)));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                    $this->model->validate($validate);
                }
                $result = $this->model->insertAdviser($params,$this->auth->id);
                if ($result['status'] !== false)
                {
                    $this->success($result['msg']);
                }
                else
                {
                    $this->error($result['msg']);
                }
            }             
            
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //获取班主任信息
        $GH = Db::name('admin') -> where('id',$this->auth->id) -> field('username') ->find()['username'];
        $infoList = Db::name('bzr_adviser') -> where('GH',$GH) -> find();
        if (empty($infoList['timestamp'])) {
            $this->view->assign([
                'adviser' => [
                    'XM' => $infoList['XM'],
                    'GH' => $infoList['GH'],
                    'question' => [],
                ]
            ]);
            
        } else {      
            $this->view->assign([
                'adviser' => [
                    'XM' => $infoList['XM'],
                    'GH' => $infoList['GH'],
                    'question' => [
                        'BHCS' => $infoList['BHCS'],
                        'HDCS' => $infoList['HDCS'],
                        'FDXS' => $infoList['FDXS'],
                    ]
                ]
            ]);
        }

        return $this->view->fetch();
    }
}
