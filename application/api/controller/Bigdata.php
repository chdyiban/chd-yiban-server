<?php

namespace app\api\controller;

use app\common\controller\Api;
use wechat\wxBizDataCrypt;
use think\Db;
use fast\Http;
use think\Cache;
/**
 * 更新接口
 */
class Bigdata extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    //获取token的url
    const GET_TOKEN_URL = "http://bigdata.chd.edu.cn:3003/open_api/authentication/get_access_token";
    //获取成绩的url
    const GET_SCORE_URL = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxxsbkscj/list";
    //获取体测成绩URL
    const GET_TC_SCORE_URL = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxxsbkstzjkbzcsxx/full";
    //获取四六级成绩
    const GET_SL_SCORE_URL = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxxsbksyysljcj/full";
    //获取当前借阅信息
    const GET_NOW_BOOK_URL = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxtstsjy/full";
    //获取历史借阅信息
    const GET_HISTORY_BOOK_URL = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxtsjyls/full";
    //获取图书信息
    const GET_BOOK_INFO_URL = "http://bigdata.chd.edu.cn:3003/open_api/customization/tgxtstsxx_alpha/full";
    
    const APPKEY = "201906132614147905";
    const APPSECRET = "83004580acbae7bfbae62235c983e5842bf9dbf5";
    
    public function getAccessToken()
    {
        $access_token = Cache::get('bigdata_access_token');
        if (empty($access_token)) {
            $token_url = self::GET_TOKEN_URL;
            $post_data = ["key" => self::APPKEY,"secret" => self::APPSECRET];
            $data = Http::get($token_url,$post_data);
            $res_hash = json_decode($data, true);
            if ($res_hash['message'] != 'ok') {
                //错误处理
                return ["status" => false,"msg" => $res_hash["message"]];
            } else {
                $access_token = $res_hash['result']['access_token'];
                Cache::set('bigdata_access_token',$access_token,$res_hash['result']["expires_in"]);
                return ["status" => true,"access_token" => $access_token];
            }
        } else {
            // dump($access_token);
            return ["status" => true,"access_token" => $access_token];            
        }
    }

    /**
     * 获取考试成绩接口
     * @param int XH 
     * @param string access_token  
     * @return array 
     */
    public function getScore($params)
    {
        // $request_url = self::GET_SCORE_URL."?access_token=".$params["access_token"];
        $request_url = self::GET_SCORE_URL;

        $data = Http::get($request_url,$params);
        $data = json_decode($data,true);
        $result = [];
        if ($data["code"] == "10000") {

            for ($i = 1; $i <= $data["result"]["max_page"]; $i++) { 
                $returnData = [];
                $params["page"] = $i;
                sleep(0.1);
                $returnData = Http::get($request_url,$params);
                $returnData = json_decode($returnData,true);   
                foreach ($returnData["result"]["data"] as $key => $value) {
                    $mykey = $value["XN"]." ".$value["XQ"];
                    $result[$mykey][] = $value;
                }
            }
            $arrayKeys = array_keys($result);
            $list = [];
            foreach ($arrayKeys as $key => $value) {
                $temp = [
                    "XNXQ" => $value,
                    "XN"   => explode(" ",$value)[0],
                    "XQ"   => explode(" ",$value)[1],
                    "list" => $result[$value],
                ];
                $list[] = $temp;
            }
        } else {
            return [];
        }
        // dump($list);
        return $list;
    }

    /**
     * 获取体测成绩接口
     * @param int XH
     * @param string access_token
     * @return array
     */

    public function getTcScore($params)
    {
        // $request_url = self::GET_TC_SCORE_URL."?access_token=".$params["access_token"];
        $request_url = self::GET_TC_SCORE_URL;

        $data = Http::get($request_url,$params);
        $data = json_decode($data,true);
        $result = [];

        if ($data["message"] != "ok" ) {
            return ["status" => false,"msg" => $data["name"]];
        }

        for ($i = 1; $i <= $data["result"]["max_page"]; $i++) { 
            $returnData = [];
            $params["page"] = $i;
            sleep(0.1);
            $returnData = Http::get($request_url,$params);
            $returnData = json_decode($returnData,true);   
            foreach ($returnData["result"]["data"] as $key => $value) {
                $temp = [
                    "CSNF"  => empty($value["CSNF"]) ? date("Y")."-".date("Y", strtotime("+1 year")) : $value["CSNF"],
                    "XM"    =>  $value["XM"],
                    "XYMC"  =>  $value["XYMC"],
                    "ZF"    =>  $value["ZF"],
                    "ZFDJMS"=>  $value["ZFDJMS"],
                ];
                $result[] = $temp;
            }
        }
        return ["status" => true,"data" => $result];
    }
    /**
     * 获取四六级成绩接口
     * @param int XH
     * @param string access_token
     * @return array
     */

    public function getSlScore($params)
    {
        // $request_url = self::GET_SL_SCORE_URL."?access_token=".$params["access_token"];
        $request_url = self::GET_SL_SCORE_URL;

        $data = Http::get($request_url,$params);
        $data = json_decode($data,true);
        $result = [];
        if ($data["message"] != "ok") {
            return [];
        }
        for ($i = 1; $i <= $data["result"]["max_page"]; $i++) { 
            $returnData = [];
            $params["page"] = $i;
            sleep(0.1);
            $returnData = Http::get($request_url,$params);
            $returnData = json_decode($returnData,true);   
            foreach ($returnData["result"]["data"] as $key => $value) {
                $result[] = $value;
            }
        }
        return $result;
    }
    /**
     * 获取图书馆当前借阅信息
     *  @param int ZJH
     *  @param string access_token
     *  @return array
     */
    public function getNowBook($params)
    {
        // $request_url = self::GET_SL_SCORE_URL."?access_token=".$params["access_token"];
        $request_url = self::GET_NOW_BOOK_URL;
        $params["page"] = empty($params["page"]) ? 0 : $params["page"];

        $data = Http::get($request_url,$params);
        $data = json_decode($data,true);
        $result = [];
        if ($data["message"] != "ok") {
            return ["status" => false, "msg" => $data["message"]];
        }
        $result["extra"] = ["page" => $params["page"],"total" => $data["result"]["total"],"max_page"=>$data["result"]["max_page"]];
        foreach ($data["result"]["data"] as $key => $value) {
            sleep(0.1);
            $bookUrl = self::GET_BOOK_INFO_URL;
            $bookInfo = ["TSTM" => $value["TSTM"],"access_token" => $params["access_token"]];
            $returnData = Http::get($bookUrl,$bookInfo);
            $returnData = json_decode($returnData,true);   
            $result["data"][] = ["book" => $returnData["result"]["data"][0]["TM"],"jsrq" => $value["JCRQ"],"yhrq" => $value["YHRQ"] ];
        }
        return ["status" => true,"data" => $result,"msg" => "success"];
    }

    /**
     * 获取历史借阅信息
     *  @param int ZJH
     *  @param string access_token
     *  @param int page
     *  @return array
     */
    public function getHistoryBook($params)
    {
        // $request_url = self::GET_SL_SCORE_URL."?access_token=".$params["access_token"];
        $request_url = self::GET_HISTORY_BOOK_URL;

        $params["page"] = empty($params["page"]) ? 0 : $params["page"];

        $data = Http::get($request_url,$params);
        $data = json_decode($data,true);
        if ($data["message"] != "ok") {
            return ["status" => false, "msg" => $data["message"]];
        }

        $result["extra"] = ["page" => $params["page"],"total" => $data["result"]["total"],"max_page"=>$data["result"]["max_page"]];
            
        foreach ($data["result"]["data"] as $key => $value) {
            $bookUrl = self::GET_BOOK_INFO_URL;
            $bookInfo = ["TSTM" => $value["TSTM"],"access_token" => $params["access_token"]];
            $returnData = Http::get($bookUrl,$bookInfo);
            $returnData = json_decode($returnData,true);   
            $result["data"][] = ["book" => $returnData["result"]["data"][0]["TM"],"jsrq" => $value["JCRQ"],"yhrq" => $value["YHRQ"] ];
        }
        return ["status" => true,"data" => $result,"msg" => "success"];
    }


}
