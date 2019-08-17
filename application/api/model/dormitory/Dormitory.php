<?php

namespace app\api\model\dormitory;

use think\Model;
use think\Db;
use think\Config;

class Dormitory extends Model
{

    /**
     * 填写家庭问卷信息
     */
    public function setinfo($param,$userInfo)
    {
        $exit_info = Db::name('fresh_questionnaire_base') -> where('XH', $userInfo['XH']) -> field('ID') -> find();
        if (!empty($exit_info)) {
            return ['status' => false, 'msg' => "信息已经完善", 'data' => null];
        } 
        $ZCYF = '';
        if (empty($param['form2'][7])) {
            $ZCYF = '';
        } else{
            foreach ($param['form2'][7] as $k => $v) {
                $ZCYF = $k == 0 ? $v : $ZCYF.",".$v;
            }
        }
        if ($param["form1"]['JTRKS'] == 0) {
            return ['status' => false, 'msg' => "这样子不太好哦！", 'data' => null];
        }
        $data['XH'] =   $userInfo['XH'];
        $data['SFGC'] = !empty($param["form1"]['SFGC']) ? $param["form1"]['SFGC'] : null;
        $data['RXQHK'] = !empty($param["form1"]['RXQHK']) ? $param["form1"]['RXQHK'] : null;
        $data['JTRKS'] = !empty($param["form1"]['JTRKS']) ? $param["form1"]['JTRKS'] : null;
        $data['YZBM'] = !empty($param["form1"]['YZBM']) ? $param["form1"]['YZBM'] : null;
        $data['SZDQ'] = !empty($param["form1"]['SZDQ_CN']) ? $param["form1"]['SZDQ_CN'] : null;
        $data['XXDZ'] = !empty($param["form1"]['XXDZ']) ? $param["form1"]['XXDZ'] : null;
        $data['BRDH'] = !empty($param["form1"]['BRDH']) ? $param["form1"]['BRDH'] : null;
        $data['BRQQ'] = !empty($param["form1"]['QQ']) ? $param["form1"]['QQ'] : null;
        $data['ZP'] =  !empty($param["form1"]['ZP'][0]['url']) ? $param["form1"]['ZP'][0]['url'] : 'http://';
        $data['ZSR'] = !empty($param["form1"]['ZSR']) ? $param["form1"]['ZSR'] : 0;
        //$data['RJSR'] = $RJSR;
        if (empty($param['form2'][0]) ||empty($param['form2'][1]) ||empty($param['form2'][2]) ||empty($param['form2'][3]) ||empty($param['form2'][4]) ||empty($param['form2'][5]) ||empty($param['form2'][6]) ) {
            return ['status' => false, 'msg' => "请完成家庭经济情况调查", 'data' => null];
        } 
        $data['FQZY']   = $param['form2'][0][0];
        $data['MQZY']   = $param['form2'][1][0];
        $data['FQLDNL'] = $param['form2'][2][0];
        $data['MQLDNL'] = $param['form2'][3][0];
        $data['YLZC']   = $param['form2'][4][0];
        $data['SZQK']   = $param['form2'][5][0];
        $data['JTBG']   = $param['form2'][6][0];
        $data['ZCYF']   = $ZCYF; 
        if (empty($param["form1"]['member']) || empty($param["form1"]['member'][0]) ) {
            $family_info = array();
            $info_family = array();
        } else {
            $info_family = array(); 
            $family_info = array();
            foreach ($param["form1"]['member'] as $k => $v) {
                //这里income不是纯数字的时候还存在bug
                $data['ZSR'] += $v["income"];
                $family_info = array(
                    'XH'   => $userInfo['XH'],
                    'XM'   => $v['name'],
                    'NL'   => $v['age'],
                    'GX'   => $v['relation'],
                    'GZDW' => $v['unit'],
                    'ZY'   => $v['job'],
                    'NSR'  => $v['income'],
                    'JKZK' => $v['health'],
                    'LXDH' => $v['mobile'],
                );
                $info_family[] = $family_info;
            }
        }
        return ['status' => true, 'msg' => "填写成功", 'data' => $data, 'info' => $info_family];
        
    }
    /**
     * 返回学生家庭信息基本信息
     */
    public function getBaseInfo($param)
    {
        $XH = $param["XH"];
        // $XH = "2019901872";
        $userInfo = Db::name("fresh_questionnaire_base")->where("XH",$XH)->find();
        $familyInfo = Db::name("fresh_questionnaire_family")->where("XH",$XH)->select();
        if (empty($userInfo)) {
            return ["status" => false, "msg" => "未填写家庭信息调查表","data"=>null];
        } elseif (empty($familyInfo)) {
            unset($userInfo["ID"]);
            $userInfo["JTRKS"] = 1; 
            $userInfo["family"] = [];
            return ["status" => true, "msg" => "获取成功","data"=> $userInfo];
        } else {
            foreach ($familyInfo as $key => &$value) {
                unset($value["ID"]);
            }
            $userInfo["family"] = $familyInfo;
            return ["status" => true, "msg" => "获取成功","data"=> $userInfo];
        }
    }

