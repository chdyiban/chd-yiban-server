<?php

namespace app\admin\controller\conversationrecord;
use think\Db;
use think\Config;
use app\common\controller\Backend;
use app\admin\model\record\RecordContent as RecordContentModel;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Count extends Backend
{
    
    /**
     * 谈话概况统计
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RecordContentModel();
        $this->relationSearch = TRUE;
    }
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
            $adminId = $this->auth->id;
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            $getParam = $this->request->param();
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with("getcontent")
                    -> where("admin_id",$adminId)
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with("getcontent")
                    -> where("admin_id",$adminId)
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        // $this->request->filter(['strip_tags']);
        // if ($this->request->isAjax())
        // {
        //     //如果发送的来源是Selectpage，则转发到Selectpage
        //     if ($this->request->request('pkey_name'))
        //     {
        //         return $this->selectpage();
        //     }
        //     list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
        //     $info = $this -> model -> getTableData($adminId,$offset,$limit);
        //     $total = $info['count'];
        //     $data = $info['data'];
        //     //遍历进行分页
        //     // $list = array();
        //     // foreach ($data as $key => $value) {
        //     //     if ($key >=  $offset && $key < ($offset + $limit) ) {
        //     //         $list[] = $value;
        //     //     }
        //     // } 
        //     $result = array("total" => $total, "rows" => $data);
        //     return json($result);

        // }
        //获取累积谈话次数，累积谈话学生，本月谈话次数，本学谈话学生数量
        $countParam = $this->model->getCountParam($adminId);
        $this->view->assign([
            "countInfo" => $countParam,
        ]);
        return $this->view->fetch();
    }

    /**
     * 获取图表信息
     * @return  { "label":[5.1,5.2...],"stuCount":[1,0,1,0],"numCount":[1,1,0,1]}
     */
    public function getChartData()
    {
        if ($this->request->isAjax()){
           //设置过滤方法
            $adminId = $this->auth->id;
            $type = $this->request->get("type");
            if ($type == "count") {
                $info = $this->model->getChartData($adminId);
            } else if ($type == "tags") {
                $info = $this->model->getChartTagsData($adminId);
            } else if ($type == "class"){
                $info = $this->model->getChartClassData($adminId);
            }
            return json($info);
        } else {
            $this -> error("请求错误");
        }
    }


}
