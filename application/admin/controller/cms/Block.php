<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;

/**
 * 区块表
 *
 * @icon fa fa-circle-o
 */
class Block extends Backend
{

    /**
     * Block模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Block');
        $this->view->assign("statusList", $this->model->getStatusList());
    }

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

    public function import()
    {
        return parent::import();
    }

}
