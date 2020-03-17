<?php

namespace app\admin\controller\police;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Category extends Backend
{
    
    /**
     * PoliceCategory模型对象
     * @var \app\admin\model\PoliceCategory
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('PoliceCategory');
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    public function selectpage()
    {
        $response = parent::selectpage();
        $word = (array)$this->request->request("q_word/a");
        if (array_filter($word)) {
            $result = $response->getData();
            foreach ($word as $k => $v) {
                array_unshift($result['list'], ['id' => $v, 'name' => $v]);
                $result['total']++;
            }
            $response->data($result);
        }
        return $response;
    }

    public function getCategoryJson()
    {
        $result = Db::name("police_category")->select();
        $returnResult = [];
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $returnResult[$value["name"]] =  $value["name"];
            }
        }
        return json($returnResult);
    }
}
