<?php

namespace app\admin\controller\bx;
use think\Db;

use app\common\controller\Backend;

/**
 * 
 * 工单结算
 * @icon fa fa-circle-o
 */
class Repairsettlement extends Backend
{
    
    /**
     * 工单结算
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('RepairSettlement');

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
                    ->with('getcompanyname,getrepairname')
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with('getcompanyname,getrepairname')
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
     * 获取公司名称
     */
    public function getCompany()
    {
        // $companyList = array();
        // $com_id = Db::name('auth_group') -> where('name','报修单位') -> field('id') -> find()['id'];
        // //获取公司名称
        // $company = Db::view('auth_group_access') 
        //             -> view('admin','nickname,id','auth_group_access.uid = admin.id')
        //             -> where("group_id = $com_id") 
        //             -> select();
        // foreach ($company as $key => $value) {
        //     $tempArray = array();
        //     $tempArray['value'] = $value['uid'];
        //     $tempArray['name'] = $value['nickname'];
        //     $companyList[] = $tempArray;
        // }
        $companyList = [
            [
                'value' => '1',
                'name'  => '动力',
            ],
            [
                'value' => '2',
                'name'  => '修建',
            ]
        ];
        $this->success('', null, $companyList);
    }
    /**
     * 获取维修内容名称电工水工等
     */
    public function getContent()
    {
        $contentList = array();
      
        //获取公司名称
        $content = Db::name('repair_type') 
                    -> group('id')
                    -> select();
        foreach ($content as $key => $value) {
            $tempArray = array();
            $tempArray['value'] = $value['id'];
            $tempArray['name'] = $value['name'];
            $contentList[] = $tempArray;
        }
        $this->success('', null, $contentList);
    }

}
