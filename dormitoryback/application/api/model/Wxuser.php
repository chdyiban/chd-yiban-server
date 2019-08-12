<?php

namespace app\api\model;

use think\Model;

class Wxuser extends Model
{
    // 表名
    protected $name = 'wx_user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'last_visit_time';

    // // 追加属性
    // protected $append = [
    //     'prevtime_text',
    //     'logintime_text',
    //     'jointime_text'
    // ];

    // public function saveUserInfo($data){

    //     $this->open_id = $data['openid'];
    //     $this->session_key = $datap['session_key'];
    //     $user->save();
    // }

}
