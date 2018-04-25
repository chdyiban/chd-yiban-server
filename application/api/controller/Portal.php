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
                'week' => get_weeks(),
                'day' => '1',
                'lessons' => [
                [
                    [
                        ['weeks' => '8','number' => '2','name' => '测试课程2','place' => 'WM1405'],
                        ['weeks' => '8','number' => '1','name' => '测试课程1','place' => 'WH1405'],
                        ['weeks' => '8','number' => '4','name' => '测试课程4','place' => 'WH1405'],
                        // ['weeks' => '3','number' => '5','name' => '测试课程3','place' => 'WH1405'],
                        // ['weeks' => '3','number' => '6','name' => '测试课程6','place' => 'WH1405'],
                        // ['weeks' => '3','number' => '7','name' => '测试课程7','place' => 'WH1405'],
                        //['weeks' => '3','number' => '8','name' => '测试课程8','place' => 'WH1405']
                    ],
                    [
                        ['weeks' => '8','number' => '2','name' => '测试课程5','place' => 'WM1405'],
                        ['weeks' => '8','number' => '1','name' => '测试课程6','place' => 'WH1405'],
                        ['weeks' => '8','number' => '4','name' => '测试课程7','place' => 'WH1405'],
                    ]
                ]
                ],
                'time_list' => [
                    ['begin'=>'8:00','end'=>'8:45'],
                    ['begin'=>'8:55','end'=>'9:40'],
                    ['begin'=>'10:05','end'=>'10:50'],
                    ['begin'=>'11:00','end'=>'11:45'],
                    ['begin'=>'14:00','end'=>'14:45'],
                    ['begin'=>'14:55','end'=>'15:40'],
                    ['begin'=>'16:05','end'=>'16:50'],
                    ['begin'=>'17:00','end'=>'17:45'],
                    ['begin'=>'19:00','end'=>'19:45'],
                    ['begin'=>'19:55','end'=>'20:40'],
                    ['begin'=>'20:50','end'=>'21:35']
                ],
                'is_vacation' => 'F'
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
                // '2018-03-18 18:00:00' => [
                //     'cost' => '4:50'
                // ],
                // 'last_time' => '2018-03-08 17:58:00',
                // 'balance' => '0.80'
                [
                    'balance' => '17.4',
                    'cost' => '-5.00',
                    'time' => '2018-04-17 17:58:00',
                ],
                [
                    'balance' => '20.6',
                    'cost' => '-5.80',
                    'time' => '2018-04-16 17:58:00',
                ],
                [
                    'balance' => '30',
                    'cost' => '-1.80',
                    'time' => '2018-04-16 17:58:00',
                ],
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

    //获取考试成绩
    public function score(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                [
                    'term'=>'2017-2018学年 第一学期',
                    'xh'=>'2017900000',
                    'course_name'=>'高等数学',
                    'score'=>'95'
                ],
                [
                    'term'=>'2017-2018学年 第二学期',
                    'xh'=>'2017900000',
                    'course_name'=>'大学英语',
                    'score'=>'88'
                ],
            ]
        ];
        return json($info);
    }

    //获取空闲教室
    public function empty_room(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        /**
         * [
         *  weekNo:第几周
         *  weekDay:周几（周一:1 周二:2 ……）
         *  classNo:第几节课 1@2:一二节课 1@2@3@4 一二三四节课
         *  buildingNo: 1:宏远 2:明远 3:修远
         *  openid:微信openid
         *  timestamp:时间戳
         *  sign:签名验证字符串
         * ]
         */
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                ['room' => ['WM1211']],
                ['room' => ['WM2501']]
            ]
        ];
        return json($info);
    }
}