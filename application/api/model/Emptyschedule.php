<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;
use think\Validate;

class Emptyschedule extends Model
{
    // 表名
    protected $name = '';

    /**
     * 获取餐厅拥挤情况数据
     */
    public function getMsg()
    {
        $data =  json_decode(Http::get(self::GET_MSG_URL),"true");
        if ($data["code"] == 200) {
            return ["status" => true,"msg" => "查询成功","data" => $data["data"]];
        } 
        return ["status" => false,"msg" => "查询失败，请稍后再试","data" => "" ];

    }
    public function test(){
        $return = [];
        $emptyclass = Db::table('fa_empty_class')
                        ->where('classroom','exp',"like 'WH1101'")
                        ->select();
        return ['status' =>true,'data'=>$emptyclass];
    }
    
    public function getClassroom($key){
        $emptyclass=[];
        $emptyclass[0]['area'] = 'WH 1区';
        $emptyclass[1]['area'] = 'WH 2区';
        $emptyclass[2]['area'] = 'WM 1区';
        $emptyclass[3]['area'] = 'WM 2区';
        $emptyclass[4]['area'] = 'WM 3区';
        $emptyclass[5]['area'] = 'WX 1区';
        $emptyclass[6]['area'] = 'WX 2区';
        $emptyclass[7]['area'] = 'WX 3区';
        $emptyclass[8]['area'] = 'WX 4区';
        $emptyclass[9]['area'] = 'WT';

        $emptyclass[0]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WH1%' and ".$key)->select();
        $emptyclass[1]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WH2%' and ".$key)->select();

        $emptyclass[2]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WM1%' and ".$key)->select();
        $emptyclass[3]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WM2%' and ".$key)->select();
        $emptyclass[4]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WM3%' and ".$key)->select();

        $emptyclass[5]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WX1%' and ".$key)->select();
        $emptyclass[6]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WX2%' and ".$key)->select();
        $emptyclass[7]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WX3%' and ".$key)->select();
        $emptyclass[8]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WX4%' and ".$key)->select();

        $emptyclass[9]['list'] = Db::table('fa_empty_class')->whereRaw("classroom like 'WT%' and ".$key)->select();
                        
        return ['status' =>true,'data'=>$emptyclass];
    }
 
}