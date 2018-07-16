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
            //第一步，输入自己信息
            case 'setinfo':
                $data = Db::view('fresh_info')
                            -> view('dict_college', 'YXDM, YXMC', 'fresh_info.YXDM = dict_college.YXDM')
                            -> where('XH', $stuid)
                            -> find();
                $info['XH'] = $data['XH'];
                $info['XM'] = $data['XM'];
                $info['YXMC'] = $data['YXMC'];
                $info['XB'] = $data['XBDM'] == 1? "男":"女";
                $info['MZ'] = $data['MZ'];
                $info['SYD'] = $data['SYD'];
                return $info;
                break;
            //第二步，选房阶段
            //16 => [101 => [1,2], 102],
            //15 => 
            case 'select':
                $msg = $this -> showAll($userinfo);
                return $msg;
                break;
            //第三步, 订单已经提交等待确认
            case 'waited':
                $data = Db::name('fresh_list') -> where('XH', $stuid) -> find();
                $info['XH'] = $stuid;
                $dor = explode("#", $data['SSDM']);
                $info['LH'] = $dor[0];
                $info['SSH'] = $dor[1];
                $info['CH'] = $data['CH'];
                $info['start_time'] = date('Y-m-s h:i:s', $data['SDSJ']);
                $info['end_time'] = date('Y-m-s h:i:s', $data['SDSJ'] + 1800);

                return $info;
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
     */
    private function showAll($info)
    {
        $msg = array();
        $college_id = $info['college_id'];
        $sex = $info['XBDM'];
        $place = $info['place'];
        $nation = $info['nation'];
        $data = Db::name('fresh_dormitory') 
                        -> where('YXDM',$college_id)
                        -> where('SYRS','>','0')
                        -> where('XB',$sex)
                        -> select();

        foreach ($data as $v) {
            $building = $v['LH'];
            $dormitory = $v['SSH'];
            $list = $this -> getBedNum($sex,$college_id, $building, $dormitory);
            $msg[$building][$dormitory] = $list;
        }
        return $msg;
    }

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
            //需要楼号
            case 'building':
                $data = $this -> where('YXDM',$college_id)
                            -> where('XB', $sex)
                            -> group('LH')
                            -> select();
                foreach ($data as $key => $value) {
                    $build = $value -> toArray()['LH'];
                    $info = array(
                        'name' => $build <= 6 ? $build."号楼（西区）":$build."号楼（东区）",
                        'value' => $build,
                    );
                    $list[] = $info;
                }
                $dormitory_info = $this -> where('SYRS','>=','1') 
                                            -> where('XB',$sex)
                                            -> where('YXDM',$college_id)
                                            -> field('SYRS')
                                            -> select();
                if (empty($dormitory_info)) {
                    
                }
                $dormitory_number = count($dormitory_info);
                $bed_number = 0;
                foreach ($dormitory_info as $key => $value) {
                    $bed_number += $value -> toArray()['SYRS'];
                }
                return ['status' => true, 'msg' => "查询成功", 'data' => $list, 'dormitory_number' => $dormitory_number, 'bed_number' => $bed_number];
                break;
            //需要宿舍号
            case 'dormitory':
                if (empty($key['building'])) {
                    return ['status' => false, 'msg' => "参数有误", 'data' => null];
                }else{
                    $building = $key['building'];
                    $data = $this -> where('YXDM',$college_id)
                                -> where('XB', $sex)
                                -> where('LH', $building)
                                -> where('SYRS','>=', 1)
                                -> select();
                    foreach ($data as $key => $value) {
                        $list[] = $value -> toArray()['SSH'];
                    }
                    return ['status' => true, 'msg' => "查询成功", 'data' => $list];
                }
                break;
            //需要床号
            case 'bed':
                if (empty($key['dormitory'])) {
                    return ['status' => false, 'msg' => "参数有误", 'data' => null];
                }else{
                    $building = $key['building'];
                    $dormitory = $key['dormitory'];
                    $SSDM = (string)$building.'#'.$dormitory;
                    //判断该宿舍少数民族人数是否超过一人
                    if ($nation <> "汉族") {
                        $msg = $this -> checkNation($SSDM);
                        if (!$msg) {
                            return ['status' => false, 'msg' => "因不符合学校相关住宿规定，该宿舍无法选择", 'data' => null];
                        }
                    }
                    $data = $this -> where('YXDM',$college_id)
                                -> where('XB', $sex)
                                -> where('LH', $building)
                                -> where('SSH', $dormitory)
                                -> where('SYRS','>=', 1)
                                -> find();
                    //判断该宿舍非陕西籍的人数是否超过2人
                    if ($place <> "陕西") {
                        $msg = $this -> checkNation($SSDM);
                        if (!$msg) {
                            return ['status' => false, 'msg' => "因不符合学校相关住宿规定，该宿舍无法选择", 'data' => null];
                        } else {
                            $list = $this -> getBedNum($sex,$college_id, $building, $dormitory);
                            return ['status' => true, 'msg' => "查询成功", 'data' => $list];
                        }   
                        //如果是陕西人，则不必判断只需返回可选的床位号 
                    } else {
                        $list = $this -> getBedNum($sex,$college_id, $building, $dormitory);
                        return ['status' => true, 'msg' => "查询成功", 'data' => $list];
                    }            
                }
                break;
        }
    }
    /**
     *  该方法用来返回宿舍可选床号
     *  @return array {"name": "1号床（上床下柜）", "value": 1},
     */
    private  function getBedNum($sex,$college_id, $building, $dormitory)
    {
        $list = [];
        $data = $this -> where('YXDM',$college_id)
                    -> where('XB', $sex)
                    -> where('LH', $building)
                    -> where('SSH', $dormitory)
                    -> find();
        //床铺选择情况 例如：111111
        $CP = $data['CPXZ'];
        $length = strlen($CP);
        if ($length == 4) {
            for ($i=0; $i < $length ; $i++) { 
                $k = $i + 1;
                if ($CP[$i] == 1) {
                    $temp = array(
                        'name' => $k."号床（上床下柜）",                    
                        'value' => $k,
                    );
                }
                $list[] = $temp;
            }
            return $list;
        } elseif ($length == 6) {
            for ($i=0; $i < $length ; $i++) { 
                $k = $i + 1;
                if ($CP[$i] == 1) {
                    $temp = array(
                        'name' =>( $k == 1 || $k == 2 ) ? ($k."号床（上床下柜）") : ( ($k == 3 || $k == 5) ?  ($k."号床（上铺）"): ($k."号床（下铺）")),
                        'value' => $k,
                    );
                }
                $list[] = $temp;
            }
            return $list;
        }
    }
    /**
     * 完善信息方法
     */
    public function setinfo($info, $key){
    
        $exit_info = Db::name('fresh_info_add') -> where('XH', $info['stu_id']) -> count();
        if ($exit_info) {
            return ['status' => false, 'msg' => "信息已经完善", 'data' => null];
        } else {
            $ZCYF = '';
            foreach ($key['JJDC'][7] as $k => $v) {
                $ZCYF = $k == 0 ? $v:$ZCYF.",".$v;
            }
            if ($key['JTRKS'] == 0) {
                return ['status' => false, 'msg' => "数据提供有问题", 'data' => null];
            } else {
                $RJSR = $key['ZSR']/$key['JTRKS'];
            }
            $data = array(
                'XH' => $info['stu_id'],
                'RXQHK' => $key['RXQHK'],
                'JTRKS' => $key['JTRKS'],
                'YZBM' => $key['YZBM'],
                'SZDQ' => $key['SZDQ'],
                'XXDZ' => $key['XXDZ'],
                'BRDH' => $key['BRDH'],
                'ZP' => $key['ZP'][0]['url'],
                'ZSR' => $key['ZSR'],
                'RJSR' => $RJSR,
                'FQZY' => $key['JJDC'][0][0],
                'MQZY' => $key['JJDC'][1][0],
                'FQLDNL' => $key['JJDC'][2][0],
                'MQLDNL' => $key['JJDC'][3][0],
                'YLZC' => $key['JJDC'][4][0],
                'SZQK' => $key['JJDC'][5][0],
                'JTBG' => $key['JJDC'][6][0],
                'ZCYF' => $ZCYF,
            );
        
        // $res = Db::name('fresh_info_add') -> insert($data);
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
            return ['status' => true, 'msg' => "返回成功", 'data' => $data, 'info' => $info_family];
        }
    }

    /**
     * 将数据提交给redis
     */
    public function giveredis($info, $key)
    {
        $stu_id = $info['stu_id'];
        $college_id = $info['college_id'];
        $sex = $info['XBDM'];
        $place = $info['place'];
        $dormitory_id = $key['dormitory_id'];
        $bed_id = $key['bed_id'];
        // 把记录写进redis队列中
        // 首先加载Redis组件
        $redis = new \Redis();
        $redis -> connect('127.0.0.1', 6379);
        $redis_name = "order_msg";
        // 接收用户信息
        $msg = array('XH' => $stu_id, 'SSDM' => $dormitory_id, 'CH' => $bed_id, 'YXDM' => $college_id, 'SDSJ' => time(), 'status' => 'waited');
        
        $redis -> rpush($redis_name, $msg);
    }
    
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
            if ($nation <> "汉族") {
                $msg = $this -> checkNation($dormitory_id);
                if (!$msg) {
                    return ['status' => false, 'msg' => "不符合学校相关住宿规定，无法选择该宿舍！", 'data' => null];
                }
            }
            //如果不是陕西省的学生，则需要判断该宿同省人数
            if ($place <> "陕西") {
                $msg = $this -> checkNation($dormitory_id, $place);
                if (!$msg) {
                    return ['status' => false, 'msg' => "不符合学校相关住宿规定，无法选择该宿舍！", 'data' => null];                    
                }
            }
            $data = Db::name('fresh_list') -> where('XH', $stu_id)->find();
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
                        'status' => 'waited', 
                    ]);
                    //第二步，将frsh_dormitory中对于宿舍，剩余人数-1，宿舍选择情况更新
                    $list = $this -> where('YXDM',$college_id)
                                -> where('SSDM', $dormitory_id)
                                -> find();
                    
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
                        $choice = sprintf("%04d", $choice);
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
                    return ['status' => false, 'msg' => "请求失败", 'data' => null];
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
                        //计算天数
                        $timediff = $now_time-$old_time;
                        $days = intval($timediff/86400);
                        //计算小时数
                        $remain = $timediff%86400;
                        $hours = intval($remain/3600);
                        //计算分钟数
                        $remain = $remain%3600;
                        $mins = intval($remain/60);
                        if ( $days != 0 || $hours != 0 && $mins > 30) {
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
                                            -> find();
                                $rest_num = $list['SYRS'] + 1;
                                //宿舍总人数
                                $length = strlen($list['CPXZ']);
                                //指数
                                $exp = (int)$length - (int)$bed_id;
                                $sub = pow(10, $exp);
                                $choice = (int)$list['CPXZ'] + $sub;
                                $choice = sprintf("%04d", $choice);
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
                                return ['status' => false, 'msg' => "超时，已经取消", 'data' => null];
                            } else {
                                return ['status' => false, 'msg' => "未成功取消", 'data' => null];                                
                            }   
                        } else {
                            $update_status = Db::name('fresh_list') -> where('XH', $stu_id)->update(['status' => 'finished']);
                            if ($update_status == 1) {
                                return ['status' => true, 'msg' => "宿舍确认成功", 'data' => null];                                
                            } else {
                                return ['status' => false, 'msg' => "宿舍已经确认过", 'data' => null];                                
                            }
                        }
                        break;
                    }
                
                case 'cancel':     
                    $data_in_list = Db::name('fresh_list') -> where('XH', $stu_id) -> find();
                    if (empty($data_in_list)) {
                        return ['status' => false, 'msg' => "尚未申请宿舍", 'data' => null];                                
                    } else {
                        $insert_exception = false;
                        $delete_list = false;
                        // 启动事务
                        Db::startTrans();            
                        try{
                            $data = Db::name('fresh_list') -> where('XH', $stu_id)->find();
                            $data['status'] = 'cancelled';
                            $data['CZSJ'] = time();
                            unset($data['ID']);
                            //第一步 把取消的选择插入特殊列表
                            $insert_exception = Db::name('fresh_exception') -> insert($data);
                            //第二步 将原先锁定的数据删除
                            $delete_list = Db::name('fresh_list') -> where('XH', $stu_id)->delete();
                            //第三步 把该宿舍的剩余人数以及床铺选择情况更新
                            $list = $this -> where('YXDM',$college_id)
                                        -> where('SSDM', $dormitory_id)
                                        -> find();
                            $rest_num = $list['SYRS'] + 1;
                            //宿舍总人数
                            $length = strlen($list['CPXZ']);
                            //指数
                            $exp = (int)$length - (int)$bed_id;
                            $sub = pow(10, $exp);
                            $choice = (int)$list['CPXZ'] + $sub;
                            $choice = sprintf("%04d", $choice);
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
                            return ['status' => false, 'msg' => "已经成功取消", 'data' => null];                                
                        } else {
                            return ['status' => false, 'msg' => "请求失败", 'data' => null];                                
                        }   
                    }    
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
                    ->view('fresh_info','XM, XH, SYD','fresh_list.XH = fresh_info.XH')
                    -> where('fresh_info.XH', $stu_id) 
                    -> find();
            
            $room_msg = $this -> where('SSDM', $list['SSDM']) -> find();
            $max_number = strlen($room_msg['CPXZ']);
            $money = $max_number == 4 ? 1200: 700;

            $array = array();
            $array['XH'] = $list['XH'];
            $array['XM'] = $list['XM'];
            $array['SYD'] = $list['SYD'];
            $array['CH'] = $list['CH'];
            $array['LH'] = explode('#', $list['SSDM'])[0];
            $array['SSH'] = explode('#', $list['SSDM'])[1];
            $array['ZSF'] = $money;
            $info['personal'] = $array;
           
            $roommate_msg = Db::view('fresh_list') 
                                ->view('fresh_info','XM, XH','fresh_list.XH = fresh_info.XH')
                                -> where('SSDM', $list['SSDM'])
                                -> where('fresh_list.XH', '<>', $list['XH'])
                                -> where('status','finished')
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
                $info['roommate'][$value['CH']]['XM'] =  mb_substr($value['XM'], 0, 1, 'utf-8').'**';
                $info['roommate'][$value['CH']]['CH'] = $value['CH'];
                $info['roommate'][$value['CH']]['LXFS'] = '****';
                unset($bed[$value['CH'] - 1]);
            }

            if (empty($bed)) {
                return $info;
            } else {
                foreach ($bed as $key => $value) {
                    $info['roommate'][$value] = [
                        'XM' => '空余',
                        'SYD' => '-',
                        'LXFS' => '-'
                    ];
                }
                return ['status' => true, 'msg' => "查询成功", 'data' => $info];  
            }    
        //}
    }
    /**
     * 用来验证民族选择情况
     */
    private function checkNation($dormitory_id){
        $place_number = Db::view('fresh_list') 
                        -> view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
                        -> where('SSDM', $dormitory_id) 
                        -> where('MZ','<>', '汉族')
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