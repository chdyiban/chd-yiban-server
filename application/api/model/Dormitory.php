<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;

class Dormitory extends Model
{
    // 表名
    protected $name = 'fresh_dormitory';
    /**
     *  初始化方法，依据用户所处的位置不同，返回相应的数据
     */
    public function initSteps($steps, $userinfo)
    {
        $stuid = $userinfo['stu_id'];
        $info = array();
        switch ($steps) {
            //不需要重复返回个人信息
            //第一步，输入自己信息
            // case 'setinfo':
            //     break;
            //第二步，选房阶段
            //16 => [101 => [1,2], 102],
            //15 => 

            //返回楼号以及房间数和床位数
            case 'select':
                $college_id = $userinfo['college_id'];
                $sex = $userinfo['XBDM'];
                $msg = $this -> showBuilding($college_id, $sex);
                return $msg;
                break;
            //第三步, 订单已经提交等待确认
            case 'waited':
                $data = Db::name('fresh_list') 
                        -> where('XH', $stuid) 
                        -> where('status','waited') 
                        -> find();
                if (empty($data)) {
                    return ['status' => false, 'msg' => '不存在需要确认的订单', 'data' => null];
                } else {
                    //新增判断订单是否超时
                    $stu_id = $userinfo['stu_id'];
                    $college_id = $userinfo['college_id'];
                    $sex = $userinfo['XBDM'];
                    $place = $userinfo['place'];
                    $dormitory_id = $data['SSDM'];
                    $bed_id = $data['CH'];
                    $old_time = $data['SDSJ'];
                    $now_time = time();
                    $second = $now_time - $old_time;
                    if ( $second >= 1800) {
                        // 启动事务
                        Db::startTrans();
                        try{
                            $data['status'] = 'timeover';
                            $data['CZSJ'] = time();
                            unset($data['ID']);
                            // 第一步 把取消的选择插入特殊列表
                            $insert_exception = Db::name('fresh_exception') -> insert($data);
                            // 第二步 将原先锁定的数据删除
                            $delete_list = Db::name('fresh_list') -> where('XH', $stu_id)->delete();
                            // 第三步 把该宿舍的剩余人数以及床铺选择情况更新
                            $list = $this -> where('YXDM',$college_id)
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
                            $update_flag = $this -> where('ID', $list['ID'])
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
                            return ['status' => false, 'msg' => "服务器出了点问题哦！", 'data' => null];                                
                        }   
                    } else {
                        $info['XH'] = $stuid;
                        $dor = explode("#", $data['SSDM']);
                        $info['LH'] = $dor[0];
                        $info['SSH'] = $dor[1];
                        $info['CH'] = $data['CH'];
                        $info['start_time'] = date('Y-m-d H:i:s', $data['SDSJ']);
                        $info['end_time'] = date('Y-m-d H:i:s', $data['SDSJ'] + 1800);
                        return ['status' => true, 'msg' => '查询成功', 'data' => $info];   
                    }
                }
                break;
            //第四步，所有工作都已结束
            case 'finished':
                $info = $this -> finished($userinfo);
                return $info;
                break;

        }
    }
    /**
     *  该方法一次性返回宿舍楼以及对应的可以选择的宿舍号
     *  已经确定使用分次请求的方法，该方法失效
     */
    // private function showAll($info)
    // {
    //     $msg = array();
    //     $college_id = $info['college_id'];
    //     $sex = $info['XBDM'];
    //     $place = $info['place'];
    //     $nation = $info['nation'];
    //     $data = Db::name('fresh_dormitory') 
    //                     -> where('YXDM',$college_id)
    //                     -> where('SYRS','>','0')
    //                     -> where('XB',$sex)
    //                     -> select();
        
    //     foreach ($data as $v) {
    //         $building = $v['LH'];
    //         $dormitory = $v['SSH'];
    //         $list = $this -> getBedNum($sex,$college_id, $building, $dormitory);
    //         $msg[$building][$dormitory] = $list;
    //     }
    //     return $msg;
    // }

