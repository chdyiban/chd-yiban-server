<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use \think\Cache;
use app\api\model\Wxuser as WxuserModel;


/**
 * 获取微信小程序码的接口
 */
class Wxcode extends Api
{

    protected $noNeedLogin = [''];
    protected $noNeedRight = [''];

    const GET_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
    const GET_CODE_URL = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=';

    private function getAccessToken()
    {
        $appid = Config::get('wx.appId');
        $appsecret = Config::get('wx.appSecret');
        $param = [
            'grant_type' => 'client_credential',
            'appid'      => $appid,
            'secret'     => $appsecret,
        ];
        $response = Http::get($this::GET_ACCESS_TOKEN_URL,$param);
        $response = json_decode($response,true);
        if (empty($response['errcode']) || $response['errcode'] == 0) {
            //将结果写入缓存
            Cache::set('WxAccessToken', $response['access_token'],$response['expires_in']);
            return $response['access_token'];
        } else {
            $this -> error($response['errmsg']);
        }
    }

    public function getWXACodeUnlimit()
    {
        header('Content-type:image/jpeg'); 
        //判断缓存是否有access_token
        $access_token = Cache::get('WxAccessToken');

        if (empty($access_token)) {
             $access_token = $this->getAccessToken();

        }
        $scene = $this->request->post('scene');
        $page = $this->request->post('page');

        if (empty($scene)) {
            $this->error('参数有误');
        }

        if (empty($page)) {

            $param = [
                'scene'        => $scene,
            ];
        } else {

            $param = [
                'scene'        => $scene,
                'page'        => $page,
            ];
        }
        $postData = json_encode($param);
        $response = Http::post(self::GET_CODE_URL.$access_token,$postData);
        $result = json_decode($response,true);
        
        if (empty($result)) {
            return base64_encode($response);
        } else {
            $this->error($result['srrmsg']);
        }
    }

}