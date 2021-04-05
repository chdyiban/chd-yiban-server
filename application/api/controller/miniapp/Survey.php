<?php
namespace app\api\controller\miniapp;

use app\common\controller\Api;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Survey as SurveyModel;
use app\common\library\Token;
/**
 * 投票api
 */

 class Survey extends Api{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 初始化
     * @param token
     * @type 不加密
     */
    public function init() {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        // $key = $this->request->param();
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
        $key['openid'] = $userInfo["open_id"];
        $key['id']=$userInfo["portal_id"];
	    if(empty($userInfo["portal_id"])){ $this->error("请先绑定门户账号"); }
        $SurveyModel = new SurveyModel;
        $data = $SurveyModel -> getInitData($key);
        $this->success("success",$data);

    }

    public function testinit(){
        $key['id']='2019902509';
        $SurveyModel = new SurveyModel;
        $data = $SurveyModel -> getInitData($key);
        $this->success("success",$data);
    }

    /**
     * @param token
     * @param vote_id
     * @param option
     * @type 加密
     */
    public function submit() {
        //解析后应对签名参数进行验证
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
        $key['openid'] = $userInfo["open_id"];
        $key["id"] = $userInfo["portal_id"];
        if(empty($key["id"])){ $this->error("请先绑定门户账号"); }
        $SurveyModel = new SurveyModel;
        $data = $SurveyModel -> submit($key);

        $returnData = [
            "voteStatus" => $data["status"],
            "data" => $data["data"]
        ];
        $this->success($data["msg"],$returnData);
    }

    public function testsubmit(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $key['id']='2019902509';
        $SurveyModel = new SurveyModel;
        $data = $SurveyModel -> submit($key);

        if($data['status']){
            $this->success($data);
        }
        
    }

 }