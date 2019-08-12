<?php

namespace app\api\controller\more;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use fast\Random;
use wechat\wxBizDataCrypt;
//use app\api\model\Wxuser as WxuserModel;


/**
 * 反馈控制器
 */
class Issue extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                [
                    'issues'=>'1',
                    'title'=>'测试反馈',
                    'content'=>
                    [
                        'state' => 'open',
                        'labels'=>[
                            'name'=>'测试反馈',
                        ],
                        'body'=>'测试反馈的详细内容body',

                    ],
                    'comments'=>
                    [
                        [
                            'user'=>[
                                'avatar_url'=>'https://avatars2.githubusercontent.com/u/14970643?s=460&v=4',
                                'login'=>'chdyjy'
                            ],
                            'body'=>'评论内容',
                        ]
                    ]

                ],[
                    'issues'=>'2',
                    'title'=>'测试反馈2',
                    'content'=>
                    [
                        'state' => 'closed',
                        'labels'=>[
                            'name'=>'测试反馈',
                        ],
                        'body'=>'测试反馈的详细内容body2',
                    ]
                ],
            ]
        ];
        return json($info);
    }

    public function submit(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $key
        ];
        return json($info);
    }
}