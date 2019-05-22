<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use think\Db;
use fast\Http;
use think\cache;


class Adviser extends Frontend
{
    // protected $noNeedLogin = ['detail','getAccessToken'];
    // protected $noNeedRight = ['detail','getAccessToken'];
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    const DOMAIN = "https://yiban.chd.edu.cn";

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Repairlist');
        //获取总控的id
        $this -> control_id = Db::view('auth_group') 
                -> view('auth_group_access','uid,group_id','auth_group.id = auth_group_access.group_id')
                -> where('name','报修管理员') 
                -> find()['uid'];
    }
    /**
     * 获取学院信息
     */
    public function index()
    {
        return $this->success("请求成功");
        /* 
        $njArray = ["2018","2017","2016","2015"];
        $info = Db::name("dict_college")
                ->where("YXDM","<>","1500")
                ->where("YXDM","<>","1700")
                ->where("YXDM","<>","1800")
                ->where("YXDM","<>","1801")
                ->where("YXDM","<>","2101")
                ->where("YXDM","<>","5100")
                ->where("YXDM","<>","7100")
                ->where("YXDM","<>","9999")
                ->select();
        foreach ($info as $key => $value) {
            $YXDM = $value["YXDM"];
            if ($YXDM != "4100") {
                $stuAllCount = Db::name("stu_detail") 
                        -> where("YXDM",$YXDM)
                        -> where('XH',['like','2015%'],['like','2016%'],['like','2017%'],['like','2018%'],['like','2014%'],'or')
                        -> where("XSLBDM",3)
                        -> count();
            } else {
                $stuAllCount = Db::name("stu_detail") 
                        -> where("YXDM",$YXDM)
                        -> where('XH',['like','2015%'],['like','2016%'],['like','2017%'],['like','2018%'],'or')
                        -> where("XSLBDM",3)
                        -> count();
            }
            $finishedStuCount = Db::view("bzr_result")
                        -> view("stu_detail","YXDM,XH","bzr_result.stu_id = stu_detail.XH")
                        -> where("stu_detail.YXDM",$YXDM)
                        -> count();
            $rate = round($finishedStuCount/$stuAllCount,4)*100;
            $info[$key]["rate"] = $rate;
        }
        $arr = array_column($info,'rate');
        array_multisort($arr, SORT_DESC, $info );
        $this->view->assign(["info" => $info]);
        return $this->view->fetch();
        */
    }
    /**
     * 获取详细统计信息
     */
    /*
    public function detail()
    {
        $info = $this->request->param();
        $YXDM = $info["college"];
        if (empty($YXDM)) {
            $this->error("param error!");
        } else {
            if ($YXDM == "4100") {
                $njArray = ["2018","2017","2016","2015","2014"];
            } else if ($YXDM == "0001") {
                $njArray = ["2018"];
            }else {
                $njArray = ["2018","2017","2016","2015"];
            }
            $result = array();
            $result["YXJC"] = $info["name"];
            $result["YXDM"] = $YXDM;
            $result["count"] = array();
            foreach ($njArray as $value) {
                $stuAllCount = Db::name("stu_detail") 
                            -> where("YXDM",$YXDM)
                            -> where("XH","LIKE","$value%")
                            -> where("XSLBDM",3)
                            -> count();

                $finishedStuCount = Db::view("bzr_result")
                                    -> view("stu_detail","YXDM,XH","bzr_result.stu_id = stu_detail.XH")
                                    -> where("stu_id","LIKE","$value%")
                                    -> where("stu_detail.YXDM",$YXDM)
                                    -> count();
                $rate = round($finishedStuCount/$stuAllCount,4)*100;
                $temp = [
                    "NJ"  => $value,
                    "YXDM" => $YXDM,
                    "stuAllCount" => $stuAllCount,
                    "finishedStuCount" => $finishedStuCount,
                    "finishedRate"   => "$rate%",
                ];
                $result["count"][] = $temp;
            }
            $this->view->assign(["info" => $result]);
            return $this->view->fetch();
        }
    }
    */
    /**
     * 获取学院某年级未完成人员名单
     */
    /*
    public function student()
    {
        $info = $this->request->param();
        $YXDM = $info["college"];
        $NJ = $info["nj"];
        $YXJC = $info["name"];
        if (empty($YXDM) || empty($NJ)) {
            $this->error("param error!");
        } else {
            // $this->success('请求成功');
            $stuAllList = Db::name("stu_detail") 
                    -> where("YXDM",$YXDM)
                    -> where('XH','like',"$NJ%")
                    -> order("BJDM")
                    -> where("XSLBDM",3)
                    -> select();
            $infoShow = array(
                "YXJC" => $YXJC,
                "YXDM" => $YXDM,
                "NJ" => $NJ,
            );
            foreach ($stuAllList as $key => $value) {
                $info = Db::name("bzr_result")->where("stu_id",$value["XH"])->find();
                if (empty($info)) {
                    $temp = [
                        "XH" => $value["XH"],
                        "BJ" => empty($value["BJDM"])?"":$value["BJDM"],
                        "YXDM" => $YXDM,
                        "NJ" => $NJ,
                        "XM" => $value["XM"],        
                    ];
                    $infoShow["student"][] = $temp;
                }
            }
            $this->view->assign(["info"=>$infoShow]);
            return $this->view->fetch();
        }
        
    }
    */
}