    /**
     * 将学生原先数据挪到备份数据表中，并删除之前的数据。
     */
    public function setBaseInfo($param)
    {
        $XH = $param["XH"];
        // $XH = "2019900265";
        $userInfo = Db::name("fresh_questionnaire_base")->where("XH",$XH)->find();
        $familyInfo = Db::name("fresh_questionnaire_family")->where("XH",$XH)->select();
        $recheck   = Db::name("fresh_questionnaire_base_backup")->where("XH",$XH)->find();
        //区分是否已经重填过
        if (empty($recheck)) {
            //区分是否填写过家庭问卷
            if (!empty($userInfo)) {
                $insert_flag1 = false;
                $insert_flag2 = false;
                $delete_flag1  = false;
                $delete_flag2  = false;
                $idTemp  = [];
                Db::startTrans();
                try{   
                    $delete_flag1  = Db::name("fresh_questionnaire_base")->delete($userInfo["ID"]);
                    unset($userInfo["ID"]);
                    $insert_flag1 = Db::name("fresh_questionnaire_base_backup")->insert($userInfo);
                    if (!empty($familyInfo)) {
                        foreach ($familyInfo as $key => &$value) {
                            $idTemp[$key] = $value["ID"]; 
                            unset($value["ID"]);
                        }
                        $delete_flag2 =  Db::name("fresh_questionnaire_family")->delete($idTemp);
                        $insert_flag2 = Db::name("fresh_questionnaire_family_backup")->insertAll($familyInfo);
                    } else {
                        $delete_flag2 =  true;
                        $insert_flag2 = true;
                    }
                    //提交事务
                    Db::commit();      
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }    

                if ($insert_flag1 && $insert_flag2 && $delete_flag1 && $delete_flag2) {
                    return ["status" => true, "msg" => "success","data"=>null];
                } else {
                    return ["status" => false, "msg" => "error","data"=>null];
                }
            } else {
                return ["status" => false, "msg" => "未填写家庭信息问卷","data"=>null];
            }
        } else {
            return ["status" => false, "msg" => "重填次数已用光！","data"=>null];
        }
    }


    /**
     * 向fa_fresh_questionnaire_base中插入数据
     */
    public function insertBase($data)
    {
        $res = Db::name("fresh_questionnaire_base") ->insert($data);
        $response  = Db::name("fresh_info") -> where("XH",$data["XH"]) -> update(["QQ" => $data["BRQQ"],"LXDH" => $data["BRDH"]]);

        return $res;
    }  
    
    /**
     * 向fa_fresh_questionnaire_family中插入数据
     */
    public function insertFamily($data)
    {
        $res = Db::name("fresh_questionnaire_family") ->insertAll($data);
        return $res;

    }



