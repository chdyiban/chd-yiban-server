<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use think\Validate;
use app\api\model\Dormitory as DormitoryModel;

use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
/**
 * 
 */
class Dormitoryadmin extends User
{
    protected $noNeedLogin = [];
    protected $noNeedRight = [];

    public function getinfo()
    {
        
    }

}
