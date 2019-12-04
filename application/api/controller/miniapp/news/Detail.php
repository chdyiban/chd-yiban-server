<?php

namespace app\api\controller\miniapp\news;

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

        $returnData = [
            'title' => $data['title'],
            'author' => $data['author'],
            //'createtime'=> formatTime($data['create_time']),
            'createtime'=> date("Y-m-d", $data['create_time']),
            'views' => isset($data['views']) ? $data['views'] : '保密',
            'likes' => isset($data['likes']) ? $data['likes'] : '保密',
            'body' => $data['content'],
            'source' => $model->getSourceName($type),
        ];
        // $info = [
        //     'status' => 200,
        //     'message' => 'success',
        //     'data' => [
        //         'title' => $data['title'],
        //         'author' => $data['author'],
        //         //'createtime'=> formatTime($data['create_time']),
        //         'createtime'=> date("Y-m-d", $data['create_time']),
        //         'views' => isset($data['views']) ? $data['views'] : '保密',
        //         'likes' => isset($data['likes']) ? $data['likes'] : '保密',
        //         'body' => $data['content'],
        //         'source' => $model->getSourceName($type),
        //         // 'fjlist' => [
        //         //     [
        //         //         'fjtitle'=>'附件.doc',
        //         //         'flink'=>$data['attachment'],
        //         //     ]
        //         // ]
        //     ]
        // ];
        $this->success("success",$returnData);
        // return json($info);
    }
    
}