    /**
     * 返回可选择宿舍楼以及宿舍号以及剩余人数
     */
    public function show($info, $key)
    {
        $list = [];
        $college_id = $info['college_id'];
        $sex = $info['XBDM'];
        $place = $info['place'];
        $nation = $info['nation'];
        $type = $key['type'];
        switch ($type) {
            //需要宿舍号
            case 'dormitory':
                if (empty($key['building'])) {
                    return ['status' => false, 'msg' => "参数有误", 'data' => null];
                }else{
                   $result = $this -> showDormitory($college_id, $sex, $key['building']);
                   return $result;
                }
                break;
            //需要床号
            case 'bed':
                if (empty($key['dormitory'] || empty($key['building']))) {
                    return ['status' => false, 'msg' => "参数有误", 'data' => null];
                }else{
                    $result = $this -> showBed($college_id, $sex, $key['building'] , $key['dormitory'], $place,$nation);
                    return $result;
                }
                break;
            default:
                return ['status' => false, 'msg' => '参数有误', 'data' => null];
                break;
            }
    }

    /**
     * show方法中返回宿舍楼号模块
     */
    private function showBuilding($college_id, $sex)
    {
        $list = [];
        $data = $this -> where('YXDM',$college_id)
                    -> where('XB', $sex)
                    -> group('LH')
                    -> field('LH')
                    -> select();
        foreach ($data as $key => $value) {
            
            $build = $value->toArray()['LH'];
            if ($build <= 6 && $build > 0) {
                $info = array(
                    'name' =>  $build."号楼（西区）",
                    'value' => $build,
                );   
            } elseif ($build <= 15) {
                $info = array(
                    'name' =>  $build."号楼（东区）",
                    'value' => $build,
                );   
            } elseif ( $build <= 20) {
                $info = array(
                    'name' =>  $build."号楼（高层）",
                    'value' => $build,
                );   
            }
            $list[] = $info;
        }
        $dormitory_info = $this -> where('SYRS','>=','1') 
                                -> where('XB',$sex)
                                -> where('YXDM',$college_id)
                                -> field('SYRS')
                                -> select();
        //$dormitory_number = count($dormitory_info);
        $dormitory_number = 0;
        $bed_number = 0;
        foreach ($dormitory_info as $key => $value) {
            $bed_number += $value->toArray()['SYRS'];
            $dormitory_number += 1;
        }
        return ['status' => true, 'msg' => "查询成功", 'data' => $list, 'dormitory_number' => $dormitory_number, 'bed_number' => $bed_number];
    }
    /**
     * show方法中返回宿舍号
     */
    private function showDormitory($college_id, $sex, $building)
    {
        //return ['status' => false, 'msg' => $college_id, 'data' => null];
        $data = $this -> where('YXDM',$college_id)
                    -> where('XB', $sex)
                    -> where('LH', $building)
                    -> where('SYRS','>=', 1)
                    -> field('SYRS, SSH')
                    -> select();
        if (empty($data)) {
            return ['status' => false, 'msg' => $building.'号楼已经没有空宿舍了', 'data' => null];
        }
        foreach ($data as $key => $value) {
            $rest_num = $value -> toArray()['SYRS'];
            $list[$key]['name'] = $value -> toArray()['SSH'].'（剩余床位：'.$rest_num.'）';
            $list[$key]['value'] =  $value -> toArray()['SSH'];
        }
        return ['status' => true, 'msg' => "查询成功", 'data' => $list];
    }
    /**
     * show方法返回床号以及验证该宿舍是否可选
     */
    private function showBed($college_id,$sex,$building,$dormitory, $place, $nation)
    {
        $SSDM = (string)$building.'#'.$dormitory;
        //判断该宿舍少数民族人数是否超过一人
        if ($nation != "汉族") {
            $msg = $this -> checkNation($SSDM, $nation);
            if (!$msg) {
                return ['status' => false, 'msg' => "因不符合学校相关住宿规定，该宿舍无法选择", 'data' => null];
            }
        }
        // $data = $this -> where('YXDM',$college_id)
        //             -> where('XB', $sex)
        //             -> where('LH', $building)
        //             -> where('SSH', $dormitory)
        //             -> where('SYRS','>=', 1)
        //             -> find();
        //判断该宿舍非陕西籍的人数是否超过2人
        if ($place != "陕西") {
            $msg = $this -> checkPlace($SSDM, $nation);
            if (!$msg) {
                return ['status' => false, 'msg' => "因不符合学校相关住宿规定，该宿舍无法选择", 'data' => null];
            } else {
                $list = $this -> getBedNum($sex,$college_id, $building, $dormitory);
                if (empty($list)) {
                    return ['status' => false, 'msg' => "该宿舍没有空床位请换一间", 'data' => null];
                } else {
                    return ['status' => true, 'msg' => "查询成功", 'data' => $list];
                }
            }   
            //如果是陕西人，则不必判断只需返回可选的床位号 
        } else {
            $list = $this -> getBedNum($sex,$college_id, $building, $dormitory);
            if (empty($list)) {
                return ['status' => false, 'msg' => "该宿舍没有空床位请换一间", 'data' => null];
            } else {
                return ['status' => true, 'msg' => "查询成功", 'data' => $list];
            }
        }            
    }
    /**
     *  该方法用来返回宿舍可选床号
     *  @return array {"name": "1号床（上床下桌）", "value": 1},
     */
    private  function getBedNum($sex,$college_id, $building, $dormitory)
    {
        $list = [];
        $data = $this -> where('YXDM',$college_id)
                    -> where('XB', $sex)
                    -> where('LH', $building)
                    -> where('SSH', $dormitory)
                    -> field('CPXZ')
                    -> find();
        //床铺选择情况 例如：111111
        $CP = str_split($data['CPXZ']);
        $length = count($CP);
        if ($length == 4) {
            foreach ($CP as $key => $value) {
                if ($value == "1") {
                    $temp = [];
                    $k = $key + 1;
                    $temp = array(
                        'name' => $k."号床（上床下桌）",                    
                        'value' => $k,
                    );
                    $list[] = $temp;
                }
            }
            return $list;
        } elseif ($length == 6) {
            foreach ($CP as $key => $value) {
                if ($value == "1") {
                    $k = $key + 1;
                    $temp = [];
                    $temp = array(
                        'name' =>( $k == 1 || $k == 2 ) ? ($k."号床（上床下柜）") : ( ($k == 3 || $k == 5) ?  ($k."号床（上铺）"): ($k."号床（下铺）")),
                        'value' => $k,
                    );
                    $list[] = $temp;
                }
            }
            return $list;
        }
    }
    /**
     * 完善信息方法
     */
    public function setinfo($info, $key){
    
        $exit_info = Db::name('fresh_info_add') -> where('XH', $info['stu_id']) -> field('ID') -> count();
        if ($exit_info) {
            return ['status' => false, 'msg' => "信息已经完善", 'data' => null];
        } else {
            $ZCYF = '';
            if (empty($key['JJDC'][7])) {
                $ZCYF = '';
            } else{
                foreach ($key['JJDC'][7] as $k => $v) {
                    $ZCYF = $k == 0 ? $v : $ZCYF.",".$v;
                }
            }
            if ($key['JTRKS'] == 0) {
                return ['status' => false, 'msg' => "这样子不太好哦！", 'data' => null];
            }
            $data['XH'] = $info['stu_id'];
            $data['SFGC'] = !empty($key['SFGC']) ? $key['SFGC'] : null;
            $data['RXQHK'] = !empty($key['RXQHK']) ? $key['RXQHK'] : null;
            $data['JTRKS'] = !empty($key['JTRKS']) ? $key['JTRKS'] : null;
            $data['YZBM'] = !empty($key['YZBM']) ? $key['YZBM'] : null;
            $data['SZDQ'] = !empty($key['SZDQ']) ? $key['SZDQ'] : null;
            $data['XXDZ'] = !empty($key['XXDZ']) ? $key['XXDZ'] : null;
            $data['BRDH'] = !empty($key['BRDH']) ? $key['BRDH'] : null;
            $data['BRQQ'] = !empty($key['BRQQ']) ? $key['BRQQ'] : null;
            $data['ZP'] =  !empty($key['ZP'][0]['url']) ? $key['ZP'][0]['url'] : '';
            $data['ZSR'] = $key['ZSR'];
            //$data['RJSR'] = $RJSR;
            if (empty($key['JJDC'][0]) ||empty($key['JJDC'][1]) ||empty($key['JJDC'][2]) ||empty($key['JJDC'][3]) ||empty($key['JJDC'][4]) ||empty($key['JJDC'][5]) ||empty($key['JJDC'][6]) ) {
                return ['status' => false, 'msg' => "请先完成家庭经济情况调查", 'data' => null];
            } else {
                $data['FQZY'] = $key['JJDC'][0][0];
                $data['MQZY'] = $key['JJDC'][1][0];
                $data['FQLDNL'] = $key['JJDC'][2][0];
                $data['MQLDNL'] = $key['JJDC'][3][0];
                $data['YLZC'] = $key['JJDC'][4][0];
                $data['SZQK'] = $key['JJDC'][5][0];
                $data['JTBG'] = $key['JJDC'][6][0];
                $data['ZCYF'] = $ZCYF; 
            }
            if (empty($key['JTRK']) || empty($key['JTRK'][0]) ) {
                $family_info = array();
                $info_family = array();
            } else {
                $info_family = array(); 
                $family_info = array();
                foreach ($key['JTRK'] as $k => $v) {        
                    $family_info = array(
                        'XH' => $info['stu_id'],
                        'XM' => $v['name'],
                        'NL' => $v['age'],
                        'GX' => $v['relation'],
                        'GZDW' => $v['unit'],
                        'ZY' => $v['job'],
                        'NSR' => $v['income'],
                        'JKZK' => $v['health'],
                        'LXDH' => $v['mobile'],
                    );
                    $info_family[] = $family_info;
                }
            }
            return ['status' => true, 'msg' => "返回成功", 'data' => $data, 'info' => $info_family];
        }
    }

