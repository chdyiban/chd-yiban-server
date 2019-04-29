<?php

namespace app\admin\model;

use think\Model;
use think\Db;

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
        return ['0' => __('取消关注'),'1' => __('一般'),'2' => __('重点')];
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
        $result = $this->insert($param);
        $XSID = Db::name('user')->getLastInsID();
        $this -> updateCourseFlag($param['CXKC'], $XSID);
        $this -> updatePersonnalFlag($param['tags'], $XSID);
        $result = true;
        return $result;
    }

    /**
     * 更新个人评价标签
     * 注：此处只实现添加学生ID至flags表中，无法将学生评价删去的评价减去
     */
    public function updatePersonnalFlag($ZTPJ,$XSID)
    {
        $flagArray = explode(",",$ZTPJ);
        if (empty($flagArray)) {
            return true;
        } else {
            foreach ($flagArray as $key => $value) {
                $isexist = model("RecordTags") -> where("name",$value) -> find();
                //此标签数据库中存在则更新反之则新建标签
                if (!empty($isexist)) {
                    $oldArray = explode(",",$isexist["student"]);
                    if (in_array($XSID,$oldArray)) {
                        continue;
                    } else {
                        $res = model("RecordTags") -> where("name",$value) -> update(["nums" => $isexist["nums"]+1,"student" => $isexist["student"].",$XSID"]);
                    }
                } else {
                    $insertData = [
                        "name"    => $value,
                        "student" => $XSID,
                        "nums"    => 1,
                    ];
                    $res = model("RecordTags")->insert($insertData);
                }

            }
        }
    }
    /**
     * 更新课程标签
     */
    public function updateCourseFlag($CXKC,$XSID)
    {
        $flagArray = explode(",",$CXKC);
        if (empty($flagArray)) {
            return true;
        } else {
            foreach ($flagArray as $key => $value) {
                $isexist = model("RecordCourse") -> where("name",$value) -> find();
                if (!empty($isexist)) {
                    $oldArray = explode(",",$isexist["student"]);
                    if (in_array($XSID,$oldArray)) {
                        continue;
                    } else {
                        $res = model("RecordCourse") -> where("name",$value) -> update(["nums" => $isexist["nums"]+1,"student" => $isexist["student"].",$XSID"]);
                    }
                } else {
                    $insertData = [
                        "name"    => $value,
                        "student" => $XSID,
                        "nums"    => 1,
                    ];
                    $res = model("RecordCourse")->insert($insertData);
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
                    -> where('XH',['like','2015%'],['like','2016%'],['like','2017%'],['like','2018%'],'or')
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

}
