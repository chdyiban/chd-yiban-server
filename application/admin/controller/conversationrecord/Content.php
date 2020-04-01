<?php

namespace app\admin\controller\conversationrecord;

use app\common\controller\Backend;
use think\Db;
use app\admin\model\record\RecordContent as RecordContentModel;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Content extends Backend
{
    
    /**
     * RecordContent模型对象
     * @var \app\admin\model\RecordContent
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RecordContentModel();

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
            $getParam = $this->request->param();
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
        } else {
            $params = $this->request->param();
            $result = $this->model->getStuInfo($params["ID"]);
            $stuInfo = $result["stuInfo"];
            $familyInfo = $result["familyInfo"];
            $this->view->assign(["params" => $params]);
            $this->view->assign(["stuInfo" => $stuInfo]);
            $this->view->assign(["familyInfo" => $familyInfo]);
            return $this->view->fetch();
        }
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
                    $result = $this->model->insertContent($params,$this->auth->id);
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
        $params = $this->request->param();
        $this->view->assign(["params" => $params]);
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
                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    //将时间转为时间戳
                    $params['THSJ'] = strtotime($params['THSJ']);
                    $result = $row->allowField(true)->save($params);
                    $XSID = $this->model->get($ids)["XSID"];
                    $resulUpdate = $this->model->UpdateLatestTime($XSID);
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
            $XSID = $list[0]["XSID"];
            foreach ($list as $k => $v)
            {
                Db::startTrans();
                try{
                    //学生谈话次数减一
                    $result = Db::name("record_stuinfo") -> where('ID',$v['XSID']) -> setDec("THCS");
                    $count += $v->delete();
                    Db::commit();  
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
            $resulUpdate = $this->model->UpdateLatestTime($XSID);
            if ($count && $result && $resulUpdate)
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

}
