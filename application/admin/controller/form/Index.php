<?php

namespace app\admin\controller\form;
use think\Db;
use think\Config;
use app\common\controller\Backend;
use app\admin\model\form\Form as FormModel;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{
    
    /**
     * 
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new FormModel();
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
        $formId = empty($this->request->get("form")) ? "1" : $this->request->get("form");
        if ($this->request->isAjax())
        {
            $formId = $this->request->get("form");
            $configId = $this->request->get("config");
            // dump($configId);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->where($where)
                    ->with(["getstuname" => function($query){$query->withField('XM');},])
                    ->order($sort, $order)
                    ->group("config_id,user_id")
                    ->where("form_id",$formId)
                    // ->where("config_id",$configId)
                    ->count();

            $list = $this->model
                    ->where($where)
                    ->with(["getstuname" => function($query){$query->withField('XM');},])
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->group("config_id,user_id")
                    ->where("form_id",$formId)
                    // ->where("config_id",$configId)
                    ->select();
            $return = [];
            foreach ($list as $key => $value) {
                $temp = [];
                $temp["user_id"] = $value["user_id"];
                $temp["config_id"] = $value["config_id"];
                $temp["XM"] = $value["getstuname"]["XM"];
                $resultList = $this->model
                        ->where("user_id",$value["user_id"])
                        ->where("config_id",$value["config_id"])
                        ->select();
                foreach ($resultList as $k => $v) {
                    $temp[$v["title"]] = $v["value"];
                }
                $return[] = $temp;
            }
            $return = collection($return)->toArray();
            $result = array("total" => $total, "rows" => $return);

            return json($result);
        }

        $questionnaireList = $this->getQuestionnaire();
        $this->view->assign(["questionnaireList" => $questionnaireList]);
        $this->view->assign(["formId" => $formId]);
        return $this->view->fetch();
    }

    /**
     * 获取可查看的问卷
     */
    public function getQuestionnaire()
    {
        $list = Db::name("form")->select();
        $return = [];
        foreach ($list as $key => $value) {
            $temp = [
                "title" => $value["title"],
                "id"    => $value["ID"],
            ];
            $return[] = $temp;
        }
        return json($return);
    }
   
    /**
     * 获取column 数据
     */
    public function getColumn()
    {
        $formId = $this->request->get("form");
        if(!empty($formId)){
            $titleList = $this->model->getColumn($formId);
            return json($titleList);
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