    public function room($userInfo)
    {
        $returnData = ["list" => []];
        if($userInfo["type"] == 0) {
            $college = $userInfo["YXDM"];
            $roomList = Db::name("fresh_dormitory_north")
                    ->where("YXDM",$college)
                    ->where("XB",$userInfo["XBDM"])
                    ->field("SSH,CPXZ,SYRS,LH")
                    ->select();
            $temp = [];
            if ($userInfo["step"]["step"] == "NST") {
                foreach ($roomList as $key => $value) {
                    $LH = $value["LH"];
                    $temp[$LH][] = [
                        "name"    => $value["SSH"],
                        "value"   => $value["SSH"],
                        // "selected"=> strlen($value["CPXZ"])-$value["SYRS"]."/".strlen($value["CPXZ"]),
                        "selected"=> "0/".strlen($value["CPXZ"]),
                        "free"    => $value["SYRS"] == 0 ? false : true, 
                    ];
                }
            } else {
                foreach ($roomList as $key => $value) {
                    $LH = $value["LH"];
                    $temp[$LH][] = [
                        "name"    => $value["SSH"],
                        "value"   => $value["SSH"],
                        "selected"=> strlen($value["CPXZ"])-$value["SYRS"]."/".strlen($value["CPXZ"]),
                        "free"    => $value["SYRS"] == 0 ? false : true, 
                    ];
                }
            }
            $keyArray = array_keys($temp);
            foreach ($keyArray as $k => $v) {
                $newArray = [
                    "name"  => $v."号楼",
                    "value" => $v,
                    "room"  => $temp[$v],
                ];
                $returnData["list"][] = $newArray;
            }
        } else {
            // $roomList = Db::name("fresh_dormitory_south")->where("YXDM",$college)->select();
        }
        return ["status" => true,"msg" =>null,"data" => $returnData];
    }

    /**
     * 查询床位状态
     * @param int $key["building"]
     * @param int $key["room"]
     * @param array $userInfo
     */
    public function bed($param,$userInfo)
    {
        if (empty($param["building"]||empty($param["room"]))) {
            return ["status"  => false, "msg" => "param error!" , "data" => null];
        } 
        //本科生
        $XQ        = $userInfo['XQ'];
        $building  = $param["building"];
        $dormitory = $param["room"];
        $allCount = Db::name("fresh_dormitory_".$XQ) 
                        // -> where("LH",$building)
                        // -> where("SSH",$dormitory)
                        -> where("SSDM",$building."#".$dormitory)
                        -> where("YXDM",$userInfo["YXDM"])
                        -> field("CPXZ")
                        -> find();
        // dump($this->getLastSql());
        if (empty($allCount)) {
            return ["status"  => false, "msg" => "info error!" , "data" => null];            
        }
        $roommate = Db::view("fresh_result","XQ,SSDM,XH,CH")
                -> view("fresh_info","XH,XM,avatar","fresh_result.XH = fresh_info.XH")
                -> where("XQ",$XQ)
                -> where("SSDM",$building."#".$dormitory)
                // -> where("status","finished")
                -> select();
        // dump($this->getLastSql());
        $length = strlen($allCount["CPXZ"]);
        $list = [];
        if ($length == 4) {
            for ($i=0; $i < 4; $i++) { 
                $temp = [
                    "type"      => "上床下桌",
                    "disabled"  => $allCount["CPXZ"][$i] == 0 ? true : false,
                    // "isfreshbed"=> $allCount["CPXZ"][$i] == 0 ? false : true,
                    "value"     => $i+1,
                    "name"      => ($i+1)."床",
                    // "avatar"    => "#icon-default",
                ];
                $list[$i] = $temp;
                if ($i + 1 == 1) {
                    $list[$i]["type"] = "上床下桌(靠门)";                    
                } elseif ($i+1==2) {
                    $list[$i]["type"] = "上床下桌(靠窗)";
                } elseif ($i+1==3) {
                    $list[$i]["type"] = "上床下桌(靠窗)";
                } elseif ($i+1==4) {
                    $list[$i]["type"] = "上床下桌(靠门)";
                }
            }
            //若未开始则。。。
            if ($userInfo["step"]["step"] == "NST") {
                foreach ($roommate as $key => $value) {
                    $k = $value["CH"] - 1;
                    $list[$k]["disabled"]   = false;
                }
            } else {
                foreach ($roommate as $key => $value) {
                    $k = $value["CH"] - 1;
                    $list[$k]["disabled"]   = true;
                    $list[$k]["user"]   = mb_substr($value['XM'], 0, 1, 'utf-8').'**';
                    $list[$k]["avatar"] = $value['avatar'];
                }
            }
        } else {
            for ($i=0; $i < 6; $i++) { 
                $temp = [
                    "type"      => "上床下柜",
                    "disabled"  => $allCount["CPXZ"][$i] == 0 ? true  :  false,
                    "value"     => $i+1,
                    "name"      => ($i+1)."床",
                    // "avatar"    => "#icon-default",
                ];
                $list[$i] = $temp;
                if ($i + 1 == 1) {
                    $list[$i]["type"] = "上床下柜(靠门)";
                } elseif ($i + 1 == 2) {
                    $list[$i]["type"] = "上床下柜(靠窗)";
                } elseif ($i + 1== 3) {
                    $list[$i]["type"] = "上铺(靠窗)";
                } elseif ($i+1==4) {
                    $list[$i]["type"] = "下铺(靠窗)";
                } elseif ($i+1==5) {
                    $list[$i]["type"] = "上铺(靠门)";
                } elseif ($i+1==6) {
                    $list[$i]["type"] = "下铺(靠门)";
                }
            }
            if ($userInfo["step"]["step"] == "NST") {
                foreach ($roommate as $key => $value) {
                    $k = $value["CH"] - 1;
                    $list[$k]["disabled"]   = false;
                }
            } else {
                foreach ($roommate as $key => $value) {
                    $k = $value["CH"] - 1;
                    $list[$k]["disabled"]   = true;
                    $list[$k]["user"]     = mb_substr($value['XM'], 0, 1, 'utf-8').'**';
                    $list[$k]["avatar"]   = $value['avatar'];
                }
            }
        }

        $return = ["status"  => true, "msg" => "" , "data" => ["list" => $list]];
        return $return;
    }

