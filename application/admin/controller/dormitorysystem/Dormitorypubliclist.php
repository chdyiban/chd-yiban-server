<?php

namespace app\admin\controller\dormitorysystem;
use think\Db;
use app\common\controller\Backend;

/**
 * 此表查看以宿舍为单位信息
 * @icon fa fa-circle-o
 */
class Dormitorypubliclist extends Backend
{
    
    /**
     * Dormitorypubliclist模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Dormitory');
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
        //获取当前管理员id的方法
        $now_admin_id = $this->auth->id;
        //设置过滤方法
        // $this->relationSearch = true;
        // $this->searchFields = "getcollege.YXJC,studetail.BJDM,studetail.XM";
        
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        
            //dump(json_decode($this->request->param()['filter'],true));
            //dump($where);
            $total = $this->model
                    // ->with('getcollege,getstuname')
                    ->where($where)
                    -> where('status','<>','1')
                    //->group('LH,SSH')
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    // ->with('getcollege,getstuname')
                    ->where($where)
                    //->group('LH,SSH')
                    ->where('status','<>','1')
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

                //是否采用模型验证
                if ($this->modelValidate)
                {
                    $name = basename(str_replace('\\', '/', get_class($this->model)));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                    $this->model->validate($validate);
                }
                $result = $this->model->addRoom($params,$this->auth->id);
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
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds))
        {
            if (!in_array($row[$this->dataLimitField], $adminIds))
            {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {

                //是否采用模型验证
                if ($this->modelValidate)
                {
                    $name = basename(str_replace('\\', '/', get_class($this->model)));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                    $row->validate($validate);
                }
                $result = $this->model -> editRoom($params,$ids,$this->auth->id);
                //$result = $row->allowField(true)->save($params);
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function delete($ids = "")
    {
        if ($ids)
        {
           $result = $this -> model -> deleteRoom($ids,$this->auth->id);
           if ($result['status']) {
               $this -> success($result['msg']);
           } else {
               $this -> error($result['msg']);
           }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}