<?php

namespace app\api\controller\dormitory2020;

use app\api\controller\dormitory2020\Api;
use think\Config;
use fast\Http;
use app\api\model\dormitory2020\Dormitory as DormitoryModel;
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
use think\Db;


/**
 * 
 */
class Dormitory extends Api
{
    protected $noNeedBindPortal = [""];
    protected $AppSecretKey = "0paIib2iL0L6tirAVigty0Q";


    /**
     * 补充完善问卷信息
     * @param array $infomation
     */
    public function setinfo()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $DormitoryModel = new DormitoryModel();
        $result = $DormitoryModel -> setinfo($key, $this->_user);
        if (!$result['status']) {
            $this -> error($result['msg'], $result['data']);
        } else {
            $data = $result['data'];
            if (empty($data["BRSG"])) {
                $this->error("请填写身高");
            } else {
                $data["BRSG"] = (int)$data["BRSG"];
            }
            if (empty($data["BRTZ"])) {
                $this->error("请填写体重");
            } else {
                $data["BRTZ"] = (float)$data["BRTZ"];
            }
            $Userinfo = parent::validate($data,'Userinfo.user');
            if (gettype($Userinfo) == 'string') {
                $this->error($Userinfo);
            }

            $res = $DormitoryModel->insertFirst($data,$this->_user);
            $res == 1 ? $this -> success('信息录入成功'): $this -> error('信息录入失败');
        }  
    }

	/**
     * 返回学生所填家庭信息
     * @param string action get|set
     * @param string action 
     */
    public function investigation()
    {
        $DormitoryModel = new DormitoryModel();
		$action = $this->request->get("action");
		if (empty($action)) {
			$this->error("param error!");
		} elseif ($action == "get") {
			$result = $DormitoryModel->getBaseInfo($this->_user);
		} elseif ($action == "set") {
			$result = $DormitoryModel->setBaseInfo($this->_user);
			// $result = $DormitoryModel->setBaseInfo();
		}
		
		if ($result["status"]) {
			$this->success($result["msg"],$result["data"]);
		} else {
			$this->error($result["msg"],$result["data"]);
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
        if (empty($key["verify"])) {
            $this->error("params error!");
        }
        $key["verify"]["userip"] =  $this->request->ip();
        $response = $this->captcha($key["verify"]);

        if (!$response["status"]) {
            $this->error($response["msg"]);
        }
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
            $userInfo = $DormitoryModel->getUserInfo($this->_user["openid"]);
            if ($userInfo["subscribe"] == 1) {
                $DormitoryModel->sendMsg($this->_user);
            }
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }
    
    /**
     * 标记床位
     * @param string $key["action"] mark||get | unmark
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

    /**
     * 腾讯验证码后台接入
     * @param $param["appid"]
     * @param $param["AppSecretKey"]
     * @param $param["Ticket"]
     * @param $param["randstr"]
     */

    private function captcha($params)
    {
        $getUrl = "https://ssl.captcha.qq.com/ticket/verify";
        if (empty($params["appid"]) || empty($params["ticket"]) || empty($params["randstr"])) {
            return ["status" => false,"msg"=>"params error!","data" => null];
        }
        $params = [
            "aid" => $params["appid"],
            // "AppSecretKey" => "0paIib2iL0L6tirAVigty0Q**",
            "AppSecretKey" => $this->AppSecretKey,

            "Ticket"  => $params["ticket"],
            "Randstr" => $params["randstr"],
            "UserIP"  => $params["userip"]
        ];
        // dump($params);
        $params = http_build_query($params);
        $response = Http::get($getUrl,$params);
        $result = json_decode($response,true);
        if ($result["response"] == 1) {
            return ["status" => true,"msg"=>"验证成功","data" => null];
        } else {
            return ["status" => false,"msg"=> $result["err_msg"],"data" => null];
        }
    }
}