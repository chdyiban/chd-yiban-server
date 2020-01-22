<?php

namespace app\api\controller\miniapp;

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

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    const GET_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
    const GET_CODE_URL = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=';

    public function getAccessToken()
    {
        $appid = Config::get('wechat.miniapp_chdyiban')["appId"];
        $appsecret = Config::get('wechat.miniapp_chdyiban')["appSecret"];
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

    public function getWXACodeUnlimit($access_token = '')
    {
        header('Content-type:image/jpeg');  
        //判断缓存是否有access_token
        $access_token = Cache::get('WxAccessToken');
 
        if (empty($access_token)) {
             $access_token = $this->getAccessToken();
        }
        $scene = $this->request->get('scene');
        $page = $this->request->get('page');

        if (empty($scene)) {
            $this->error('error');
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

        //dump($response);
        if (empty($result)) {
            return response($response,200,['Content-Length' => strlen($response)]) ->contentType('image/jpeg');
        } else {
            //如果发现是因为验证码过期，则再次生成
            if ($result['errcode'] == '40001') {
                $access_token = $this->getAccessToken();
                $res = $this->getWXACodeUnlimit($access_token);
                //return $res;
                //echo $response;
            }
            $this->error($result);
        }
    }

}