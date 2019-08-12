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
    const CAPTCHA_URL = 'http://ids.chd.edu.cn/authserver/captcha.html';
    const GET_CURRENT_DATA_URL = "http://202.117.64.236:8011/info";
    const GET_HISTORY_DATA_URL = "http://202.117.64.236:8011/history";
    //一卡通获取数据由爬取改为从接口获取数据
    public function get_yikatong_data($key)
    {
        $username = $key['id'];
        $info = $this->where('portal_id',$username)->field('open_id,portal_pwd')->find();
        $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
        //获取当日消费记录
        $post_data = ["username" => $username, "password" => "888888"];
        $todayData = Http::post(self::GET_CURRENT_DATA_URL,$post_data);
        return $todayData;
    }


    /*
    public function get_yikatong_data($key){
        $username = $key['id'];
        $info = $this->where('portal_id',$username)->field('open_id,portal_pwd')->find();
        $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
        $params[CURLOPT_COOKIEJAR] = RUNTIME_PATH .'/cookie/cookie_'.$username.'.txt';
        $params[CURLOPT_COOKIEFILE] = $params[CURLOPT_COOKIEJAR];
        $params[CURLOPT_FOLLOWLOCATION] = 1;
        //首先带着cookie去尝试获取数据，判断cookie是否过期
        $data = $this->get_data($username, $params);
        if($data){
            //cookie没有过期，获取到数据。
            return $data;
        }else{
             //1.获取lt es
             $response = Http::get(self::PORTAL_URL,'',$params);

             $lt = explode('name="lt" value="', $response);
             $lt = explode('"/>', $lt[1]);
             $lt = $lt[0];
             
             $es = explode('name="execution" value="', $response);
             $es = explode('"/>', $es[1]);
             $es = $es[0];

            //判断是否需要验证码
            $need_url = "http://ids.chd.edu.cn/authserver/needCaptcha.html?username=".$username;
            $need = Http::get($need_url,'',$params);
            //$need值为true或者false
            if(strlen($need) == 5){
                //需要验证码
                $res = Http::get(self::CAPTCHA_URL,'',$params);
                $base64_str = base64_encode($res);
                $code = recognize_captcha($base64_str);
                $code = json_decode($code,true);
                if($code['err_no'] != 0){
                    $captcha = '';
                }else{
                   $captcha = $code['pic_str'];
                }

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
                $response = Http::post(self::PORTAL_URL,$post_data,$params);
                $res = $this->get_data($username, $params);
                return $res;
            }else{
                //不需要验证码
                $post_data = [
                    "username" => $username,
                    "password" => $password,
                    "captchaResponse" => '', 
                    "btn" => "登录",
                    "lt" => $lt,
                    "dllt" => "userNamePasswordLogin",
                    "execution" => $es,
                    "_eventId" => "submit",
                    "rmShown" => "1"
                ];
                $response = Http::post(self::PORTAL_URL,$post_data,$params);
                $res = $this->get_data($username, $params);
                return $res;
            }
        }
    }

    //这个方法用来获取数据并返回
    public function get_data($username,$params){
        if(strlen($username) == 6){
            $url_card = 'http://portal.chd.edu.cn/index.portal?.pn=p48_p1369';
        }else{
            $url_card = 'http://portal.chd.edu.cn/index.portal?.pn=p56_p232';
        }
        $html = Http::get($url_card, '',$params);
        preg_match_all('/url:"(.*?)",/', $html, $url_card_detail);
        //如果此数组为空，表示未能登录成功
        if(empty($url_card_detail[1])){
            return false;
        }else{
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
        }
    }
    */
}