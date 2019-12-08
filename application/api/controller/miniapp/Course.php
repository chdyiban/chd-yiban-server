<?php

namespace app\api\controller\miniapp;

use think\Db;
use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\common\library\Token;
/**
 * 课表查询
 */
class Course extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 只需要token则不加密
     */
    public function index(){
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定账号！");
        }
        $key["id"] = $userInfo["portal_id"];

        $course = $this->get_course($key);
        $data = [
            'week' => get_weeks(),
            'day' => date("w"),
            'lessons' => $course,
            'is_vacation' => 'F',
        ];
        $this->success("success",$data);
    }

    public function get_course($key){
        $stu_id = $key['id'];
        $course = array();
        $res = Db::name('stu_course')->where('XH', $stu_id)->order('ZJ')->select();
        foreach($res as $val){
            $info = array();
            $info['name'] = $val['KCMC'];
            $info['number'] = $val['CXSJ'];
            $info['place'] = $val['SKDD'];
            $info['class_id'] =  $val['XZBJ'];
            $info['teacher'] = $val['JSMC'];
            $info['xf'] = $val['XF'];
            $info['type'] = $val['KCLB'];
            for($i = 0; $i <= 52; $i++){
                if($val['ZC'][$i] == 1){
                    $info['weeks'][] = $i;
                }
            }
            //这块用来构造all_week
            $start_week = array();
            $end_week = array();
            for($i = 0; $i <= 51; $i++){
                if($val['ZC'][$i] == 0 && $val['ZC'][$i+1]== 1){
                    array_push($start_week,$i + 1);
                }
                if($val['ZC'][$i] == 1 && $val['ZC'][$i+1]== 0){
                    array_push($end_week, $i);
                }
            }
            switch(count($start_week)){
                case 1:
                    $info['all_week'] = [$start_week[0].'-'.$end_week[0]];
                    break;
                case 2:
                    $info['all_week'] = [$start_week[0].'-'.$end_week[0], $start_week[1].'-'.$end_week[1]];
                    break;
                case 3:
                    $info['all_week'] = [$start_week[0].'-'.$end_week[0], $start_week[1].'-'.$end_week[1], $start_week[2].'-'.$end_week[2]];
                    break;
            }
            $d = $val['ZJ'] - 1;
            //'name' => '', 'number' => '1', 'place' => '', 'class_id' => '', 'teacher' => '', 'xf' => '', 'type' => ''
        
            //这块用来处理2-4节的情况，没处理好
            if($val['JC'] % 2 == 0){
                $j = ($val['JC'])/2;
                // $class_two = $info;
                // $course[$d][$j-1][0] = $class_two;
                // $class_two['number'] = 1;
                // $course[$d][$j-1][1] = $class_two;
            }else{
                $j = ($val['JC'] + 1)/2 - 1;
            }
            //$j = ($val['JC'] + 1)/2 - 1;
            if(empty($course[$d][0])){
                $course[$d][0] = [];
            } 
            if(empty($course[$d][1])){
                $course[$d][1] = [];
            } 
            if(empty($course[$d][2])){
                $course[$d][2] = [];
            } 
            if(empty($course[$d][3])){
                $course[$d][3] = [];
            } 
            if(empty($course[$d][4])){
                $course[$d][4] = [];
            }
            $course[$d][$j][0] = $info;
        }
        $array_empty = [
            [],
            [],
            [],
            [],
            [],
        ];
        for ($i=0; $i < 7; $i++) { 
            if (empty($course[$i])) {
                $course = array_merge($course , array($i => $array_empty) );
                //$course[$i] = $array_empty;
            }
        }
        // dump($course);
        return $course;
    }

    /**
     * 由于爬虫获取数据问题，18级新生目前课表只能先获取班级然后获取课表
     * 后期要更新
     * 2018-9-15
     * 新生课表信息已经更新，该方法失效
     */
    /*
    public function get_2018_course($stu_id){
        $course = array();
        $class_num = Db::name('fresh_class') -> where('XH', $stu_id) -> field('BJDM') -> find()['BJDM'];
        $res = Db::name('stu_course')->where('XZBJ', $class_num)->order('ZJ')->select();
        foreach($res as $val){
            $info = array();
            $info['name'] = $val['KCMC'];
            $info['number'] = $val['CXSJ'];
            $info['place'] = $val['SKDD'];
            $info['class_id'] =  $val['XZBJ'];
            $info['teacher'] = $val['JSMC'];
            $info['xf'] = $val['XF'];
            $info['type'] = $val['KCLB'];
            for($i = 0; $i <= 52; $i++){
                if($val['ZC'][$i] == 1){
                    $info['weeks'][] = $i;
                }
            }
            //这块用来构造all_week
            $start_week = array();
            $end_week = array();
            for($i = 0; $i <= 51; $i++){
                if($val['ZC'][$i] == 0 && $val['ZC'][$i+1]== 1){
                    array_push($start_week,$i + 1);
                }
                if($val['ZC'][$i] == 1 && $val['ZC'][$i+1]== 0){
                    array_push($end_week, $i);
                }
            }
            switch(count($start_week)){
                case 1:
                    $info['all_week'] = [$start_week[0].'-'.$end_week[0]];
                    break;
                case 2:
                    $info['all_week'] = [$start_week[0].'-'.$end_week[0], $start_week[1].'-'.$end_week[1]];
                    break;
                case 3:
                    $info['all_week'] = [$start_week[0].'-'.$end_week[0], $start_week[1].'-'.$end_week[1], $start_week[2].'-'.$end_week[2]];
                    break;
            }
            $d = $val['ZJ'] - 1;
            //'name' => '', 'number' => '1', 'place' => '', 'class_id' => '', 'teacher' => '', 'xf' => '', 'type' => ''
        
            //这块用来处理2-4节的情况，没处理好
            if($val['JC'] % 2 == 0){
                $j = ($val['JC'])/2;
                // $class_two = $info;
                // $course[$d][$j-1][0] = $class_two;
                // $class_two['number'] = 1;
                // $course[$d][$j-1][1] = $class_two;
            }else{
                $j = ($val['JC'] + 1)/2 - 1;
            }
            //$j = ($val['JC'] + 1)/2 - 1;
            if(empty($course[$d][0])){
                $course[$d][0] = [];
            } 
            if(empty($course[$d][1])){
                $course[$d][1] = [];
            } 
            if(empty($course[$d][2])){
                $course[$d][2] = [];
            } 
            if(empty($course[$d][3])){
                $course[$d][3] = [];
            } 
            if(empty($course[$d][4])){
                $course[$d][4] = [];
            }
            $course[$d][$j][0] = $info;
        }
        return $course;
    }
    */
}