    /**
     * 提交数据
     * @param string $param["building"]
     * @param int    $param["room"]
     * @param int    $param["bed"]
     */
    public function submit($param, $userInfo)
    {
        // if ($steps != 'select') {
        //     return ['status' => false, 'msg' => "执行顺序出错", 'data' => null];
        // } else {
            $stu_id       = $userInfo['XH'];
            $college_id   = $userInfo['YXDM'];
            $sex          = $userInfo['XBDM'];
            $place        = $userInfo['SYD'];
            $nation       = $userInfo['MZ'];
            $building     = $param["building"];
            $room         = $param['room'];
            $bed          = $param['bed'];
            $XQ           = $userInfo['XQ'];
            $dormitory_id = "$building#$room";
            $CWDM         = "$XQ-$dormitory_id-$bed";
            //判断提交的宿舍数据是否合法
            if (!$this->checkDormitory($building,$room,$bed,$userInfo)) {
                return ['status' => false, 'msg' => "数据有误！", 'data' => null];            
            }
            //判断提交时间是否合法
            $checkResult = $this->checkTime($userInfo);
            if (!$checkResult["status"]) {
                return ['status' => false, 'msg' => $checkResult["msg"], 'data' => null];            
            }

            //如果是少数民族验证要选的宿舍是否满足要求
            if ($nation != "汉族") {
                $msg = $this -> checkNation($XQ,$dormitory_id, $nation);
                if (!$msg) {
                    return ['status' => false, 'msg' => "不符合学校相关住宿规定，无法选择该宿舍！", 'data' => null];
                }
            }
            //如果不是陕西省的学生，则需要判断该宿同省人数
            if ($place != "陕西") {
                $msg = $this -> checkPlace($XQ,$dormitory_id, $place);
                if (!$msg) {
                    return ['status' => false, 'msg' => "不符合学校相关住宿规定，无法选择该宿舍！", 'data' => null];                    
                }
            }

            if (empty($key['origin'])) {
                $origin = 'selection';
            } else {
                $origin = 'system';
            }

            $data = Db::name('fresh_result')->lock(true) -> where('XH', $stu_id) ->field('ID') ->find();
            if(empty($data)){
                $insert_flag = false;
                $update_flag = false;
                Db::startTrans();
                try{                    
                    // 第一步，将记录写进fresh_result表中
                    $insert_flag = Db::name('fresh_result') -> insert([
                        'XH'     => $stu_id,
                        'SSDM'   => $dormitory_id,
                        'XQ'     => $XQ,
                        'CH'     => $bed,
                        'CWDM'   => $CWDM,
                        'YXDM'   => $college_id,
                        'SDSJ'   => time(),
                        'origin' => $origin,
                        'status' => 'waited', 
                    ]);
                    //第二步，将frsh_dormitory中对于宿舍，剩余人数-1，宿舍选择情况更新
                    $list = Db::name("fresh_dormitory_".$XQ)
                                -> where('YXDM',$college_id)
                                -> where('SSDM', $dormitory_id)
                                -> field('CPXZ, SYRS, ID')
                                -> find();          
                    if (empty($list)) {
                        return ['status' => false, 'msg' => "未发现所选宿舍", 'data' => null];
                    }
                    $rest_num = $list['SYRS'] - 1;
                    // 宿舍总人数
                    $length = strlen($list['CPXZ']);
                    // 核查床位是否被选过
                    if ( $list['CPXZ'][$bed - 1] == 0 ) {
                        //说明该床位已经被选过
                        return ['status' => false, 'msg' => "该床位已经被选了", 'data' => null];
                    } else {
                        //指数
                        $exp = (int)$length - (int)$bed;
                        $sub = pow(10, $exp);
                        $choice = (int)$list['CPXZ'] - $sub;
                        $choice = sprintf("%0".$length."d", $choice);
                        $choice = (string)$choice;
                        $update_flag =  Db::name("fresh_dormitory_".$XQ)
                                        -> where('ID', $list['ID'])
                                        -> update([
                                            'SYRS' => $rest_num,
                                            'CPXZ' => $choice,
                                        ]);                        
                        //提交事务
                        Db::commit();      
                    }
                
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }    
                if($insert_flag == 1 && $update_flag == 1){
                    return ['status' => true, 'msg' => "成功选择宿舍", 'data' => null];
                }else{
                    return ['status' => false, 'msg' => "未成功选择", 'data' => null];
                }
            } else {
                return ['status' => false, 'msg' => "你已经选择过宿舍", 'data' => null];
            }
    
    }

