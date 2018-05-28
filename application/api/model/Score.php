<?php

namespace app\api\model;

use think\Model;
use fast\Http;

class Score extends Model
{
    // 表名
    protected $name = 'wx_user';
    // 爬取学生成绩
    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';
    const CAPTCHA_URL = 'http://ids.chd.edu.cn/authserver/captcha.html';
    const SCORE_URL = "http://ids.chd.edu.cn/authserver/login?service=http://bkjw.chd.edu.cn/eams/teach/grade/course/person!search.action?semesterId=";
    //本学期的id
    const SCORE_ITEM_ID = 77;

    public function get_score($key){
        $username = $key['id'];
        //用来根据年级判断查询的范围
        $year = substr($username,0,4);
        $Y = date('Y');
        $m = date('m');
        //如果是下学期
        if(self::SCORE_ITEM_ID % 2 != 0){
            $temp = $Y - $year;
            //获取入学时对应的学期id
            $score_item_id =  self::SCORE_ITEM_ID - ($temp * 2 - 1); 
            $score_id = array();
            for($i = $score_item_id; $i <= self::SCORE_ITEM_ID; $i++){
                array_push($score_id, $i);
            }
        }else{
            //考虑第一学期，年份在变
            $temp = $Y - $year;
        }
        $info = $this->where('portal_id',$username)->field('open_id,portal_pwd')->find();
        $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
        $params[CURLOPT_COOKIEJAR] = RUNTIME_PATH .'/cookie/cookie_'.$username.'.txt';
        $params[CURLOPT_COOKIEFILE] = $params[CURLOPT_COOKIEJAR];
        $params[CURLOPT_FOLLOWLOCATION] = 1;
        //首先带着cookie去尝试获取数据，判断cookie是否过期
        $data = $this->get_stu_score($username, $params, $score_id);
        if($data != false){
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
                $res = $this->get_stu_score($username, $params, $score_id);
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
                $res = $this->get_stu_score($username, $params, $score_id);
                return $res;
            }
        }
    }
    public function get_stu_score($username, $params, $score_id){
        $data = [];
        
        //$res = $this -> get_data($username, $params, $score_id[1]);
        foreach ($score_id as $key => $value) {
            $res = $this -> get_data($username, $params, $value);
            if($res == false){
                return false;
            }else{
                $data[$key] = $res;
            }  
            sleep(1);
       }
        
        return $data;
    }
     //这个方法用来获取数据并返回
    public function get_data($username, $params, $id){
        $data = array();
        $url = self::SCORE_URL.(string)$id;
        $response = Http::get($url,'',$params);
        preg_match_all('/<th.*?>(.*?)<\/th?>/i', $response, $matches_header);
        preg_match_all('/<td.*?>(.*?)<\/td?>/si', $response, $matches);
        //dump($response);
        //dump($matches[1]); 
        if(empty($matches[1])){
            $data = ["尚未出成绩"];
        }elseif(strpos($matches[1][0],'<strong>') !== false){
            return false;
        }else{
            //通过循环判断抓出的表格有几列
            $countColums = count($matches_header[1]);

            //当前有几门课
            $num = count($matches[1])/$countColums;
            // if($num == 0){
            //     //未出成绩
            //     $data = [];
            // }
            //门数循环
            for($i=0;$i<$num;$i++){
                //字段循环
                for($j=0;$j<$countColums;$j++){
                    $score[$i][$j]['key'] = $matches_header[1][$j];
                    $score[$i][$j]['val'] = trim($matches[1][$i*$countColums+$j]);
                }
            }
        
            foreach ($score as $value) {
                $res = [];
                foreach ($value as $k => $v) {
                    if ($v['key'] == "学年学期") {
                    $res['term'] = $v['val'];
                    }
                    if ($v['key'] == "课程名称") {
                        $res['course_name'] = $v['val'];
                    }
                    if ($v['key'] == "最终") {
                        $res['score'] = $v['val'];
                    }
                    $res['xh'] = $username;
                }
                $data[] = $res;
            }              
        }
        return $data;
    }
}