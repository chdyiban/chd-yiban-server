<?php

namespace app\api\controller\miniapp\news;

use think\Config;
use think\Exception;
use app\common\controller\Api;
use app\api\model\news\Comment as CommentModel;
use app\common\library\Token;
use app\api\model\Wxuser as WxuserModel;
/**
 * 评论
 */
class Comment extends Api
{

    protected $noNeedLogin = ['*'];

    /**
     * 评论列表
     * @param token
     * @param aid
     * @param page
     * @param pid
     * @type 加密
     */
    public function index()
    {
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

        $aid = (int)$key["aid"];
        $page = (int)$key["page"];
        $type = $key["type"];
        $pid = empty($key["pid"]) ? 0 : (int)$key["pid"] ; 
        $field = ["content","createtime","user_id","id"];
        Config::set('paginate.page', $page);    
        $commentList = CommentModel::getCommentList(['aid' => $aid,"field" => $field,"pid" => $pid,"type" => $type]);
        $this->success('', ['commentList' => $commentList->getCollection()]);
    }

    /**
     * 发表评论
     * @param token
     * @param aid
     * @param content
     * @type 加密
     */
    public function post()
    {
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
        $openid = $userInfo["open_id"];
        $key["openid"] = $openid;
       
        $result = CommentModel::postComment($key);
        if ($result["status"]) {
            $this->success("评论成功");
        }
        $this->error($result["msg"],$result);
        // try {
        //     // $params = $this->request->post();
        //     CommentModel::postComment($key);
        // } catch (Exception $e) {
        //     $this->error($e->getMessage(), null, ['token' => $key["token"]]);
        // }
        // $this->success(__('评论成功'));
    }

    public function set_comment_like(){
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
        $key["wxid"] = $tokenInfo['user_id'];
        if(empty($key["id"])){ $this->error("请先绑定门户账号"); }

        $SurveyModel = new CommentModel;
        $data=$SurveyModel->set_comment_like($key);

        if($data['status']){
            $this->success($data);
        }
    }
    
    public function get_comment_like(){
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
        $key["wxid"] = $tokenInfo['user_id'];
        if(empty($key["id"])){ $this->error("请先绑定门户账号"); }

        $SurveyModel = new CommentModel;
        $data=$SurveyModel->get_comment_like($key);

        $this->success('success',$data);
    }

}
