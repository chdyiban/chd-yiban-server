<?php

namespace app\admin\controller\form;
use app\admin\model\form\FormQuestionnaireList as FormQuestionnaireListModel;


use app\common\controller\Backend;

/**
 * 表单详情管理
 *
 * @icon fa fa-circle-o
 */
class Questionnairelist extends Backend
{
    
    /**
     * FormQuestionnaire模型对象
     * @var \app\admin\model\FormQuestionnaire
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new FormQuestionnaireListModel();
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("extraList", $this->model->getExtraList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("mustList", $this->model->getMustList());
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
        $formId = $this->request->get("form");


        if ($this->request->isAjax())
        {
            $formId = $this->request->get("form");
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->where($where)
                    ->where("form_id",$formId)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->where($where)
                    ->where("form_id",$formId)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        $this->view->assign(["formId" => $formId]);
        return $this->view->fetch();
    }

}
