<?php
namespace app\api\model\dormitory2020;


use think\Model;
use think\Db;
use think\Cache;
use think\Config;
use fast\Http;


class Wxuser extends Model {
    // 表名

    protected $name = 'wx_unionid_user';


    const GET_ACCESS_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/access_token";
    const GET_USERINFO_URL = "https://api.weixin.qq.com/sns/userinfo";
    const TEST_URL = "http://202.117.64.236:8080/auth/login";
    /**
     * 初始化方法，判断用户状态
     * @param string code
     * @return array 
     */
    public function initStep($code)
    {
        $result = $this->getOpenId($code);
        if ($result["status"] == false) {
            return ["status" => false ,"msg" => $result["msg"] ];
        }
        //第一次请求获取用户openid以及access_token信息
        $accessInfo = $result["data"];
        $returnArray    =   [
            "is_bind"           =>  false,  //用户是否绑定门户账号
            "is_bind_mobile"    =>  false,  //用户是否绑定手机号
        ];
        $infoList = $this->where("open_id",$accessInfo["openid"])->find();
        //获取数据库用户的详细信息
        $userInfoData = $this->getUserInfo($accessInfo);
        if ($userInfoData["status"] == false) {
            return ["status" => false,"msg" => $userInfoData["msg"]];
        }
        $userInfo = $userInfoData["data"];

        foreach ($accessInfo as $key => $value) {
            $userInfo[$key] = $value;
        }

        if (empty($infoList)) {
            //用户未曾登录，则使用户先授权，将用户基本信息放入数据库
            $userId = $this->insertInfo($userInfo);
        } else {
            $userId = $this->updateInfo($userInfo);
        }
        if (empty($infoList["portal_id"])) {
            $returnArray["is_bind"] = false;
        }
        $checkUnionid = Db::name("fresh_info")->where("XH",$infoList["portal_id"])->field("unionid")->find();
        if(empty($checkUnionid["unionid"]) || $checkUnionid["unionid"] != $infoList["unionid"] ){
            $returnArray["is_bind"] = false;
            $result = Db::name("wx_unionid_user")
                        ->where("unionid",$infoList["unionid"])
                        ->update(["portal_id" => ""]);
        } else {
            $returnArray["is_bind"] = true;
        }
        $returnArray["is_bind_mobile"] = !empty($infoList["mobile"]) ? true : false;
        $returnArray["open_id"] = $accessInfo["openid"];
        $returnArray["wxuser"] = [
            "nickname"  =>  $userInfo["nickname"],
            "avatar"    =>  $userInfo["headimgurl"],
        ];
        if(!empty($infoList["portal_id"])) {
            $returnArray["wxuser"]["portal_id"] = $infoList["portal_id"];
        }

        return ["status" => true,"data" => $returnArray];
    }
  
    /**
     * 获取用户openid以及access_token信息
     * @param string $code
     * @return array 
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
     * @param $params
     * @return array
     */
    private function getUserInfo($params)
    {
      
        $access_token = $params["access_token"];
        $openid    =   $params["openid"];
        $params = [
            "access_token"  =>  $access_token,
            "openid"        =>  $openid,
            "lang"          =>  "zh_CN",
        ];
        $userInfo = json_decode(Http::get(self::GET_USERINFO_URL, $params),true);
        if (!empty($userInfo["errmsg"])) {
            return ["status" => false, "msg" => $userInfo["errmsg"]];
        }
        return ["status" => true, "data" => $userInfo];
    }

    /**
     * 将用户所有信息插入数据库
     * @return int  返回用户新增id
     */
    private function insertInfo($params)
    {
        //首先判断小程序表中是否有门户信息
        // $portalInfo = Db::name("wx_user")->where("unionid",$params["unionid"])->find();
        $portal_id  =   "";
        $portal_pwd =   "";
        // if (!empty($portalInfo["portal_id"]) && !empty($portalInfo["portal_pwd"]) ) {
        //     $portal_id = $portalInfo["portal_id"];
        //     $portal_pwd = _token_decrypt($portalInfo["portal_pwd"], $portalInfo['open_id']);
        //     $portal_pwd = _token_encrypt($portal_pwd, $params['openid']);
        // }
        $insertData = [
            "open_id"           =>  $params["openid"],
            "unionid"           =>  $params["unionid"],
            "create_time"       =>  time(),
            "last_visit_time"   =>  time(),
            "portal_id"         =>  $portal_id,
            "portal_pwd"        =>  $portal_pwd,
            "access_token"      =>  $params["access_token"],
            "refresh_token"     =>  $params["refresh_token"],
            "expires_time"      =>  $params["expires_in"],
            "avatar"            =>  substr_replace($params['headimgurl'],"https://",0,7),
            "nickname"          =>  base64_encode($params["nickname"]),
            "mobile"            =>  "",
            "update_time"       =>  time(),
        ];
        $result = Db::name("wx_unionid_user")->insertGetId($insertData);
        return $result;
    }

