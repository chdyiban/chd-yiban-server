<?php

namespace app\admin\controller\conversationrecord;
use think\Db;
use think\Config;
use app\common\controller\Backend;

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
        $this->model = model('RecordContent');
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
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $info = $this -> model -> getTableData($adminId);
            $total = $info['count'];
            $data = $info['data'];
            //遍历进行分页
            // $list = array();
            // foreach ($data as $key => $value) {
            //     if ($key >=  $offset && $key < ($offset + $limit) ) {
            //         $list[] = $value;
            //     }
            // } 
            $result = array("total" => $total, "rows" => $data);
            return json($result);

        }
        //获取累积谈话次数，累积谈话学生，本月谈话次数，本学谈话学生数量
        $countParam = $this->model->getCountParam($adminId);
        $this->view->assign([
            "countInfo" => $countParam,
        ]);
        return $this->view->fetch();
    }

}
