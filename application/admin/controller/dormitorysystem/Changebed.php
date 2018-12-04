<?php

namespace app\admin\controller\dormitorysystem;
use think\Db;
use app\common\controller\Backend;

/**
 * 换宿界面
 */
class Changebed extends Backend
{
    
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
     * 首页
     */


    public function index()
    {
       //获取当前管理员id的方法
       $now_admin_id = $this->auth->id;
       //设置过滤方法
       $this->request->filter(['strip_tags']);
       if ($this->request->isAjax())
       {
           //如果发送的来源是Selectpage，则转发到Selectpage
           if ($this->request->request('pkey_name'))
           {
               return $this->selectpage();
           }
       } else {

           $param = $this->request->param();
           $this->view->assign([
               'param' => $param,
           ]);
       }

       return $this->view->fetch();
    } 


    /**
     * 搜索界面
     */
    public function search()
    {
        //获取当前管理员id的方法
        $now_admin_id = $this->auth->id;
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
            $filter = json_decode($this->request->get('filter'),true);
            if (empty($filter['option'])) {
                $result = ['total' => 0, 'rows' => []];
                return 0;
            } else {
                //判断筛选条件是姓名还是学号
                $option = $filter['option'];
                if(is_numeric($option)){
                    $result = $this->model->searchStuByXh($option);
                } else {
                    $result = $this->model->searchStuByName($option);
                }
                //遍历进行分页
                $list = array();
                foreach ($result['rows'] as $key => $value) {
                    if ($key >=  $offset && $key < ($offset + $limit) ) {
                        $list[] = $value;
                    }
                } 
                $return = array('total' => $result['total'],'rows'=>$list);
                return json($return);
            }

        } else {
            $param = $this->request->param();
            $this->view->assign([
                'param' => $param,
            ]);
        }
         return $this->view->fetch(); 
    }

}
