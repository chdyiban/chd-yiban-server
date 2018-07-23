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
    protected $noNeedLogin = [];
    protected $noNeedRight = [];

    private $loginInfo = null;
    private $token = null;
    private $userInfo = null;

    public function finish()
    {
        $stu_info = Db::name('fresh_info') -> select();
        foreach ($stu_info as $k => $value) {
            $stu_id   = $value['XH'];
            $exit_stu = Db::name('fresh_list') -> where('XH', $stu_id) -> find();
            if (!empty($exit_stu)) {
                unset($stu_info[$k]);
            }
        }


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
            $building_choice = $this -> getBuilding('select', $info);
            $dormitory = $this -> getDormitory($info, $building_choice);
            while (!$dormitory['status']) {
                $building_choice = $this -> getBuilding('select',$info);
                $dormitory = $this -> getDormitory($info, $building_choice);
            }

            $dormitory = $dormitory['data'];
            $count = count($dormitory);
            $dormitory_choice = rand(0, $count-1);
            $dormitory_choice = $dormitory[$dormitory_choice]['value'];

            $bed = $this -> getBed($info, $building_choice, $dormitory_choice);
            //将楼号构造数组得到随机分配的宿舍
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
            
            $bed = $bed['data'];
            $count = count($bed);
            $bed_choice = rand(0, $count-1);
            $bed_choice = $bed[$bed_choice]['value'];
            //将选择宿舍的结果提交
            $dormitory_id = $building_choice.'#'.$dormitory_choice;
            $key = array('dormitory_id' => $dormitory_id, 'bed_id' => $bed_choice, 'origin' => 'system');
            $response = $DormitoryModel -> submit($info, $key);
            if ($response['status']) {
                $key = array('type' => 'confirm');
                $response = $DormitoryModel -> confirm($info, $key);
            } else {
                continue;
            }
        }
        return "本次处理数据".count($stu_info)."个";
    }

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

    private function getDormitory($info, $building_choice)
    {
        $DormitoryModel = new DormitoryModel;
        //将楼号构造数组得到随机分配的宿舍
        $key = array('type' => 'dormitory', 'building' => $building_choice);
        $dormitory = $DormitoryModel -> show($info, $key);
        return $dormitory;

    }

    private function getBed($info, $building_choice, $dormitory_choice)
    {
        $DormitoryModel = new DormitoryModel;
        //将楼号构造数组得到随机分配的宿舍
        $key = array('type' => 'bed', 'building' => $building_choice, 'dormitory' => $dormitory_choice);
        $bed = $DormitoryModel -> show($info, $key);
        return $bed;
    }

    

}
    