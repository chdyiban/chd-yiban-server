<?php

namespace app\api\controller\wrws;

use app\common\controller\Api;
use think\Config;
use think\Db;
use \think\Cache;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Wrws as WrwsModel;
use app\common\library\Token;
/**
 * 我认我生接口
 */
class Wrws extends Api{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    //获取题目的接口
    public function getquestion(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
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
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];

        if (empty($key['openid']) ) {
            $this->error("params error");
        }else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $this->error("authority error");
            }
        }


        $wrwsModel = new WrwsModel;
        $returnData=$wrwsModel->getquestiontest($userInfo["portal_id"],$key["quesnumber"]);
        if (empty($returnData)) {
            $this->error("error",$returnData);
        } 
        $this->success("success",$returnData);
    }

    //提交答题结果的接口
    public function submit(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
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
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];

        if (empty($key['openid'])) {
            $this->error("params error");
        }else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $this->error("authority error");
            }
        }


        //
        $wrwsModel = new WrwsModel;
        $returnData=$wrwsModel->submit($userInfo["portal_id"],$key["result"]);
        if (!$returnData) {
            $this->error("error",$returnData);
        } 
        $this->success("success",$returnData);
    }

    //获取用户信息的接口
    public function getuserinfo(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
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
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];

        if (empty($key['openid'])) {
            $this->error("params error");
        }else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $this->error("authority error");
            }
        }


        //
        $wrwsModel = new WrwsModel;
        $returnData=$wrwsModel->getuserinfo($userInfo["portal_id"]);
        if (empty($returnData)) {
            $this->error("error",$returnData);
        } 
        $this->success("success",$returnData);
    }

    //获取辅导员代班信息的接口

    public function getclasslist(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
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
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];

        if (empty($key['openid'])) {
            $this->error("params error");
        }else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $this->error("authority error");
            }
        }


        //
        $wrwsModel = new WrwsModel;
        $returnData=$wrwsModel->getclasslist($userInfo["portal_id"]);
        if (empty($returnData)) {
            $this->error("error",$returnData);
        } 
        $this->success("success",$returnData);
    }
    //获取班级内学生信息的接口

    public function getstuinfo(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
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
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];

        if (empty($key['openid'])) {
            $this->error("params error");
        }else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $this->error("authority error");
            }
        }


        //
        $wrwsModel = new WrwsModel;
        $returnData=$wrwsModel->getstuinfo($key['classid']);
        if (empty($returnData)) {
            $this->error("error",$returnData);
        } 
        $this->success("success",$returnData);
    }

    //测试接口
    public function test(){
        $wrwsModel = new WrwsModel;
        $returnData=$wrwsModel->getstuinfo('2019240203');
        if (empty($returnData)) {
            $this->error("error",$returnData);
        } 
        $this->success("success",$returnData);
    }
}