<?php

namespace app\api\controller\news;

use app\common\controller\Api;
use think\Config;
use fast\Http;


/**
 * 资讯栏目控制器
 */
class Yiban extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index(){
        $page = $this->request->post('page');
        $openid = $this->request->post('openid');

        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                [
                    'articleid' => '1',
                    'type' => 'yiban',
                    'title'=>'关于头条的测试情况',
                    'time'=> '2018-04-07 19:00:00',
                ],
                [
                    'articleid' => '2',
                    'type' => 'yiban',
                    'title'=>'关于头条的测试情况2',
                    'time'=> '2018-04-07 20:00:00',
                ],
                [
                    'articleid' => '3',
                    'type' => 'yiban',
                    'title'=>'关于头条的测试情况2',
                    'time'=> '2018-04-07 20:00:00',
                ],
                [
                    'articleid' => '4',
                    'type' => 'yiban',
                    'title'=>'关于头条的测试情况2',
                    'time'=> '2018-04-07 20:00:00',
                ],
                [
                    'articleid' => '5',
                    'type' => 'yiban',
                    'title'=>'关于头条的测试情况2',
                    'time'=> '2018-04-07 20:00:00',
                ],
                [
                    'articleid' => '6',
                    'type' => 'yiban',
                    'title'=>'关于头条的测试情况2',
                    'time'=> '2018-04-07 20:00:00',
                ],
                [
                    'articleid' => '7',
                    'type' => 'yiban',
                    'title'=>'关于头条的测试情况2',
                    'time'=> '2018-04-07 20:00:00',
                ],
            ]
        ];
        return json($info);
    }
}