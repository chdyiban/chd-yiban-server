<?php

namespace app\api\controller\dormitory2019;

use app\api\controller\dormitory2019\Api;
use think\Config;
use app\api\model\dormitory\Dormitory as DormitoryModel;
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;


/**
 * 
 */
class Dormitory extends Api
{
    protected $noNeedLogin = [];



    /**
     * 补充完善问卷信息
     * @param array $infomation
     */
    public function setinfo()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel();
        // dump($key);
        $result = $DormitoryModel -> setinfo($key, $this->_user);
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
                // $res = model('fresh_questionnaire_base') -> insert($data);
                $res = $DormitoryModel->insertBase($data);
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

                // $res = model('fresh_questionnaire_base') -> insert($data);
                $res = $DormitoryModel->insertBase($data);
                foreach ($info as $key => $value) {
                    // $res1 = model('fresh_questionnaire_family') -> insert($value);
                    $res1 = $DormitoryModel->insertFamily($data);
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
     * 查询当前各个宿舍状态
     */
    public function room()
    {
        $DormitoryModel = new DormitoryModel();
        $data = $DormitoryModel->room($this->_user);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }

    /**
     * 查询当前可选床位
     * @param int $key["building"]
     * @param int $key["dormitory"]
     */
    public function bed()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel();
        $data = $DormitoryModel->bed($key,$this->_user);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }
    
    /**
     * 提交选宿舍结果
     * @param int $key["room_id"] 9#3320
     * @param int $key["bed_id"]
     */
    public function submit()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel();
        $data = $DormitoryModel->submit($key,$this->_user);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }
    
    /**
     * 确认选宿舍结果
     * @param string $key["type"] confirm||cancel
     */
    public function confirm()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel();
        $data = $DormitoryModel->confirm($key,$this->_user);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }
    
    /**
     * 标记床位
     * @param string $key["action"] get||set
     * @return array ["code" "msg" "data"=>["mark_list" => []]]
     */
    public function mark()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel();
        $data = $DormitoryModel->mark($key,$this->_user);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }
    
    /**
     * 查看室友
     * 
     */
    public function roommates()
    {
        // $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel();
        $data = $DormitoryModel->roommates($this->_user);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
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

}