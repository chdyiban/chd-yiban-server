<?php

namespace app\api\controller\news;

use app\common\controller\Api;
use think\Config;
use fast\Http;

use app\api\model\News as NewsModel;


/**
 * 资讯栏目控制器
 */
class Chdnews extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index(){
        $page = $this->request->get('page');
        $openid = $this->request->get('openid');

        $model = new NewsModel;
        $news = $model->getNews('news',$page);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $news
        ];
        return json($info);
    }
}