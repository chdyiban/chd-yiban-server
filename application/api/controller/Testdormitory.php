<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use think\Hook;
use fast\Random;
use app\common\library\Token;

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
    
    /**
     * 测试并发登录
     */
    public function testlogin()
    {
        header('Access-Control-Allow-Origin:*');
        $count = Db::name('fresh_info') -> count();
        $id = rand(1,$count);
        $info = Db::name('fresh_info') -> where('id',$id) ->field('XH, ZKZH') -> find();
        $XH = $info['XH'];
        $ZKZH = $info['ZKZH'];
        $userid = $this -> loginself($XH, $ZKZH);
        if($userid){
            $this->_token = Random::uuid();
            Token::set($this->_token, $userid, $this->keeptime);
            Hook::listen("user_login_successed", $userid);
            $info = $this -> _token;
            $this->success('认证成功',$info);
        } else {
            $this->error('认证失败','请检查学号以及密码是否正确');
        } 
    }

    private function loginself($XH, $ZKZH)
    {
        $info = Db::name('fresh_info')
                    -> where('XH', $XH)
                    -> where('ZKZH', $ZKZH)
                    ->find(); 
        if (empty($info)) {
            return false;
        } else {
            $userid = $info['ID'];
            return $userid;
        }
    }
    /**
     * 测试返回剩余房间数和床位数
     */
    public function testshow(){
        header('Access-Control-Allow-Origin:*');
        $mem_p1 = memory_get_usage();
        $count = Db::name('fresh_info') -> count();
        $id = rand(1,$count);
        $data = Db::name('fresh_info') -> where('id',$id) ->field('XBDM,YXDM') -> find();
        $college_id = $data['YXDM'];
        $sex = $data['XBDM'];

        $data = Db::name('fresh_dormitory')
                    -> where('YXDM',$college_id)
                    -> where('XB', $sex)
                    -> group('LH')
                    -> select();
        echo Db::name('fresh_dormitory')->getLastSql();
        foreach ($data as $key => $value) {
            $build = $value['LH'];
            if ($build <= 6 && $build > 0) {
                $info = array(
                    'name' =>  $build."号楼（西区）",
                    'value' => $build,
                );   
            } elseif ($build <=15) {
                $info = array(
                    'name' =>  $build."号楼（东区）",
                    'value' => $build,
                );   
            } elseif ( $build <= 19) {
                $info = array(
                    'name' =>  $build."号楼（高层）",
                    'value' => $build,
                );   
            }
            $list[] = $info;
        }
        $dormitory_info = Db::name('fresh_dormitory') -> where('SYRS','>=','1') 
                                -> where('XB',$sex)
                                -> where('YXDM',$college_id)
                                -> field('SYRS')
                                -> select();
        echo Db::name('fresh_dormitory')->getLastSql();
        $dormitory_number = count($dormitory_info);
        $bed_number = 0;
        foreach ($dormitory_info as $key => $value) {
            $bed_number += $value['SYRS'];
        }
        $mem_p9 = memory_get_usage();

        $mem_cost = ($mem_p9 - $mem_p1) / 1024 / 1024 ;

        $this -> success('查询成功', ['memory'=> $mem_cost.'mb','list' => $list, 'dormitory_number' => $dormitory_number, 'bed_number' => $bed_number]);
    }

    public function testLoad(){
        $result = Db::name('fresh_info') -> find();
        $this->success('success',$result);
    }

    public function testPHP(){
        $this->success('success');
    }
    /**
     * 测试服务器环境
     */
    public function testinfo()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel;
        //$steps = parent::getSteps($this->loginInfo['user_id']);
        //$steps = 'setinfo';
        $result = $DormitoryModel -> setinfo($this->userInfo, $key);
        if (!$result['status']) {
            $this -> error($result['msg'], $result['data']);
        } else {
            $data = $result['data'];
            $info = $result['info'];
            $Userinfo = parent::validate($data,'Userinfo.user');
            $this -> success($Userinfo);
            $Family[0] = $Userinfo;
            if (empty($info)) {
                if (gettype($Userinfo) == 'string') {
                    $this->error($Userinfo);
                } 
                //$res = Db::name('fresh_info_add') -> insert($data);
                //$res == 1 ? $this -> success('信息录入成功'): $this -> error('信息录入失败');
                $this -> success($info);
            } else {
                foreach ($info as $key => $value) {
                    $Familyinfo = parent::validate($value,'Userinfo.family');
                    $Family[] = $Familyinfo;
                }
                foreach ($Family as $key => $value) {
                    if (gettype($value) == "string") {
                        $this->error($value);
                    }
                }
                //$res = Db::name('fresh_info_add') -> insert($data);
                // foreach ($info as $key => $value) {
                //    $res1 = Db::name('fresh_family_info') -> insert($value);
                // }
                // if ($res && $res1) {
                //     $this -> success("信息录入成功");
                // }else {
                //     $this -> error("信息录入失败");
                // }
            }
        }
        
    }

}
    