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
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    private $loginInfo = null;
    private $token = null;
    private $userInfo = null;

    public function distribute(){
        set_time_limit(0);
        //所有学生信息
        //$stu_info = Db::name('fresh_info') ->field('id,xh,yxdm,xbdm,mz,syd') -> find();
        //1.统计未分配的少数民族学生数量
        $sql = "select count(*) as num from `fa_fresh_info` A left join `fa_fresh_list` B on A.XH=B.XH where B.ID is null and A.MZ not like '汉族' LIMIT 1";
        $count_of_not_hz = Db::query($sql);
        if($count_of_not_hz){
            //少数民族还有人，继续分配
            $sql = "select A.ID,A.XH,A.XM,A.YXDM,A.XBDM,A.MZ,A.SYD from `fa_fresh_info` A left join `fa_fresh_list` B on A.XH=B.XH where B.ID is null and A.MZ not like '汉族' LIMIT 1";
            $person = Db::query($sql);

            //初步找出可选宿舍，条件：学院、性别、剩余人数
            $rooms = Db::name('fresh_dormitory')
                ->where('YXDM',$person[0]['YXDM'])//找学院
                ->where('XB',$person[0]['XBDM'])//找性别
                ->where('SYRS','>=',1)
                ->select();

            //遍历每一个宿舍
            foreach ($rooms as $k => $v) {
                //遍历可用宿舍
                $map['SSDM'] = $v['SSDM'];
                //$roommates = Db::name('fresh_list')->where($map)->select();
                //判断是否“可住”:
                //1.是不是有相同少数民族，学生为汉族则不用考虑，为了通用性，这里要判断一下
                if($person[0]['MZ'] !== '汉族'){
                    $roommates = Db::view('fresh_list') 
                        -> view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
                        -> where($map) 
                        -> where('MZ','like', $person[0]['MZ'])
                        ->count();
                    // $roommates = Db::name('fresh_list')
                    //     ->where($map)
                    //     ->where('MZ','like',$person[0]['MZ'])
                    //     ->count();
                    //该宿舍与备选person相同民族的人数大于或等于1，则不能选，跳出
                    if($roommates >= 1){
                        continue;
                    }
                }
                //2.如果不是陕西籍，那么该省份有几个？
                if($person[0]['SYD'] != '陕西'){
                    $roommates = Db::view('fresh_list') 
                            -> view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
                            -> where($map) 
                            ->where('SYD','like',$person[0]['SYD'])
                            ->count();
                    // $roommates = Db::name('fresh_list')
                    //     ->where($map)
                    //     ->where('SYD','like',$person[0]['SYD'])
                    //     ->count();
                    if($roommates >= 2){
                        continue;
                    }
                }
                //构造选宿舍插入的数据
                $data = [
                    'XH'=>$person[0]['XH'],
                    'SSDM'=>$v['SSDM'],
                    'CH'=>'',
                    'YXDM'=>$person[0]['YXDM'],
                    'SDSJ'=>time(),
                    'origin'=>'system',
                    'status'=>'finished',
                ];
                dump($data);
                //走到这里，应该是符合选宿舍条件了，直接插入,插入成功则退出
                // if(Db::name('fresh_list')->insert($data)){
                //     //宿舍信息表更新一下

                //     break;
                // }
                break;
            }
        }
        //$sql = "select A.ID,A.XH,A.XM,A.YXDM,A.XBDM,A.MZ,A.SYD from `fa_fresh_info` A left join `fa_fresh_list` B on A.XH=B.XH where B.ID is null and A.SYD not like '陕西' LIMIT 1";

        //取出第一个没有选宿舍的学生
        //$sql = "select A.ID,A.XH,A.XM,A.YXDM,A.XBDM,A.MZ,A.SYD from `fa_fresh_info` A left join `fa_fresh_list` B on A.XH=B.XH where B.ID is null LIMIT 1";

    }
    public function newfinish(){
        $college_array = Db::name('dict_college') -> field('YXDM') -> select();
        $college = '2400';
        $this -> distribution($college);
    }
    // public function distribution()
    // {
        // $list = Db::query(" select *  from  fa_fresh_info where fa_fresh_info.XH not in (select XH from fa_fresh_list) ");
        // foreach ($list as $key => $value) {
        //     $result = $this -> disbuilding($value);


        //     dump($result);
        //     break;
        // }
    // }
    public function disbuilding($info)
    {
        $msg = array();
        $college_id = $info['YXDM'];
        $sex = $info['XBDM'];
        $place = $info['SYD'];
        $nation = $info['MZ'];
        $data = Db::name('fresh_dormitory') 
                        -> where('YXDM',$college_id)
                        -> where('SYRS','>','0')
                        -> where('XB',$sex)
                        -> select();
                        dump($info);

        foreach ($data as $v) {
            $building = $v['LH'];
            $dormitory = $v['SSH'];
            $list = $this -> getBedNum($sex,$college_id, $building, $dormitory);
            $msg[$building][$dormitory] = $list;
        }
        return $msg;
    }
    private  function getBedNum($sex,$college_id, $building, $dormitory)
    {
        $list = [];
        $data = Db::name('fresh_dormitory') 
                    -> where('YXDM',$college_id)
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
                if ($CP[$i] == "1") {
                    $temp = [];
                    $temp = array(
                        'name' => $k."号床（上床下柜）",                    
                        'value' => $k,
                    );
                    $list[] = $temp;
                }
            }
            return $list;
        } elseif ($length == 6) {
            for ($i=0; $i < $length ; $i++) { 
                $k = $i + 1;
                if ($CP[$i] == 1) {
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
        //使用联查的方法，然后进行过滤
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
            $this->success($dormitory,$bed);
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
            // $result[$k]['info'] = $info;
            // $result[$k]['key'] = $key;
            $response = $DormitoryModel -> submit($info, $key);
            //$array = ['response' => $response, 'dormitory' => $dormitory, 'bed'=> $bed];
          
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
    