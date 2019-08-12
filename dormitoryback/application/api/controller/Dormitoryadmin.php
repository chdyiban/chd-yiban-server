<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
/**
 * 
 */
class Dormitoryadmin extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = [];

    /**
     * @param ['XH' => number] -> base64加密
     * @return array
     */
    public function info()
    {
        //提示开发公司此时的token具有管理员权限，不可以写到前端
        $info = array();
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (!empty($key['XH'])) {
            $data = Db::view('fresh_result') 
                    -> view('dict_college','YXDM,YXMC', 'fresh_list.YXDM = dict_college.YXDM')
                    -> where('XH', $key['XH']) 
                    -> find();
            if (!empty($data)) {
                $info['XH'] = $data['XH'];
                $info['SSDM'] = !empty($data['SSDM']) ? ("渭水校区 ".$data['SSDM']) : $this->error('参数有误');
                $info['CH'] = !empty($data['CH']) ? $data['CH'].'号床' : $this->error('参数有误');
                $info['YXDM'] = $data['YXDM'];
                $info['YXMC'] = $data['YXMC'];
                $type = Db::name('fresh_dormitory') 
                        -> where('YXDM', $data['YXDM'])
                        -> where('SSDM', $data['SSDM'])
                        -> field('CPXZ') 
                        -> find();
                $type = !empty($type['CPXZ']) ? strlen($type['CPXZ']) : $this->error('参数有误');
                $money = $type == 4 ? 1200 : 900;
                $info['ZSF'] = $money;
                $info['origin'] = $data['origin'];
                $info['status'] = $data['status'];
                switch ($data['status']) {
                    case 'waited':
                        $this -> success('存在尚待确认的宿舍', $info);
                        break;       
                    case 'finished':
                        if ($info['origin'] == 'selection') {
                            $this -> success('宿舍由学生选择完成', $info);
                        } else {
                            $this -> success('宿舍由系统随机分配完成', $info);
                        }
                        break;
                }
            } else {
                $this -> error('尚未选择宿舍');
            }
        } else {
            $this -> error('参数有误');
        }
    }

    /**
     * 删除一个宿舍记录的方法
     */
    public function deletelist()
    {
        $stu_id = $this -> request -> param('stuid');
        if (empty($stu_id)) {
            $this -> error('参数错误');
        } else {
            $data_in_list = Db::view('fresh_list')
                            -> view('fresh_info','XBDM','fresh_list.XH = fresh_info.XH') 
                            -> where('fresh_list.XH', $stu_id) 
                            -> field('CH,SSDM') 
                            -> find();
            $sex = $data_in_list['XBDM'];
            $college_id = $data_in_list['YXDM'];
            if (empty($data_in_list)) {
                $this -> error('该生未申请宿舍');
            } else {
                $dormitory_id = $data_in_list['SSDM'];
                $bed_id = $data_in_list['CH'];
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
                    $list = Db::name('fresh_dormitory') -> where('YXDM',$college_id)
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
                    $update_flag = Db::name('fresh_dormitory')
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
                if (!empty($update_flag)) {
                    $this -> success('已经成功取消！');
                } else {
                    $this -> error('服务器出了点问题哦！');
                }
            }    
        }
    }
}
