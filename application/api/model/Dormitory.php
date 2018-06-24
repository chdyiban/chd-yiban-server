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
     * 返回可选择宿舍楼以及宿舍号以及剩余人数
     */
    public function show($info, $key)
    {
        $list = [];
        $college_id = $info['college_id'];
        $sex = $info['sex'];
        $place = $info['place'];
        $type = $key['type'];
        switch ($type) {
            //需要楼号
            case 'building':
                $data = $this -> where('YXDM',$college_id)
                              -> where('XB', $sex)
                              -> group('LH')
                              -> select();
                foreach ($data as $key => $value) {
                    $list[] = $value -> toArray()['LH'];
                }
                return $list;
                break;
            //需要宿舍号
            case 'dormitory':
                if (empty($key['building'])) {
                    return false;
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
                    return $list;
                }
                break;
            //需要床号
            case 'bed':
                if (empty($key['dormitory'])) {
                    return false;
                }else{
                    $building = $key['building'];
                    $dormitory = $key['dormitory'];
                    //判断该宿舍陕西籍的人数是否超过2人
                    $SSDM = (string)$building.'#'.$dormitory;
                    $shanxi_number = Db::view('fresh_list') 
                                        -> view('fresh_info', 'XM, XH, SYD', 'fresh_list.XH = fresh_info.XH')
                                        -> where('SSDM', $SSDM) 
                                        -> where('SYD','LIKE', '%陕西%')
                                        -> count();
                    if ($shanxi_number >= 2) {
                        return ['该宿舍陕西省人数过多，请更换！'];
                    } else {
                        $data = $this -> where('YXDM',$college_id)
                                  -> where('XB', $sex)
                                  -> where('LH', $building)
                                  -> where('SSH', $dormitory)
                                  -> find();
                        //床铺选择情况 例如：111111
                        $CP = $data['CPXZ'];
                        $length = strlen($CP);
                        for ($i=0; $i < $length ; $i++) { 
                            if ($CP[$i] == 1) {
                                array_push($list, $i+1);
                            }
                        }
                        return $list;
                    }                    
                }
                break;
        }
        
    }

    /**
     * 提交数据
     */
    public function submit($info, $key)
    {
        $stu_id = $info['stu_id'];
        $college_id = $info['college_id'];
        $sex = $info['sex'];
        $place = $info['place'];
        $dormitory_id = $key['dormitory_id'];
        $bed_id = $key['bed_id'];
        $data = Db::name('fresh_list') -> where('XH', $stu_id)->find();
        if(empty($data)){
            $insert_flag = false;
            $update_flag = false;
            Db::startTrans();
            try{
                //第一步，将记录写进fresh_list表中
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
                //宿舍总人数
                $length = strlen($list['CPXZ']);
                //核查床位是否被选过
                if ( $list['CPXZ'][$bed_id - 1] == 0 ) {
                    //说明该床位已经被选过
                    return ['该床位被选了', false];
                } else {
                     //指数
                    $exp = (int)$length - (int)$bed_id;
                    $sub = pow(10, $exp);
                    $choice = (int)$list['CPXZ'] - $sub;
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
                return ['成功选择宿舍', true];
            }else{
                return ['请求失败', true];
            }
        } else {
            return ['您已经选择过宿舍', false];
        }

    }

    public function confirm($info, $key)
    {
        $stu_id = $info['stu_id'];
        $college_id = $info['college_id'];
        $sex = $info['sex'];
        $place = $info['place'];
        $dormitory_id = $key['dormitory_id'];
        $bed_id = $key['bed_id'];
        $type = $key['type'];
        switch ($type) {
            case 'confirm':
                //判断是否超时
                $get_msg = Db::name('fresh_list') -> where('XH', $stu_id) -> where('status', 'waited') ->find();
                if (empty($get_msg)) {
                    return ['不存在需要确认的宿舍订单', false];
                } else {
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
                            return ['超时，已经取消', true];
                        } else {
                            return ['未因超时成功取消', false];
                        }   
                    } else {
                        $update_status = Db::name('fresh_list') -> where('XH', $stu_id)->update(['status' => 'finished']);
                        if ($update_status == 1) {
                            return ['宿舍确认成功', true];
                        } else {
                            return ['宿舍已经确认结束', false];
                        }
                    }
                    break;
                }
            
            case 'cancel':     
                $data_in_list = Db::name('fresh_list') -> where('XH', $stu_id) -> find();
                if (empty($data_in_list)) {
                    return ['您还未申请宿舍', false];
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
                        return ['已经成功取消', true];
                    } else {
                        return ['请求失败', false];
                    }   
                }    
                break;
        }
    }

    public function finished($key)
    {
        $info = [];
        $stu_id = $key['stu_id'];
        $college_id = $key['college_id'];
        $sex = $key['sex'];
        $place = $key['place'];
        $list = Db::name('fresh_list') -> where('XH', $stu_id) -> find();
        if ($list['status'] == 'finished') {
            $info[0] = $list;
            $room_msg = $this -> where('SSDM', $list['SSDM']) -> select();
            $max_number = strlen($room_msg[0]['CPXZ']);
            $roommate_msg = Db::view('fresh_list') 
                                    ->view('fresh_information','XM, XH','fresh_list.XH = fresh_information.XH')
                                    -> where('SSDM', $list['SSDM'])
                                    -> where('fresh_list.XH', '<>', $list['XH'])
                                    -> where('status','finished')
                                    -> select();
            
            $number = count($roommate_msg);
            if ($max_number == 4) {
                $bed = [1,2,3,4];
            } elseif ($max_number == 6) {
                $bed = [1,2,3,4,5,6];
            }
            unset($bed[$list['CH'] - 1]);
            foreach ($roommate_msg as $key => $value) {
                $info[1][$value['CH']]['XM'] = $value['XM'];
                $info[1][$value['CH']]['CH'] = $value['CH'];
                $info[1][$value['CH']]['SSDM'] = $value['SSDM'];
                unset($bed[$value['CH'] - 1]);
            }
            
            if (empty($bed)) {
                return $info;
            } else {
                foreach ($bed as $key => $value) {
                    $info[1][$value] = ['暂无人'];
                }
                return $info;
            }
            
        } else {
            return false;
        }
        
    }



}