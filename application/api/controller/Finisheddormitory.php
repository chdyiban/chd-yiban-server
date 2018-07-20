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
    const LOCAL_URL = "http://localhost:8080/yibanbx/public/api/";
    const SERVICE_URL = "http://yiban.chd.edu.cn/api/";

    public function finish()
    {
        $type = $this -> request -> get('type');
        if ($type = 'local'){
            $url_base = self::LOCAL_URL;
        }elseif ($type = 'service') {
            $url_base = self::SERVICE_URL;
        } else {
            $url_base = self::LOCAL_URL;
        }

        $stu_info = Db::name('fresh_info') -> select();
        foreach ($stu_info as $k => $value) {
            $stu_id   = $value['XH'];
            $exit_stu = Db::name('fresh_list') -> where('XH', $stu_id) -> find();
            if (!empty($exit_stu)) {
                unset($stu_info[$k]);
            }
        }

        foreach ($stu_info as $k => $v) {
            $stu_id   = $v['XH'];
            $stu_name = $v['XM'];
            $stu_zkzh = $v['ZKZH'];
            $login_url = $url_base.'Freshuser/login';
            //获取token
            $info = array('XH' => $stu_id, 'ZKZH' => $stu_zkzh);
            $key = array('key' => base64_encode(json_encode($info)));
            $response_login = json_decode(Http::post($login_url, $key), true);
            $token = $response_login['data'][1];
            //得出所选楼号
            $show_url = $url_base."dormitory/show?token=".$token;
            $info = array('type' => 'building');
            $key = array('key' => base64_encode(json_encode($info)));
            $response_show_building = Http::post($show_url, $key);
            $response_show_building = json_decode($response_show_building,true);
            $building = $response_show_building['data']['data'];
            $count = count($building);
            $building_choice = rand(0, $count-1);
            $building_choice = $building[$building_choice]['value'];
            //将楼号构造数组得到随机分配的宿舍
            $info = array('type' => 'dormitory', 'building' => $building_choice);
            $key = array('key' => base64_encode(json_encode($info)));
            $response_show_dormitory = Http::post($show_url, $key);
            $response_show_dormitory = json_decode($response_show_dormitory,true);
            $dormitory = $response_show_dormitory['data']['data'];
            $count = count($dormitory);
            $dormitory_choice = rand(0, $count-1);
            $dormitory_choice = $dormitory[$dormitory_choice]['value'];


            //将楼号以及宿舍号构造数组得到可选床号
            $info = array('type' => 'bed', 'building' => $building_choice, 'dormitory' => $dormitory_choice);
            $key = array('key' => base64_encode(json_encode($info)));
            $response_show_bed = Http::post($show_url, $key);
            $response_show_bed = json_decode($response_show_bed,true);

            while ($response_show_bed['code'] == 0) {
                 //将楼号构造数组得到随机分配的宿舍
                $info = array('type' => 'dormitory', 'building' => $building_choice);
                $key = array('key' => base64_encode(json_encode($info)));
                $response_show_dormitory = Http::post($show_url, $key);
                $response_show_dormitory = json_decode($response_show_dormitory,true);
                $dormitory = $response_show_dormitory['data']['data'];
                $count = count($dormitory);
                $dormitory_choice = rand(0, $count-1);
                $dormitory_choice = $dormitory[$dormitory_choice]['value'];


                //将楼号以及宿舍号构造数组得到可选床号
                $info = array('type' => 'bed', 'building' => $building_choice, 'dormitory' => $dormitory_choice);
                $key = array('key' => base64_encode(json_encode($info)));
                $response_show_bed = Http::post($show_url, $key);
                $response_show_bed = json_decode($response_show_bed,true);
            }
            $bed = $response_show_bed['data']['data'];
            $count = count($bed);
            $bed_choice = rand(0, $count-1);
            $bed_choice = $bed[$bed_choice]['value'];

            //将选择宿舍的结果提交
            $dormitory_id = $building_choice.'#'.$dormitory_choice;
            $info = array('dormitory_id' => $dormitory_id, 'bed_id' => $bed_choice, 'origin' => 'system');
            $key = array('key' => base64_encode(json_encode($info)));
            $submit_url = $url_base."dormitory/submit?token=".$token;
            $response_submit = Http::post($submit_url, $key);
            $response_submit = json_decode($response_submit,true);

            if ($response_submit['code'] == 1) {
                
                $info = array('type' => 'confirm');
                $key = array('key' => base64_encode(json_encode($info)));
                $confirm_url = $url_base."dormitory/confirm?token=".$token;
                $response_confirm= Http::post($confirm_url, $key);
                $response_confirm = json_decode($response_confirm,true);
                
            } else {
                continue;
            }
            
        }
        

        
  
    
        
       
        // $finish_url = $url_base."dormitory/finished?token=".$token."&type=confirm";
        // $finish_url= Http::get($finish_url);
        // $finish_url = json_decode($finish_url,true);
        
    }
    

}
    