<?php

namespace app\admin\controller\sports;
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
        $this->model = model('Sports');
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
                    ->with('geteventname,getcollege')
                    ->where($where)
                    //->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with('geteventname,getcollege')
                    ->where($where)
                    //->order($sort, $order)
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
            $params = $this->request->post();
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
                $result = $this->model->addScoreDetail($params);
                if ($result['status'] !== false) {
                    $this->success($result['msg']);
                } else {
                    $this->error($result['msg']);
                }
            }
               
            
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
     /**
     * 一次添加多个
     */
    public function addmulti()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post();
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
                //dump($params);
                $result = $this->model->addScoreDetailMulti($params);
                if ($result['status'] !== false) {
                    $this->success($result['msg']);
                } else {
                    $this->error($result['msg']);
                }
            }
               
            
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $arrayKey = [0,1,2,3,4,5,6,7];
        $this->view->assign(['arrayKey' => $arrayKey]);
        return $this->view->fetch();
    }
     /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = Db::view('sports_score_detail')
                -> view('sports_type','id,type_id','sports_score_detail.event_id = sports_type.id')
                -> where('sports_score_detail.id',$ids)
                -> find();
        $row['detail_id'] = $ids;
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
            $params = $this->request->post();
            if ($params)
            {

                //是否采用模型验证
                if ($this->modelValidate)
                {
                    $name = basename(str_replace('\\', '/', get_class($this->model)));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                    $row->validate($validate);
                }
                
                $result = $this->model -> editDetail($params);
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
    /**
     * 获取院系json,添加项目时调用
     */
    public function getCollege()
    {
        $collegeList = array();
        $collegeInfo = Db::name('dict_college') 
                -> where('yb_group_id','<>','') 
                -> where('YXDM','<>','2101') 
                -> where('YXDM','<>','1400') 
                -> where('YXDM','<>','1500') 
                -> where('YXDM','<>','7100') 
                -> where('YXDM','<>','9999') 
                -> select();
        foreach ($collegeInfo as $key => $value) {
            $tempArray = array();
            $tempArray['value'] = $value['YXDM'];
            $tempArray['name'] = $value['YXJC'];
            $collegeList[] = $tempArray;
        }
        $this->success('', null, $collegeList);
    }
    /**
     * 获取项目类型名称多级联动
     */
    public function getType()
    {
        $typeList = array();
        $type = Db::name('sports_type') -> group('type_id') -> select();
        foreach ($type as $key => $value) {
            $tempArray = array();
            $tempArray['value'] = $value['type_id'];
            $tempArray['name'] = $value['type_name'];
            $typeList[] = $tempArray;
        }
        $this->success('', null, $typeList);
    }
    /**
     * 获取项目名称
     */
    public function getEvent()
    {
        $type_id = $this->request->get('type');
        $eventList = array();

        $event = Db::name('sports_type') -> where('type_id',$type_id) -> select();
        foreach ($event as $key => $value) {
            $tempArray = array();
            $tempArray['value'] = $value['id'];
            $tempArray['name'] = $value['event_name'];
            $eventList[] = $tempArray;
        }
        
        $this->success('', null, $eventList);
    
    }

}
