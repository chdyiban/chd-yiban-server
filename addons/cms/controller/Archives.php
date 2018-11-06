<?php

namespace addons\cms\controller;

use addons\cms\model\Archives as ArchivesModel;
use addons\cms\model\Channel;
use addons\cms\model\Modelx;
use think\Config;

class Archives extends Base
{

    public function index()
    {
        $action = $this->request->post("action");
        if ($action && $this->request->isPost()) {
            return $this->$action();
        }
        $diyname = $this->request->param('diyname');
        if ($diyname && !is_numeric($diyname)) {
            $archives = ArchivesModel::getByDiyname($diyname);
        } else {
            $id = $diyname ? $diyname : $this->request->get('id', '');
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
        $this->view->assign("__ARCHIVES__", $archives);
        $this->view->assign("__CHANNEL__", $channel);
        Config::set('cms.title', $archives['title']);
        Config::set('cms.keywords', $archives['keywords']);
        Config::set('cms.description', $archives['description']);
        return $this->view->fetch('/' . preg_replace('/\.html$/', '', $channel['showtpl']));
    }

    /**
     * 赞与踩
     */
    public function vote()
    {
        $id = (int)$this->request->post("id");
        $type = trim($this->request->post("type", ""));
        if (!$id || !$type) {
            $this->error(__('Operation failed'));
        }
        $archives = ArchivesModel::get($id);
        if (!$archives || $archives['status'] == 'hidden') {
            $this->error(__('No specified article found'));
        }
        $archives->where('id', $id)->setInc($type === 'like' ? 'likes' : 'dislikes', 1);
        $archives = ArchivesModel::get($id);
        $this->success(__('Operation completed'), null, ['likes' => $archives->likes, 'dislikes' => $archives->dislikes, 'likeratio' => $archives->likeratio]);
    }

}
