<?php

namespace app\api\controller\news;

use app\common\controller\Api;
use think\Config;
use fast\Http;


/**
 * 资讯栏目控制器
 */
class Information extends Api
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
                    'type' => 'all',
                    'style' => '0',
                    'tag' => '头条',
                    'title'=>'关于头条的测试情况',
                    'time'=> '2018-04-07 19:00:00',
                    'views' => '123',
                    'likes' => '5',
                ],
                [
                    'articleid' => '2',
                    'type' => 'new',
                    'style' => '0',
                    'tag' => '测试2',
                    'title'=>'这里如果突然来了一个很长很长很长很长的title，不知道是不是可以显示完全',
                    'time'=> '2018-04-07 20:00:00',
                    'views' => '3.5k',
                    'likes' => '75',
                ],
                [
                    'articleid' => '2',
                    'type' => 'new',
                    'style' => '1',
                    'tag' => '测试2',
                    'title'=>'这里有一个大图，我将何去何从？',
                    'time'=> '2018-04-07 20:00:00',
                    'views' => '3.5k',
                    'icons' => [
                        'http://www.chd.edu.cn/_upload/article/images/cb/1e/a1222683457fb6b039a49d82093a/81815730-7ba9-4666-a918-e102c2eb033b.jpg',
                    ],
                    'likes' => '75',
                ],
                [
                    'articleid' => '2',
                    'type' => 'new',
                    'style' => '2',
                    'tag' => '测试2',
                    'title'=>'常规普通模式',
                    'time'=> '2018-04-07 20:00:00',
                    'summary' => '这里其实可以显示一些简介，如果可以有这个功能的话，前期可以不提取。',
                    'views' => '3.5k',
                    'icons' => [
                        'http://www.chd.edu.cn/_upload/article/images/e8/bd/a561655346b6881ab48ca323662d/78678899-9674-4845-8304-f1edf4ac0850.jpg',
                    ],
                    'likes' => '75',
                ],
                [
                    'articleid' => '2',
                    'type' => 'new',
                    'style' => '3',
                    'tag' => '三图模式',
                    'title'=>'三图模式的新闻',
                    'time'=> '2018-04-07 20:00:00',
                    'summary' => '这里其实可以显示一些简介，如果可以有这个功能的话，前期可以不提取。',
                    'views' => '3.5k',
                    'icons' => [
                        'http://news.chd.edu.cn/_upload/article/images/59/86/f686906f4e80928139e1776b449d/4ff673c1-78e0-4dc1-b218-dedf3c21f548.png',
                        'http://www.chd.edu.cn/_upload/article/images/e8/bd/a561655346b6881ab48ca323662d/78678899-9674-4845-8304-f1edf4ac0850.jpg',
                        'http://www.chd.edu.cn/_upload/article/images/e8/bd/a561655346b6881ab48ca323662d/78678899-9674-4845-8304-f1edf4ac0850.jpg',
                    ],
                    'likes' => '75',
                ],
            ]
        ];
        return json($info);
    }
}