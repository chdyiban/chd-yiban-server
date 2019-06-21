<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 获取慕读小程序请求地址
 */
class Geturl extends Api
{

    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];


    public function geturl()
    {
        $url = "http://3ywaaz.natappfree.cc";
        $this->success('返回成功', $url);
    }


}
