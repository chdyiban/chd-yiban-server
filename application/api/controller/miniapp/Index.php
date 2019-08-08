<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use app\api\model\Wxuser as WxuserModel;

/**
 * 
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 获取小程序前端banner list
     */
    public function banner_list(){
        // sleep(3);
        $key = json_decode(base64_decode($this->request->post('key')),true);
        // $course = $this->get_course($key);
        $info = [
            'status' => 200,
            'msg' => 'success',
            'data' => [
                [
                    "id"   =>  0,
                    "type" =>  'image',
                    "color"=>  '#2c6dd1',
                    "url"  =>  'https://2019.yibancdn.ohao.ren/weapp/test/banner3.jpg',
                    ""
                ],
                [
                    "id"   =>  1,
                    "type" =>  'image',
                    "color"=>  '#090f4d',
                    "url"  =>  'https://2019.yibancdn.ohao.ren/weapp/test/banner2.jpg',
                ],
                [
                    "id"   =>  2,
                    "type" =>  'image',
                    "color"=>  '#1f1669',
                    "url"  =>  'https://2019.yibancdn.ohao.ren/weapp/test/banner1.jpg',
                ],
                [
                    "id"   =>  3,
                    "type" =>  'image',
                    "color"=>  '#51a5fd',
                    "url"  =>  'https://2019.yibancdn.ohao.ren/weapp/test/banner4.jpg',
                ],
                [
                    "id"   =>  4,
                    "type" =>  'image',
                    "color"=>  '#1a47e2',
                    "url"  =>  'https://2019.yibancdn.ohao.ren/weapp/test/banner5.jpg',
                ],
            ]
        ];
        return json($info);
    }

    /**
     * 获取小程序前端应用list
     */

    public function app_list()
    {
        // sleep(3);
        $key = json_decode(base64_decode($this->request->post('key')),true);
        // $course = $this->get_course($key);
        $info = [
            'status' => 200,
            'msg' => 'success',
            'data' => [
                [
                    "id"   =>  'bx',
                    "icon" =>  'repairfill',
                    "color"=>  'brown',
                    "badge"=> '试运行',
                    "name" =>  '报修',
                    "permissions" => [ 
                        "unauthorized" => false,
                        "teacher"      => true,
                    ],
                    "usable" => true,
                    "errMsg" => '',
                ],
                [
                    "id"   =>  'ykt',
                    "icon" =>  'card',
                    "color"=>  'yellow',
                    "badge"=>   0,
                    "name" =>  '一卡通',
                    "permissions" => [ 
                        "unauthorized" => false,
                        "teacher"      => true,
                    ],
                    "usable" => false,
                    "errMsg" => '应用升级中',
                ],
                [
                    "id"   =>  'kb',
                    "icon" =>  'newsfill',
                    "color"=>  'blue',
                    "badge"=>   0,
                    "name" =>  '课表查询',
                    "permissions" => [ 
                        "unauthorized" => false,
                        "teacher"      => false,
                    ],
                    "usable" => true,
                    "errMsg" => '应用升级中',
                ],
                [
                    "id"   =>  'form',
                    "icon" =>  'formfill',
                    "color"=>  'cyan',
                    "name" =>  "万能表单",
                    "permissions" => [ 
                        "unauthorized" => false,
                        "teacher"      => true,
                    ],
                    "usable" => true,
                ],
                [
                    "icon" =>  'babyfill',
                    "color"=>  'blue',
                    "badge"=>   0,
                    "name" =>  '成绩查询',
                ],
                [
                    "icon" =>  'medalfill',
                    "color"=>  'orange',
                    "badge"=>   0,
                    "name" =>  '运动会',
                ],
                [
                    "icon" =>  'attentionfavorfill',
                    "color"=>  'mauve',
                    "badge"=>   0,
                    "name" =>  '班主任评价',
                ],
                [
                    "icon" =>  'favorfill',
                    "color"=>  'mauve',
                    "badge"=>   0,
                    "name" =>  '最佳辅导员',
                ],
                [
                    "icon" =>  'homefill',
                    "color"=>  'cyan    ',
                    "badge"=>   0,
                    "name" =>  '我的宿舍',
                ],
                [
                    "icon" =>  'barcode',
                    "color"=>  'green',
                    "badge"=>   0,
                    "name" =>  '借阅信息',
                ],
                [
                    "icon" =>  'servicefill',
                    "color"=>  'blue',
                    "badge"=>   0,
                    "name" =>  '智能客服',
                ],
            
            ],
        ];
        return json($info);
    }
}