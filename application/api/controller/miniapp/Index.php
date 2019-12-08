<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use think\Db;
use app\api\model\Wxuser as WxuserModel;
use app\common\library\Token;
/**
 * 
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 获取小程序前端banner list
     */
    public function banner_list(){
        $data = Db::name("miniapp_banner")->order("ID")->select();
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = [
                "id"    =>  $value["ID"],
                "type"  =>  $value["type"],
                "color" =>  $value["color"],
                "url"   =>  $value["url"],
                "location"=>$value["location"],
            ];
        }
        $this->success("succcess",$data);
    }

    /**
     * 获取小程序前端应用list
     * 此处只需要post未加密的token便可
     */

    public function app_list()
    {
        $param = $this->request->post();
        // $param = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($param['token'])) {
            $this->error("access error");
        }
        $token = $param['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userWxInfo = WxuserModel::get($userId);
        $appInfo = Db::name("miniapp_index")->select();
        if (empty($userWxInfo) || empty($userWxInfo["portal_id"])) {
            foreach ($appInfo as $key => $value) {
                $temp = [
                    "id"   =>   $value["item_id"],
                    "icon" =>   $value["icon"],
                    "color"=>   $value["color"],
                    "badge"=>   $value["badge"] == "0" ? 0 : $value["badge"],
                    "name" =>   $value["name"],
                    "permissions" => [
                        "unauthorized"  => $value["unauthorized"] == 0 ? false : true , 
                        "teacher"       => $value["teacher"] == 0 ? false : true,
                    ],
                    "usable"    => $value["usable"] == 0 ? false : true,
                    "errMsg"    =>  empty($value["errMsg"]) ? "" : $value["errMsg"],
                ];
                $data[] = $temp;
            }
       
        } else {
            // $open_id = $userWxInfo["openid"];
            // $safe = Db::name('wx_user') -> where('open_id',$open_id) -> field('portal_id') -> find();
            $stu_id = $userWxInfo["portal_id"];
            
            $data = [];
            
            if (strlen($stu_id) == 6) {
                //教师登录
                foreach ($appInfo as $key => $value) {
                    if ($value["teacher"] != 0 ) {
                        $temp = [
                            "id"   =>   $value["item_id"],
                            "icon" =>   $value["icon"],
                            "color"=>   $value["color"],
                            "badge"=>   $value["badge"] == "0" ? 0 : $value["badge"],
                            "name" =>   $value["name"],
                            "permissions" => [
                                "unauthorized"  => $value["unauthorized"] == 0 ? false : true , 
                                "teacher"       => $value["teacher"] == 0 ? false : true,
                            ],
                            "usable"    => $value["usable"] == 0 ? false : true,
                            "errMsg"    =>  empty($value["errMsg"]) ? "" : $value["errMsg"],
                        ];
                        $data[] = $temp;
                    }
                }
            } else {
                //学生登录
                foreach ($appInfo as $key => $value) {
                    $temp = [
                        "id"   =>   $value["item_id"],
                        "icon" =>   $value["icon"],
                        "color"=>   $value["color"],
                        "badge"=>   $value["badge"] == "0" ? 0 : $value["badge"],
                        "name" =>   $value["name"],
                        "permissions" => [
                            "unauthorized"  => $value["unauthorized"] == 0 ? false : true , 
                            "teacher"       => $value["teacher"] == 0 ? false : true,
                        ],
                        "usable"    => $value["usable"] == 0 ? false : true,
                        "errMsg"    =>  empty($value["errMsg"]) ? "" : $value["errMsg"],
                    ];
                    $data[] = $temp;
                }
            }
        }
        $this->success("success",$data);
    }
}