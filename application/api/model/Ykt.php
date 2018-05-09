<?php

namespace app\api\model;

use think\Model;
use fast\Http;

class Ykt extends Model
{
    // 表名
    protected $name = 'wx_user';
    // 爬取门户上校园卡的消费记录
    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';

    
    public function get_yikatong_data($key){
        $username = $key['id'];
        $info = $this->where('portal_id',$username)->field('open_id,portal_pwd')->find();
        $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
        $params[CURLOPT_COOKIEJAR] = RUNTIME_PATH .'/cookie/cookie_'.$username.'.txt';
        $captcha = '';
        if($captcha == ''){
            //无验证码情况下

            //1.获取lt es
            $response = Http::get(self::PORTAL_URL,'',$params);
            $lt = explode('name="lt" value="', $response);
        	$lt = explode('"/>', $lt[1]);
        	$lt = $lt[0];
            
        	$es = explode('name="execution" value="', $response);
        	$es = explode('"/>', $es[1]);
            $es = $es[0];
            // 2.post
            $post_data = [
                "username" => $username,
                "password" => $password,
                "captchaResponse" => $captcha, 
                "btn" => "登录",
                "lt" => $lt,
                "dllt" => "userNamePasswordLogin",
                "execution" => $es,
                "_eventId" => "submit",
                "rmShown" => "1"
            ];
            $params[CURLOPT_COOKIEFILE] = $params[CURLOPT_COOKIEJAR];
            $params[CURLOPT_FOLLOWLOCATION] = 1;
            $response = Http::post(self::PORTAL_URL,$post_data,$params);
            $url_card = 'http://portal.chd.edu.cn/index.portal?.pn=p56_p232';
            //先到主页通过查找js代码来找到获取校园卡的查看的按钮的url地址
            // $html = Http::get('http://portal.chd.edu.cn/index.portal', '',$params);
            // preg_match_all('/var url=\'(.*?)\';/', $html, $url_card);
            // $url_card = "http://portal.chd.edu.cn/".$url_card[1][0];
            // $html = Http::get($url_card, '',$params);
            //匹配下面的查看的url
            //preg_match_all('/<a target="_blank" style=".*?".*?href=\'(.*?)\'>查看<\/a>/s', $html, $url_card);
            //$url_card = $url_card[1][0];
            //接着到了校园卡的界面，这里将校园卡的界面写成定值.
            //得到校园卡界面后需要找出itemid, stuid, rar等等数据来构造出校园卡消费记录的地址
            $html = Http::get($url_card, '',$params);
            preg_match_all('/url:"(.*?)",/', $html, $url_card_detail);
            $url_card_detail = "http://portal.chd.edu.cn/".$url_card_detail[1][1];
            preg_match_all('/<input type="hidden" name=".*?" value="(.*?)" \/>/', $html, $data);
            $item_id = $data[1][0];  
            $child_id = $data[1][1]; 
            $res = [];
            //这里循环页数每页5跳数据
            for($i = 1;$i <= 2;$i++){
                $page = $i;
                $get_data = [
                    'itemId' => $item_id,
                    'childId' => $child_id,
                    'page' => $page,
                ];
                $html_card = Http::get($url_card_detail, $get_data, $params);
                preg_match_all('/<tr>(.*?)<\/tr>/s', $html_card, $card_data);
                $card_data = $card_data[1];   
                foreach($card_data as $k => $v){
                    $info = array();
                    $result = array();
                    if($k != 0){
                        preg_match_all('/<td>(.*?)<\/td>/s', $v, $msg);
                        foreach($msg[1] as $key => $value){
                            $info[$key] = trim($value);
                        }
                        $result['balance'] = $info[6];
                        $result['palce'] = $info[4];
                        $result['cost'] = $info[5];
                        $result['time'] = $info[2].' '.$info[3];
                        $result['ykt_id'] = $info[1];
                        array_push($res, $result);
                    }
                }
            }
            return $res;
        }else{
            //时间原因，暂时不考虑验证码的情况
            return false;
        }
    }
    
}