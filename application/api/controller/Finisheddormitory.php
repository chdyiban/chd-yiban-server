<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;

use app\api\model\Dormitory as DormitoryModel;
/**
 * 
 */
class Finisheddormitory extends Freshuser
{
    //记得该权限
    protected $noNeedLogin = [];
    protected $noNeedRight = [];

    private $loginInfo = null;
    private $token = null;
    private $userInfo = null;

    /**
     * 分配宿舍方法
     */
    public function finish()
    {
        set_time_limit(0);
        $result = array();
        $morenation = array();
        $lessnation = array();
        $stu_info = Db::name('fresh_info') -> select();
        //将已经选过的人从数组中去掉
        foreach ($stu_info as $k => $value) {
            $stu_id   = $value['XH'];
            $exit_stu = Db::name('fresh_list') -> where('XH', $stu_id) -> find();
            if (!empty($exit_stu)) {
                unset($stu_info[$k]);
            }
        }

        // foreach ($stu_info as $key => $value) {
        //     if ($value['MZ'] == '汉族') {
        //         $morenation[] = $value;
        //     } else {
        //         $lessnation[] = $value;
        //     }
        // }


        //对于没有选择宿舍的人进行遍历
        foreach ($stu_info as $k => $v) {
            $info['stu_id'] = $v['XH'];
            $info['place'] = $v['SYD'];
            $info['college_id'] = $v['YXDM'];
            $info['XBDM'] = $v['XBDM'];
            $info['nation'] = $v['MZ'];

            $stu_id   = $v['XH'];
            $stu_name = $v['XM'];
            $stu_zkzh = $v['ZKZH'];
            $DormitoryModel = new DormitoryModel;
            //此方法返回一个选择的楼号
            $building_choice = $this -> getBuilding('select', $info);
            //返回该楼号能否选择以及能选的宿舍号
            $dormitory = $this -> getDormitory($info, $building_choice);
            //如果此楼无法选择，则进行循环重新获取楼号
            while (!$dormitory['status']) {

                $building_choice = $this -> getBuilding('select',$info);
                $dormitory = $this -> getDormitory($info, $building_choice);
            }
            //获取能选的宿舍号，并且随机选择一个宿舍
            $dormitory = $dormitory['data'];
            $count = count($dormitory);
            $dormitory_choice = rand(0, $count-1);
            $dormitory_choice = $dormitory[$dormitory_choice]['value'];
            //将楼号以及宿舍号作为参数获取能选的床号或者返回错误            
            $bed = $this -> getBed($info, $building_choice, $dormitory_choice);
            //当该宿舍不能选择时，重新随机选择楼号，重新选择宿舍号，直到有床能选为止
            while (!$bed['status']) {
                $building_choice = $this -> getBuilding('select', $info);
                $dormitory = $this -> getDormitory($info, $building_choice);
                while (!$dormitory['status']) {
                    $building_choice = $this -> getBuilding('select', $info);
                    $dormitory = $this -> getDormitory($info, $building_choice);
                }

                $dormitory = $dormitory['data'];
                $count = count($dormitory);
                $dormitory_choice = rand(0, $count-1);
                $dormitory_choice = $dormitory[$dormitory_choice]['value'];

                $bed = $this -> getBed($info, $building_choice, $dormitory_choice);
            }
            //获取能选的床号，并且随机选择一个床
            $bed = $bed['data'];
            $count = count($bed);
            $bed_choice = rand(0, $count-1);
            $bed_choice = $bed[$bed_choice]['value'];
            //将结果构造并且提交给submit方法
            $dormitory_id = $building_choice.'#'.$dormitory_choice;
            $key = array('dormitory_id' => $dormitory_id, 'bed_id' => $bed_choice, 'origin' => 'system');
            $result[$k]['info'] = $info;
            $result[$k]['key'] = $key;
            $response = $DormitoryModel -> submit($info, $key);
            if ($response['status']) {
               $key = array('type' => 'confirm');
               $response = $DormitoryModel -> confirm($info, $key);
            } else {
               continue;
            }
           

        }
        return '本次处理数据'.count($stu_info).'个';
        // if (count($result) == count($stu_info)) {    
        //     foreach ($result as $key => $value) {
        //         $info = $value['info'];
        //         $key = $value['key'];
        //         $response = $DormitoryModel -> submit($info, $key);
        //         dump($response);
        //         if ($response['status']) {
        //             $key = array('type' => 'confirm');
        //             $response = $DormitoryModel -> confirm($info, $key);
        //         }
        //     }
        //     return '本次处理数据'.count($result).'个';
        // } else {
        //     return "尚未完全分配，请重新运行";
        // }


    }
    /**
     * 得到一个随机选择的楼号
     * @return int
     */
    private function getBuilding($steps, $info)
    {
        $DormitoryModel = new DormitoryModel;
        //得出所选楼号
        $key = array('type' => '');
        $building = $DormitoryModel -> initSteps($steps, $info);
        $building = $building['data'];
        $count = count($building);
        $building_choice = rand(0, $count-1);
        $building_choice = $building[$building_choice]['value'];
        return $building_choice;
    }
    /**
     * 获取对应楼号能选的宿舍号
     * @return array ['status' => true, 'msg' => "查询成功", 'data' => array]
     * @return array ['status' => false, 'msg' => '该楼已经没有空宿舍了', 'data' => null]
     */
    private function getDormitory($info, $building_choice)
    {
        $DormitoryModel = new DormitoryModel;
        //将楼号构造数组得到随机分配的宿舍
        $key = array('type' => 'dormitory', 'building' => $building_choice);
        $dormitory = $DormitoryModel -> show($info, $key);
        return $dormitory;

    }
    /**
     * 获取某楼某宿舍对应的床号
     * @return array ['status' => false, 'msg' => "因不符合学校相关住宿规定，该宿舍无法选择", 'data' => null];
     * @return array ['status' => true, 'msg' => "查询成功", 'data' => $list];
     */
    private function getBed($info, $building_choice, $dormitory_choice)
    {
        $DormitoryModel = new DormitoryModel;
        //将楼号构造数组得到随机分配的宿舍
        $key = array('type' => 'bed', 'building' => $building_choice, 'dormitory' => $dormitory_choice);
        $bed = $DormitoryModel -> show($info, $key);
        return $bed;
    }

    

}
    