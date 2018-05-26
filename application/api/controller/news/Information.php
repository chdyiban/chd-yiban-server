<?php

namespace app\api\controller\news;

use app\common\controller\Api;
use think\Config;
use fast\Http;

use addons\cms\model\Archives as ArchivesModel;
use addons\cms\model\Channel;
use addons\cms\model\Comment;
use addons\cms\model\Modelx;


/**
 * 资讯栏目控制器
 */
class Information extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index(){
        $page = (int)$this->request->get('page');
        $openid = $this->request->get('openid');

        $params = [];
        $model = (int)$this->request->request('model');
        $channel = (int)$this->request->request('channel');

        if ($model) {
            $params['model'] = $model;
        }
        if ($channel) {
            $params['channel'] = $channel;
        }
        $page = max(1, $page);
        $params['limit'] = ($page - 1) * 10 . ',10';

        $list = ArchivesModel::getArchivesList($params);
        //$list = ArchivesModel::getWeAppArchivesList($params);

        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $list
        ];
      
        return json($info);
    }

    //对应CMS模块下的新闻详情
    public function detail(){
        // $action = $this->request->post("action");
        // if ($action && $this->request->isPost()) {
        //     return $this->$action();
        // }
        $diyname = $this->request->param('diyname');
        if ($diyname && !is_numeric($diyname)) {
            $archives = ArchivesModel::getByDiyname($diyname);
        } else {
            $id = $diyname ? $diyname : $this->request->request('id', '');
            $archives = ArchivesModel::get($id);
        }
        if (!$archives || $archives['status'] == 'hidden' || $archives['deletetime']) {
            $this->error(__('No specified article found'));
        }
        $channel = Channel::get($archives['channel_id']);
        if (!$channel) {
            $this->error(__('No specified channel found'));
        }
        $model = Modelx::get($channel['model_id']);
        if (!$model) {
            $this->error(__('No specified model found'));
        }
        $archives->setInc("views", 1);
        $addon = db($model['table'])->where('id', $archives['id'])->find();
        if ($addon) {
            $archives = array_merge($archives->toArray(), $addon);
        }

        $commentList = Comment::getCommentList(['aid' => $archives['id']]);

        $list = ['archivesInfo' => $archives, 'channelInfo' => $channel, 'commentList' => $commentList->getCollection()];
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $list
        ];

        return json($info);
    }
}