<?php

namespace app\admin\model;
use think\Db;
use think\Model;

class Dormitory extends Model
{
    // 表名
    //protected $name = 'dormitory_system';
    protected $name = 'dormitory_rooms';
    
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
            return Db::name('dormitory_beds') -> count();
        } else {
            //return $this->view('dormi')
            $allBedNums = Db::view('dormitory_beds',['status'=>'bed_status'])
                        -> view('dormitory_rooms','*','dormitory_beds.FYID = dormitory_rooms.ID')
                        -> where('LH',$key)
                        -> count();
            //return $this -> where('LH', $key) -> count();
            return $allBedNums;
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
            $allBoyNums = Db::view('dormitory_beds',['status'=>'bed_status'])
                        -> view('dormitory_rooms','*','dormitory_beds.FYID = dormitory_rooms.ID')
                        -> where('XBDM','1')
                        -> where('bed_status','1')
                        -> count();

            $allGirlNums = Db::view('dormitory_beds',['status'=>'bed_status'])
                        -> view('dormitory_rooms','*','dormitory_beds.FYID = dormitory_rooms.ID')
                        -> where('XBDM','2')
                        -> where('bed_status','1')
                        -> count();

            //$allBoyNums = $this ->where('XBDM',1) ->where('status',1) -> count();
            //$allGirlNums = $this ->where('XBDM',2) ->where('status',1) -> count();
            $allStuNums = $allBoyNums + $allGirlNums;
        } else {
            
            $allBoyNums = Db::view('dormitory_beds',['status'=>'bed_status'])
                        -> view('dormitory_rooms','*','dormitory_beds.FYID = dormitory_rooms.ID')
                        -> where('XBDM','1')
                        -> where('bed_status','1')
                        -> where('LH',$key)
                        -> count();

            $allGirlNums = Db::view('dormitory_beds',['status'=>'bed_status'])
                        -> view('dormitory_rooms','*','dormitory_beds.FYID = dormitory_rooms.ID')
                        -> where('XBDM','2')
                        -> where('bed_status','1')
                        -> where('LH',$key)
                        -> count();

            // $allBoyNums = $this -> where('LH',$key) ->where('XBDM',1) ->where('status',1) -> count();
            // $allGirlNums = $this -> where('LH',$key) ->where('XBDM',2) ->where('status',1) -> count();
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
        $buildingNumList = Db::name('dormitory_rooms') -> group('LH') -> field('LH') -> order('LH asc') -> select();

        $buildingInfoResult = array();
        // $westBuildingNumList = ['1','2','3','4','5','6'];
        // $eastBuildingNumList = ['7','8','9','10','11','12','13','14','15'];
        // $highBuildingNumList = ['16','17','19','20'];
        foreach ($buildingNumList as  $value) {
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
            //校区
            $tempArray['XQ'] = Db::name('dormitory_rooms') -> where('LH',$value['LH']) -> find()['XQ'];
            //剩余床位数
            $tempArray['restBedNums'] =  $tempArray['allBedNums'] - $buildingInfo['all'];
            //总学生房间数量
            $tempArray['allStuRoomsNums'] = Db::name('dormitory_rooms') -> where('LH',$value['LH']) -> where('status','1')-> count();
            //剩余房间数量
            $tempArray['restStuRoomsNums'] = Db::name('dormitory_rooms') -> where('LH',$value['LH']) -> where('RZS',0) -> where('status','1') -> count();
            //总公用房数量
            $tempArray['allPublicRoomsNums'] = Db::name('dormitory_rooms') -> where('LH',$value['LH']) -> where('status','2') -> count();


            $buildingInfoResult[] = $tempArray;
        }
        // foreach ($eastBuildingNumList as  $value) {
        //     $tempArray = array();
        //     //每个楼的入住人数
        //     $buildingInfo = $this -> getAllStuNums($value);
        //     $tempArray['LH'] = $value;
        //      //每个楼的总床位数
        //     $tempArray['allBedNums'] = $this -> getAllBedNums($value);
        //     //入住人数情况
        //     $tempArray['allStuNums'] = $buildingInfo['all'];
        //     $tempArray['allBoyNums'] = $buildingInfo['boy'];
        //     $tempArray['allGirlNums'] = $buildingInfo['girl'];
        //     $buildingInfoResult['east'][] = $tempArray;
        // }
        // foreach ($highBuildingNumList as  $value) {
        //     $tempArray = array();
        //     //每个楼的入住人数
        //     $buildingInfo = $this -> getAllStuNums($value);
        //     $tempArray['LH'] = $value;
        //      //每个楼的总床位数
        //     $tempArray['allBedNums'] = $this -> getAllBedNums($value);
        //     //入住人数情况
        //     $tempArray['allStuNums'] = $buildingInfo['all'];
        //     $tempArray['allBoyNums'] = $buildingInfo['boy'];
        //     $tempArray['allGirlNums'] = $buildingInfo['girl'];
        //     $buildingInfoResult['high'][] = $tempArray;
        // }
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
    public function getDormitoryFreeBedInfo($bedIdList)
    {

        $bedIdList = json_decode($bedIdList,true);
        $result = array();
        foreach ($bedIdList as $key => $value) {
            $temp = array();
            $temp['ID'] = $value;
            $freeBed = [];
            $fullBed = [];

            // $bedInfo = $this->where('id',$value)->field('LH,SSH') -> find();
            // $LH = $bedInfo['LH'];
            // $SSH = $bedInfo['SSH'];

            //$dormitoryInfo = $this->where('LH',$LH) -> where('SSH',$SSH) ->field('status,CH')->order('CH') -> select();
            $dormitoryInfo = Db::name('dormitory_beds') -> where('FYID',$value) -> select();

            $dormitory = array();
            foreach ($dormitoryInfo as $key => $value) {
                if ($value['status'] == 0) {
                    $dormitory[$value['CH']] = '○';
                    $freeBed[] = $value['CH'];
                } elseif ($value['status'] == 1) {
                    $dormitory[$value['CH']] = '●';   
                    $fullBed[] = $value['CH'];
                } else{
                    $dormitory[$value['CH']] = '△';
                }
            }



            $situation = '';
            foreach ($dormitory as  $value) {
                $situation = $situation.$value;
            }


            $temp['situation'] = $situation;
            $temp['allBedNum'] = count($dormitoryInfo);
            $temp['freeBedNum'] = count($freeBed);
            $temp['fullBedNum'] = count($fullBed);

            $result[] = $temp;
        }
        return $result;
        
    }
    /**
     * 获取宿舍的详细信息，包括入住信息
     */
    public function getDormitoryInfo($LH,$SSH)
    {
        $dormitoryInfoList = array();
        $tempArray = array();

        //$dormitoryInfo = $this->where('LH',$LH)->where('SSH',$SSH)->order('CH')->select();
        $dormitoryInfo = Db::view('dormitory_beds',['status'=>'bed_status','*'])
                    -> view('dormitory_rooms','*','dormitory_beds.FYID = dormitory_rooms.ID')
                    -> where('LH',$LH)
                    -> where('SSH',$SSH)
                    -> order('CH')
                    -> select();

        $dormitoryInfoList['LH'] = $LH;
        $dormitoryInfoList['SSH'] = $SSH;
        foreach ($dormitoryInfo as $key => $value) {
            $stuInfo = array();
            $dormitory = $value;
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
        $stuClassInfo = Db::name('stu_detail') -> where('XH',$param['XH']) -> field('YXDM,BJDM')->find();
        $param['YXDM'] = $stuClassInfo['YXDM'];
        $param['oldBJDM'] = $stuClassInfo['BJDM'];
        $insert_data = [
            'XH' => $param['XH'],
            'admin_id' => $adminId,
            'old_class' => $param['oldBJDM'],
            'new_class' => $param['oldBJDM'],
            'operation' => 'delete',
            //此处逻辑存在一些问题
            'new_dormitory' => '',
            'old_dormitory' => $param['LH'].'#'.$param['SSH'].'-'.$param['CH'],
            'handle_time'   => strtotime($param['handletime']),
            'handle_end_time' => strtotime($param['handleendtime']),
            'reason' => $param['reason'],
            'remark' => $param['remark'],
            'operate_time' => time(),
        ];
        Db::startTrans();
        $insert_res = false;
        $delete_res = false;
        try{
            //第一步将操作写入日志
            $insert_res = Db::name('dormitory_special') -> insert($insert_data);
            //第二步将对应学生数据删除
            //获取房源ID
            $FYID = Db::name('dormitory_rooms') -> where('LH',$param['LH']) -> where('SSH',$param['SSH']) -> find()['ID'];

            $delete_res = Db::name('dormitory_beds') 
                            -> where('FYID',$FYID)
                            -> where('CH',$param['CH'])
                            -> update([
                                'XH' => '',
                                'NJ' => '',
                                'YXDM' => '',
                                'status' => 0,
                            ]);
            // $delete_res = $this->where('LH',$param['LH']) 
            //                 -> where('SSH',$param['SSH'])
            //                 -> where('CH',$param['CH'])
            //                 -> update([
            //                     'XH' => '',
            //                     'NJ' => '',
            //                     'YXDM' => '',
            //                     'status' => 0,
            //                 ]);
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
        $isExitRecord = Db::name('dormitory_beds')->where('XH',$param['XH']) -> find();
        if (empty($isExitRecord)) {
            $XB = $param['XB'] == '男' ? 1 : 2;
            $isSexRight = Db::name('dormitory_rooms') 
                            -> where('XBDM',$XB) 
                            -> where('LH',$param['LH'])
                            -> where('SSH',$param['SSH'])
                            -> find();
            if (empty($isSexRight)) {
                return ['status' => false, 'msg'=> '分配学生性别不符!'];
            } else {
                //获取学生信息
                $stuClassInfo = Db::name('stu_detail') -> where('XH',$param['XH']) -> field('YXDM,BJDM')->find();
                $param['YXDM'] = $stuClassInfo['YXDM'];
                $param['oldBJDM'] = $stuClassInfo['BJDM'];
                $insert_data = [
                    'XH' => $param['XH'],
                    'admin_id' => $adminId,
                    'old_class' => $param['oldBJDM'],
                    'new_class' => empty($param['BJDM']) ? $param['oldBJDM'] : $param['BJDM'],
                    'operation' => 'distribute',
                    //此处逻辑存在一些问题
                    'old_dormitory' => '',
                    'new_dormitory' => $param['LH'].'#'.$param['SSH'].'-'.$param['CH'],
                    'handle_time'   => strtotime($param['handletime']),
                    'handle_end_time' => '',
                    'reason' => $param['reason'],
                    'remark' => $param['remark'],
                    'operate_time' => time(),
                ];
                Db::startTrans();
                $insert_res = false;
                $add_res = false;
                try{
                    //第一步将操作写入日志
                    $insert_res = Db::name('dormitory_special') -> insert($insert_data);
                    //第二步将对应学生添加至表中
                    //获取房源ID
                    $FYID = Db::name('dormitory_rooms') -> where('LH',$param['LH']) -> where('SSH',$param['SSH']) -> find()['ID'];

                    $add_res = Db::name('dormitory_beds') 
                                -> where('FYID',$FYID)
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
     * 添加宿舍功能
     * @param list param ['XQ','LH','LC','LD','SSH','XBDM','CWS','','status','GYFMC']
     * @return bool result
     */
    public function addRoom($params,$admin_id)
    {
        //验证床位是否 存在
        $roomList = Db::name('dormitory_rooms')
                    -> where('XQ',$params['XQ']) 
                    -> where('LH',$params['LH'])
                    -> where('LC',$params['LC'])
                    -> where('SSH',$params['SSH'])
                    -> find();
        if (empty($roomList)) {
            Db::startTrans();
            $insert_res = false;
            $add_res = false;
            try{
                //第一步将房间加入room表中
                $params['RZS'] = '0';
                $insert_res = Db::name('dormitory_rooms')
                            -> insert($params);
                //将对应的床位插入beds表中
                $FYID = Db::name('dormitory_rooms')->getLastInsID(); 
                if ($params['status'] == '1') {
                    for ($i=0; $i < $params['CWS'] ; $i++) { 
                        $insert_data = [
                            'FYID' => $FYID,
                            'XH'   => '',
                            'NJ'   => '',
                            'YXDM' => '',
                            'CH'   => $i+1,
                            'status' => '0',
                        ];
                        $add_res = Db::name('dormitory_beds') -> insert($insert_data);
                    }
                } else {
                    $add_res = true;
                }
                //记录至log
                $insert_log_data = [
                    'admin_id' => $admin_id,
                    'operate'  => 'add',
                    'LH'       => $params['LH'],
                    'LC'       => $params['LC'],
                    'SSH'      => $params['SSH'],
                    'old_status' => '',
                    'new_status' => $params['status'],
                    'old_cws'  => '',
                    'new_cws'  => $params['CWS'],
                    'old_xbdm' => '',
                    'new_xbdm' => $params['XBDM'],
                    'old_gyfmc'    => '',
                    'new_gyfmc'    => $params['GYFMC'],
                    'timestamp'=> time(),
                ];
                $res_log = Db::name('dormitory_rooms_log')->insert($insert_log_data);
                // 提交事务
                Db::commit();  
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
            if ($add_res == 1 && $insert_res == 1) {
                return ['status' => true, 'msg' => '宿舍新增成功'];
            } else {
                return ['status' => false, 'msg' => "网络原因，新增失败"];
            }
        } else {
            return ['status' => false, 'msg' => "该宿舍已经存在"];
        }
    }
    /**
     * 修改宿舍功能
     * @param list param ['XBDM','CWS','status','GYFMC']
     * @return bool result
     */
    public function editRoom($params,$ids,$admin_id)
    {
        //查询表中原床位
        $oldRoomsList = Db::name('dormitory_rooms') -> where('ID',$ids) ->find();
        $CWS = $oldRoomsList['CWS'];
        Db::startTrans();
        $edit_res = false;
        $del_res = false;
        try{
            //根据房间类别修改room表
            //公用房
            if ($params['status'] == '2') {
                $edit_data = [
                    'XBDM' => '',
                    'CWS'  => '0',
                    'RZS'  => '',
                    'status' => '2',
                    'GYFMC' => $params['GYFMC'],
                ];
                $edit_res = Db::name('dormitory_rooms') -> where('ID',$ids) -> update($edit_data);
                //将操作记录log表中
                $insert_log_data = [
                    'admin_id' => $admin_id,
                    'operate'  => 'edit',
                    'LH'       => $oldRoomsList['LH'],
                    'LC'       => $oldRoomsList['LC'],
                    'SSH'      => $oldRoomsList['SSH'],
                    'old_status' => $oldRoomsList['status'],
                    'new_status' => $params['status'],
                    'old_cws'  => $CWS,
                    'new_cws'  => '',
                    'old_xbdm' => $oldRoomsList['XBDM'],
                    'new_xbdm' => '',
                    'old_gyfmc'    => $oldRoomsList['GYFMC'],
                    'new_gyfmc'    => $params['GYFMC'],
                    'timestamp'=> time(),
                ];
                $res_log = Db::name('dormitory_rooms_log')->insert($insert_log_data);
            //学生用房
            } elseif ($params['status'] == '1') {
                $edit_data = [
                    'XBDM' => $params['XBDM'],
                    'CWS'  => $params['CWS'],
                    'status' => '1',
                    'GYFMC' => '',
                ];
                $edit_res = Db::name('dormitory_rooms') -> where('ID',$ids) -> update($edit_data);
                //将操作记录log表中
                $insert_log_data = [
                    'admin_id' => $admin_id,
                    'operate'  => 'edit',
                    'LH'       => $oldRoomsList['LH'],
                    'LC'       => $oldRoomsList['LC'],
                    'SSH'      => $oldRoomsList['SSH'],
                    'old_status' => $oldRoomsList['status'],
                    'new_status' => $params['status'],
                    'old_cws'  => $CWS,
                    'new_cws'  => $params['CWS'],
                    'old_xbdm' => $oldRoomsList['XBDM'],
                    'new_xbdm' => $params['XBDM'],
                    'old_gyfmc'    => $oldRoomsList['GYFMC'],
                    'new_gyfmc'    => '',
                    'timestamp'=> time(),
                ];
                $res_log = Db::name('dormitory_rooms_log')->insert($insert_log_data);
            //无法使用
            } else {
                $edit_data = [
                    'XBDM' => '',
                    'CWS'  => '0',
                    'status' => '0',
                    'GYFMC' => '',
                ];
                $edit_res = Db::name('dormitory_rooms') -> where('ID',$ids) -> update($edit_data);
            }

            //修改对应beds表中数据
            
            //是公用房，则清空beds表中此房间所有数据
            if ($params['status'] == '2' || $params['status'] == '0') {
                if (empty($CWS)) {
                    $del_res = true;
                } else {
                    $del_res = Db::name('dormitory_beds') -> where('FYID',$ids) -> delete();
                }
            } elseif ($params['status'] == '1') {

                if (empty($CWS)) {
                    for ($i=0; $i < $params['CWS']; $i++) { 
                        $insert_data = [
                            'FYID' => $ids,
                            'XH'   => '',
                            'NJ'   => '',
                            'YXDM' => '',
                            'CH'   => $i+1,
                            'status' => '0',
                        ];
                        $del_res = Db::name('dormitory_beds') -> insert($insert_data);
                    }
                } elseif ($CWS < $params['CWS']) {
                    for ($i = $CWS; $i < $params['CWS']; $i++) { 
                        $insert_data = [
                            'FYID' => $ids,
                            'XH'   => '',
                            'NJ'   => '',
                            'YXDM' => '',
                            'CH'   => $i+1,
                            'status' => '0',
                        ];
                        $del_res = Db::name('dormitory_beds') -> insert($insert_data);
                    }
                } elseif ($CWS > $params['CWS']) {
                    for ($i = $CWS; $i > $params['CWS'] ; $i--) { 
                        $del_res = Db::name('dormitory_beds') -> where('FYID',$ids)-> where('CH',$i) -> delete();
                    }
                } elseif ($CWS == $params['CWS']) {
                    $del_res = true;                    
                }
            } 
            // 提交事务
            Db::commit();  
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        if ($del_res && $edit_res ) {
            return ['status' => true, 'msg' => '宿舍状态修改成功'];
        } else {
            return ['status' => false, 'msg' => "网络原因，修改失败"];
        }
    }

    /**
     * 删除宿舍功能
     * @param list param $ids
     * @return bool result
     */

    public function deleteRoom($ids,$admin_id)
    {
        Db::startTrans();
        $del_res = false;
        $delete_res = false;
        try{
            $oldRoomsList = Db::name('dormitory_rooms') -> where('ID',$ids) ->find();
            //删除rooms里数据
            $del_res = Db::name('dormitory_rooms') -> where('ID',$ids) -> delete();
            //查询表中原床位
            //记录至log
            $insert_log_data = [
                'admin_id' => $admin_id,
                'operate'  => 'delete',
                'LH'       => $oldRoomsList['LH'],
                'LC'       => $oldRoomsList['LC'],
                'SSH'      => $oldRoomsList['SSH'],
                'old_status' => $oldRoomsList['status'],
                'new_status' => '',
                'old_cws'  => $oldRoomsList['CWS'],
                'new_cws'  => '',
                'old_xbdm' => $oldRoomsList['XBDM'],
                'new_xbdm' => '',
                'old_gyfmc'    => $oldRoomsList['GYFMC'],
                'new_gyfmc'    => '',
                'timestamp'=> time(),
            ];
            $res_log = Db::name('dormitory_rooms_log')->insert($insert_log_data);
            //删除bed里数据
            $bedList = Db::name('dormitory_beds') -> where('FYID',$ids) -> select();
            if (empty($bedList)) {
                $delete_res = true;
            } else {
                $delete_res = Db::name('dormitory_beds') -> where('FYID',$ids) -> delete();
            }
            // 提交事务
            Db::commit();  
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        if ($del_res && $delete_res ) {
            return ['status' => true, 'msg' => '删除房间成功'];
        } else {
            return ['status' => false, 'msg' => "网络原因，删除失败"];
        }

    }


    /**
     * 插入调换宿舍记录
     * @param list param ['XH','CH','LH','SSH']
     * @param int adminid
     * @return array ['status' => true,'msg'=>'']
     */

    function addChangeRecord($param,$adminId)
    {
   
        $firstStuInfo = array();
        //获取学生信息
        $firstStuClassInfo = Db::name('stu_detail') -> where('XH',$param['oldXH']) -> field('YXDM,BJDM')->find();
        $firstStuInfo['old_dormitory'] = $param['oldLH'].'#'.$param['oldSSH'].'-'.$param['oldCH'];
        $firstStuInfo['new_dormitory'] = $param['newLH'].'#'.$param['newSSH'].'-'.$param['newCH'];
        $firstStuInfo['XH'] = $param['oldXH'];
        $firstStuInfo['remark'] = $param['remark'];
        $firstStuInfo['old_class'] = $firstStuClassInfo['BJDM'];
        $firstStuInfo['new_class'] = $firstStuClassInfo['BJDM'];
        $firstStuInfo['handle_time'] = strtotime($param['handletime']);
        $firstStuInfo['handle_end_time'] = '';
        $firstStuInfo['admin_id'] = $adminId;
        $firstStuInfo['operation'] = 'change';
        $firstStuInfo['reason'] = 'TS';
        $firstStuInfo['operate_time'] = time();

        $secondStuInfo = array();
        $secondStuClassInfo = Db::name('stu_detail') -> where('XH',$param['newXH']) -> field('YXDM,BJDM')->find();
        $secondStuInfo['new_dormitory'] = $param['oldLH'].'#'.$param['oldSSH'].'-'.$param['oldCH'];
        $secondStuInfo['old_dormitory'] = $param['newLH'].'#'.$param['newSSH'].'-'.$param['newCH'];
        $secondStuInfo['XH'] = $param['newXH'];
        $secondStuInfo['remark'] = $param['remark'];
        $secondStuInfo['old_class'] = $secondStuClassInfo['BJDM'];
        $secondStuInfo['new_class'] = $secondStuClassInfo['BJDM'];
        $secondStuInfo['handle_time'] = strtotime($param['handletime']);
        $secondStuInfo['handle_end_time'] = '';
        $secondStuInfo['admin_id'] = $adminId;
        $secondStuInfo['operation'] = 'change';
        $secondStuInfo['reason'] = 'TS';
        $secondStuInfo['operate_time'] = time();

        Db::startTrans();
        $first_insert_res = false;
        $second_insert_res = false;
        $first_add_res = false;
        $second_add_res = false;
        try{
            //第一步先把两条记录都插入到special表里
            $first_insert_res = Db::name('dormitory_special') -> insert($firstStuInfo);
            $second_insert_res = Db::name('dormitory_special') -> insert($secondStuInfo);

            //第二步把两个人对调
            $FYID_first = Db::name('dormitory_rooms') 
                    -> where('LH',$param['oldLH']) 
                    -> where('SSH',$param['oldSSH']) 
                    -> find()['ID'];

            $first_add_res = Db::name('dormitory_beds') 
                        -> where('FYID',$FYID_first)
                        -> where('CH',$param['oldCH'])
                        -> update([
                            'XH' => $param['newXH'],
                            'NJ' => substr($param['newXH'],0,4),
                            'YXDM' => $secondStuClassInfo['YXDM'],
                            'status' => 1,
                        ]);
            $FYID_second = Db::name('dormitory_rooms') 
                    -> where('LH',$param['newLH']) 
                    -> where('SSH',$param['newSSH']) 
                    -> find()['ID'];

            $second_add_res = Db::name('dormitory_beds') 
                        -> where('FYID',$FYID_second)
                        -> where('CH',$param['newCH'])
                        -> update([
                            'XH' => $param['oldXH'],
                            'NJ' => substr($param['oldXH'],0,4),
                            'YXDM' => $firstStuClassInfo['YXDM'],
                            'status' => 1,
                        ]);
            // $first_add_res = $this->where('LH',$param['oldLH']) 
            //                 -> where('SSH',$param['oldSSH'])
            //                 -> where('CH',$param['oldCH'])
            //                 -> update([
            //                     'XH' => $param['newXH'],
            //                     'NJ' => substr($param['newXH'],0,4),
            //                     'YXDM' => $secondStuClassInfo['YXDM'],
            //                     'status' => 1,
            //                 ]);

            // $second_add_res = $this->where('LH',$param['newLH']) 
            //                 -> where('SSH',$param['newSSH'])
            //                 -> where('CH',$param['newCH'])
            //                 -> update([
            //                     'XH' => $param['oldXH'],
            //                     'NJ' => substr($param['oldXH'],0,4),
            //                     'YXDM' => $firstStuClassInfo['YXDM'],
            //                     'status' => 1,
            //                 ]);
            Db::commit();  
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        if ($first_add_res == 1 && $first_insert_res == 1 && $second_add_res == 1 && $second_insert_res == 1) {
            return ['status' => true, 'msg' => '调换成功！'];
        } else {
            return ['status'=>false,'msg'=>'网络原因，调换失败！'];
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
