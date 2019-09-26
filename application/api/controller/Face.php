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
                "name" => "头 像 1",
                "coverImgUrl" => "https://yibancdn.ohao.ren/guoqing/avatar/1.png",
            ],
            [
                "name" => "头 像 2",
                "coverImgUrl" => "https://yibancdn.ohao.ren/guoqing/avatar/2.png",
            ],
            [
                "name" => "头 像 3",
                "coverImgUrl" => "https://yibancdn.ohao.ren/guoqing/avatar/3.png",
            ],
            [
                "name" => "头 像 4",
                "coverImgUrl" => "https://yibancdn.ohao.ren/guoqing/avatar/4.png",
            ],
            [
                "name" => "信息学院",
                "coverImgUrl" => "https://yibancdn.ohao.ren/guoqing/avatar/xx.png",
            ],
        ];
        $this->success('success', $result);
    }


}
