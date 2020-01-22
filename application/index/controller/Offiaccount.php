<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use think\Db;
use fast\Http;
use think\cache;
use think\Config;
use app\index\model\Offiaccount as offiaccountModel;
use app\ids\controller\Index as idsIndex;

/**
 * 易班长大订阅号中控界面
 */
class Offiaccount extends Frontend
{

    const GET_ACCESS_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/access_token";
    const GET_USERINFO_URL = "https://api.weixin.qq.com/sns/userinfo";
    const TEST_URL = "http://202.117.64.236:8080/auth/login";
    const DOMAIN = "https://chdliutao.mynatapp.cc/yibanbx/public";
    // const DOMAIN = "https://yiban.chd.edu.cn";

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
    
        $domain = self::DOMAIN;
        $code = $this->request->get('code');
        $state = $this->request->get("state");
        $appid = Config::get('wechat.yibanchd')["appId"];
        $this->view->assign([
            'appid' => $appid,
            'domain'=> $domain,
            'state' =>  $state,
        ]);
        $result = $this-> getOpenId($code);
        //open_id不为空，判断用户状态
        if ($result["status"]) {
            $userInfo = $result["data"];
            $offiaccountModel = new offiaccountModel;
            
            $return = $offiaccountModel->initStep($userInfo);
            // step = 0 
            if ($return["step"] == 0) {
                $this->view->assign([
                    "func"      =>  "portal",
                    "openid"    =>  $userInfo["openid"],
                ]);
                // return $this->view->fetch();
                $this->redirect("offiaccount/portal", ['openid' =>  $userInfo["openid"],'state' => $state]);
            } 
            $this->redirect("offiaccount/$state", ['openid' =>  $userInfo["openid"]]);
            
        }
    }

    /**
     * 门户认证方法
     */
    public function portal()
    {
        $domain = self::DOMAIN;
        $appid = Config::get('wechat.yibanchd')["appId"];
        //是否提交门户账号与密码
        if ($this->request->isPost()) {
            $portal_id = $this->request->post("portal_id");
            $portal_pwd = $this->request->post("portal_pwd");
            $openid   =   $this->request->post("openid");
            $state  =   $this->request->post("state");
            if (empty($portal_id) || empty($portal_pwd) ) {
                $this->error("门户账号密码不可以为空");
            }
            $bind = $this->checkBind($portal_id,$portal_pwd);
            if (!$bind["status"]) {
                $this->error($bind["msg"]);
            }
            $offiaccountModel = new offiaccountModel;
            $params = [
                "portal_id"     => $portal_id,
                "portal_pwd"    =>  $portal_pwd,
                "openid"        =>  $openid
            ];
            $data = [
                'state' =>  $state,
            ];
            $return = $offiaccountModel->bind($params);
            if($return["status"] == true) {
                $this->success($return["msg"],"",$data);
            } else {
                $this-> error($return["msg"]);
            }
                
        }

        $openid = $this->request->param('openid');
        $state = $this->request->param("state");
        $this->view->assign([
            'appid' => $appid,
            'domain'=> $domain,
            'state' =>  $state,
            'openid'  =>  $openid,
        ]);
        return $this->view->fetch();
    }

    /**
     * 易班社区登录方法
     */
    public function yiban()
    {
        $open_id = $this->request->param("openid");
        $portal_id = Db::name("wx_unionid_user")->where("open_id",$open_id)->field("portal_id")->find()["portal_id"];
        $ids = new idsIndex;
        $ids->index($portal_id);
    }

    /**
     * 获取openid方法
     */
    private function getOpenId($code)
    {
        $appid = Config::get('wechat.yibanchd')["appId"];
        $appsecret = Config::get('wechat.yibanchd')["appSecret"];
        $retData = [];
        $params = [
            'appid' => $appid,
            'secret' => $appsecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
        
        $result = json_decode(Http::get(self::GET_ACCESS_TOKEN_URL, $params),true);
        if (empty($result["openid"])) {
            return ["status" => false,"msg" => $result["errmsg"] ];
        }
        $params = [
            "access_token"  =>  $result["access_token"],
            "openid"        =>  $result["openid"],
            "lang"          =>  "zh_CN",
        ];
        $userInfo = json_decode(Http::get(self::GET_USERINFO_URL, $params),true);
        foreach ($result as $key => $value) {
            $userInfo[$key] = $value;
        }
        return ["status" => true, "data" => $userInfo];
    }
    /**
     * 确认绑定信息
     */
    private function checkBind($portal_id, $portal_pwd){
        $post_data = [
            'userName' => $portal_id,
            'pwd' => $portal_pwd,
        ];
        $return = [];
        $response = Http::post(self::TEST_URL,$post_data);
        $response = json_decode($response,true);
        $return['status'] = $response['success'] == "true" ? true:false;
        if ($return['status']) {
            return ["status" => true,"msg" => "绑定成功！"];
        } else {
            return ["status" => false,"msg" => "绑定失败，请检查用户名或密码"];
        }
    }
}
