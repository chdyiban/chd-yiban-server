<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;

/**
 * 课表查询
 */
class Course extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                'week' => get_weeks(),
                'day' => '2',
                'lessons' => [
                    [
                        [],
                        [],
                        [
                           ['weeks' => [8],'number' => 2,'type'=>'必修','name' => '测试课程4','teacher'=>'杨老师','all_week'=>'2-11周','place' => 'WM1405','class_id' => '2402090201','xf'=>'2'],
                        ],
                        [
                            ['weeks' => [8],'number' => 2,'name' => '测试课程6','place' => 'WM1405','class_id' => '2402090202','xf'=>'2'],
                        ],
                        [
                            ['weeks' => [7,8],'number' => 3,'name' => '测试课程10','place' => 'WM1405','class_id' => '2402090201','xf'=>'2'],
                        ],
                        [
                            ['weeks' => [7],'number' => 2,'name' => '测试课程11','place' => 'WM1405','class_id' => '2402090201','xf'=>'2'],
                        ],

                    ],
                    [
                        [
                            ['weeks' => [8],'number' => 2,'name' => '测试课程00','place' => 'WM2502','class_id' => '2402090201','xf'=>'2'],
                        ],
                        [],
                        [
                            ['weeks' => [8],'number' => 2,'name' => '测试课程8','place' => 'WM2502','class_id' => '2402090201','xf'=>'2'],
                        ],
                    ],
                    [
                        [
                            ['weeks' => [8],'number' => 2,'name' => '测试课程9','place' => 'WM2502','class_id' => '2402090201','xf'=>'2'],
                        ],
                    ],
                ],
                'is_vacation' => 'F'
            ]
        ];
        return json($info);
    }
}