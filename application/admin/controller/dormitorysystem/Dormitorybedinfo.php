<?php

namespace app\admin\controller\dormitorysystem;
use think\Db;
use app\common\controller\Backend;

/**
 * 此表查看床位入住学生信息
 * @icon fa fa-circle-o
 */
class Dormitorybedinfo extends Backend
{
    
    /**
     * 展示已经安排床位学生信息
     * Dormitory模型对象
     */
    protected $model = null;
    // protected $relationSearch = true;
    // protected $searchFields = '';


    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Dormitorybeds');
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

            $total = $this->model
                    ->with('getstuname,getrooms,getcollege')
                    ->where($where)
                    //->group('LH,SSH')
                    //->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with('getstuname,getrooms,getcollege')
                    ->where($where)
                    //->group('LH,SSH')
                    //->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);           
            return json($result);
        }
         return $this->view->fetch(); 
    }

}
