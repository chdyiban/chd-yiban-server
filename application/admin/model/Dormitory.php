<?php

namespace app\admin\model;
use think\Db;
use think\Model;

class Dormitory extends Model
{
    // 表名
    protected $name = 'dormitory_system';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('空床'),'1' => __('正常'),'2' => __('有损坏')];
    } 
    
    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    //表关联获取院系名称
    public function getcollege(){
        return $this->belongsTo('College', 'YXDM')->setEagerlyType(0);
    }

    //表关联获取个人姓名
    public function getstuname(){
        return $this->belongsTo('Studetail', 'XH')->setEagerlyType(0);
    }

    /**
     * 获取总的床位数
     * @param $key all -- 总床位数 
     * @param $key number -- 某个楼的总床位数 
     */
    public function getAllBedNums($key)
    {
        if ($key == 'all') {
            return $this -> count();
        } else {
            return $this -> where('LH', $key) -> count();
        }
    }

    /**
     * 查询入住人数
     * @param $key all --- 所有楼
     * @param $key number --- 某个楼的入住情况
     * @return list
     */
    public function getAllStuNums($key)
    {
        if ($key == 'all') {
            $allBoyNums = $this ->where('XBDM',1) ->where('status',1) -> count();
            $allGirlNums = $this ->where('XBDM',2) ->where('status',1) -> count();
            $allStuNums = $allBoyNums + $allGirlNums;
        } else {
            $allBoyNums = $this -> where('LH',$key) ->where('XBDM',1) ->where('status',1) -> count();
            $allGirlNums = $this -> where('LH',$key) ->where('XBDM',2) ->where('status',1) -> count();
            $allStuNums = $allBoyNums + $allGirlNums;
        }

        return ['boy' => $allBoyNums, 'girl' => $allGirlNums, 'all' => $allStuNums];
        
    }

    /**
     * 获取不同楼信息的列表
     * @return list
     */
    public function getBuildingList()
    {
        $buildingNumList = $this -> group('LH') -> field('LH') -> select();
        $buildingInfoResult = array();
        foreach ($buildingNumList as $key => $value) {
            $tempArray = array();
            //每个楼的入住人数
            $buildingInfo = $this -> getAllStuNums($value['LH']);
            $tempArray['LH'] = $value['LH'];
             //每个楼的总床位数
            $tempArray['allBedNums'] = $this -> getAllBedNums($value['LH']);
            //入住人数情况
            $tempArray['allStuNums'] = $buildingInfo['all'];
            $tempArray['allBoyNums'] = $buildingInfo['boy'];
            $tempArray['allGirlNums'] = $buildingInfo['girl'];
            $buildingInfoResult[] = $tempArray;
        }
        return $buildingInfoResult;
    }

    /**
     * 获取学院代码对应名称的json
     */
    public function getCollegeJson()
    {
        $list = Db::name('dict_college') -> select();
        $collegeJson = array();
        foreach ($list as $key => $value) {
            if ($value['YXDM'] != 1800 && $value['YXDM'] != 1801 &&  $value['YXDM'] != 5100 && $value['YXDM'] != 9999) {
                $collegeJson[$value['YXJC']] = $value['YXJC'];
            }
        }
        return $collegeJson;
    }
    /**
     * 获取具体宿舍相关信息，获取床位入住情况以及入住比例
     * @param bedid
     * @param type:situation   入住情况
     * @param type:proportion  入住比例
     */
    public function getDormitoryFreeBedInfo($bedId,$type)
    {
        $bedInfo = $this->where('id',$bedId)->field('LH,SSH') -> find();
        $LH = $bedInfo['LH'];
        $SSH = $bedInfo['SSH'];
        $dormitoryInfo = $this->where('LH',$LH) -> where('SSH',$SSH) ->field('status,CH')->order('CH') -> select();
        switch ($type) {
            case 'situation':
                $dormitory = array();
                foreach ($dormitoryInfo as $key => $value) {
                    if ($value['status'] == 0) {
                        $dormitory[$value['CH']] = '○';
                    } elseif ($value['status'] == 1) {
                        $dormitory[$value['CH']] = '●';       
                    } else{
                        $dormitory[$value['CH']] = '△';
                    }
                }
                return $dormitory;
                break;

            case 'proportion':
                $dormitoryBedInfo = [];
                $freeBed = [];
                $fullBed = [];
        
                foreach ($dormitoryInfo as $key => $value) {
                    if ($value['status'] == 0) {
                        $freeBed[] = $value['CH'];
                    } elseif ($value['status'] == 1) {
                        $fullBed[] = $value['CH'];                
                    } 
                }
        
                $dormitoryBedInfo['freeBedNum'] = count($freeBed);
                $dormitoryBedInfo['fullBedNum'] = count($fullBed);
                $dormitoryBedInfo['allBedNum'] = count($dormitoryInfo);
                $dormitoryBedInfo['freeBed'] = $freeBed;
                $dormitoryBedInfo['fullBed'] = $fullBed;
                return $dormitoryBedInfo;
                break;
        }
        
    }
    /**
     * 获取宿舍的详细信息，包括入住信息
     */
    public function getDormitoryInfo($LH,$SSH)
    {
        $dormitoryInfoList = array();
        $tempArray = array();
        $dormitoryInfo = $this->where('LH',$LH)->where('SSH',$SSH)->order('CH')->select();
        $dormitoryInfoList['LH'] = $LH;
        $dormitoryInfoList['SSH'] = $SSH;
        foreach ($dormitoryInfo as $key => $value) {
            $stuInfo = array();
            $dormitory = $value -> toArray();
            $dormitoryInfoList['XQ'] = $dormitory['XQ'];
            $dormitoryInfoList['LD'] = empty($dormitory['LD']) ? '无': $dormitory['LD'];
            $dormitoryInfoList['LC'] = $dormitory['LC'];
            if (empty($dormitory['XH'])) {
                $stuInfo['XH'] = '---';
                $stuInfo['XM'] = '---';
                $stuInfo['YXJC'] = '---';
                $stuInfo['XB'] = '---';
            } else{
                $stuInfo = Db::name('stu_detail') -> where('XH',$dormitory['XH']) -> field('XH,XM,YXDM,XBDM')->find();
                $stuInfo['XB'] = $stuInfo['XBDM'] == 1 ? '男' : '女';
                $stuInfo['YXJC'] = Db::name('dict_college') -> where('YXDM',$stuInfo['YXDM'])->field('YXDM,YXJC')->find()['YXJC'];
            }
            $dormitory['stuinfo'] = $stuInfo;
            $tempArray[] = $dormitory;
        }
        $dormitoryInfoList['dormitory'] = $tempArray; 
        return $dormitoryInfoList;
    }

    /**
     * 删除某个床的学生记录
     * @param list param ['XH','CH','LH','SSH',reason,remark]
     * @param int adminid
     * @return bool 
     */
    public function deleteStuRecord($param,$adminId)
    {
        $insert_data = [
            'admin_id' => $adminId,
            'operation' => 'delete',
            'object_dormitory' => $param['LH'].'#'.$param['SSH'].'-'.$param['CH'],
            'object_stuid' => $param['XH'],
            'reason' => $param['reason'],
            'remark' => $param['remark'],
            'timestamp' => time(),
        ];
        Db::startTrans();
        $insert_res = false;
        $delete_res = false;
        try{
            //第一步将操作写入日志
            $insert_res = Db::name('dormitory_log') -> insert($insert_data);
            //第二步将对应学生数据删除
            $delete_res = $this->where('LH',$param['LH']) 
                            -> where('SSH',$param['SSH'])
                            -> where('CH',$param['CH'])
                            -> update([
                                'XH' => '',
                                'NJ' => '',
                                'YXDM' => '',
                                'status' => 0,
                            ]);
            // 提交事务
            Db::commit();  
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        if ($insert_res == 1 && $delete_res == 1) {
            return true;
        } else {
            return false;
        }
        
    }

    /**
     * 向某个床位添加学生
     * @param list param ['XH','CH','LH','SSH']
     * @param int adminid
     * @return array ['status' => true,'msg'=>'']
     */
    public function addStuRecord($param,$adminId)
    {
        //首先确定该学生是否已经有了床位
        $isExitRecord = $this->where('XH',$param['XH']) -> find();
        if (empty($isExitRecord)) {
            $XB = $param['XB'] == '男' ? 1 : 2;
            $isSexRight = $this -> where('XBDM',$XB) 
                            -> where('LH',$param['LH'])
                            -> where('SSH',$param['SSH'])
                            -> where('CH',$param['CH'])
                            -> find();
            if (empty($isSexRight)) {
                return ['status' => false, 'msg'=> '分配学生性别不符!'];
            } else {
                $insert_data = [
                    'admin_id' => $adminId,
                    'operation' => 'distribute',
                    'object_dormitory' => $param['LH'].'#'.$param['SSH'].'-'.$param['CH'],
                    'object_stuid' => $param['XH'],
                    'reason' => '',
                    'remark' => '',
                    'timestamp' => time(),
                ];
                Db::startTrans();
                $insert_res = false;
                $add_res = false;
                try{
                    //第一步将操作写入日志
                    $insert_res = Db::name('dormitory_log') -> insert($insert_data);
                    //第二步将对应学生添加至表中
                    $add_res = $this->where('LH',$param['LH']) 
                                    -> where('SSH',$param['SSH'])
                                    -> where('CH',$param['CH'])
                                    -> update([
                                        'XH' => $param['XH'],
                                        'NJ' => substr($param['XH'],0,4),
                                        'YXDM' => $param['YXDM'],
                                        'status' => 1,
                                    ]);
                    // 提交事务
                    Db::commit();  
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
                if ($add_res == 1 && $insert_res == 1) {
                    return ['status' => true, 'msg' => '分配成功！'];
                } else {
                    return ['status'=>false,'msg'=>'网络原因，分配失败！'];
                }
            }
        } else {
            return ['status'=> false, 'msg' => '该学生已经分配了床位！'];
        }
    }


    /**
     * 查找学生信息通过学号
     * @param int XH
     * @return array 
     */
    public function searchStuByXh($XH)
    {
        $stuInfo = Db::view('stu_detail','XM,XH,YXDM,XBDM')
                    -> view('dict_college','YXDM,YXJC','stu_detail.YXDM = dict_college.YXDM')
                    -> where('XH',$XH)
                    -> find();
        if (empty($stuInfo)) {
            $stuInfo = array();
            $stuInfo['XH'] = '';
            $stuInfo['msg'] = '没有查到相关学生';
        } else {
            $stuInfo['XB'] = $stuInfo['XBDM'] == 1 ? '男':'女';
        }
        return $stuInfo;
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
                    -> select();
        if (empty($stuInfo)) {
            $stuInfo = array();
            $stuInfo['XH'] = '';
            $stuInfo['msg'] = '没有查到相关学生';
        } else {
            $stuInfo['XB'] = $stuInfo['XBDM'] == 1 ? '男':'女';
        }
        return $stuInfo;
    }
}
