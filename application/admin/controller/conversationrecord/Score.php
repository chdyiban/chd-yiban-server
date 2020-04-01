<?php

namespace app\admin\controller\conversationrecord;

use app\common\controller\Backend;
use think\Db;
use think\Cache;
use app\api\controller\Bigdata as BigdataController;
use app\admin\model\record\RecordContent as RecordContentModel;

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
            $XH = $this->request->param("XH");
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name("stu_score")
                    ->where("XH",$XH)
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = Db::name("stu_score")
                    ->where("XH",$XH)
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        } else {

            $XH = $this->request->param("XH");

            //查询数据库判断是否无成绩或者更新时间超过一天
            $userScore = Db::name("stu_score")->where("XH",$XH)->find();
            if (empty($userScore) || ( time()-$userScore["update_time"] ) >= 3600*24 ) {
                $score = new BigdataController;
                $access_token = $score->getAccessToken();
                $access_token = $access_token["access_token"];
                $params = ["access_token" => $access_token,"XH" => $XH];
                $userScore = array_reverse($score->getScore($params));
                if (!empty($userScore)) {
                    $res = Db::name("stu_score")->where("XH",$XH)->delete();
                    $returnResult = [];
                    foreach ($userScore as $key => $value) {
                        foreach ($value["list"] as $k => $v) {
                            $returnResult[] = [
                                "XH"    =>  $v["XH"],
                                "XN"    =>  $v["XN"],
                                "XQ"    =>  $v["XQ"],
                                "XNXQ"  =>  $v["XN"]." ".$v["XQ"],
                                "KCH"   =>  $v["KCH"],
                                "KXH"   =>  $v["KXH"],
                                "KCM"   =>  $v["KCM"],
                                "XF"    =>  $v["XF"],
                                "FSLKSCJ"   =>  $v["FSLKSCJ"],
                                "DJLKSCJ"   =>  $v["DJLKSCJ"],
                                "SFTG"  =>  $v["SFTG"],
                                "KCSX"  =>  $v["KCSX"],
                                "JD"    =>  $v["JD"],
                                "update_time"    =>  time(),
                            ];
                        }
                    }
                    $result = Db::name("stu_score")->insertAll($returnResult);
                }
            } 


            $params = $this->request->param();
            $result = $this->model->getStuInfo($params["ids"]);
            $stuInfo = $result["stuInfo"];
            $stuExtraInfo = $result["stuExtraInfo"];
            //获取挂科总数
            $allList = Db::name("stu_score")->where("XH",$XH)->select();
            $GkCount = 0;
            foreach ($allList as $key => $value) {
                if ($value["SFTG"] == "否" ) {
                    $GkCount++;
                }
            }
            $allCount = count($allList);
            // $familyInfo = $result["familyInfo"];
            $this->view->assign(["params" => $params]);
            $this->view->assign(["stuInfo" => $stuInfo,"stuExtraInfo" => $stuExtraInfo, "GkCount" => $GkCount,"allCount" =>$allCount ]);
            $this->view->assign(["time" => date("Y-m-d",time())]);
            // $this->view->assign(["familyInfo" => $familyInfo]);
            return $this->view->fetch();
        }
    }

}
