<?php
namespace app\api\controller\miniapp;

use app\common\controller\Api;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Vote as VoteModel;
use app\common\library\Token;
/**
 * 投票api
 */
class Vote extends Api
{

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
        $id = 1;
        $VoteModel = new VoteModel;
        $data = $VoteModel -> getInitData($key,$id);
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

        $VoteModel = new VoteModel;
        $data = $VoteModel -> submit($key);

        $returnData = [
            "voteStatus" => $data["status"],
            "data" => $data["data"]
        ];
        $this->success($data["msg"],$returnData);
    }
    
}