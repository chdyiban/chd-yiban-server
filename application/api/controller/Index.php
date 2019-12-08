<?php

namespace app\api\controller;

use app\common\controller\Api;
use wechat\wxBizDataCrypt;
use think\Db;
use think\config;
use fast\Http;
/**
 * 首页接口
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {

        set_time_limit(0);
        // $form_id = 2;
        // $nj = "2016";
        // // $this->success('请求成功');
        // $finishedStuCount = Db::view("form_result")
        //                 // -> where("value","学生干部")
        //                 -> group("user_id")
        //                 -> where("form_id",$form_id)
        //                 -> view("stu_detail","YXDM,XH","form_result.user_id = stu_detail.XH")
        //                 // -> where("user_id","LIKE","$nj%")
        //                 // -> where("XBDM",2)
        //                 -> count();
        // $questionnaire = Db::name("form_questionnaire")->where("form_id",$form_id) -> select();
        // $result = [
        //     "ques1" =>  [],
        //     "ques2" =>  [],
        // ];
        // foreach ($questionnaire as $key => $value) {
        //     if ($value["type"] == "radio") {
        //         $temp = [
        //             "title" =>  $value["title"],
        //         ];
        //         $options = json_decode($value["options"],true);
        //         $timeStart = microtime(true);
        //         foreach ($options as $k => $v) {
        //             $stuCount = Db::name("form_result")
        //                             // -> where("user_id","IN",function($query){
        //                             //     $query->table('fa_form_result')->where('value',"学生干部")->group("user_id")->field('user_id');
        //                             // })
        //                             // ->field("ID")
        //                             -> group("user_id")
        //                             // -> view("stu_detail","YXDM,XH","form_result.user_id = stu_detail.XH")
        //                             // -> where("user_id","LIKE","$nj%")
        //                             // -> where("XBDM",2)
        //                             // -> where("user_id","LIKE","$nj%")
        //                             -> where("form_id",$form_id)
        //                             -> where("title",$value["title"])
        //                             -> where("value",$v)
        //                             -> count(); 
        //             $rate = round($stuCount/$finishedStuCount,4);
        //             $temp["data"][]   = $rate;
        //         }
        //         $result["ques1"][] = $temp;
        //         $timeEnd = microtime(true);
        //         // continue;
        //         // break;
        //     } else if ($value["type"] == "checkbox") {
        //         $temp = [
        //             "title" =>  $value["title"],
        //         ];
        //         $options = json_decode($value["options"],true);
        //         foreach ($options as $k => $v) {
        //             $stuCount = Db::name("form_result")
        //                             // -> where("user_id","IN",function($query){
        //                             //     $query->table('fa_form_result')->where('value',"学生干部")->group("user_id")->field('user_id');
        //                             // })
        //                             -> group("user_id")
        //                             -> where("form_id",$form_id)
        //                             // -> where("user_id","LIKE","$nj%")
        //                             // -> view("stu_detail","YXDM,XH","form_result.user_id = stu_detail.XH")
        //                             // -> where("user_id","LIKE","$nj%")
        //                             // -> where("XBDM",2)
        //                             -> where("title",$value["title"])
        //                             -> where("value","LIKE","%$v%")
        //                             -> count(); 
        //             $rate = round($stuCount/$finishedStuCount,4);
        //             $temp["data"][]   = $rate;
        //         }
        //         $result["ques1"][] = $temp;
        //     }
        // }
        // return json($result);
        // $info = [
        //     "building" => "8",
        //     "room"    => "2437",
        //     "bed"  =>  "2",
        // ];
        // $info = [
        //     "type" => "cancel",
        // ];
        // dump(base64_encode(urlencode(json_encode($info))));

        //青年大学习
        $url = "http://zqw.lerwin.com/api/study/add";
        set_time_limit(0);
        $XH = 0;

        for ($i=0; $i < 179; $i++) { 
            $userInfo = $this->getUser($XH);
            if (empty($userInfo)) {
                break;
            } 
            $XH = $userInfo["XH"];
            $name = $userInfo["XM"];
            $postData = [
                'studyUserName' => $userInfo["XM"],
                'dept' => $userInfo["BJDM"],
                'orgId'=>'48b73742b5e34df7b59d83c80a674c2e',
                // 'studyId'=>'87ba33d75ecb44259ac484c72129d61d',
                //第12期
                'studyId'=>'3156767bbbf34210af3946552358b4df',
                // 'WXOPENID'=>'oIKzvjszo21BXeuI_xMV4IjAmoOw',            
                'WXOPENID'=> $this->generate_password(),
            ];
            $jsonStr = json_encode($postData);
            list($returnCode, $returnContent) = $this->http_post_json($url, $jsonStr);
            dump($returnCode);
            if($returnCode != "200") {
                dump($XH);
                break;
            }
            dump($returnContent);
            dump($i);
        }

    }
    function http_post_json($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Referer : http://zqw.lerwin.com/dxx/bd.html?orgType=ad690b164502445891bc487fa5ad6e53&id=ae04a5c38b7144bba3789d908a2ec2b4&name=%E5%90%84%E9%AB%98%E6%A0%A1',
                'User-Agent : Mozilla/5.0 (Linux; Android 9; MI 8 Build/PKQ1.180729.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/66.0.3359.126 MQQBrowser/6.2 TBS/044704 Mobile Safari/537.36 MMWEBID/7227 MicroMessenger/7.0.4.1420(0x2700043B) Process/tools NetType/WIFI',
                'Content-Type: application/json; charset=utf-8',
                'Origin: http://zqw.lerwin.com',
                'Content-Length: ' . strlen($jsonStr),
                "Accept-Encoding: gzip, deflate",
                "Accept-Language: zh-CN,en-US;q=0.9",
                "Cookie: pagemode=Tab",
                "Host: zqw.lerwin.com",
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        return array($httpCode, $response);
    }

    public function getUser($XH)
    {
        $userInfo = Db::name("stu_detail")
                ->where("YXDM","2400")
                -> where("XH","LIKE","2017%")
                -> where("XH",">",$XH)
                -> where("BJDM","<>","")
                -> limit(1)
                -> order("XH")
                -> find();
        return $userInfo;
    }

    function generate_password( $length = 28 ) { 
        // 密码字符集，可任意添加你需要的字符 
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_'; 
        $password = ''; 
        for ( $i = 0; $i < $length; $i++ ) 
        { 
        // 这里提供两种字符获取方式 
        // 第一种是使用 substr 截取$chars中的任意一位字符； 
        // 第二种是取字符数组 $chars 的任意元素 
        // $password .= substr($chars, mt_rand(0, strlen($chars) – 1), 1); 
            // if ($i == 20) {
            //     $password .= "-";
            // } else {
                $password .= $chars[mt_rand(0, strlen($chars) - 1) ]; 
            // }
        } 
        return $password; 
    } 


    public function insert()
    {
        $user = Db::name("fdy_major")->select();
        foreach ($user as $key => $value) {
            $insertInfo = Db::view("teacher_detail")
                        -> view("dict_college","YXDM,YXJC","teacher_detail.YXDM = dict_college.YXDM")
                        -> where("ID",$value["GH"])
                        -> find();

            $res = Db::name("fdy_major")->where("GH",$value["GH"])->update(["image" => $insertInfo["YXDM"], "card" => $insertInfo["YXJC"] ]);
            dump($res);
        }
    }


    public function press()
    {
        // $list = Db::view("bzr_result")
        //     ->view("bzr_adviser","id,XM","bzr_result.adviser_id = bzr_adviser.id")
        //     ->where("bzr_result.q_id",4)
        //     ->select();
        // foreach ($list as $key => &$value) {
        //     unset($value["id"]);
        //     $value["raw_data"] = json_decode($value["raw_data"],true)[19];
        // }
        // // $result = Db::name("bzr_result")->where("adviser_id",849)->update(["adviser_id"=>851]);
        // $result = Db::name("bzr_result_count")->insertAll($list);
        // dump($result);

        $teacher = Db::name("bzr_adviser")->where("q_id",4)->select();
        foreach ($teacher as $key => $value) {
            $temp = [];
            $total = Db::name("bzr_result_count")->where("adviser_id",$value["id"])->count();
            $count1 = Db::name("bzr_result_count")->where("adviser_id",$value["id"])->where("raw_data",0)->count();
            $count2 = Db::name("bzr_result_count")->where("adviser_id",$value["id"])->where("raw_data",1)->count();
            $count3 = Db::name("bzr_result_count")->where("adviser_id",$value["id"])->where("raw_data",2)->count();
            $count4 = Db::name("bzr_result_count")->where("adviser_id",$value["id"])->where("raw_data",3)->count();
            $count5 = Db::name("bzr_result_count")->where("adviser_id",$value["id"])->where("raw_data",4)->count();
            $temp = [
                "XM" => $value["XM"],
                "count1"    =>  $count1,
                "count2"    =>  $count2,
                "count3"    =>  $count3,
                "count4"    =>  $count4,
                "count5"    =>  $count5,
                "total"     =>  $total,
                "class_id"  => $value["class_id"],
            ];
            $result[] = $temp;
            
            // dump($temp);
            Db::name("bzr_result_count_back")->insert($temp);
        }
    }

    public function testPost()
    {
        
        // $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $account["key"] . "&secret=" . $account["secret"] . "&js_code=" . $code . "&grant_type=authorization_code";
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=wx1efb3683c3574f82&secret=cb1e0cee41affcd2e3626aa04e116e9e&js_code=081N8iB12SEG2W06xHy129MhB12N8iBb&grant_type=authorization_code";
		$result = Http::get($url);
		var_dump($result);
		$result = json_decode($result, true);
    }


}
