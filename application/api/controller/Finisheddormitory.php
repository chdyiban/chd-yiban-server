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

    public function distribute(){

        set_time_limit(0);
        $t1 = microtime(true);
        //第一步分配少数民族学生
        $sql = "select A.ID,A.XH,A.XM,A.YXDM,A.XBDM,A.MZ,A.SYD from `fa_fresh_info` A  WHERE A.MZ not like '汉族' AND (SELECT COUNT(1) FROM `fa_fresh_list` B WHERE B.XH = A.XH) = 0";
        $not_hz = Db::query($sql);
        echo "未分配少数民族人数:".count($not_hz).'<br/>';
        Log::write('未分配少数民族人数:'.count($not_hz));
        foreach ($not_hz as $key => $value) {
            $this->distributenation($value);
        }
        $t2 = microtime(true);
        echo '<br/>耗时'.round($t2-$t1,3).'秒<br>';
        //第二步分配外省人数
        $sql = "select A.ID,A.XH,A.XM,A.YXDM,A.XBDM,A.MZ,A.SYD from `fa_fresh_info` A  WHERE A.SYD not like '陕西' AND (SELECT COUNT(1) FROM `fa_fresh_list` B WHERE B.XH = A.XH) = 0";
        $not_sx = Db::query($sql);
        echo "未分配外省人数:".count($not_sx).'<br/>';
        Log::write('未分配外省人数'.count($not_sx));
        foreach ($not_sx as $key => $value) {
            $this->distributeplace($value);
        }
        $t3 = microtime(true);
        echo '<br/>耗时'.round($t3-$t1,3).'秒<br>';

        
        //2.统计未分配的非陕西籍学生数量
        //$sql = "select count(*) as num from `fa_fresh_info` as A left join `fa_fresh_list` as B on A.XH=B.XH where B.ID is null and A.SYD not like '陕西' LIMIT 1";
        //效率太差注释掉
        //$sql = "select A.ID as stu_id from `fa_fresh_info` as A left join `fa_fresh_list` as B on A.XH=B.XH where B.ID is null and A.SYD not like '陕西'";
        //$not_hz = Db::query($sql);
        //dump($not_hz);
        //$count_of_not_hz = count($not_hz);
        //echo "未分配非陕西籍人数:".$count_of_not_hz.'<br/>';
        
        $sql = "select A.ID,A.XH,A.XM,A.YXDM,A.XBDM,A.MZ,A.SYD from `fa_fresh_info` A left join `fa_fresh_list` B on A.XH=B.XH where B.ID is null";
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
        $rooms = Db::name('fresh_dormitory')
                ->where('YXDM',$person['YXDM'])//找学院
                ->where('XB',$person['XBDM'])//找性别
                ->where('SYRS','>=',1)
                ->select();
        //echo Db::name('fresh_dormitory')->getLastSql();
        //遍历每一个宿舍
        foreach ($rooms as $k => $v) {
            // dump($v);
            //遍历可用宿舍
            $map['SSDM'] = $v['SSDM'];
            //$roommates = Db::name('fresh_list')->where($map)->select();
            //判断是否“可住”:
            //1.是不是有相同少数民族，学生为汉族则不用考虑，为了通用性，这里要判断一下
            if($person['MZ'] !== '汉族'){
                $roommates = Db::view('fresh_list') 
                        ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
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
                $roommates = Db::view('fresh_list') 
                        ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
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
                'SDSJ'=>time(),
                'origin'=>'system',
                'status'=>'finished',
            ];
            Log::write('正常学生的插入数据'.json_encode($data));
            //dump($data);
            //走到这里，应该是符合选宿舍条件了，直接插入,插入成功则退出
            if(Db::name('fresh_list')->insert($data)){
                //宿舍信息表更新一下
                Db::name('fresh_dormitory')
                    ->where('SSDM',$v['SSDM'])
                    ->where('YXDM',$person['YXDM'])
                    ->update([
                        'SYRS' => $restNum,
                        'CPXZ' => implode('',$bedArray)
                    ]);
                $sql = Db::name('fresh_list')->getLastSql();
                //Log::write('正常学生的SQL语句'.$sql,Log::SQL);
               // echo Db::name('fresh_dormitory')->getLastSql();
                //echo '<script>window.location.href="http://localhost/fastadmin/public/api/Finisheddormitory/distribute";</script>';
                //echo '<script>window.location.href="http://localhost:8080/yibanbx/public/api/Finisheddormitory/distribute";</script>';
                break;
            }else{
                $sql = Db::name('fresh_list')->getLastSql();
                //Log::write('正常学生的SQL语句'.$sql,Log::SQL);
                //echo Db::name('fresh_list')->getLastSql();
            }
        }
    }
    private function distributeplace($person)
    {
        //随机分配少数民族宿舍，否则会出现少数民族在前面聚集的情况
        $rooms = Db::name('fresh_dormitory')
                ->where('YXDM',$person['YXDM'])//找学院
                ->where('XB',$person['XBDM'])//找性别
                ->where('SYRS','>=',1)
                ->select();

        foreach($rooms as $k => $v){
                $map['SSDM'] = $v['SSDM'];
                //民族
                if($person['MZ'] != '汉族'){
                    $roommates = Db::view('fresh_list') 
                            ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
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
                $roommates = Db::view('fresh_list') 
                        ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
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
                'SDSJ'=>time(),
                'origin'=>'system',
                'status'=>'finished',
            ];
            Log::write('外省插入的数据'.json_encode($data));
            //dump($data);
            if(Db::name('fresh_list')->insert($data)){
                //宿舍信息表更新一下
                Db::name('fresh_dormitory')
                    ->where('SSDM',$restRoom['SSDM'])
                    ->where('YXDM',$person['YXDM'])
                    ->update([
                        'SYRS' => $restRoom['SYRS'] - 1,
                        'CPXZ' => implode('',$bedArray)
                    ]);
                //echo '床铺号：'.$bedNum.'<br/>';
                //echo Db::name('fresh_dormitory')->getLastSql();
                //$sql = Db::name('fresh_dormitory')->getLastSql();
                //Log::write('外省插入的SQL语句'.$sql,Log::SQL);
                
                //echo 'Now memory_get_usage: ' . memory_get_usage()/(1024*1024) . 'MB <br />';
                //echo '<script>window.location.href="http://localhost/fastadmin/public/api/Finisheddormitory/distribute";</script>';
                //echo '<script>window.location.href="http://localhost:8080/yibanbx/public/api/Finisheddormitory/distribute";</script>';
            }else{
                //$sql = Db::name('fresh_list')->getLastSql();
                //Log::write('外省插入的SQL语句'.$sql,Log::SQL);
                //echo Db::name('fresh_list')->getLastSql();
            } 
    }
    private function distributenation($person)
    {
        //随机分配少数民族宿舍，否则会出现少数民族在前面聚集的情况
        $rooms = Db::name('fresh_dormitory')
                ->where('YXDM',$person['YXDM'])//找学院
                ->where('XB',$person['XBDM'])//找性别
                ->where('SYRS','>=',1)
                ->select();

        foreach($rooms as $k => $v){
            $map['SSDM'] = $v['SSDM'];
            $roommates = Db::view('fresh_list') 
                ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
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
                $roommates = Db::view('fresh_list') 
                    ->view('fresh_info', 'XM, XH, SYD, MZ', 'fresh_list.XH = fresh_info.XH')
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
            'SDSJ'=>time(),
            'origin'=>'system',
            'status'=>'finished',
        ];
        Log::write('少数民族插入的数据'.json_encode($data));
        if(Db::name('fresh_list')->insert($data)){
            //宿舍信息表更新一下
            Db::name('fresh_dormitory')
                ->where('SSDM',$restRoom['SSDM'])
                ->where('YXDM',$person['YXDM'])
                ->update([
                    'SYRS' => $restRoom['SYRS'] - 1,
                    'CPXZ' => implode('',$bedArray)
                ]);
            $sql = Db::name('fresh_dormitory')->getLastSql();
            //Log::write('少数民族插入时的SQL语句'.$sql,Log::SQL);
           // echo '床铺号：'.$bedNum.'<br/>';
            //echo Db::name('fresh_dormitory')->getLastSql();
            //echo '<script>window.location.href="http://localhost/fastadmin/public/api/Finisheddormitory/distribute";</script>';
            // echo '<script>window.location.href="http://localhost:8080/yibanbx/public/api/Finisheddormitory/distribute";</script>';
        }
    }

}

