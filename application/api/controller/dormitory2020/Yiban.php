<?php

namespace app\api\controller\dormitory2020;

use app\api\controller\dormitory2020\Api;
use think\Config;
use fast\Http;
use app\common\library\Token;
use fast\Random;
use app\common\library\Sms as Smslib;
use app\api\model\dormitory2020\User as userModel;
use app\api\model\dormitory2020\Yiban as yibanModel;
use app\ids\controller\Fresh as FreshController;


/**
 * 微信订阅号跳转易班控制器
 */
class Yiban extends Api
{

    protected $noNeedBindPortal = ['redirect'];
    protected $noNeedRight = ['*'];

    const YIBAN_URL = "http://www.yiban.cn/";
    /**
     * 易班跳转url
     * @param $key["url"]
     * @way get
     * @return array
     */
    public function redirect()
    {
        // $key = json_decode(base64_decode($this->request->get('url')),true);
        $url = $this->request->get("url");
        $token = $this->request->get("token");
        $YXDM = $this->request->get("YXDM");
        $BJDM = $this->request->get("BJDM");
        $url = empty($url) ? self::YIBAN_URL : $url;
        if (!empty($YXDM)) {
            $yibanModel = new yibanModel;
            $urlData = $yibanModel->getCollegeUrl($YXDM);
            if ($urlData["status"] == false) {
                $this->error($urlData["msg"]);
            }
            $url = $urlData["data"]["url"];
        }
        if (!empty($BJDM)) {
            $yibanModel = new yibanModel;
            $urlData = $yibanModel->getClassUrl($BJDM);
            if ($urlData["status"] == false) {
                $this->error($urlData["msg"]);
            }
            $url = $urlData["data"]["url"];
        }
        $checkResult = parent::init($token);
        if ($checkResult == true) {
            $userInfo = $this->_user;
            $_bindStatus = $this->_bindStatus;
            if ($_bindStatus["is_bind"] == false ) {
                $this->error("请先绑定门户账号！");
            }
            if ($_bindStatus["is_bind_mobile"] == false) {
                $this->error("请完成家庭问卷调查表");
            }
            $portal_id = $userInfo["XH"];
            $ids = new FreshController;
            $ids->yiban($portal_id,$url);
            exit;
        } else {
            $this->error("access error!");
        }
    }

    public function apply()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $yibanModel = new yibanModel();
        $data = $yibanModel->apply($key,$this->_user);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }
}