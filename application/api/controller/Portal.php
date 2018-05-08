<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Ykt as YktModel;
/**
 * 获取课表
 */
class Portal extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';

    public function yikatong(){
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $Ykt = new YktModel;
        $data = $Ykt -> get_yikatong_data($key);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $data,
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