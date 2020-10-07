<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use think\Db;

class Major extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {      
        $param = $this->request->param();
        $adminId = $param["id"];

        $basicInfo = Db::view("admin","username,id")
                    -> view("teacher_detail","YXDM,XM,XBDM,ID","admin.username = teacher_detail.ID")
                    -> view("dict_college","YXJC,YXDM","teacher_detail.YXDM = dict_college.YXDM")
                    -> view("fdy_type","GH,XM,type","admin.username = fdy_type.GH")
                    -> where("admin.id",$adminId)
                    -> find();
        if (empty($basicInfo)) {
            $this->error("信息不存在，请联系管理员");
        }
        //判断是否报名
        $checkSign = Db::name("fdy_major")->where("GH",$basicInfo["username"])->find();

        $basicInfo["GH"] = $basicInfo["username"];
        $basicInfo["time"] = date("Y-m-d",time());
        return $this->view->fetch("index",["basicInfo" => $basicInfo,"signInfo" => $checkSign]);
    }
}
