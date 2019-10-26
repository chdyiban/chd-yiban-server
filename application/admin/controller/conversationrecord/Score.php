<?php

namespace app\admin\controller\conversationrecord;

use app\common\controller\Backend;
use think\Db;
use think\Cache;
use app\api\controller\Bigdata as BigdataController;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Score extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('RecordContent');

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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $XH = $this->request->param("XH");
            $userScore = Cache("score_$XH");
            if ($userScore == false) {
                $score = new BigdataController;
                $access_token = $score->getAccessToken();
                $access_token = $access_token["access_token"];
                $params = ["access_token" => $access_token,"XH" => $XH];
                $userScore = array_reverse($score->getScore($params));
                Cache::set("score_$XH",$userScore,3600*24);
            } 
            $returnResult = [];
            foreach ($userScore as $key => $value) {
                foreach ($value["list"] as $k => $v) {
                    $returnResult[] = $v;
                }
            }
            $list = array();
            foreach ($returnResult as $key => $value) {
                if ($key >=  $offset && $key < ($offset + $limit) ) {
                    $list[] = $value;
                }
            } 

            $total = count($returnResult);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        } else {

            $params = $this->request->param();
            // $result = $this->model->getStuInfo($params["ID"]);
            // $stuInfo = $result["stuInfo"];
            // $familyInfo = $result["familyInfo"];
            $this->view->assign(["params" => $params]);
            // $this->view->assign(["stuInfo" => $stuInfo]);
            // $this->view->assign(["familyInfo" => $familyInfo]);
            return $this->view->fetch();
        }
    }

}
