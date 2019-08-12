<?php

namespace app\api\controller;

use app\common\controller\Api;
use wechat\wxBizDataCrypt;
use think\Db;
/**
 * 更新接口
 */
class Update extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    
    public function index()
    {
        //bigdata.chd.edu.cn:3003/open_api/customization/tgxxsbksjbxx/list?access_token=d7a45ea2891dab4d29eb85a7786abf068d34a6ac&XH=2017902148
        $token_url = "http://bigdata.chd.edu.cn:3003/open_api/authentication/get_access_token";
        $token_params = Array("key" => "201906132614147905", "secret" => "83004580acbae7bfbae62235c983e5842bf9dbf5");
        $headers = Array("Content-type: application/json");
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $token_url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);//设置请求头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($token_params));
        $data = curl_exec($ch);//运行curl
        dump($data);
        curl_close($ch);
        $res_hash = json_decode($data, true);
        if ($res_hash['message'] != 'ok') {
        //错误处理
            dump($res_hash["message"]);
        } else {
            $access_token = $res_hash['result']['access_token'];
                $XH = "2017902148";
                $data = $this->getData($access_token,$XH);
                dump($data);
            // for ($i=1; $i <= 28; $i++) { 
            //     $data = $this->getData($access_token,$i);
            //     $sumData = $data["result"]["data"];
            //     foreach ($sumData as $key => $value) {
            //         $isCheck = Db::name("dict_major")->where("ZYDM",$value["ZYDM"])->find();
            //         if (empty($isCheck)) {
            //             $res = Db::name("dict_major")->insert($value);
            //         }
            //     }
            // }
        }
    }

    public function getData($access_token,$XH)
    {
       
            // $access_token = $res_hash['result']['access_token'];
            
            #以学生基础信息列表查询API为例
            $request_params = Array();
            // $request_url = "http://bigdata.chd.edu.cn:3003/open_api/student/infos/list";
            // $request_url = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxxsbksjbxx/list?access_token=$access_token";
            // $request_url = "http://bigdata.chd.edu.cn:3003/open_api/customization/dmxbjwxkml/full?access_token=$access_token";
            // $request_url = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxjxzyxx/list?access_token=$access_token&page=$page";
            // $request_url = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxxsbkscj/list?acess_token=$access_token&page=4";
            $request_url = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxjgjzgjbxx/list?access_token==$access_token&page=4";
            
            
            $headers = Array("Content-type: application/json");
            $request_params['access_token'] = $access_token; 
            $request_params['XH'] = $XH;
            $request_params['per'] = 50;
            $ch = curl_init();//初始化curl
            curl_setopt($ch, CURLOPT_URL, $request_url);//抓取指定网页
            curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);//设置请求头
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
            curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_params));
            $return_data = curl_exec($ch);//运行curl
            // dump(json_decode($return_data));
            curl_close($ch);
            return json_decode($return_data,true);

    }
}
