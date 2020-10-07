<?php

namespace app\admin\controller\major;

use app\common\controller\Backend;
use think\Config;
use think\Db;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{
    
    /**
     * Index模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Major');

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
        //ajax随机分配座号
        if ($this->request->isAjax())
        {
    
            $param = $this->request->param();
            if (empty($param["GH"]) || empty($param["KC"]) || empty($param["type"]) ) {
                $this->error("params error!");
            }
            //获取可选座号列表
            $seatArray = Db::name("fdy_major_kc")
                    ->where("KC",$param["KC"])
                    ->where("type",$param["type"])
                    ->where("GH","")
                    ->select();
            $length = count($seatArray);
            $seed = time();                   
            srand($seed);                     
            $key = rand(0, $length);
            //获取随机座号
            $infoArray = $seatArray[$key];
            $insertData = [
                "GH"    =>  $param["GH"],
                "XM"    =>  $param["XM"],
                "YXJC"    =>  $param["YXJC"],
                "image" =>  "",
                "KC"    =>  $infoArray["JS"],
                "ZH"    =>  $infoArray["ZH"],
                "place" =>  $infoArray["JS"]."-".$infoArray["ZH"],
                "card"  =>  "",
                "time"  =>  time(),
                "type"  =>  $param["type"],
            ];
            $update_flag = false;
            $insert_flag = false;
            Db::startTrans();
            try{   
                $update_flag  = Db::name("fdy_major_kc")->where("ID",$infoArray["ID"])->update(["GH" => $param["GH"]]);
                $insert_flag = Db::name("fdy_major")->insert($insertData);
              
                //提交事务
                Db::commit();      
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }    
            if ($update_flag && $insert_flag) {
                return json(["status" => true, "msg" => "success","data"=>null]);
            } else {
                return json(["status" => false, "msg" => "error","data"=>null]);
            }
            
        }

        //获取管理员ID，之后判断是否进行报名
        $adminId = $this->auth->id;
        // $adminId = 22;
        //基础信息
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
        if (empty($checkSign)) {
            //未报名
            $basicInfo["GH"] = $basicInfo["username"];
            $basicInfo["time"] = date("Y-m-d",time());
            return $this->view->fetch("index",["basicInfo" => $basicInfo]);

        } else {
            require_once "phpqrcode.php";
            $request_url = $this->request->domain().$this->request->root().DS."index".DS."major".DS."index?id=".$adminId;
            $value = $request_url;                    //二维码内容
            $errorCorrectionLevel = 'L';    //容错级别
            $matrixPointSize = 5;            //生成图片大小
            $filename = './uploads/major/'.$basicInfo["username"].'.png';
            QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
            $QR = $filename;                //已经生成的原始二维码图片文件
            $imageUrl = $this->request->domain().$this->request->root().$QR;
            $basicInfo["GH"] = $basicInfo["username"];
            $basicInfo["time"] = date("Y-m-d",time());
            return $this->view->fetch("print",["basicInfo" => $basicInfo,"signInfo" => $checkSign,"requestUrl"=> $request_url,"imageUrl" => $imageUrl]);
        }

    }

}
