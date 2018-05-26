<?php

namespace addons\cms\controller;

use addons\cms\model\Page as PageModel;
use think\Config;

class Page extends Base
{

    public function index()
    {
        $diyname = $this->request->param('diyname');
        if ($diyname && !is_numeric($diyname)) {
            $page = PageModel::getByDiyname($diyname);
        } else {
            $id = $diyname ? $diyname : $this->request->get('id', '');
            $page = PageModel::get($id);
        }
        if (!$page || $page['status'] == 'hidden') {
            $this->error(__('No specified page found'));
        }
        $this->view->assign("__PAGE__", $page);
        Config::set('cms.title', $page['title']);
        Config::set('cms.keywords', $page['keywords']);
        Config::set('cms.description', $page['description']);
        return $this->view->fetch('/' . rtrim($page['showtpl'] ? $page['showtpl'] : 'page', '.html'));
    }

}
