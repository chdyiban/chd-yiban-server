<?php

namespace app\admin\model;

use think\Model;
use think\Session;
use fast\Http;

class Admin extends Model
{
    const CHECK_URL = "http://202.117.64.236:8080/auth/login";
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 重置用户密码
     * @author baiyouwen
     */
    public function resetPassword($uid, $NewPassword)
    {
        $passwd = $this->encryptPassword($NewPassword);
        $ret = $this->where(['id' => $uid])->update(['password' => $passwd]);
        return $ret;
    }
    //对接学校的用户账号与密码
    public function check($username,$password)
    {
        $post_data = [
            'userName' => $username,
            'pwd' => $password,
        ];
        $return = [];
        $response = Http::post(self::CHECK_URL,$post_data);
        $response = json_decode($response,true);
        $return['status'] = $response['success'] == "true" ? true:false;
        $return["status"] = true;
        return $return["status"];
    }

    // 密码加密
    protected function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($password . $salt);
    }

}
