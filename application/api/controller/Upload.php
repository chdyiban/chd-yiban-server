<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use fast\Random;
use wechat\wxBizDataCrypt;

use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
//use app\api\model\Wxuser as WxuserModel;


/**
 * 反馈控制器
 */
class Upload extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function token(){
        $key = json_decode(base64_decode($this->request->post('key')),true);

        $upManager = new UploadManager();
        $auth = new Auth(Config::get('qiniu.AccessKey'), Config::get('qiniu.SecretKey'));
        $token = $auth->uploadToken(Config::get('qiniu.bucket'));

        //list($ret, $error) = $upManager->put($token, 'formput', 'hello world');

        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                'token'=>$token
            ]
        ];
        return json($info);
    }
}