    /**
     * 将数据提交给redis
     * 暂时不需要redis服务
     */
    // public function giveredis($info, $key)
    // {
    //     $stu_id = $info['stu_id'];
    //     $college_id = $info['college_id'];
    //     $sex = $info['XBDM'];
    //     $place = $info['place'];
    //     $dormitory_id = $key['dormitory_id'];
    //     $bed_id = $key['bed_id'];
    //     // 把记录写进redis队列中
    //     // 首先加载Redis组件
    //     $redis = new \Redis();
    //     $redis -> connect('127.0.0.1', 6379);
    //     $redis_name = "order_msg";
    //     // 接收用户信息
    //     $msg = array('XH' => $stu_id, 'SSDM' => $dormitory_id, 'CH' => $bed_id, 'YXDM' => $college_id, 'SDSJ' => time(), 'status' => 'waited');
        
    //     $redis -> rpush($redis_name, $msg);
    // }
    
    /**
     * 提交数据
     */
    public function submit($info, $key)
    {
        // if ($steps != 'select') {
        //     return ['status' => false, 'msg' => "执行顺序出错", 'data' => null];
        // } else {
            $stu_id = $info['stu_id'];
            $college_id = $info['college_id'];
            $sex = $info['XBDM'];
            $place = $info['place'];
            $nation = $info['nation'];
            $dormitory_id = $key['dormitory_id'];
            $bed_id = $key['bed_id'];
            //如果是少数民族验证要选的宿舍是否满足要求
            if ($nation != "汉族") {
                $msg = $this -> checkNation($dormitory_id, $nation);
                if (!$msg) {
                    return ['status' => false, 'msg' => "不符合学校相关住宿规定，无法选择该宿舍！", 'data' => null];
                }
            }
            //如果不是陕西省的学生，则需要判断该宿同省人数
            if ($place != "陕西") {
                $msg = $this -> checkPlace($dormitory_id, $place);
                if (!$msg) {
                    return ['status' => false, 'msg' => "不符合学校相关住宿规定，无法选择该宿舍！", 'data' => null];                    
                }
            }

            if (empty($key['origin'])) {
                $origin = 'selection';
            } else {
                $origin = 'system';
            }
            $data = Db::name('fresh_list') -> where('XH', $stu_id) ->field('ID') ->find();
            if(empty($data)){
                $insert_flag = false;
                $update_flag = false;
                Db::startTrans();
                try{                    
                    // 第一步，将记录写进fresh_list表中
                    $insert_flag = Db::name('fresh_list') -> insert([
                        'XH' => $stu_id,
                        'SSDM' => $dormitory_id,
                        'CH' => $bed_id,
                        'YXDM' => $college_id,
                        'SDSJ' => time(),
                        'origin' => $origin,
                        'status' => 'waited', 
                    ]);
                    //第二步，将frsh_dormitory中对于宿舍，剩余人数-1，宿舍选择情况更新
                    $list = $this->lock(true) -> where('YXDM',$college_id)
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
                    if ( $list['CPXZ'][$bed_id - 1] == 0 ) {
                        //说明该床位已经被选过
                        return ['status' => false, 'msg' => "该床位已经被选了", 'data' => null];
                    } else {
                        //指数
                        $exp = (int)$length - (int)$bed_id;
                        $sub = pow(10, $exp);
                        $choice = (int)$list['CPXZ'] - $sub;
                        $choice = sprintf("%0".$length."d", $choice);
                        $choice = (string)$choice;
                        $update_flag = $this -> where('ID', $list['ID'])
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
        //}    
    }

    public function confirm($info, $key)
    {
        // if ($steps != 'waited') {
        //     return ['status' => false, 'msg' => "执行顺序出错", 'data' => null];
        // } else {
            $stu_id = $info['stu_id'];
            $college_id = $info['college_id'];
            $sex = $info['XBDM'];
            $place = $info['place'];
            $type = $key['type'];
            switch ($type) {
                case 'confirm':
                    //判断是否超时
                    $get_msg = Db::name('fresh_list') -> where('XH', $stu_id) -> where('status', 'waited') ->find();
                    if (empty($get_msg)) {
                        return ['status' => false, 'msg' => "不存在需要确认的宿舍订单", 'data' => null];
                    } else {     
                        $dormitory_id = $get_msg['SSDM'];
                        $bed_id = $get_msg['CH'];
                        $old_time = $get_msg['SDSJ'];
                        $now_time = time();
                        $second = $now_time - $old_time;
                        if ( $second >= 1800) {
                            // 启动事务
                            Db::startTrans();  
                            try{
                                $get_msg['status'] = 'timeover';
                                $get_msg['CZSJ'] = time();
                                unset($get_msg['ID']);
                                // 第一步 把取消的选择插入特殊列表
                                $insert_exception = Db::name('fresh_exception') -> insert($get_msg);
                                // 第二步 将原先锁定的数据删除
                                $delete_list = Db::name('fresh_list') -> where('XH', $stu_id)->delete();
                                // 第三步 把该宿舍的剩余人数以及床铺选择情况更新
                                $list = $this -> where('YXDM',$college_id)
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
                                $update_flag = $this -> where('ID', $list['ID'])
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
                            $update_status = Db::name('fresh_list') -> where('XH', $stu_id)->update(['status' => 'finished']);
                            if ($update_status == 1) {
                                return ['status' => true, 'msg' => "宿舍确认成功！", 'data' => null];                                
                            } else {
                                return ['status' => false, 'msg' => "服务器出了点问题哦！", 'data' => null];                                
                            }
                        }
                        break;
                    }
                
                case 'cancel':     
                    $data_in_list = Db::name('fresh_list') -> where('XH', $stu_id) -> field('ID,CH,SSDM') -> find();
                    if (empty($data_in_list)) {
                       return ['status' => false, 'msg' => "尚未申请宿舍", 'data' => null];                                
                    } else {
                        $update_flag = false;
                        $insert_exception = false;
                        $dormitory_id = $data_in_list['SSDM'];
                        $bed_id = $data_in_list['CH'];
                        $delete_list = false;
                        // 启动事务
                        Db::startTrans();            
                        try{
                            $data = Db::name('fresh_list') -> where('XH', $stu_id) ->find();
                            $data['status'] = 'cancelled';
                            $data['CZSJ'] = time();
                            unset($data['ID']);
                            //第一步 把取消的选择插入特殊列表
                            $insert_exception = Db::name('fresh_exception') -> insert($data);  
                            //第二步 将原先锁定的数据删除
                            $delete_list = Db::name('fresh_list') -> where('XH', $stu_id)->delete();
                            //第三步 把该宿舍的剩余人数以及床铺选择情况更新
                            $list = $this -> where('YXDM',$college_id)
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
                            $update_flag = $this -> where('ID', $list['ID'])
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

    public function finished($key)
    {
            $info = [];
            $stu_id = $key['stu_id'];
            $college_id = $key['college_id'];
            $sex = $key['XBDM'];
            $place = $key['place'];
            $list = Db::view('fresh_list') 
                    -> view('fresh_info','XM, XH, SYD','fresh_list.XH = fresh_info.XH')
                    //-> view('fresh_info_add','BRDH,BRQQ','fresh_list.XH = fresh_info_add.XH')
                    -> where('fresh_info.XH', $stu_id) 
                    -> field('XM,SYD,CH,SSDM,origin')
                    -> find();            
            if (empty($list)){
                return ['status' => false, 'msg' => "没有找到你宿舍哦", 'data' => '']; 
            } else {
                $room_msg = $this -> where('SSDM', $list['SSDM']) -> where('YXDM',$list['YXDM']) ->field('CPXZ') -> find();                
                $max_number = strlen($room_msg['CPXZ']);
                $money = $max_number == 4 ? 1200: 900;

                $array = array();
                $array['XH'] = $list['XH'];
                $array['XM'] = $list['XM'];
                $array['SYD'] = $list['SYD'];
                $array['CH'] = $list['CH'];
                $array['LH'] = explode('#', $list['SSDM'])[0];
                $array['SSH'] = explode('#', $list['SSDM'])[1];
                $array['ZSF'] = $money;
                $array['origin'] = $list['origin'];
                $info['personal'] = $array;
                //补充yiban认证所需信息
                $add_info = $this -> getyibaninfo($key);
                $info['yiban'] = $add_info;
                
                $roommate_msg = Db::view('fresh_list') 
                                    -> view('fresh_info','XM ,SYD, XH','fresh_list.XH = fresh_info.XH')
                                    -> view('fresh_info_add','BRDH,BRQQ','fresh_list.XH = fresh_info_add.XH')
                                    -> where('SSDM', $list['SSDM'])
                                    -> where('fresh_list.XH', '<>', $list['XH'])
                                    -> where('status','finished')
                                    -> field('CH,SSDM')
                                    -> select();
                
                $number = count($roommate_msg);
                $bed = array();
                if ($max_number == 4) {
                    $bed = [1,2,3,4];
                } elseif ($max_number == 6) {
                    $bed = [1,2,3,4,5,6];
                }   
                unset($bed[$list['CH'] - 1]);
                foreach ($roommate_msg as $key => $value) {
                    $info['roommate'][$value['CH']]['XM'] = $value['XM'];
                    //$info['roommate'][$value['CH']]['XM'] =  mb_substr($value['XM'], 0, 1, 'utf-8').'**';
                    $info['roommate'][$value['CH']]['SYD'] = $value['SYD'];
                    //$info['roommate'][$value['CH']]['LXFS'] = '****';
                    $info['roommate'][$value['CH']]['LXFS'] = empty($value['BRDH']) ? '****' : $value['BRDH'];
                    $info['roommate'][$value['CH']]['BRQQ'] = empty($value['BRQQ']) ? '未填写' : $value['BRQQ'];
                    unset($bed[$value['CH'] - 1]);
                }

                if (empty($bed)) {
                    return ['status' => true, 'msg' => "查询成功", 'data' => $info]; 
                } else {
                    foreach ($bed as $key => $value) {
                        $info['roommate'][$value] = [
                            'XM' => '空余',
                            'SYD' => '-',
                            'LXFS' => '-',
                            'BRQQ' => '-',
                        ];
                    }
                    return ['status' => true, 'msg' => "查询成功", 'data' => $info];  
                }    
            }
    }
    /**
     * 用来获取易班的信息
     */
    private function getyibaninfo($key){

        $info_add = array();
        $stu_id = $key['stu_id'];
        $sex = $key['XBDM'];
        $college_id = $key['college_id'];
        $college = Db::name('dict_college') 
                    -> where('YXDM',$college_id)
                    -> field('yb_group_id')
                    -> find();
        $info_list = Db::name('fresh_info_add')
                    -> where('XH',$stu_id)
                    -> field('BRDH')
                    -> find();
        $phone = $info_list['BRDH'];
        $yiban_college = $college['yb_group_id'];
        $info['role'] = '0';
        $info['build_time'] = time();
        $info['status'] = '0';
        $info['schooling'] = '0';
        $info['sex'] = $sex;
        $info['college'] = $yiban_college;
        $info['phone'] = $phone;
        $info['enter_year'] = '2018';
        $info['specialty'] = '';
        $info['eclass'] = '';
        return $info;
        
    }
    /**
     * 用来验证民族选择情况
     */
    private function checkNation($dormitory_id, $nation){
        $place_number = Db::view('fresh_list') 
                        -> view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
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
    private function checkPlace ($dormitory_id, $place) {
        $place_number = Db::view('fresh_list') 
                        -> view('fresh_info', 'XM, XH, SYD', 'fresh_list.XH = fresh_info.XH')
                        -> where('SSDM', $dormitory_id) 
                        -> where('SYD','LIKE', $place)
                        -> count();
        if ($place_number >= 2) {
            return false;
        } else {
            return true;
        }
    }
}