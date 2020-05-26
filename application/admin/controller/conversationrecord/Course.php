<?php

namespace app\admin\controller\conversationrecord;

use app\common\controller\Backend;
use app\admin\model\record\RecordCourse as RecordCourseModel;

/**
 * 标签管理
 *
 * @icon fa fa-circle-o
 */
class Course extends Backend
{
    
    /**
     * RecordCourse模型对象
     * @var \app\admin\model\RecordCourse
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RecordCourseModel();

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

}
