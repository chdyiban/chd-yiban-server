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
class Testdormitory extends Freshuser
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    private $loginInfo = null;
    private $token = null;
    private $userInfo = null;
    const LOCAL_URL = "http://localhost:8080/yibanbx/public/api/";
    const SERVICE_URL = "https://service.knocks.tech/api/";

    public function test()
    {
        $type = $this -> request -> get('type');
        if ($type = "local"){
            $url_base = self::LOCAL_URL;
        }
        if ($type = "service") {
            $url_base = self::SERVICE_URL;
        }
        $stu_info = Db::name('fresh_info') -> where('LXDH','') -> find();
        $stu_id   = $stu_info['XH'];
        $stu_name = $stu_info['XM'];
        $stu_zkzh = $stu_info['ZKZH'];
        $login_url = $url_base."Freshuser/login?XM=".$stu_name."&XH=".$stu_id."&ZKZH=".$stu_zkzh;
        $response_login = Http::get($login_url);
        $response_login = json_decode($response_login,true);
        $token = $response_login['data'][1];
        $res = Db::name('fresh_info') -> where('XH', $stu_id) -> update(['LXDH' => '1']);
        
        $show_url = $url_base."dormitory/show?token=".$token."&type=building";
        $response_show_building = Http::get($show_url);
        $response_show_building = json_decode($response_show_building,true);
        $building = $response_show_building['data'];
        $count = count($building);
        $building_choice = rand(0, $count-1);
        $building_choice = $building[$building_choice];

        $show_dormitory_url = $url_base."dormitory/show?token=".$token."&type=dormitory&building=".$building_choice;
        $response_show_dormitory = Http::get($show_dormitory_url);
        $response_show_dormitory = json_decode($response_show_dormitory,true);
        $dormitory = $response_show_dormitory['data'];
        $count = count($dormitory);
        $dormitory_choice = rand(0, $count-1);
        $dormitory_choice = $dormitory[$dormitory_choice];
        
        $show_bed_url = $url_base."dormitory/show?token=".$token."&type=bed&building=".$building_choice."&dormitory=".$dormitory_choice;
        $response_show_bed = Http::get($show_bed_url);
        $response_show_bed = json_decode($response_show_bed,true);
        $bed = $response_show_bed['data'];    
        while ($bed == "该宿舍陕西省人数过多，请更换！") {
            $show_dormitory_url = $url_base."dormitory/show?token=".$token."&type=dormitory&building=".$building_choice;
            $response_show_dormitory = Http::get($show_dormitory_url);
            $response_show_dormitory = json_decode($response_show_dormitory,true);
            $dormitory = $response_show_dormitory['data'];
            $count = count($dormitory);
            $dormitory_choice = rand(0, $count-1);
            $dormitory_choice = $dormitory[$dormitory_choice];
            
            $show_bed_url = $url_base."dormitory/show?token=".$token."&type=bed&building=".$building_choice."&dormitory=".$dormitory_choice;
            $response_show_bed = Http::get($show_bed_url);
            $response_show_bed = json_decode($response_show_bed,true);
            $bed = $response_show_bed['data']; 
        }
        $count = count($bed);
        $bed_choice = rand(0, $count-1);
        $bed_choice = $bed[$bed_choice];
        
        $submit_url = $url_base."dormitory/submit?token=".$token."&dormitory_id=".$building_choice."_".$dormitory_choice."&bed_id=".$bed_choice;
        $response_submit = Http::get($submit_url);
        $response_submit = json_decode($response_submit,true);
        
        $confirm_url = $url_base."dormitory/confirm?token=".$token."&type=confirm";
        $response_confirm= Http::get($confirm_url);
        $response_confirm = json_decode($response_confirm,true);
       
        $finish_url = $url_base."dormitory/finished?token=".$token."&type=confirm";
        $finish_url= Http::get($finish_url);
        $finish_url = json_decode($finish_url,true);
        
    }
    

}
    