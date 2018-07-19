<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use think\Validate;
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

    function _initialize(){
        header('Access-Control-Allow-Origin:*');    
        $this -> token = $this->request->param('token');
        $this -> loginInfo = $this->isLogin($this -> token);
        $this -> userInfo = $this -> get_info($this -> token);
        if(!$this->loginInfo){
            $this->error('参数非法');
        }
        $choice_type = Config::get('dormitory.type');
        $end_time = Config::get('dormitory.endtime'); 
        $end_time = strtotime($end_time);
        $now_time = strtotime('now');
        if ($choice_type == 'sametime') {
            $start_time = Config::get('dormitory.sametime');
            $start_time = strtotime($start_time);
            if ($now_time < $start_time || $now_time > $end_time) {
                $this -> error('选宿舍尚未开始');
            } 
        } elseif ($choice_type == 'difftime')  {
            $college_id = $this ->userInfo['college_id'];
            $college_start_time = Config::get('dormitory.'.$college_id);
            $college_start_time = strtotime($college_start_time);
            if ($now_time < $college_start_time || $now_time > $end_time) {
                $this -> error($this->userInfo['college_name'].'选宿舍尚未开始');
            }
        }
    }

    public function init(){
        header('Access-Control-Allow-Origin:*');
        $user_id = $this->loginInfo['user_id'];
        $steps = parent::getSteps($user_id);
        $DormitoryModel = new DormitoryModel;
        $info = $DormitoryModel -> initSteps($steps, $this->userInfo);
        if ($info) {
            $this -> success('success', ['steps' => $steps, 'info' => $info, 'userinfo' => $this ->userInfo]);
        } else {
            $this -> error('error', ['steps' => $steps]);            
        }
    }
    /**
     * demo 可以这样实现。
     */
    public function index(){
        dump($this->loginInfo);
        echo 'index method';
    }
    /**
     * 展示可选择宿舍楼以及宿舍号接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * @param string $building 
     * @param string $dormitory
     * @param string $type 有building, dormitory, bed
     * @return array $list 包含楼号/宿舍号/床号
     */
    public function show()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel;
        //$steps = parent::getSteps($this->loginInfo['user_id']);
        $list = $DormitoryModel -> show($this->userInfo,$key);
        if ($list['status']) {
            if ($key['type'] == 'building') {
                $this -> success($list['msg'], ['data' => $list['data'], 'dormitory_number' => $list['dormitory_number'],'bed_number' => $list['bed_number']]);
            } else {
                $this -> success($list['msg'], ['data' => $list['data']]);
            }
        } else {
            $this -> error($list['msg'], $list['data']);
        }   
    }

    /**
     * 补充完善信息的接口
     * @param array $infomation
     * 迁移至 Stuinfo.php
     */
    // public function setinfo()
    // {      
    // }

    /**
     * 提交选择至redis
     */
    public function giveredis()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel;
        $info = $DormitoryModel -> giveredis($this -> userInfo, $key);   
        
    }


    /**
     * 提交选择调用接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * @param string $dormitory_id
     * @param string $bed_id
     * @return array data => true/false 
     */
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

    /**
     * 确认床铺接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * @param string type confirm/cancel
     * 
     */
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

    }

    /**
     * 宿舍确定结束接口
     * @param array $token
     * 
     */
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

    /**
     * 获取七牛云上传token
     * bucket2018 => stu2018
     */
    public function uploadtoken(){

        $upManager = new UploadManager();
        $auth = new Auth(Config::get('qiniu.AccessKey'), Config::get('qiniu.SecretKey'));
        $token = $auth->uploadToken(Config::get('qiniu.bucket2018'));
        $this -> success('success', $token);
    }

    /**
     * 通过token获取学生信息
     * @param string $token
     */
    private function get_info($token)
    {
        $user_id = $this->loginInfo['user_id'];
        $list = Db::view('fresh_info') 
                    -> view('dict_college', 'YXDM,YXMC','fresh_info.YXDM = dict_college.YXDM')
                    -> where('ID', $user_id) 
                    -> find();
        if ($list) {
            $info['stu_id'] = $list['XH'];
            $info['place'] = $list['SYD'];
            $info['college_id'] = $list['YXDM'];
            $info['college_name'] = $list['YXMC'];
            $info['XBDM'] = $list['XBDM'];
            $info['sex'] = $list['XBDM'] == '1'? '男':'女';
            $info['nation'] = $list['MZ'];
            return $info;
        } else {
            return false;
        }
    }

}







