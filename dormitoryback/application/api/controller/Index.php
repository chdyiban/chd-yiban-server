<?php

namespace app\api\controller;

use app\common\controller\Api;
use wechat\wxBizDataCrypt;
use think\Db;
use think\config;
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
        $info = [
            "studentID" => "2018900002",
            "password"  =>  "110029",
        ];
        // $info = [
        //     "building" => "8",
        //     "room"    => "2437",
        //     "bed"  =>  "2",
        // ];
        // $info = [
        //     "type" => "cancel",
        // ];
        // dump(base64_encode(urlencode(json_encode($info))));
        $this->success("请求成功");
        // $url = "http://zqw.lerwin.com/api/study/add";
        // set_time_limit(0);
        // $XH = 0;

        // for ($i=0; $i < 124; $i++) { 
        //     $userInfo = $this->getUser($XH);
        //     if (empty($userInfo)) {
        //         break;
        //     } 
        //     $XH = $userInfo["XH"];
        //     $name = $userInfo["XM"];
        //     $postData = [
        //         'studyUserName' => $userInfo["XM"],
        //         'dept' => $userInfo["BJDM"],
        //         'orgId'=>'48b73742b5e34df7b59d83c80a674c2e',
        //         // 'studyId'=>'87ba33d75ecb44259ac484c72129d61d',
        //         //第12期
        //         'studyId'=>'d765c3f5a9d34e68b2db8cb7a2b1f315',
        //         // 'WXOPENID'=>'oIKzvjszo21BXeuI_xMV4IjAmoOw',            
        //         'WXOPENID'=> $this->generate_password(),
        //     ];
        //     $jsonStr = json_encode($postData);
        //     list($returnCode, $returnContent) = $this->http_post_json($url, $jsonStr);
        //     dump($returnCode);
        //     dump($returnContent);
        //     dump($XH);
        //     dump($i);
        // }

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
    public function getUser($id)
    {
        $userInfo = Db::name("stu_detail") 
                    -> where("YXDM","2400")
                    // -> where('XH',['like','2015%'],['like','2016%'],['like','2017%'],['like','2018%'],'or')
                    -> where('XH',"LIKE","2016%")
                    -> where("BJDM","<>","")
                    -> where("XSLBDM",3)
                    -> where("XH",">",$id)
                    -> find();
        return $userInfo;
    }

    public function test()
    {
        $yxdmList = Db::name("dict_college")->select();
        $returnData = [];
        foreach ($yxdmList as $key => $value) {
            $boyStu = Db::name("fresh_info")
                    -> where("YXDM",$value["YXDM"])
                    -> where("XBDM",1)
                    -> count();
            $girlStu = Db::name("fresh_info")
                    -> where("YXDM",$value["YXDM"])
                    -> where("XBDM",2)
                    -> count();
            //男生某个楼的床位数
            $temp = [
                "YXDM" => "",
                "boy"  => $boyStu,
                "girl" => $girlStu,
            ];
            $LHnumber = Db::name("fresh_dormitory_north")
                            -> where("YXDM",$value["YXDM"])
                            -> where("XB",1)
                            -> group("LH")
                            -> select();
            $temp["YXDM"] = $value["YXDM"];
            $nansheng = 0;
            foreach ($LHnumber as $k => $v) {
                //某个楼男生宿舍数量
                $LHArray = Db::name("fresh_dormitory_north")
                        -> where("YXDM",$v["YXDM"])
                        -> where("XB",1)
                        -> where("LH",$v["LH"])
                        -> count();
                $LHnum  = Db::name("fresh_dormitory_north")
                        -> where("YXDM",$v["YXDM"])
                        -> where("XB",1)
                        -> where("LH",$v["LH"])
                        -> sum("SYRS");
                $nansheng = $nansheng + $LHnum;
                $array = [
                    "LH"  =>  $v["LH"],
                    "boyRoom" => $LHArray,
                    "boyBed"  => $LHnum,
                ];
                $temp[] = $array;
            }
            $nvsheng = 0;
            //女生某个楼的床位数
            $LHnumber = Db::name("fresh_dormitory_north")
                        -> where("YXDM",$value["YXDM"])
                        -> where("XB",2)
                        -> group("LH")
                        -> select();
            foreach ($LHnumber as $k => $v) {
            //某个楼男生宿舍数量
                $LHArray = Db::name("fresh_dormitory_north")
                        -> where("YXDM",$v["YXDM"])
                        -> where("XB",2)
                        -> where("LH",$v["LH"])
                        -> count();
                $LHnum  = Db::name("fresh_dormitory_north")
                        -> where("YXDM",$v["YXDM"])
                        -> where("XB",2)
                        -> where("LH",$v["LH"])
                        -> sum("SYRS");
                $array = [
                    "LH"  =>  $v["LH"],
                    "girlRoom" => $LHArray,
                    "girlBed"  => $LHnum,
                ];
                $nvsheng = $nvsheng + $LHnum;

                $temp[] = $array;
            }
            $temp["nvhsnegBed"] = $nvsheng;
            $temp["nanshengbed"] = $nansheng;
            $returnData[] = $temp;
            // break;
            
        }
        dump($returnData);
    }

}
