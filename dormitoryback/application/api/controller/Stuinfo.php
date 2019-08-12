<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;
use app\api\model\Dormitory as DormitoryModel;
/**
 * 
 */
class Stuinfo extends Freshuser
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
            $this->error('参数非法');
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
            $this -> success('success', ['steps' => $steps, 'info' => $info, 'userinfo' => $this ->userInfo]);
        } else {
            $this -> error('error', $steps);            
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
        if (!$result['status']) {
            $this -> error($result['msg'], $result['data']);
        } else {
            $data = $result['data'];
            $info = $result['info'];
            $Userinfo = parent::validate($data,'Userinfo.user');
            $Family[0] = $Userinfo;
            if (empty($info)) {
                if (gettype($Userinfo) == 'string') {
                    $this->error($Userinfo);
                } 
                $data['RJSR'] = $data['ZSR']/$data['JTRKS'];
                $data['RJSR'] = round($data['RJSR'], 2);
                $res = Db::name('fresh_info_add') -> insert($data);
                $res == 1 ? $this -> success('信息录入成功'): $this -> error('信息录入失败');
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
                $data['RJSR'] = $data['ZSR']/$data['JTRKS'];
                $data['RJSR'] = round($data['RJSR'], 2);
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
        }
        
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
            $info['sex'] = $list['XBDM'];
            $info['nation'] = $list['MZ'];
            return $info;
        } else {
            return false;
        }
    }

}