    /**
     * 确认所选宿舍
     * @param $param["type"] confirm|cancel
     */
    public function confirm($param, $userInfo)
    {
        // if ($steps != 'waited') {
        //     return ['status' => false, 'msg' => "执行顺序出错", 'data' => null];
        // } else {
            $stu_id     = $userInfo['XH'];
            $college_id = $userInfo['YXDM'];
            $sex        = $userInfo['XBDM'];
            $place      = $userInfo['SYD'];
            $type       = $param['type'];
            $XQ         = $userInfo['XQ'];
            switch ($type) {
                case 'confirm':
                    //判断是否超时
                    $get_msg = Db::name('fresh_result') -> where('XH', $stu_id) -> where('status', 'waited') ->find();
                    if (empty($get_msg)) {
                        return ['status' => false, 'msg' => "不存在需要确认的宿舍订单", 'data' => null];
                    } else {     
                        $dormitory_id = $get_msg['SSDM'];
                        $bed_id       = $get_msg['CH'];
                        $old_time     = $get_msg['SDSJ'];
                        $now_time     = time();
                        $second = $now_time - $old_time;
                        if ( $second >= 3600) {
                            // 启动事务
                            Db::startTrans();  
                            try{
                                $insertCancelData = [
                                    "XH"    =>  $get_msg["XH"],
                                    "XQ"    =>  $get_msg["XQ"],
                                    "SSDM"  =>  $get_msg["SSDM"],
                                    "CH"    =>  $get_msg["CH"],
                                    "YXDM"  =>  $get_msg["YXDM"],
                                    "SDSJ"  =>  $get_msg["SDSJ"],
                                    "CZSJ"  =>  time(),
                                    "origin"=>  $get_msg["origin"],
                                    "status"=>  "timeover",
                                ];
                                // 第一步 把取消的选择插入特殊列表
                                $insert_exception = Db::name('fresh_cancel') -> insert($insertCancelData);
                                // 第二步 将原先锁定的数据删除
                                $delete_list = Db::name('fresh_result') -> where('XH', $stu_id)->delete();
                                // 第三步 把该宿舍的剩余人数以及床铺选择情况更新
                                $list = Db::name("fresh_dormitory_".$XQ) 
                                            -> where('YXDM',$college_id)
                                            -> where('SSDM', $dormitory_id)
                                            -> field('SYRS,CPXZ,ID')
                                            -> find();
                                $rest_num = $list['SYRS'] + 1;
                                //宿舍总人数
                                $length = strlen($list['CPXZ']);
                                //指数
                                $exp = (int)$length - (int)$bed_id;
                                $sub = pow(10, $exp);
                                $choice = (int)$list['CPXZ'] + $sub;
                                $choice = sprintf("%0".$length."d", $choice);
                                $choice = (string)$choice;
                                $update_flag =  Db::name("fresh_dormitory_".$XQ) 
                                                    -> where('ID', $list['ID'])
                                                    -> update([
                                                        'SYRS' => $rest_num,
                                                        'CPXZ' => $choice,
                                                    ]);
                                // 提交事务
                                Db::commit();  
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                            }
                            if ( $insert_exception == 1 && $delete_list == 1) {
                                return ['status' => false, 'msg' => "规定时间内未确认！", 'data' => null];
                            } else {
                                return ['status' => false, 'msg' => "服务器出错了哦！", 'data' => null];                                
                            }   
                        } else {
                            $update_status = Db::name('fresh_result') -> where('XH', $stu_id)->update(['status' => 'finished']);
                            if ($update_status == 1) {
                                return ['status' => true, 'msg' => "宿舍确认成功！", 'data' => null];                                
                            } else {
                                return ['status' => false, 'msg' => "服务器出了点问题哦！", 'data' => null];                                
                            }
                        }
                        break;
                    }
                
                case 'cancel':     
                    $data_in_list = Db::name('fresh_result') -> where('XH', $stu_id) -> field('ID,CH,SSDM') -> find();
                    if (empty($data_in_list)) {
                       return ['status' => false, 'msg' => "尚未申请宿舍", 'data' => null];                                
                    } else {
                        $update_flag      = false;
                        $insert_exception = false;
                        $dormitory_id     = $data_in_list['SSDM'];
                        $bed_id           = $data_in_list['CH'];
                        $delete_list      = false;
                        // 启动事务
                        Db::startTrans();            
                        try{
                            $data = Db::name('fresh_result') -> where('XH', $stu_id) ->find();
                            $data['status'] = 'cancelled';
                            $data['CZSJ'] = time();
                            unset($data["CWDM"]);
                            unset($data['ID']);
                            unset($data['latitude']);
                            unset($data['longitude']);
                            //第一步 把取消的选择插入特殊列表
                            $insert_exception = Db::name('fresh_cancel') -> insert($data);  
                            //第二步 将原先锁定的数据删除
                            $delete_list = Db::name('fresh_result') -> where('XH', $stu_id)->delete();
                            //第三步 把该宿舍的剩余人数以及床铺选择情况更新
                            $list = Db::name("fresh_dormitory_".$XQ) 
                                        -> where('YXDM',$college_id)
                                        -> where('XB',$sex)
                                        -> where('SSDM', $dormitory_id)
                                        -> field('SYRS,CPXZ,ID')
                                        -> find();
                            $rest_num = $list['SYRS'] + 1;
                            //宿舍总人数
                            $length = strlen($list['CPXZ']);
                            //指数
                            $exp = (int)$length - (int)$bed_id;
                            $sub = pow(10, $exp);
                            $choice = (int)$list['CPXZ'] + $sub;
                            $choice = sprintf("%0".$length."d", $choice);
                            $choice = (string)$choice;    
                            $update_flag =  Db::name("fresh_dormitory_".$XQ)  
                                            -> where('ID', $list['ID'])
                                            -> update([
                                                'SYRS' => $rest_num,
                                                'CPXZ' => $choice,
                                            ]);
                            // 提交事务
                            Db::commit();  
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        }
                        if ( $insert_exception == 1 && $delete_list == 1 && $update_flag == 1) {
                            return ['status' => true, 'msg' => "已经成功取消", 'data' => null];                                
                        } else {
                            return ['status' => false, 'msg' => "服务器出了点问题哦！", 'data' => null];                                
                        }   
                    }    
                    break;
                default:
                    return ['status' => false, 'msg' => '参数有误', 'data' => null];
                    break;
            }
       // }
    }


