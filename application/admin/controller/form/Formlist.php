<?php

namespace app\admin\controller\form;
use think\Db;
use think\Config;
use app\common\controller\Backend;
use app\admin\model\form\Formlist as FormlistModel;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Formlist extends Backend
{
    
    /**
     * 
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new FormlistModel();
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
            $total = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

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
                    $params["create_time"] = time();
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
     * 获取可配置问卷title
     */
    public function getForm()
    {
        $list = Db::name("form")->select();
        $result = [];
        foreach ($list as $key => $value) {
            $temp = [
                "value" => $value["ID"],
                "name"  => $value["title"],
            ];
            $result[] = $temp;
        }
        $this->success('', null, $result);
    }
    /**
     * 获取可配置的学院
     */
    public function college()
    {
        $info = array();
        $data = Db::name('dict_college') -> field('YXDM, YXJC') -> select();
        foreach ($data as $key => $value) {
            if ($value['YXDM'] == 9999 || $value['YXDM'] == 5100 || $value['YXDM'] == 1800 || $value['YXDM'] == 1801 || $value['YXDM'] == 1700) {
                unset($data[$key]);
            } else {
                $temp = [
                    "value" => $value["YXDM"],
                    "name"  => $value["YXJC"],
                ];
                $info[] = $temp;
            }
        }
        // $total = count($info);
        // $result = array("total" => $total, "rows" => $info);
        // return json($result);
        $this->success('', null, $info);
    }


}
