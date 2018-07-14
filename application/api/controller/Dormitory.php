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
        if(!$this->loginInfo){
            $this->error('失败','参数非法');
        }
        $this -> userInfo = $this -> get_info($this -> token);  

    }
    public function init(){
        header('Access-Control-Allow-Origin:*');
        $user_id = $this->loginInfo['user_id'];
        $steps = parent::getSteps($user_id);
        $DormitoryModel = new DormitoryModel;
        $info = $DormitoryModel -> initSteps($steps, $this->userInfo);
        if ($info) {
            $this -> success('success', ['steps' => $steps, 'info' => $info]);
        } else {
            $this -> error('error', $steps);            
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
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $DormitoryModel = new DormitoryModel;
        $steps = parent::getSteps($this->loginInfo['user_id']);
        $list = $DormitoryModel -> show($this->userInfo,$key, $steps);
        if ($list) {
            $this -> success('查询成功', $list);
        } else {
            $this -> error('请求失败', $list);
        }   
    }

    /**
     * 补充完善信息的接口
     * @param array $infomation
     */
    public function setinfo()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel;
        $steps = parent::getSteps($this->loginInfo['user_id']);
        $result = $DormitoryModel -> setinfo($this->userInfo, $key, $steps);
        $data = $result['data'];
        $info = $result['info'];
        $Userinfo = $this -> validate($data,'Userinfo.user');
        $Family[0] = $Userinfo;
        foreach ($info as $key => $value) {
            $Familyinfo = $this -> validate($value,'Userinfo.family');
            $Family[] = $Familyinfo;
        }
        foreach ($Family as $key => $value) {
            if (gettype($value) == "string") {
                $this->error($value);
            }
        }
        $res = Db::name('fresh_info_add') -> insert($data);
        foreach ($info as $key => $value) {
            $res1 = Db::name('fresh_family_info') -> insert($value);
        }
        if ($res && $res1) {
            $this -> success("信息录入成功");
        }else {
            $this -> error("信息录入失败");
    }
        
    }

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
        $steps = parent::getSteps($this->loginInfo['user_id']);
        $info = $DormitoryModel -> submit($this -> userInfo, $key, $steps);
        if ($info[1]) {
            $this -> success($info[0], $info[1]);
        } else {
            $this -> error($info[0], $info[1]);
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
        $steps = parent::getSteps($this->loginInfo['user_id']);
        $info = $DormitoryModel -> confirm($this -> userInfo, $key, $steps);
        if ($info[1]) {
            $this -> success($info[0], $info[1]);
        } else {
            $this -> error($info[0], $info[1]);
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
        $steps = parent::getSteps($this->loginInfo['user_id']);
        $info = $DormitoryModel -> finished($this -> userInfo, $steps);
        $this -> success('选择完成，查看室友信息', $info);
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
        $list = Db::name('fresh_info') -> where('ID', $user_id) -> find();
        if ($list) {
            $info['stu_id'] = $list['XH'];
            $info['place'] = $list['SYD'];
            $info['college_id'] = $list['YXDM'];
            $info['sex'] = $list['XBDM'];
            $info['nation'] = $list['MZ'];
            return $info;
        } else {
            return false;
        }
    }

}







