<?php

namespace app\admin\model\record;

use think\Model;
use think\Db;
use app\admin\model\record\RecordTags as RecordTagsModel;
use app\admin\model\record\RecordCourse as RecordCourseModel;

class RecordStuinfo extends Model
{
    // 表名
    protected $name = 'record_stuinfo';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'GZLX_text'
    ];
    

    
    public function getGzlxList()
    {
        return ['0' => __('取消关注'),'1' => __('一般'),'2' => __('重点'),'3' => __('非重点关注')];
    }     


    public function getGzlxTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['GZLX'];
        $list = $this->getGzlxList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    /**
     * add新增数据
     */
    public function insertInfo($param,$adminId)
    {
        $infoArray = explode("-",$param['info']);
        unset($param['info']);
        $param['XH'] = (int)$infoArray[0];
        $param['XM'] = $infoArray[1];
        $param['JDSJ'] = strtotime($param['JDSJ']);
        $param['THCS'] = 0;
        $param['THSJ'] = 0;
        $param['admin_id'] = $adminId;
        //判断学生是否已经添加过
        $check = Db::name("record_stuinfo")
                ->where("XH",$param["XH"])
                ->where("admin_id",$param["admin_id"])
                ->find();
        if (!empty($check)) {
            return false;
        }
        $result = $this->insert($param);
        $XSID = Db::name('user')->getLastInsID();
        $this -> updateCourseFlag($param['CXKC'], $XSID);
        $this -> updatePersonnalFlag($param['tags'], $XSID);
        $result = true;
        return $result;
    }
    /**
     * 共享学生管理信息录入
     * @param $params["stu_ids"]
     * @param $params["admin"]
     * @param $params["admin_id"]
     */
    public function insertShareInfo($params)
    {
        $insertData = [];
        $params["stu_ids"] = json_decode($params["stu_ids"],true);
        $params["admin"] = explode(",",$params["admin"]);
        foreach ($params["admin"] as $key => $value) {
            foreach ($params["stu_ids"] as $k => $v) {
                $temp = [
                    "share_id"  =>  $params["admin_id"],
                    "accept_id" =>  $value,
                    "stu_id"    =>  $v,
                ];
                $insertData[] = $temp;
            }
        }
        //先获取数据表中已经共享的记录，去重
        $adminIdArray = array_column($insertData, "accept_id");
        $stuIdArray = array_column($insertData, "stu_id");
        $shareList = Db::name("record_share")
                ->where("share_id",$params["admin_id"])
                ->where("accept_id","IN",$adminIdArray)
                ->where("stu_id","IN",$stuIdArray)
                ->select();
        if (!empty($shareList)) {
            foreach ($insertData as $key => $value) {
                foreach ($shareList as $k => $v) {
                    if ($v["accept_id"] == $value["accept_id"] && $v["stu_id"] == $value["stu_id"] ) {
                        unset($insertData[$key]);
                        break;
                    }
                }
            }
        }
        
        if (empty($insertData)) {
            return ["status" => false,"msg" => "共享学生重复"];
        }

        $updateData = [];
        foreach ($insertData as $key => $value) {
            $updateData[] = $value["stu_id"];
        }
        
        $update_flag = Db::name("record_stuinfo")->where("ID","IN",$updateData)->update(["is_share"=>1]);
        $insert_flag = Db::name("record_share")->insertAll($insertData);

        if ($insert_flag) {
            return ["status" => true,"msg" => "共享成功"];
        }
        return ["status" => false,"msg" => "请稍后再试"];
    }


    /**
     * 更新个人评价标签
     * 注：此处只实现添加学生ID至flags表中，无法将学生评价删去的评价减去
     */
    public function updatePersonnalFlag($XSPJ,$XSID)
    {
        $RecordTagsModel = new RecordTagsModel();
        if (empty($XSPJ)) {
            return true;
        }
        $flagArray = explode(",",$XSPJ);
        if (empty($flagArray)) {
            return true;
        } else {
            foreach ($flagArray as $key => $value) {
                $isexist =   $RecordTagsModel  -> where("name",$value) -> find();
                //此标签数据库中存在则更新反之则新建标签
                if (!empty($isexist)) {
                    $oldArray = explode(",",$isexist["student"]);
                    if (in_array($XSID,$oldArray)) {
                        continue;
                    } else {
                        $res =  $RecordTagsModel  -> where("name",$value) -> update(["nums" => $isexist["nums"]+1,"student" => $isexist["student"].",$XSID"]);
                    }
                } else {
                    $insertData = [
                        "name"    => $value,
                        "student" => $XSID,
                        "nums"    => 1,
                    ];
                    $res =   $RecordTagsModel ->insert($insertData);
                }

            }
        }
    }
    /**
     * 更新课程标签
     */
    public function updateCourseFlag($CXKC,$XSID)
    {
        $RecordCourseModel = new RecordCourseModel();
        if (empty($CXKC)) {
            return true;
        }
        $flagArray = explode(",",$CXKC);
        if (empty($flagArray)) {
            return true;
        } else {
            foreach ($flagArray as $key => $value) {
                $isexist = $RecordCourseModel -> where("name",$value) -> find();
                if (!empty($isexist)) {
                    $oldArray = explode(",",$isexist["student"]);
                    if (in_array($XSID,$oldArray)) {
                        continue;
                    } else {
                        $res = $RecordCourseModel -> where("name",$value) -> update(["nums" => $isexist["nums"]+1,"student" => $isexist["student"].",$XSID"]);
                    }
                } else {
                    $insertData = [
                        "name"    => $value,
                        "student" => $XSID,
                        "nums"    => 1,
                    ];
                    $res = $RecordCourseModel->insert($insertData);
                }
            }
        }
    }


     /**
     * 查找学生信息通过学号
     * @param int XH
     * @return array 
     */
    public function searchStuByXh($XH)
    {
        $XH = '%'.$XH.'%';
        $stuInfo = Db::view('stu_detail','XM,XH,YXDM,XBDM')
                    -> view('dict_college','YXDM,YXJC','stu_detail.YXDM = dict_college.YXDM')
                    -> where('XH','LIKE',$XH)
                    -> select();
        $resultInfo = [];
        if (empty($stuInfo)) {
           $stuInfo = [];
           //$total = 0;
           
        } else {
            foreach ($stuInfo as $key => $value) {
                $temp = array();
                $temp['XH'] = $value['XH'];
                $temp['XM'] = $value['XM'];
                $temp['XB'] = $value['XBDM'] == 1 ? '男':'女';
                $temp['NJ'] =  substr($value['XH'],0,4);
                $temp['YXJC'] = $value['YXJC'];
                $resultInfo[] = $temp;
            }
        }
        //$resultInfo[] = $stuInfo;
        //$result = array("total" => $total, "rows" => $resultInfo);
        //return $result;
        return $resultInfo;
    }
    
    /**
     * 查找学生信息通过姓名，支持模糊搜索
     * @param str name
     * @return array 
     */
    public function searchStuByName($name)
    {
        $name = '%'.$name.'%';
        $stuInfo = Db::view('stu_detail','XM,XH,YXDM,XBDM')
                    -> view('dict_college','YXDM,YXJC','stu_detail.YXDM = dict_college.YXDM')
                    -> where('XM','like',$name)
                    -> where('XH',['like','2015%'],['like','2016%'],['like','2017%'],['like','2018%'],['like',"2019%"],['like',"2020%"],'or')
                    -> limit(20)
                    -> select();
        $resultInfo = [];
        if (empty($stuInfo)) {
            $stuInfo = [];
            // $total = 0;
        } else {
            //$total = count($stuInfo);
            foreach ($stuInfo as $key => $value) {
                $tempArray = [];
                $tempArray = $value;
                $tempArray['XB'] = ($value['XBDM']) == 1 ? '男':'女';
                $tempArray['NJ'] = substr($value['XH'],0,4);
                $resultInfo[] = $tempArray;
            }
            //$stuInfo['XB'] = ($stuInfo['XBDM']) == 1 ? '男':'女';
        }
        //$result = array("total" => $total, "rows" => $resultInfo);    
        return $resultInfo;
    }

    /**
     * 获取学生住宿信息
     */
    public function getSSDM($XH)
    {
        $dormitoryInfo = Db::view("dormitory_beds","FYID,XH,CH")
                        -> view("dormitory_rooms","ID,XQ,LH,SSH","dormitory_beds.FYID = dormitory_rooms.ID")
                        -> where("XH",$XH)
                        -> find();
        if (empty($dormitoryInfo)) {
            return "";
        } else {
            $result = $dormitoryInfo["XQ"]."-".$dormitoryInfo["LH"]."#".$dormitoryInfo["SSH"]."-".$dormitoryInfo["CH"];
            return $result;
        }
    }

}
