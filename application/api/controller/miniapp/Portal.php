<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Ykt as YktModel;
use app\api\model\Books as BooksModel;
use app\api\model\Score as ScoreModel;
use app\api\controller\Bigdata as BigdataController;
use app\common\library\Token;

/**
 * 获取课表
 */
class Portal extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';

    /**
     * @param token
     * @type 不加密
     */
    public function yikatong(){
        //解析后应对签名参数进行验证
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();

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
        $key["openid"] = $userInfo["open_id"];

        $Ykt = new YktModel;
        $data = $Ykt -> get_yikatong_data($key);
        $this->success("success",$data);
    }
    //查询当前借阅信息
    /**
     * 查询当前借阅量
     * @param token
     * @type 不加密
     */
    public function books(){
        
        /*$info示例
        [
            'book_list' => [
                ['book' => "c",'jsrq' => '2017-08-02','yhrq' => '2018-08-02'],
                ['book' => "c",'jsrq' => '2017-08-02','yhrq' => '2018-08-02'],
                ['book' => "c",'jsrq' => '2017-08-02','yhrq' => '2018-08-02'],
            ],  //当前借阅列表
            'books_num' => 3,   //当前借阅量
            'history'   => 10,     //历史借阅量
            'dbet'      => 0,        //欠费
            'nothing'   =>  true   //当前是否有借阅
        ] */
        //解析后应对签名参数进行验证
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();

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
        $key["openid"] = $userInfo["open_id"];

        $book = new BigdataController;
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }
        $ZJH = $userInfo["portal_id"];
        $access_token = $book->getAccessToken();
        $access_token = $access_token["access_token"];
        $params = ["access_token" => $access_token,"ZJH" => $ZJH];
        $nowData = $book->getNowBook($params);
        $historyData = $book->getHistoryBook($params);
        if ($nowData["status"] == false) {
            $this->error($nowData["msg"]);
        }
        if ($historyData["status"] == false) {
            $this->error($historyData["msg"]);
        }
        $return = [
            "book_list" => $nowData["data"]["data"],
            "books_num" =>  $nowData["data"]["extra"]["total"],
            "history"   =>  $historyData["data"]["extra"]["total"],
            "nothing"   =>  $nowData["data"]["extra"]["total"] == 0 ? false : true,
            "dbet"      =>  "",
        ];
        $this->success("success",$return);
    }


    
    /**
     * 查询历史借阅信息
     * @param token
     * @param page
     * @type 不加密
     */
    public function history_books(){
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
        $key["openid"] = $userInfo["open_id"];

        $book = new BigdataController;
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }
        $ZJH = $userInfo["portal_id"];
        $access_token = $book->getAccessToken();
        $access_token = $access_token["access_token"];
        $page = empty($key["page"]) ? 0 : $key["page"];
        $params = ["access_token" => $access_token,"ZJH" => $ZJH,"page" => $page];
        $nowData = $book->getNowBook($params);
        $historyData = $book->getHistoryBook($params);
    
        if ($historyData['status'] == false) {     
            $this->error($historyData["msg"]);
        }
        if ($nowData['status'] == false) {     
            $this->error($nowData["msg"]);
        }

        $result = [
            "nothing"   =>  $historyData["data"]["extra"]["total"] == 0 ? false : true,
            "book_list" =>  $historyData["data"]["data"],
            "books_num" =>  $nowData["data"]["extra"]["total"],
            "history"   =>  $historyData["data"]["extra"]["total"],
            "dbet"      =>  "",
            "page"      =>  $historyData["data"]["extra"]["page"],
            "max_page"  =>  $historyData["data"]["extra"]["max_page"],
        ];

        $this->success("success",$result);

    }

    //续借
    public function renew()
    {
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
        $key["openid"] = $userInfo["open_id"];

        $Books = new BooksModel;
        $data = $Books -> renew_books($key);
        if ($data['status']) {
            $this->success("success",$data["data"]);
        }
        $this->error("params error",$data["data"]);
    }

    /**
     * 获取考试成绩
     * @param token
     * @type 不加密
     */
    public function score(){
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();
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
        $key["openid"] = $userInfo["open_id"];

        $score = new BigdataController;
        $Wxuser = new WxuserModel;
        // $XH = $key["id"];
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }
        $XH = $userInfo["portal_id"];
        $access_token = $score->getAccessToken();
        $access_token = $access_token["access_token"];
        $params = ["access_token" => $access_token,"XH" => $XH];
        $data = array_reverse($score->getScore($params));
        $this->success("success",$data);
    }

    /**
     * 获取学生体测成绩
     * @param token
     * @type 不加密
     */

    public function tcscore()
    {
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();

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

        $score = new BigdataController;
        $XH = $userInfo["portal_id"];
        $access_token = $score->getAccessToken();
        $access_token = $access_token["access_token"];
        $params = ["access_token" => $access_token,"XH" => $XH];
        $returnData = $score->getTcScore($params);
        if ($returnData["status"] == true) {
            $this->success("success",$returnData["data"]);
        } 

        $this->error($returnData["msg"],$returnData["data"]);

    }
    /**
     * 获取学生四六级成绩
     * @param token
     * @type 不加密
     */

    public function slscore()
    {
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();

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

        $score = new BigdataController;
        $XH = $key["portal_id"];
        $access_token = $score->getAccessToken();
        $access_token = $access_token["access_token"];
        $params = ["access_token" => $access_token,"XH" => $XH];
        $data = array_reverse($score->getSlScore($params));
 
        $this->success('success',$data);
        return json($info);
    }

    //获取空闲教室
    public function empty_room(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        /**
         * [
         *  weekNo:第几周
         *  weekDay:周几（周一:1 周二:2 ……）
         *  classNo:第几节课 1@2:一二节课 1@2@3@4 一二三四节课
         *  buildingNo: 1:宏远 2:明远 3:修远
         *  openid:微信openid
         *  timestamp:时间戳
         *  sign:签名验证字符串
         * ]
         */
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                ['room' => ['WM1211']],
                ['room' => ['WM2501']]
            ]
        ];
        
        return json($info);
    }
}