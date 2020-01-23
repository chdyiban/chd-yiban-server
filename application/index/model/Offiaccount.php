<?php
namespace app\index\model;


use think\Model;
use think\Db;



class Offiaccount extends Model {
    // 表名

    protected $name = 'wx_unionid_user';

    /**
     * 初始化方法，判断用户状态
     * @return array 
     * step = 0 该用户尚未授权（未获取unionid），需要授权
     * step = 1 该用户已经授权，需要绑定门户信息，跳转至绑定界面
     * step = 2 用户已经绑定信息门户，跳转至功能页
     */
    public function initStep($params)
    {
        //判断用户是否登录订阅号
        $infoList = $this->where("open_id",$params["openid"])->find();
        if (empty($infoList)) {
            //用户未曾登录，则使用户先授权
            return ["status" => true,"step" => 0, "msg" => "请授权" ];
            // $result = $this->insertInfo($params);
        } 
        if (empty($infoList["portal_id"])) {
            return ["status" => true,"step" => 1, "msg" => "请先绑定门户账号" ];
        }

        return ["status" => true,"step" => 2, "msg" => "已经绑定门户账号" ];
    }

    /**
     * 绑定微信授权信息，unionid，avatarurl,nickname等
     * step = 0 该用户尚未授权（未获取unionid），需要授权
     * step = 1 该用户已经授权，需要绑定门户信息，跳转至绑定界面
     * step = 2 用户已经绑定信息门户，跳转至功能页
     */
    public function bindWxUserinfo($params)
    {
        //判断用户是否登录订阅号
        $infoList = $this->where("open_id",$params["openid"])->find();
        if (empty($infoList)) {
            //用户未曾登录，则使用户先授权
            $result = $this->insertInfo($params);
            if ($result == false) {
                return ["status" => true,"step" => 1, "msg" => "请先绑定门户账号" ];
            }
            return ["status" => true,"step" => 2, "msg" => "已经绑定门户账号" ];
        } else {
            if (empty($infoList["portal_id"])) {
                return ["status" => true,"step" => 1, "msg" => "请先绑定门户账号" ];
            }
    
            return ["status" => true,"step" => 2, "msg" => "已经绑定门户账号" ];
        }
    }


    /**
     * 将用户所有信息插入数据库
     * @return bool true 插入信息并且绑定门户信息
     * @return bool false 插入信息，没有绑定门户信息
     */
    private function insertInfo($params)
    {
        //首先判断小程序表中是否有门户信息
        $portalInfo = Db::name("wx_user")->where("unionid",$params["unionid"])->find();
        $portal_id  =   "";
        $portal_pwd =   "";
        if (!empty($portalInfo["portal_id"]) && !empty($portalInfo["portal_pwd"]) ) {
            $portal_id = $portalInfo["portal_id"];
            $portal_pwd = _token_decrypt($portalInfo["portal_pwd"], $portalInfo['open_id']);
            $portal_pwd = _token_encrypt($portal_pwd, $params['openid']);
        }
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
            "avatar"            =>  $params['headimgurl'],
            "nickname"          =>  $params["nickname"],
            "update_time"       =>  time(),
        ];
        $result = Db::name("wx_unionid_user")->insert($insertData);
        if ($result && $portal_id == "") {
            return false;
        }
        return true;
    }

    /**
     * 将用户所有信息更新数据库
     * @return bool true 更新信息并且绑定门户信息
     * @return bool false 更新信息，没有绑定门户信息
     */
    private function updateInfo($params)
    {
        $portal_id = $this->where("open_id",$params["openid"])->find()["portal_id"];
        $updateData = [
            "last_visit_time"   =>  time(),
            "access_token"      =>  $params["access_token"],
            "refresh_token"     =>  $params["refresh_token"],
            "expires_time"      =>  $params["expires_in"],
            "avatar"            =>  $params['headimgurl'],
            "nickname"          =>  $params["nickname"],
            "update_time"       =>  time(),
        ];
        $result = Db::name("wx_unionid_user")
                ->where("open_id",$params["openid"])
                ->update($updateData);

        if (empty($portal_id)) {
            return false;
        }
        return true;
    }

    /**
     * 将用户门户信息保存至数据库
     */
    public function bind($params)
    {
        $params["portal_pwd"] = _token_encrypt($params["portal_pwd"], $params['openid']);
        $result =  Db::name("wx_unionid_user")
                    -> where("open_id",$params["openid"])
                    -> update([
                        "portal_id"     =>  $params["portal_id"],
                        "portal_pwd"    =>  $params["portal_pwd"],
                    ]);
        if ($result) {
            return ["status" => true,"msg" => "绑定成功"];
        }
        return ["status" => false,"msg" => "绑定失败，请稍后再试！"];
    }

}