<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 获取慕读小程序请求地址
 */
class Face extends Api
{

    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];


    public function getImage()
    {
        header('Access-Control-Allow-Origin:*');    
        $result = [
            [
                "name" => "长安大学信息学院",
                "coverImgUrl" => "******",
            ],
            [
                "name" => "类型2",
                "coverImgUrl" => "******",
            ],
            [
                "name" => "类型3",
                "coverImgUrl" => "******",
            ],
            [
                "name" => "类型4",
                "coverImgUrl" => "******",
            ],
            [
                "name" => "类型5",
                "coverImgUrl" => "******",
            ],
        ];
        $this->success('success', $result);
    }


}
