<?php

namespace app\api\controller\news;

use app\common\controller\Api;
use think\Config;
use fast\Http;

use app\api\model\News as NewsModel;

/**
 * 资讯详情控制器
 */
class Detail extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index(){
        $type = $this->request->get('type');
        $article_id = $this->request->get('id');
        $openid = $this->request->get('openid');

        $model = new NewsModel;
        $data = $model->getNewsDetail($type,$article_id);

        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                'title' => $data['title'],
                'author' => $data['author'],
                //'createtime'=> ($data['create_time'] % 3600 == 0 ) ? date('Y-m-d',$data['create_time']) : date('Y-m-d H:i:s',$data['create_time']),
                // 'views' => '3.3k',
                // 'likes' => '234',
                'body' => $data['content'],
                'source' => $model->getSourceName($type),
                // 'fjlist' => [
                //     [
                //         'fjtitle'=>'附件.doc',
                //         'flink'=>$data['attachment'],
                //     ]
                // ]
            ]
        ];

        if(is_numeric($data['create_time'])){
            $info['data']['createtime'] = ($data['create_time'] % 3600 == 0 ) ? date('Y-m-d',$data['create_time']) : date('Y-m-d H:i:s',$data['create_time']);
        }else{
            $info['data']['createtime'] = $data['create_time'];
        }
        return json($info);
    }
}