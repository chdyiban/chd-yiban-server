<?php

namespace app\api\controller\news;

use app\common\controller\Api;
use think\Config;
use fast\Http;


/**
 * 资讯详情控制器
 */
class Detail extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index(){
        $type = $this->request->post('type');
        $page = $this->request->post('page');
        $openid = $this->request->post('openid');

        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                'title' => '这里是标题',
                'author' => '易班工作站',
                'createtime'=>'2018-04-05 17:00:00',
                'views' => '3.3k',
                'likes' => '234',
                'body' => '这里是正文内容',
                'source' => '长安大学新闻网',
                'fjlist' => [
                    [
                        'fjtitle'=>'关于XX的通知.doc',
                        'flink'=>'http://www.chd.edu.cn/abc.doc',
                    ]
                ]
            ]
        ];
        return json($info);
    }
}