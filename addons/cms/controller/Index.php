<?php

namespace addons\cms\controller;

use think\Config;
use think\Db;

class Index extends Base
{

    public function index()
    {
        Config::set('cms.title', __('Home'));
        Config::set('cms.keywords', '');
        Config::set('cms.description', '');
        return $this->view->fetch('/index');
    }

}