    /**
     * 将用户access_token信息更新数据库
     * @return int 返回用户id
     */
    private function updateInfo($params)
    {
        $userId = Db::name("wx_unionid_user")->where("open_id",$params["openid"])->find()["id"];
        $updateData = [
            "last_visit_time"   =>  time(),
            "access_token"      =>  $params["access_token"],
            "refresh_token"     =>  $params["refresh_token"],
            "expires_time"      =>  $params["expires_in"],
            // "avatar"            =>  !empty($params['headimgurl']) ?$params['headimgurl'] :"",
            "avatar"            =>  !empty($params['headimgurl']) ?substr_replace($params['headimgurl'],"https://",0,7) :"",
            "nickname"          =>  base64_encode($params["nickname"]),
            "update_time"       =>  time(),
        ];  
        $result = Db::name("wx_unionid_user")
                ->where("open_id",$params["openid"])
                ->update($updateData);
        return $userId;
    }

    /**
     * 将用户门户信息保存至数据库
     */
    public function bind($params)
    {
        $mobile = "";
        $params["portal_pwd"] = _token_encrypt($params["portal_pwd"], $params['unionid']);
        $check = Db::name("wx_unionid_user")->where("unionid",$params["unionid"])->find();
        if (!empty($check["portal_id"])) {
            return ["status" => false,"msg" => "请勿重复绑定"];
        }
        //判断wx_base表中是否有学号信息记录
        $check = Db::name("wx_base")->where("portal_id",$params["portal_id"])->find();
        if (empty($check)) {
            $this->insertBase($params);
        }
        if (!empty($check["mobile"])) {
            $mobile = $check["mobile"];
        }
        $result =  Db::name("wx_unionid_user")
                    -> where("unionid",$params["unionid"])
                    -> update([
                        "portal_id"     =>  $params["portal_id"],
                        "portal_pwd"    =>  $params["portal_pwd"],
                        "mobile"        =>  $mobile,
                    ]);
        if ($result) {
            return ["status" => true,"msg" => "绑定成功"];
        }
        return ["status" => false,"msg" => "请勿重复绑定"];
    }
    /**
     * 向wx_base中插入信息
     * @param $params["portal_pwd"]通过unionID加密
     */
    private function insertBase($params)
    {
        $insertData = [
            "portal_id" =>  $params["portal_id"],
            "mobile"    =>  "",
            "yb_user_id"=>  "",
        ];
        $result = Db::name("wx_base")->insert($insertData);
    }

    /**
     * 取消门户绑定
     */
    public function bindCancel($params)
    {
        if (empty($params["unionid"])) {
            return ["status" => false,"msg" => "请勿重复取消！"];
        }
        $check = Db::name("wx_unionid_user")->where("unionid",$params["unionid"])->find();
        if (empty($check["portal_id"])) {
            return ["status" => false,"msg" => "请勿重复取消！"];
        }
        $updateFlag = false;
        $deleteFlag = false;
        Db::startTrans();
        try{   
            $updateFlag =  Db::name("wx_unionid_user")
                            -> where("unionid",$params["unionid"])
                            -> update([
                                "portal_id"     =>  "",
                                // "portal_pwd"    =>  "",
                            ]);
            $deleteFlag = Db::name("fresh_info")
                        -> where("XH",$params["XH"])
                        -> update(["unionid"=>""]);
            //提交事务
            Db::commit();      
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }    
      
        if ($updateFlag&&$deleteFlag) {
            return ["status" => true,"msg" => "取消成功"];
        }
        return ["status" => false,"msg" => "请稍后再试"];
    }

    /**
     * 验证用户的账号密码是否正确
     */
    public function checkBind($params){
        $username = $params["portal_id"];
        $password = $params["portal_pwd"];
        $post_data = [
            'userName' => $username,
            'pwd' => $password,
        ];
        $return = [];
        $response = Http::post(self::TEST_URL,$post_data);
        $response = json_decode($response,true);
        return $response['success'];
    }

     /**
     * 获取新生ID验证账号密码正确性
     * @param string $user["studentID"]
     * @param string $user["password"]
     * @param string $user["open_id"]
     * @return bool|int 
     * 
     */
    public function check($user){
        //新生数据库进行比对，若成功则返回userid ，若不成功返回false
        if (empty($user['studentID']) || empty($user['password']) || empty($user["open_id"]) ) {
            return false;
        } else {
            $info = Db::name("fresh_info")-> where('XH', $user['studentID'])
                        -> field('SFZH,ID')
                        -> find(); 
            if (empty($info)) {
                return false;
            } else {
                $id_card = $info['SFZH'];
                $password = substr($id_card, -6);
                if ($user['password'] == $password) {
                    $userid = $info['ID'];
                    $res = $this-> where("open_id",$user["open_id"]) 
                                -> update(["portal_id" => $user['studentID']]);

                    $unionID = $this->where("open_id",$user["open_id"])->field("unionid")->find()["unionid"];
                    $res = Db::name("fresh_info") -> where("XH",$user["studentID"])
                                ->update(["unionid"=> $unionID]);

                    if (!$res) {
                        return -1;
                    }
                    return $userid;
                } else {
                    return false;
                }
            }
        }
    }

}