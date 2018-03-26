<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;

/**
 * 获取课表
 */
class Portal extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';

    public function kebiao()
    {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);

        
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                'week' => '3',
                'day' => '1',
                'lessons' => [
                [
                    [
                        ['weeks' => '3','number' => '2','name' => '测试课程2','place' => 'WM1405'],
                        ['weeks' => '3','number' => '1','name' => '测试课程1','place' => 'WH1405'],
                        ['weeks' => '3','number' => '4','name' => '测试课程4','place' => 'WH1405'],
                        // ['weeks' => '3','number' => '5','name' => '测试课程3','place' => 'WH1405'],
                        // ['weeks' => '3','number' => '6','name' => '测试课程6','place' => 'WH1405'],
                        // ['weeks' => '3','number' => '7','name' => '测试课程7','place' => 'WH1405'],
                        //['weeks' => '3','number' => '8','name' => '测试课程8','place' => 'WH1405']
                    ]
                ]
                ],
            ]
        ];
        return json($info);
    }

    public function yikatong(){
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);

        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                '2018-03-18 18:00:00' => [
                    'cost' => '4:50'
                ],
                'last_time' => '2018-03-08 17:58:00',
                'balance' => '0.80'
            ]
        ];
        return json($info);
    }

    public function books(){
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => []
        ];
        return json($info);
    }
}