<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use think\Log;

use app\api\model\Dormitory as DormitoryModel;
/**
 * 
 */
class Finisheddormitory extends Freshuser
{
    //记得该权限
    //distribute
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    private $loginInfo = null;
    private $token = null;
    private $userInfo = null;

    
    /**
     * 宿舍分配方法
     * @author Yang
     * @date 2018.07.29
     * 
     * 原理：在允许分配的原则内，先分配少数民族，再分配非陕西籍，最后按顺序填空
     */
    /*
    public function distribute(){

        set_time_limit(0);
        $t1 = microtime(true);
        //第一步分配少数民族学生
        $sql = "select A.ID,A.XH,A.XM,A.YXDM,A.XBDM,A.MZ,A.SYD from `fa_fresh_info` A  WHERE A.MZ not like '汉族' AND (SELECT COUNT(1) FROM `fa_fresh_result` B WHERE B.XH = A.XH) = 0";
        $not_hz = Db::query($sql);
        echo "未分配少数民族人数:".count($not_hz).'<br/>';
        Log::write('未分配少数民族人数:'.count($not_hz));
        foreach ($not_hz as $key => $value) {
                $this->distributenation($value);
        }
        $t2 = microtime(true);
        echo '<br/>耗时'.round($t2-$t1,3).'秒<br>';
        //第二步分配外省人数
        $sql = "select A.ID,A.XH,A.XM,A.YXDM,A.XBDM,A.MZ,A.SYD from `fa_fresh_info` A  WHERE A.SYD not like '陕西' AND (SELECT COUNT(1) FROM `fa_fresh_result` B WHERE B.XH = A.XH) = 0";
        $not_sx = Db::query($sql);
        echo "未分配外省人数:".count($not_sx).'<br/>';
        Log::write('未分配外省人数'.count($not_sx));
        foreach ($not_sx as $key => $value) {
                $this->distributeplace($value);
        }
        $t3 = microtime(true);
        echo '<br/>耗时'.round($t3-$t1,3).'秒<br>';

        
        //2.统计未分配的非陕西籍学生数量
        //$sql = "select count(*) as num from `fa_fresh_info` as A left join `fa_fresh_result` as B on A.XH=B.XH where B.ID is null and A.SYD not like '陕西' LIMIT 1";
        //效率太差注释掉
        //$sql = "select A.ID as stu_id from `fa_fresh_info` as A left join `fa_fresh_result` as B on A.XH=B.XH where B.ID is null and A.SYD not like '陕西'";
        //$not_hz = Db::query($sql);
        //dump($not_hz);
        //$count_of_not_hz = count($not_hz);
        //echo "未分配非陕西籍人数:".$count_of_not_hz.'<br/>';
        
        $sql = "select A.ID,A.XH,A.XM,A.YXDM,A.XBDM,A.MZ,A.SYD from `fa_fresh_info` A left join `fa_fresh_result` B on A.XH=B.XH where B.ID is null";
        $nomal = Db::query($sql);
        echo "未分配正常人数:".count($nomal).'<br/>';
        Log::write('未分配正常人数'.count($nomal));
        foreach ($nomal as $key => $value) {
            $this->distributenomal($value);
        }
        $t4 = microtime(true);
        echo '<br/>耗时'.round($t4-$t1,3).'秒<br>';
    }


    private function distributenomal($person){
        $rooms = Db::name('fresh_dormitory_north')
                ->where('YXDM',$person['YXDM'])//找学院
                ->where('XB',$person['XBDM'])//找性别
                ->where('SYRS','>=',1)
                ->select();
        //echo Db::name('fresh_dormitory_north')->getLastSql();
        //遍历每一个宿舍
        foreach ($rooms as $k => $v) {
            // dump($v);
            //遍历可用宿舍
            $map['SSDM'] = $v['SSDM'];
            //$roommates = Db::name('fresh_result')->where($map)->select();
            //判断是否“可住”:
            //1.是不是有相同少数民族，学生为汉族则不用考虑，为了通用性，这里要判断一下
            if($person['MZ'] !== '汉族'){
                $roommates = Db::view('fresh_result') 
                        ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_result.XH = fresh_info.XH')
                        ->where($map) 
                        ->where('MZ','like',$person['MZ'])
                        ->count();
                //该宿舍与备选person相同民族的人数大于或等于1，则不能选，跳出
                if($roommates >= 1){
                    continue;
                }
            }
            //2.如果不是陕西籍，那么该省份有几个？
            if($person['SYD'] != '陕西'){
                $roommates = Db::view('fresh_result') 
                        ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_result.XH = fresh_info.XH')
                        ->where($map) 
                        ->where('SYD','like',$person['SYD'])
                        ->count();
                if($roommates >= 2){
                    continue;
                }
            }
            
            //3.顺序选择床铺并插入
            $bedNum = 0;//床号
            $bedArray = str_split($v['CPXZ']);
            foreach ($bedArray as $key => &$value) {
                if($value != '0'){
                    //符合宣传条件
                    $bedNum = $key + 1;
                    $value = 0;
                    $restNum = $v['SYRS']-1;
                    break;
                }
            }
            //构造选宿舍插入的数据
            $data = [
                'XH'=>$person['XH'],
                'SSDM'=>$v['SSDM'],
                'CH'=>$bedNum,
                'YXDM'=>$person['YXDM'],
                'CWDM'=>"north-".$v["SSDM"]."-".$bedNum,
                'SDSJ'=>time(),
                'origin'=>'system',
                'status'=>'finished',
            ];
            Log::write('正常学生的插入数据'.json_encode($data));
            //dump($data);
            //走到这里，应该是符合选宿舍条件了，直接插入,插入成功则退出
            if(Db::name('fresh_result')->insert($data)){
                //宿舍信息表更新一下
                Db::name('fresh_dormitory_north')
                    ->where('SSDM',$v['SSDM'])
                    ->where('YXDM',$person['YXDM'])
                    ->update([
                        'SYRS' => $restNum,
                        'CPXZ' => implode('',$bedArray)
                    ]);
                $sql = Db::name('fresh_result')->getLastSql();
                //Log::write('正常学生的SQL语句'.$sql,Log::SQL);
               // echo Db::name('fresh_dormitory_north')->getLastSql();
                //echo '<script>window.location.href="http://localhost/fastadmin/public/api/Finisheddormitory/distribute";</script>';
                //echo '<script>window.location.href="http://localhost:8080/yibanbx/public/api/Finisheddormitory/distribute";</script>';
                break;
            }else{
                $sql = Db::name('fresh_result')->getLastSql();
                //Log::write('正常学生的SQL语句'.$sql,Log::SQL);
                //echo Db::name('fresh_result')->getLastSql();
            }
        }
    }
    private function distributeplace($person)
    {
        //随机分配少数民族宿舍，否则会出现少数民族在前面聚集的情况
        $rooms = Db::name('fresh_dormitory_north')
                ->where('YXDM',$person['YXDM'])//找学院
                ->where('XB',$person['XBDM'])//找性别
                ->where('SYRS','>=',1)
                ->select();
    
        foreach($rooms as $k => $v){
                $map['SSDM'] = $v['SSDM'];
                //民族
                if($person['MZ'] != '汉族'){
                    $roommates = Db::view('fresh_result') 
                            ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_result.XH = fresh_info.XH')
                            ->where($map) 
                            ->where('MZ','like',$person['MZ'])
                            ->count();
                    if($roommates >= 1){
                        //这个宿舍不能选，删除
                        //echo '删除宿舍<br/>';
                        unset($rooms[$k]);
                        $rooms = array_values($rooms);
                    }
                }

                //生源地
                $roommates = Db::view('fresh_result') 
                        ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_result.XH = fresh_info.XH')
                        ->where($map) 
                        ->where('SYD','like',$person['SYD'])
                        ->count();
                if($roommates >= 2){
                    //这个宿舍不能选，删除
                    //echo '删除宿舍<br/>';
                    unset($rooms[$k]);
                    $rooms = array_values($rooms);
                }
            }
            dump($person);
            $restNum = count($rooms);
            if($restNum == 0){
                exit('没有剩余房源');
            }
            $restKey = rand(0,$restNum-1);
            //echo "restNum:$restNum<br/>";
            //echo "restkey:$restKey<br/>";

            //找到下标为restKey的房间的情况
            $restRoom = $rooms[$restKey];
            $bedKey = rand(1,$restRoom['SYRS']);
            //dump($bedKey);
            //dump($restRoom);
            //随机选择床铺
            $bedNum = 0;//床号
            $bedArray = str_split($restRoom['CPXZ']);
            
            foreach ($bedArray as $key => &$value) {
                if($value == '0'){
                    //该床铺已经被选择，跳过，下标+1
                    //$bedKey++;
                    continue;
                }else{
                    $bedKey--;
                    if($bedKey == 0){
                        $bedNum = $key+1;
                        $value = 0;
                        break;
                    }
                }
            }

            //echo $bedNum;
            //构造选宿舍插入的数据
            $data = [
                'XH'=>$person['XH'],
                'SSDM'=>$restRoom['SSDM'],
                'CH'=>$bedNum,
                'YXDM'=>$person['YXDM'],
                'CWDM'=>"north-".$restRoom["SSDM"]."-".$bedNum,
                'SDSJ'=>time(),
                'origin'=>'system',
                'status'=>'finished',
            ];
            Log::write('外省插入的数据'.json_encode($data));
            //dump($data);
            if(Db::name('fresh_result')->insert($data)){
                //宿舍信息表更新一下
                Db::name('fresh_dormitory_north')
                    ->where('SSDM',$restRoom['SSDM'])
                    ->where('YXDM',$person['YXDM'])
                    ->update([
                        'SYRS' => $restRoom['SYRS'] - 1,
                        'CPXZ' => implode('',$bedArray)
                    ]);
                //echo '床铺号：'.$bedNum.'<br/>';
                //echo Db::name('fresh_dormitory_north')->getLastSql();
                //$sql = Db::name('fresh_dormitory_north')->getLastSql();
                //Log::write('外省插入的SQL语句'.$sql,Log::SQL);
                
                //echo 'Now memory_get_usage: ' . memory_get_usage()/(1024*1024) . 'MB <br />';
                //echo '<script>window.location.href="http://localhost/fastadmin/public/api/Finisheddormitory/distribute";</script>';
                //echo '<script>window.location.href="http://localhost:8080/yibanbx/public/api/Finisheddormitory/distribute";</script>';
            }else{
                //$sql = Db::name('fresh_result')->getLastSql();
                //Log::write('外省插入的SQL语句'.$sql,Log::SQL);
                //echo Db::name('fresh_result')->getLastSql();
            } 
    }
    private function distributenation($person)
    {
        //随机分配少数民族宿舍，否则会出现少数民族在前面聚集的情况
        $rooms = Db::name('fresh_dormitory_north')
                ->where('YXDM',$person['YXDM'])//找学院
                ->where('XB',$person['XBDM'])//找性别
                ->where('SYRS','>=',1)
                ->select();

        foreach($rooms as $k => $v){
            $map['SSDM'] = $v['SSDM'];
            $roommates = Db::view('fresh_result') 
                ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_result.XH = fresh_info.XH')
                ->where($map) 
                ->where('MZ','like',$person['MZ'])
                ->count();
            if($roommates >= 1){
                //这个宿舍不能选，删除
                //echo '删除宿舍<br/>';
                unset($rooms[$k]);
                $rooms = array_values($rooms);
            }

            if($person['SYD'] != '陕西'){
                $roommates = Db::view('fresh_result') 
                    ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_result.XH = fresh_info.XH')
                    ->where($map) 
                    ->where('SYD','like',$person['SYD'])
                    ->count();
                if($roommates >= 2){
                    //这个宿舍不能选，删除
                   // echo '删除宿舍<br/>';
                    unset($rooms[$k]);
                    $rooms = array_values($rooms);
                }
            }
        }

        $restNum = count($rooms);
        if($restNum == 0){
            exit('没有剩余房源');
        }
        $restKey = rand(0,$restNum-1);
        //echo "restNum:$restNum<br/>";
        //echo "restkey:$restKey<br/>";

        //找到下标为restKey的房间的情况
        $restRoom = $rooms[$restKey];
        $bedKey = rand(1,$restRoom['SYRS']);
        //dump($bedKey);
        //dump($restRoom);
        //随机选择床铺
        $bedNum = 0;//床号
        $bedArray = str_split($restRoom['CPXZ']);
        
        foreach ($bedArray as $key => &$value) {
            if($value == '0'){
                //该床铺已经被选择，跳过，下标+1
                //$bedKey++;
                continue;
            }else{
                $bedKey--;
                if($bedKey == 0){
                    $bedNum = $key+1;
                    $value = 0;
                    break;
                }
            }
        }

        //echo $bedNum;
        //构造选宿舍插入的数据
        $data = [
            'XH'=>$person['XH'],
            'SSDM'=>$restRoom['SSDM'],
            'CH'=>$bedNum,
            'YXDM'=>$person['YXDM'],
            'CWDM'=>"north-".$restRoom["SSDM"]."-".$bedNum,
            'SDSJ'=>time(),
            'origin'=>'system',
            'status'=>'finished',
        ];
        Log::write('少数民族插入的数据'.json_encode($data));
        if(Db::name('fresh_result')->insert($data)){
            //宿舍信息表更新一下
            Db::name('fresh_dormitory_north')
                ->where('SSDM',$restRoom['SSDM'])
                ->where('YXDM',$person['YXDM'])
                ->update([
                    'SYRS' => $restRoom['SYRS'] - 1,
                    'CPXZ' => implode('',$bedArray)
                ]);
            $sql = Db::name('fresh_dormitory_north')->getLastSql();
            //Log::write('少数民族插入时的SQL语句'.$sql,Log::SQL);
           // echo '床铺号：'.$bedNum.'<br/>';
            //echo Db::name('fresh_dormitory_north')->getLastSql();
            //echo '<script>window.location.href="http://localhost/fastadmin/public/api/Finisheddormitory/distribute";</script>';
            // echo '<script>window.location.href="http://localhost:8080/yibanbx/public/api/Finisheddormitory/distribute";</script>';
        }
    }
    */
    //从这里开始都是用来提供测试数据的方法
    /**
     * 分配宿舍方法
     */
    /*
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
            $exit_stu = Db::name('fresh_result') -> where('XH', $stu_id) -> find();
            if (!empty($exit_stu)) {
                unset($stu_info[$k]);
            }
        }

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
    }
    */
    /**
     * 得到一个随机选择的楼号
     * @return int
     */
    /*
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
    */
    /**
     * 获取对应楼号能选的宿舍号
     * @return array ['status' => true, 'msg' => "查询成功", 'data' => array]
     * @return array ['status' => false, 'msg' => '该楼已经没有空宿舍了', 'data' => null]
     */
    /*
    private function getDormitory($info, $building_choice)
    {
        $DormitoryModel = new DormitoryModel;
        //将楼号构造数组得到随机分配的宿舍
        $key = array('type' => 'dormitory', 'building' => $building_choice);
        $dormitory = $DormitoryModel -> show($info, $key);
        return $dormitory;

    }
    */
    /**
     * 获取某楼某宿舍对应的床号
     * @return array ['status' => false, 'msg' => "因不符合学校相关住宿规定，该宿舍无法选择", 'data' => null];
     * @return array ['status' => true, 'msg' => "查询成功", 'data' => $list];
     */
    /*
    private function getBed($info, $building_choice, $dormitory_choice)
    {
        $DormitoryModel = new DormitoryModel;
        //将楼号构造数组得到随机分配的宿舍
        $key = array('type' => 'bed', 'building' => $building_choice, 'dormitory' => $dormitory_choice);
        $bed = $DormitoryModel -> show($info, $key);
        return $bed;
    }
    */

}

