<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use think\Db;
use fast\Http;
use think\cache;


class Form extends Frontend
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
        // return $this->success("请求成功");
        $njArray = ["2018","2017","2016","2019"];
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
                $classAllCount = Db::name("stu_detail") 
                        -> where("YXDM",$YXDM)
                        -> where('XH',['like','2015%'],['like','2016%'],['like','2017%'],['like','2018%'],['like','2019%'],'or')
                        -> group("BJDM")
                        -> where("XSLBDM",3)
                        -> count();
            } else {
                $classAllCount = Db::name("stu_detail") 
                        -> where("YXDM",$YXDM)
                        -> where('XH',['like','2019%'],['like','2016%'],['like','2017%'],['like','2018%'],'or')
                        -> where("XSLBDM",3)
                        -> group("BJDM")
                        -> count();
            }
            $finishedStuCount = Db::view("form_result")
                        -> group("user_id")
                        ->where("form_id",1)
                        -> view("stu_detail","YXDM,XH","form_result.user_id = stu_detail.XH")
                        -> where("stu_detail.YXDM",$YXDM)
                        -> count();
            // $rate = round($finishedStuCount/$stuAllCount,4)*100;
            // $info[$key]["rate"] = $rate;
            $info[$key]["finishedStuCount"] = $finishedStuCount;
            $info[$key]["classAllCount"] = $classAllCount;
        }
        $finishedAllCount = Db::name("form_result")
                        -> group("user_id")
                        -> where("form_id",1)
                        -> count();
        $arr = array_column($info,'finishedStuCount');
        array_multisort($arr, SORT_DESC, $info );
        $this->view->assign(["info" => $info]);
        $this->view->assign(["all" => $finishedAllCount]);
        return $this->view->fetch();
    }
    /**
     * 获取详细统计信息
     */

    public function detail()
    {
        $info = $this->request->param();
        $YXDM = $info["college"];
        if (empty($YXDM)) {
            $this->error("param error!");
        } else {
            if ($YXDM == "4100") {
                $njArray = ["2018","2017","2016","2015","2019"];
            } else if ($YXDM == "0001") {
                $njArray = ["2019","2018"];
            }else {
                $njArray = ["2018","2017","2016","2019"];
            }
            $result = array();
            $result["YXJC"] = $info["name"];
            $result["YXDM"] = $YXDM;
            $result["count"] = array();
            foreach ($njArray as $value) {
                $classAllCount = Db::name("stu_detail") 
                            -> where("YXDM",$YXDM)
                            -> where("XH","LIKE","$value%")
                            -> group("BJDM")
                            -> where("XSLBDM",3)
                            -> count();

                $finishedStuCount = Db::view("form_result")
                                    -> group("user_id")
                                    -> where("form_id",1)
                                    -> view("stu_detail","YXDM,XH","form_result.user_id = stu_detail.XH")
                                    -> where("user_id","LIKE","$value%")
                                    -> where("stu_detail.YXDM",$YXDM)
                                    -> count();
                // $rate = round($finishedStuCount/$stuAllCount,4)*100;
                $temp = [
                    "NJ"  => $value,
                    "YXDM" => $YXDM,
                    "classAllCount" => $classAllCount,
                    "finishedStuCount" => $finishedStuCount,
                    // "finishedRate"   => "$rate%",
                ];
                $result["count"][] = $temp;
            }
            $this->view->assign(["info" => $result]);
            return $this->view->fetch();
        }
    }
    /**
     * 获取学院某年级未完成人员名单
     */

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
            $classAllList = Db::name("stu_detail") 
                    -> where("YXDM",$YXDM)
                    -> where('XH','like',"$NJ%")
                    -> group("BJDM")
                    -> where("XSLBDM",3)
                    -> select();
            $infoShow = array(
                "YXJC" => $YXJC,
                "YXDM" => $YXDM,
                "NJ" => $NJ,
            );
            foreach ($classAllList as $key => $value) {
                $finishedCount = Db::view("form_result")
                                    -> view("stu_detail","YXDM,XH,BJDM","form_result.user_id = stu_detail.XH")
                                    -> group("user_id")
                                    -> where("form_id",1)
                                    -> where("stu_detail.BJDM",$value["BJDM"])
                                    -> where("stu_detail.YXDM",$YXDM)
                                    -> count();
                $temp = [
                    "name"  =>  $value["BJDM"],
                    "count" =>  $finishedCount,
                ];
                $infoShow["class"][] = $temp;
            }
            $this->view->assign(["info"=>$infoShow]);
            return $this->view->fetch();
        }
        
    }
}