    /**
     * 标记宿舍
     * @param string $param["action"] mark||get | unmark
     * @param string $param["building"]
     * @param string $param["room"] 
     * @param string $param["bed"] 
     */

    public function mark($param,$userInfo)
    {
        if (empty($param["building"]) || empty($param["room"])|| empty($param["bed"]) || empty($param["action"]) ) {
            return ["status" => false ,"msg" => "param error!", "data" => null];
        }
        $SSDM       = $param["building"]."#".$param["room"];
        $CH         = $param["bed"];
        $college_id = $userInfo["YXDM"];
        $stu_id     = $userInfo["XH"];
        $XQ         = $userInfo["XQ"];


        if ($param["action"] == "mark") {

            $markCheck = Db::name("fresh_mark")->where("XH",$stu_id)->field("SSDM,CH")->find();

            if (empty($markCheck)) {
                $insertData = [
                    "XH"    => $stu_id,
                    "XQ"    => $XQ,
                    "SSDM"  => $SSDM,
                    "CH"    => $CH,
                    "YXDM"  => $college_id,
                    "BJSJ"  => time(),
                ];
                $insert_flag = Db::name("fresh_mark") -> insert($insertData);
                if ($insert_flag) {
                    return ["status" => true, "msg" => "标记成功", "data" => null];
                } else {
                    return ["status" => false, "msg" => "网络错误，请稍后再试", "data" => null];
                }
            } else {
                $updateData = [
                    "XQ"    => $XQ,
                    "SSDM"  => $SSDM,
                    "CH"    => $CH,
                    "YXDM"  => $college_id,
                    "BJSJ"  => time(),
                ];
                $update_flag = Db::name("fresh_mark") ->where("XH",$stu_id)-> update($updateData);
                if ($update_flag) {
                    return ["status" => true, "msg" => "标记成功", "data" => null];
                } else {
                    return ["status" => false, "msg" => "网络错误，请稍后再试", "data" => null];
                }
            }

        } elseif ($param["action"] == "get") {
            $markList = Db::view("fresh_mark","XH")
                        -> view("fresh_info","XH,XM,avatar","fresh_mark.XH = fresh_info.XH")
                        -> where("XQ",$XQ)
                        -> where("SSDM",$SSDM)
                        -> where("CH",$CH)
                        -> select();
            
            $list = [];
            $myMark = false;
            foreach ($markList as $key => $value) {
                if ($value["XH"] == $stu_id) {
                    $myMark = true;
                }
                $temp = [
                    "XH"    => $value["XH"],
                    "name"  => mb_substr($value['XM'], 0, 1, 'utf-8').'**',
                    "avatar"=> $value["avatar"],
                ];
                $list[] = $temp;
            }
            return ["status" => true, "msg" => "查询成功","data" => ["list" => $list,"me" => $myMark]];


        } elseif ($param["action"] == "unmark") {
            $delete_flag = Db::name("fresh_mark")->where("XH",$stu_id)->delete();
            if ($delete_flag) {
                return ["status" => true, "msg" => "取消成功", "data" => null];
            } else {
                return ["status" => false, "msg" => "网络错误，请稍后再试", "data" => null];
            }
        }
    }



