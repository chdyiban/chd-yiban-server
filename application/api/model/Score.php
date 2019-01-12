<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;

class Score extends Model
{
    // 表名
    protected $name = 'stu_score';
    // 爬取学生成绩
    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';
    const CAPTCHA_URL = 'http://ids.chd.edu.cn/authserver/captcha.html';
    //const SCORE_URL = "http://ids.chd.edu.cn/authserver/login?service=http://bkjw.chd.edu.cn/eams/teach/grade/course/person!search.action?semesterId=";
    const SCORE_URL = "http://bkjw.chd.edu.cn/eams/teach/grade/course/person!search.action?projectType=&looked=yes&semesterId=";
    //本学期的id，需要更新
    const SCORE_ITEM_ID = 78;

    public function get_score($key){
        $username = $key['id'];
        //用来根据年级判断查询的范围
        /*
        *先默认查找该学生当前学期成绩
        *
        $year = substr($username,0,4);
        $Y = date('Y');
        $m = date('m');
        $temp = $Y - $year;
        //如果是下学期
        if(self::SCORE_ITEM_ID % 2 != 0){
            //获取入学时对应的学期id
            $score_item_id =  self::SCORE_ITEM_ID - ($temp * 2 - 1);     
        }else{
            //第一学期过了元旦后
            if ($m < 6) {                          
                $score_item_id =  self::SCORE_ITEM_ID - ($temp - 1) * 2; 
            }else{
                $score_item_id =  self::SCORE_ITEM_ID - $temp * 2; 
            }
        }
        //得出该学生的所有学期的id的数组
        $score_id = array();
        for($i = $score_item_id; $i <= self::SCORE_ITEM_ID; $i++){
            array_push($score_id, $i);
        }
        //查询数据库中是否已经插入了该学生的之前的成绩
        $database_id = $this->where('XH',$username)->group('item_id')->field('item_id')->select();
        $database_ids = [];  
        //销毁重复id
        if(!empty($database_id)){
            //获取已有的学期的id          
            foreach ($database_id as $key => $value) {
                $database_ids[] = $value->toArray()['item_id'];
            }
            foreach ($database_ids as $key => $value) {
                foreach ($score_id as $k => $v) {
                    if ($v == $value) {
                        //这里unset掉了对应的键和值，此时下标并没有变。
                        unset($score_id[$k]);
                    }
                }
            }
        }
        */
        $info = Db::name('wx_user')->where('portal_id',$username)->field('open_id,portal_pwd')->find();
        $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
        $params[CURLOPT_COOKIEJAR] = RUNTIME_PATH .'/cookie/cookie_'.$username.'.txt';
        $params[CURLOPT_COOKIEFILE] = $params[CURLOPT_COOKIEJAR];
        $params[CURLOPT_FOLLOWLOCATION] = 1;
        //首先带着cookie去尝试获取数据，判断cookie是否过期
        //$data = $this->get_stu_score($username, $params, $score_id, $database_ids);
        $data = $this->get_data($username, $params, self::SCORE_ITEM_ID);
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
                $res = $this->get_data($username, $params, $database_ids);
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
                $res = $this->get_data($username, $params, $database_ids);
                return $res;
            }
        }
    }
    /*
    public function get_stu_score($username, $params, $score_id, $database_ids){
        $data = [];
        if(empty($database_ids)){
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
        }else{
            foreach ($database_ids as $key => $value) {
                $msg = $this -> where('item_id', $value)->select();
                $res = [];
                foreach ($msg as $k => $v) {
                    $v = $v->toArray(); 
                    $res[$k]['term'] = $v['XNXQ']; 
                    $res[$k]['course_name'] = $v['KCMC']; 
                    $res[$k]['score'] = $v['ZZ'];
                    $res[$k]['xh'] = $username;
                }
                $data[$key] = $res;
            }
            foreach ($score_id as $key => $value) {
                $res = $this -> get_data($username, $params, $value);
                if($res == false){
                    return false;
                }else{
                    $data[$key] = $res;
                }  
                sleep(1);
           }
        }
        return $data;
    }
    */
     //这个方法用来获取数据并返回
    public function get_data($username, $params, $id){
        $data = array();
        $url = self::SCORE_URL.(string)$id;
        $response = Http::get($url,'',$params);
        preg_match_all('/<th.*?>(.*?)<\/th?>/i', $response, $matches_header);
        preg_match_all('/<td.*?>(.*?)<\/td?>/si', $response, $matches);
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
                $insert_data =[];
                $res = [];
                foreach ($value as $k => $v) {
                    $insert_data['item_id'] = $id;
                    $insert_data['username'] = $username;
                    $insert_data[$v['key']] = $v['val'];
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
                //把本学期之前的所有成绩已经出来的学期的数据存入数据库
                // if($id != self::SCORE_ITEM_ID){
                //     $count = $this->store_score($insert_data);
                // }
                $data[] = $res;
            }          
        }
        return $data;
    }
    /*
    //将爬取的数据存入数据库
    public function store_score($data){
        //在插入数据库时进行判断是否已经插入过了
        $data_database = $this->where('XH',$data['username'])->where('item_id',$data['item_id'])->where('KCDM',$data['课程名称'])->find();
        if($data_database){
            return 0;
        }else{
            $res = $this->insert([
                'item_id' => $data['item_id'],
                'XH' => $data['username'],
                'XNXQ' => $data['学年学期'],
                'KCDM' => $data['课程代码'],
                'KCXH' => $data['课程序号'],
                'KCMC' => $data['课程名称'],
                'KCLB' => $data['课程类别'],
                'XF' => $data['学分'],
                'QMCJ' => $data['期末成绩'],
                'PSCJ' => $data['平时成绩'],
                'ZPCJ' => $data['总评成绩'],
                'ZZ' => $data['最终'],
                'JD' => $data['绩点'],
            ]);
            return $res;
        }
    }
    */


}