<?php

namespace app\admin\controller\dormitorysystem;
use think\Db;
use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Dormitory extends Backend
{
    
    /**
     * Dormitory模型对象
     */
    protected $model = null;
    protected $relationSearch = true;
    protected $searchFields = 'college.YXJC,studetail.BJDM,studetail.XM';


    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Dormitory');
    }
    // public function index()
    // {
       
    // }
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
        $this->relationSearch = true;
        $this->searchFields = "getcollege.YXJC,studetail.BJDM,studetail.XM";
        
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
                    ->with('getcollege,getstuname')
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with('getcollege,getstuname')
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
     * 查看学生详细信息
     */
    public function getStuInfo()
    {
        $stuid = $this->request->get('stuid');
        $id = $this->request->get('ids');
        $stuInfoList =  Db::view('stu_detail')
                    -> view('dict_college','YXDM,YXJC','stu_detail.YXDM = dict_college.YXDM')
                    -> view('dict_nation','MZDM,MZMC','stu_detail.MZDM = dict_nation.MZDM')
                    -> where('XH',$stuid)
                    -> find();

        return view('stuinfo',[
            'stuInfoList' => $stuInfoList,
        ]);
        //return $stuInfo;
    }
    /**
     * 获取院系json,用于在js用searchlist调用
     */
    public function getCollegeJson()
    {
        if ($this->request->isAjax()){
            $result = $this->model -> getCollegeJson();
            return json($result);
        } else {
            return [];
        }
    }
}