    /**
     * 查看室友信息
     * @param array userInfo
     */
    public function roommates($userInfo)
    {
        $XQ = $userInfo["XQ"];
        $XH = $userInfo["XH"];
        $personalResult = Db::name("fresh_result","SSDM,status")
                        -> where("XQ",$XQ)
                        -> where("XH",$XH)
                        -> find();
        if (empty($personalResult)) {
            return ["status" => false,"msg" => "未选择宿舍", "data" => null];
        } else {
            $SSDM = $personalResult["SSDM"];
            $listStatus = $personalResult["status"];
            $roommateList = Db::view("fresh_result","CH,XH")
                            -> view("fresh_info","XH,XM,QQ,avatar","fresh_result.XH = fresh_info.XH")
                            -> where("XH","<>",$XH)
                            -> where("XQ",$XQ)
                            -> where("SSDM",$SSDM)
                            -> select();
            $returnList = [];
            if ($listStatus == "waited") {
                foreach ($roommateList as $key => $value) {
                    $temp = [
                        "CH"     => $value["CH"]."号床",
                        "QQ"     => empty($value["QQ"]) ? "未填写" : $value["QQ"],
                        "XM"     => mb_substr($value['XM'], 0, 1, 'utf-8').'**',
                        "avatar" => $value["avatar"],
                    ];
                    $returnList[] = $temp;
                }
                return ["status" => true, "msg"=>"查询成功","data" => ["roommate" => $returnList] ];
            } elseif ($listStatus == "finished") {
                foreach ($roommateList as $key => $value) {
                    $temp = [
                        "CH"     => $value["CH"]."号床",
                        "QQ"     => empty($value["QQ"]) ? "未填写" : $value["QQ"],
                        "XM"     => $value['XM'],
                        "avatar" => $value["avatar"],
                    ];
                    $returnList[] = $temp;
                }
                return ["status" => true, "msg"=>"查询成功","data" => ["roommate" => $returnList] ];
            }
        }
    }

