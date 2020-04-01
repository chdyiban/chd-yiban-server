<?php

namespace app\admin\controller\conversationrecord;

use app\common\controller\Backend;
use app\admin\model\record\RecordStuinfo as RecordStuinfoModel;
use fast\Tree;
use app\admin\model\Channel;
use think\Db;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{
    
    /**
     * RecordStuinfo模型对象
     * @var \app\admin\model\RecordStuinfo
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RecordStuinfoModel();
         //获取总控的id
        $this -> control_id = Db::view('auth_group') 
                        -> view('auth_group_access','uid,group_id','auth_group.id = auth_group_access.group_id')
                        -> where('name','导员管理组') 
                        -> find()['uid'];
        $this->view->assign("gzlxList", $this->model->getGzlxList());
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
        $adminId = $this->auth->id;

        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if ($adminId != 1 && $adminId != $this -> control_id) {
                $total = $this->model
                        ->where($where)
                        ->where("admin_id",$adminId)
                        ->order($sort, $order)
                        ->count();

                $list = $this->model
                        ->where($where)
                        -> where("admin_id",$adminId)
                        ->order($sort, $order)
                        ->limit($offset, $limit)
                        ->select();
            } else {
                $total = $this->model
                        ->where($where)
                        ->order($sort, $order)
                        ->count();

                $list = $this->model
                        ->where($where)
                        ->order($sort, $order)
                        ->limit($offset, $limit)
                        ->select();
            }

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
                    // $result = $this->model->allowField(true)->save($params);
                    $result = $this->model->insertInfo($params,$this->auth->id);
                    if ($result !== false)
                    {
                        $this->success();
                    }
                    else
                    {
                        // $this->error($this->model->getError());
                        $this->error("学生数据已经存在，请勿重复添加");
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    // $this->error($e->getMessage());
                    $this->error("学生数据已经存在");
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        } else {
            return $this->view->fetch();
        }
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
                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    //将个人评价以及课程标签进行更新
                    $this->model->updatePersonnalFlag($params["tags"],$ids);
                    $this->model->updateCourseFlag($params["CXKC"],$ids);
                    $params["JDSJ"] = strtotime($params["JDSJ"]);
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false)
                    {
                        $this->success();
                    }
                    else
                    {
                        $this->error($row->getError());
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
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
    public function del($ids = "")
    {
        if ($ids)
        {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds))
            {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            foreach ($list as $k => $v)
            {
                Db::startTrans();
                try{
                    if ($v['THCS'] != 0) {
                        $result = Db::name("record_content") -> where('XSID',$v["ID"]) -> delete();
                    }
                        $count += $v->delete();
                    Db::commit();  
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
            if ($count && $result)
            {
                $this->success();
            }
            else
            {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 添加
     */
    public function share()
    {
        if ($this->request->isAjax())
        {
            $params = $this->request->post("row/a");
            if ( !empty($params) || !empty($params["admin"]) || !empty($params["stu_ids"]) )
            {
                $params["admin_id"] = $this->auth->id;
                $result = $this->model->insertShareInfo($params);
                if ($result["status"] !== false)
                {
                    $this->success($result["msg"]);
                }
                    $this->error($result["msg"]);
            }
            $this->error("分配人以及学生不可为空！");
        } else {
            $stu_ids = $this->request->param("stu_ids");
            $this->view->assign(["stu_ids" => $stu_ids]);
            return $this->view->fetch();
        }
    }

     /**
     * 查找学生信息通过学号
     * @param XH
     */
    public function searchStuByXh()
    {
        if ($this->request->isAjax()) {
            $XH = $this->request->param('XH');
            $stuInfo = $this->model->searchStuByXh($XH);
            return json($stuInfo);
        } else {
            $this->error('请求错误');
        }
    }

    /**
     * 查找学生信息通过姓名
     * @param name
     */
    public function searchStuByName()
    {
        if ($this->request->isAjax()) {
            $name = $this->request->post('name');
            $stuInfo = $this->model->searchStuByName($name);
            return json($stuInfo);
        } else {
            $this->error('请求错误');
        }
    }
    /**
     * 获取学生宿舍号
     */
    public function getSSDM()
    {
        if ($this->request->isAjax()) {
            $XH = $this->request->post('XH');
            $dormitoryInfo = $this->model->getSSDM($XH);
            $result = ["status"=>true,"data" => ["dormitory"=>$dormitoryInfo]];
            return json($result);
        } else {
            $this->error('请求错误');
        }
    }
    /**
     * 判断学生在数据库中是否已经添加
     */
    public function getStatus()
    {
        if ($this->request->isAjax()) {
            $info = $this->request->post();
            if (!empty($info["row"]["info"])) {
                $info = $info["row"]["info"];
                $XH = explode("-",$info)[0];
            } else {
                $XH = $info["XH"];
            }
            $listInfo = Db::name("record_stuinfo")->where("XH",$XH) -> find();
            if (!empty($listInfo)) {
                $result = ["status"=>500, "data"=> ["error"=> "学生已经录入"]];
            } else {
                $result = ["status"=>200, "data"=> ["ok"=> ""]];
            }

            return json($result);
        } else {
            $this->error('请求错误');
        }
    }
    //获取可以共享的管理员名称
    public function getAdminName()
    {
        $group_id_array = Db::name('auth_group')
            ->where('pid|id',"IN",function($query){
                $query->name('auth_group')->where('name',"教工组")->field('id');
            })
            ->field("id")
            ->select();
        $temp_array = [];
        foreach ($group_id_array as $key => $value) {
            $temp_array[] = $value["id"];
        }
        // array_unique($temp_array); //去除重复ID

        $result = Db::view("admin","nickname,id,username")
                -> view("auth_group_access","uid,group_id","admin.id = auth_group_access.uid")
                -> where("group_id","IN",$temp_array)
                -> group("id")
                -> select();
        $return = ["list" => [],"total" => 0];
        foreach ($result as $key => $value) {
            $value["name"] = $value["nickname"];
            $return["list"][] = $value;
            $return["total"]++;
        }
        return json($return);
    }

}