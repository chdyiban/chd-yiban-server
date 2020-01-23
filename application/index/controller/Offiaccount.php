<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use think\Db;
use fast\Http;
use think\cache;
use think\Config;
use app\index\model\Offiaccount as offiaccountModel;
use app\ids\controller\Offiaccount as offiaccountController;

/**
 * 易班长大订阅号中控界面
 */
class Offiaccount extends Frontend
{

    const GET_ACCESS_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/access_token";
    const GET_USERINFO_URL = "https://api.weixin.qq.com/sns/userinfo";
    const TEST_URL = "http://202.117.64.236:8080/auth/login";
    // const DOMAIN = "http://chdliutao.mynatapp.cc/yibanbx/public";
    const DOMAIN = "https://yiban.chd.edu.cn";
    const REDIRECT_URL = "https://open.weixin.qq.com/connect/oauth2/authorize";

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
            $openidArray = $result["data"];
            $openid = $openidArray["openid"];
            $offiaccountModel = new offiaccountModel;
            
            $return = $offiaccountModel->initStep($openidArray);
            // step = 0 ,需要用户进行授权
            if ($return["step"] == 0) {
                $redirect_url = self::DOMAIN."/index/offiaccount/userinfo";
                $location_url = self::REDIRECT_URL."?appid=$appid&redirect_uri=$redirect_url&response_type=code&scope=snsapi_userinfo&state=$state#wechat_redirect";
                header("Location: $location_url");
                exit;
            } else if ($return["step"] == 1) {

                $this->redirect("portal?openid=$openid&state=$state");

            } else if ($return["step"] == 2) {
                $this->redirect("yiban?openid=$openid&state=$state");
            }
            
        }
    }
    /**
     * 微信userinfo授权接口，获取用户信息，保存至数据库
     */
    public function userinfo()
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
        $result = $this-> getUserInfo($code);
        if ($result["status"]) {
            $userInfo = $result["data"];
            $openid = $userInfo["openid"];
            $offiaccountModel = new offiaccountModel;
            
            $return = $offiaccountModel->bindWxUserinfo($userInfo);
            // step = 0 ,需要用户进行授权
            if ($return["step"] == 0) {
                $redirect_url = self::DOMAIN."index/offiaccount/userinfo";
                $location_url = self::REDIRECT_URL."?appid=$appid&redirect_uri=$redirect_url&response_type=code&scope=snsapi_userinfo&state=$state#wechat_redirect";
                header("Location:$location_url");
                exit;
            } else if ($return["step"] == 1) {
                $this->redirect("portal?openid=$openid&state=$state");

            } else if ($return["step"] == 2) {

                $this->redirect("yiban?openid=$openid&state=$state");
            }
            
        }
    }

    /**
     * 门户认证方法
     */
    public function portal()
    {
        // $domain = self::DOMAIN;
        // $appid = Config::get('wechat.yibanchd')["appId"];
        // //是否提交门户账号与密码
        // if ($this->request->isPost()) {
        //     $portal_id = $this->request->post("portal_id");
        //     $portal_pwd = $this->request->post("portal_pwd");
        //     $openid   =   $this->request->post("openid");
        //     $state  =   $this->request->post("state");
        //     if (empty($portal_id) || empty($portal_pwd) ) {
        //         $this->error("门户账号密码不可以为空");
        //     }
        //     $bind = $this->checkBind($portal_id,$portal_pwd);
        //     if (!$bind["status"]) {
        //         $this->error($bind["msg"]);
        //     }
        //     $offiaccountModel = new offiaccountModel;
        //     $params = [
        //         "portal_id"     => $portal_id,
        //         "portal_pwd"    =>  $portal_pwd,
        //         "openid"        =>  $openid
        //     ];
        //     $data = [
        //         'state' =>  $state,
        //     ];
        //     $return = $offiaccountModel->bind($params);
        //     if($return["status"] == true) {
        //         $this->success($return["msg"],"",$data);
        //     } else {
        //         $this-> error($return["msg"]);
        //     }
                
        // }

        // $openid = $this->request->param('openid');
        // $state = $this->request->param("state");
        // $this->view->assign([
        //     'appid' => $appid,
        //     'domain'=> $domain,
        //     'state' =>  $state,
        //     'openid'  =>  $openid,
        // ]);
        // return $this->view->fetch();
        $open_id = $this->request->param("openid");
        $state   = base64_encode($this->request->param("state"));
        if (empty($open_id) || empty($state)) {
            $this->error("request error!");
        }
        // $url     =  "http://www.yiban.cn/Org/orglistShow/type/forum/puid/5370552";
        $this->redirect("ids/offiaccount/index",["url" => $state,"openid" => $open_id,]);
    }

    /**
     * 易班社区登录方法
     */
    public function yiban()
    {
        $open_id = $this->request->param("openid");
        $state = $this->request->param("state");
        $portal_id = Db::name("wx_unionid_user")->where("open_id",$open_id)->field("portal_id")->find()["portal_id"];
        $ids = new offiaccountController;
        $ids->yiban($portal_id,$state);
        exit;
    }

    /**
     * 获取openid方法
     * scope=snsapi_base
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

        return ["status" => true, "data" => $result];
    }

    /**
     * 获取用户信息userinfo
     * scope=snsapi_userinfo
     */
    private function getUserInfo($code)
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
