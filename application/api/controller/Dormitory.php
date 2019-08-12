<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;
use app\api\model\Dormitory as DormitoryModel;
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
/**
 * 
 */
class Dormitory extends Freshuser
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    private $loginInfo = null;
    private $token = null;
    private $userInfo = null;
    private $steps = null;

    /*function _initialize(){
        header('Access-Control-Allow-Origin:*');    
        $this -> token = $this->request->param('token');
        $this -> loginInfo = $this->isLogin($this -> token);
        $this -> userInfo = $this -> get_info($this -> token);
        $user_id = $this->loginInfo['user_id'];
        $this -> steps = parent::getSteps($user_id);
        $sex = $this -> userInfo['XBDM'];
        $college_id = $this -> userInfo['college_id'];
        $map_id = $college_id.'_'.$sex;
        if ($user_id != '6237' ) {
            if ($this->steps != 'setinfo') {
                if(!$this->loginInfo){
                    $this->error('参数非法');
                }
                $choice_type = Config::get('dormitory.type');
                //配置的时间是不同时间段开启
                if ($choice_type == 'difftime') {
                    $end_time = Config::get('dormitory.endtime'); 
                    $end_time = strtotime($end_time);
                    $now_time = strtotime('now');
                    $college_id = $this ->userInfo['college_id'];
                    $college_start_time = Config::get('dormitory.'.$college_id);
                    if (empty($college_start_time)) {
                        $this -> error('配置错误');
                    } else {
                        $college_start_time = strtotime($college_start_time);
                        //选宿舍尚未开始
                        if ($now_time < $college_start_time) {
                            $data = array(
                                'college'   =>  $this -> userInfo['college_name'],
                                'start_time'=>  $college_start_time,
                                'end_time'  =>  $end_time,
                                'map_id'    =>  $map_id,
                                'select_status' => 'prepare',
                            );
                            $this -> error($this->userInfo['college_name'].'选宿舍尚未开始',$data);
                        //选宿舍已经结束
                            } elseif($now_time > $end_time) {
                            $data = array(
                                'college' => $this -> userInfo['college_name'],
                                'map_id'  => $map_id,
                                'select_status' => 'end',

                            );
                            $this -> error('选宿舍已经结束啦！',$data); 
                        }
                    }
                } elseif ($choice_type == 'sametime') {
                    $end_time = Config::get('dormitory.endtime'); 
                    $start_time = Config::get('dormitory.sametime'); 
                    $end_time = strtotime($end_time);
                    $start_time = strtotime($start_time);
                    $now_time = strtotime('now');
                    if ($now_time < $start_time) {
                        $data = array(
                            'college'   =>  $this -> userInfo['college_name'],
                            'start_time'=>  $start_time,
                            'end_time'  =>  $end_time,
                            'map_id'    =>  $map_id,
                            'select_status' => 'prepare',
                        );
                        $this -> error($this->userInfo['college_name'].'选宿舍尚未开始',$data);
                    } elseif($now_time > $end_time) {
                        $data = array(
                            'college' => $this -> userInfo['college_name'],
                            'map_id'  => $map_id,
                            'select_status' => 'end',

                        );
                        $this -> error('选宿舍已经结束啦！',$data); 
                    }
                }
            }else {
                if(!$this->loginInfo){
                    $this->error('参数非法');
                }
            }
        }
    }
    */
    /*
    public function init(){
        header('Access-Control-Allow-Origin:*');
        $DormitoryModel = new DormitoryModel;
        switch ($this -> steps) {
            case 'setinfo':
                $user = $this ->userInfo;
                $array = array(
                    'college_name' => $user['college_name'],
                    'name' => $user['name'],
                    'sex' => $user['sex'],
                    'stu_id' => $user['stu_id'],
                );
                $this -> success('success', ['steps' => $this->steps, 'info' => $array]);
                break;
            case 'select':
                $info = $DormitoryModel -> initSteps($this->steps, $this->userInfo);
                $user = $this ->userInfo;
                $array = array(
                    'college_name' => $user['college_name'],
                    'name' => $user['name'],
                    'sex' => $user['sex'],
                    'stu_id' => $user['stu_id'],
                );
                $map_id = $user['college_id'].'_'.$user['XBDM'];
                $this -> success($info['msg'], ['steps' => $this->steps, 'list' => $info['data'], 'dormitory_number' => $info['dormitory_number'], 'bed_number' => $info['bed_number'],'map_id' => $map_id, 'userinfo' => $array]);
                break;

            case 'waited':
                $info = $DormitoryModel -> initSteps($this->steps, $this->userInfo);
                if ($info['status']) {
                    $this -> success($info['msg'], ['steps' => $this->steps, 'info' => $info['data']]);
                } else {
                    $this -> error($info['msg'], ['steps' => $this->steps, 'info' => $info['data']]);
                }
                break;
            
            case 'finished':
                $info = $DormitoryModel -> initSteps($this->steps, $this->userInfo);
                if ($info['status']) {
                    $this -> success($info['msg'], ['steps' => $this->steps, 'info' => $info['data']]);
                } else {
                    $this -> error($info['msg'], ['steps' => $this->steps, 'info' => $info['data']]);
                }
                break;
        }
    }
    */
    /**
     * demo 可以这样实现。
     */
    // public function index(){
    //     dump($this->loginInfo);
    //     echo 'index method';
    // }
    /**
     * 展示可选择宿舍楼以及宿舍号接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * @param string $building 
     * @param string $dormitory
     * @param string $type 有building, dormitory, bed
     * @return array $list 包含楼号/宿舍号/床号
     */
    /*
    public function show()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel;
        //$steps = parent::getSteps($this->loginInfo['user_id']);
        $list = $DormitoryModel -> show($this->userInfo,$key);
        if ($list['status']) {      
            $this -> success($list['msg'], $list['data']);
        } else {
            $this -> error($list['msg'], $list['data']);
        }   
    }
    */
    /**
     * 补充完善信息的接口
     * @param array $infomation
     * 迁移至 Stuinfo.php
     */
    // public function setinfo()
    // {      
    // }

    /**
     *  暂时不需要redis
     * 提交选择至redis
     */
    // public function giveredis()
    // {
    //     $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
    //     $DormitoryModel = new DormitoryModel;
    //     $info = $DormitoryModel -> giveredis($this -> userInfo, $key);   
        
    // }


    /**
     * 提交选择调用接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * @param string $dormitory_id
     * @param string $bed_id
     * @return array data => true/false 
     */
    /*
    public function submit()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel;
        //$steps = parent::getSteps($this->loginInfo['user_id']);
        $info = $DormitoryModel -> submit($this -> userInfo, $key);
        if ($info['status']) {
            $this -> success($info['msg'], $info['data']);
        } else {
            $this -> error($info['msg'], $info['data']);
        }   
    }
    */
    /**
     * 确认床铺接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * @param string type confirm/cancel
     * 
     */
    /*
    public function confirm()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel;
        //$steps = parent::getSteps($this->loginInfo['user_id']);
        $info = $DormitoryModel -> confirm($this -> userInfo, $key);
        if ($info['status']) {
            $this -> success($info['msg'], $info['data']);
        } else {
            $this -> error($info['msg'], $info['data']);
        }   

    }*/

    /**
     * 宿舍确定结束接口
     * @param array $token
     * 
     */
    /*
    public function finished()
    {
        //$key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel;
        //$userid = $this->check($user);
       // $steps = parent::getSteps($this->loginInfo['user_id']);
        $info = $DormitoryModel -> finished($this -> userInfo);
        if ($info['status']){
            $this -> success($info['msg'], $info['data']);
        } else {
            $this -> error($info['msg'], $info['data']);
        }
    }
    */

    /**
     * 获取七牛云上传token
     * bucket2018 => stu2018
     */
    /*
    public function uploadtoken(){

        $upManager = new UploadManager();
        $auth = new Auth(Config::get('qiniu.AccessKey'), Config::get('qiniu.SecretKey'));
        $token = $auth->uploadToken(Config::get('qiniu.bucket2018'));
        $this -> success('success', $token);
    }
*/
    /**
     * 通过token获取学生信息
     * @param string $token
     */
    /*
    private function get_info($token)
    {
        $user_id = $this->loginInfo['user_id'];
        $list = Db::view('fresh_info') 
                    -> view('dict_college', 'YXDM,YXMC','fresh_info.YXDM = dict_college.YXDM')
                    -> where('ID', $user_id) 
                    -> field('XM,XH,SYD,XBDM,MZ')
                    -> find();
        if (!empty($list)) {
            $info['name'] = $list['XM'];
            $info['stu_id'] = $list['XH'];
            $info['place'] = $list['SYD'];
            $info['college_id'] = $list['YXDM'];
            $info['college_name'] = $list['YXMC'];
            $info['XBDM'] = $list['XBDM'];
            $info['sex'] = $list['XBDM'] == '1'? '男':'女';
            $info['nation'] = $list['MZ'];
            return $info;
        } else {
            $this -> error('信息不存在');
        }
    }*/

}







