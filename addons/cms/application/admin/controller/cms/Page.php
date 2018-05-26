<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;

/**
 * 单页表
 *
 * @icon fa fa-circle-o
 */
class Page extends Backend
{

    /**
     * Page模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['selectpage_type'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Page');
        $this->view->assign("flagList", $this->model->getFlagList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 类型
     * @internal
     */
    public function selectpage_type()
    {
        $response = parent::selectpage();
        $word = (array)$this->request->request("q_word/a");
        if (array_filter($word)) {
            $field = $this->request->request('showField');
            $result = $response->getData();
            foreach ($word as $k => $v) {
                array_unshift($result['list'], ['id' => $v, $field => $v]);
                $result['total']++;
            }
            $response->data($result);
        }
        return $response;
    }

}
