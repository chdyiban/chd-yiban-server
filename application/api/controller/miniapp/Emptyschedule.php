<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use app\api\model\Emptyschedule as EmptyscheduleModel;
use app\api\model\Wxuser as WxuserModel;
use app\common\library\Token;

/**
 * 空课表
 */
class Emptyschedule extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * @param token
     * @type 不加密
     */
    public function index()
    {
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
        $RestaurantModel = new RestaurantModel;
        $result = $RestaurantModel -> getMsg();
        if ($result["status"]) {
            $this->success($result["msg"],$result["data"]);
        } 
        $this->error($result["msg"],$result["data"]);       
    }

    public function test(){
        //$this->success("hello world");
        $EmptyscheduleModel = new EmptyscheduleModel;
        $result = $EmptyscheduleModel -> test();
        $this->success($result['data']);
    }

    public function getmsg(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        //$this->success($key);

        $EmptyscheduleModel = new EmptyscheduleModel;
        $week=$key['data'][0]+1;
        $day=$key['data'][1];
        // $waystr;
        // if($way==0){
        //     $waystr='empty_section like "0____"';
        // }else if($way==1){
        //     $waystr='empty_section like "_0___"';
        // }else if($way==2){
        //     $waystr='empty_section like "__0__"';
        // }else if($way==3){
        //     $waystr='empty_section like "___0_"';
        // }else if($way==4){
        //     $waystr='empty_section like "____0"';
        // }else if($way==5){
        //     $waystr='morning = 0';
        // }else if($way==6){
        //     $waystr='afternoon = 0';
        // }else if($way==7){
        //     $waystr='allday=0';
        // }else{
        //     $waystr='allday=0';
        // }
        $result = $EmptyscheduleModel -> getClassroom("week=$week and day=$day");
        $this->success($result['data']);
    }
    public function gettoweek(){
        $key = json_decode(base64_decode($this->request->post('key')),true);

        $this->success(get_weeks());
        //$this->success(12);
    }
  
}