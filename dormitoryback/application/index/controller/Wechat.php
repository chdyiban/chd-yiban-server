<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use think\Db;
use fast\Http;
use think\Cache;


class Wechat extends Frontend
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    const GET_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
    const GET_TICKET_URL = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=';
  

    /**
     * 获取签名
     * @param string nonceStr
     * @param string timeStamp
     * @param string url
     */
    public function getSignature()
    {
        $nonceStr = $this->request->post('nonceStr');
        $timeStamp = $this ->request->post('timeStamp');
        $ticket = $this -> getJsTicket();
        $url = $this ->request-> post("url");
        $string1 = "jsapi_ticket=".$ticket."&noncestr=".$nonceStr."&timestamp".$timeStamp."&url".$url;
        $signature = sha1($string1);
        return $signature;
    }
    /**
     * 获取jsapi_ticket
     */
    public function getJsTicket()
    {
        //判断缓存是否有ticket
        $ticket = Cache::get('IndexTicket');
        if (empty($ticket)) {
            $ticket = $this->getTicket();
        }
        return $ticket;
    }

    /**
     * 获取jsapi_ticket
     */
    private function getTicket()
    {   
        //判断缓存是否有access_token
        $access_token = Cache::get('IndexAccessToken');
        if (empty($access_token)) {
            $access_token = $this->getAccessToken();
        }
        $ticketUrl = self::GET_TICKET_URL.$access_token;
        $result = Http::get($ticketUrl);
        $result = json_decode($result,true);
        if ($result['errcode'] == 0) {
            Cache::set('IndexTicket', $result['ticket'],$result['expires_in']);
            return $result['ticket'];
        } else {
            $this -> error();
        }
    }

    /**
     * 获取access_token
     */
    private function getAccessToken()
    {
        $appid = "wx7127494fe62b5813";
        $appsecret = "8ceca4754c5225323bcddf71469dfd3a";
        $param = [
            'grant_type' => 'client_credential',
            'appid'      => $appid,
            'secret'     => $appsecret,
        ];
        $response = Http::get($this::GET_ACCESS_TOKEN_URL,$param);
        $response = json_decode($response,true);
        if (empty($response['errcode']) || $response['errcode'] == 0) {
            //将结果写入缓存
            Cache::set('IndexAccessToken', $response['access_token'],$response['expires_in']);
            return $response['access_token'];
        } else {
            $this -> error($response['errmsg']);
        }
    }
}