    /**
     * 用来验证民族选择情况
     */
    private function checkNation($XQ,$dormitory_id, $nation){
        $place_number = Db::view('fresh_result',"ID,XH") 
                        -> view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_result.XH = fresh_info.XH')
                        -> where("XQ",$XQ)
                        -> where('SSDM', $dormitory_id) 
                        -> where('MZ','LIKE',$nation)
                        -> count();
        if ($place_number >= 1) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * 用来验证生源地选择情况
     */
    private function checkPlace ($XQ,$dormitory_id, $place) {
        $place_number = Db::view('fresh_result',"XH") 
                        -> view('fresh_info', 'XM, XH, SYD', 'fresh_result.XH = fresh_info.XH')
                        -> where("XQ",$XQ)
                        -> where('SSDM', $dormitory_id) 
                        -> where('SYD','LIKE', $place)
                        -> count();
        if ($place_number >= 2) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * 获取用户当前步骤
     * @param string XH
     * @return string
     */

    private function getSteps($userXh){
        //判断信息是否完善
        $isInfoExist = Db::name('fresh_questionnaire_base') -> where('XH', $userXh) -> field('ID,XH') -> find();
        $isListExist = Db::name('fresh_result') -> where('XH', $userXh) -> field('ID,XH,status') -> find();
        if (empty($isInfoExist)) {
            return 'QUE';
        } elseif (empty($isListExist)) {
            return 'SEL';
        } elseif ($isListExist['status'] == "waited"){
            return "CON";
        } else {
            return "FIN";
        }
        
    }

    /**
     * 检查提交的选宿舍数据
     * @param int building
     * @param int room
     * @param int bed
     * @param array $userInfo
     * @return bool
     */
    private function checkDormitory($building,$room,$bed,$userInfo)
    {
        $check = Db::name("fresh_dormitory_".$userInfo["XQ"])
                -> where("LH",$building)
                -> where("SSH",$room)
                -> where("YXDM",$userInfo["YXDM"])
                -> where("XB",$userInfo["XBDM"])
                -> where("SYRS",">",0)
                -> field("ID")
                -> find();
        return empty($check) ? false : true;
    }
    
    /**
     * 检查用户提交的时间是否在规定时间内
     * @param array userInfo
     */
    private function checkTime($userInfo)
    {
        if ($userInfo["step"]["step"] == "NST") {
            return ["status" => false,"msg" => "选宿尚未开始！"];
        } elseif ($userInfo["step"]["step"] == "END") {
            return ["status" => false,"msg" => "选宿舍已结束！"];
        } else {
            return ["status" => true, "msg" => ""];
        }
    }


}