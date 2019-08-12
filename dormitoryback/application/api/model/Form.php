<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;

class Form extends Model
{
    // 表名
    protected $name = 'form';
    // const Q_ID = 3;

    public function initForm($key){
        $stu_id = "2017902148";
        // $open_id = $key['openid'];
        // $safe = Db::name('wx_user') -> where('open_id',$open_id) -> field('portal_id') -> find();
        // if (empty($safe)) {
        //     return ['status' => 'false', 'msg' => "请求非法"];
        // }
        // $stu_id = $safe["portal_id"];
        $userInfo = Db::name("stu_detail")->where("XH",$stu_id)->field("YXDM,BJDM")->find();
        $NJDM = substr($stu_id,0,4);
        $YXDM = $userInfo["YXDM"];
        $BJDM = $userInfo["BJDM"];
        // dump($NJDM);
        if (empty($userInfo)) {
            return ['status' => 'false', 'msg' => "信息缺失","data" => []];
        }
        //查询已完成列表
        $formFinishedList = Db::view("form")
                    -> view("form_config","form_id,start_time,end_time,YXDM,NJDM,BJDM,status","form.ID = form_config.form_id")
                    -> view("form_result","user_id,timestamp","form.ID = form_result.form_id and form_result.user_id = $stu_id")
                    -> where("YXDM = $YXDM or YXDM = 0")
                    -> where("NJDM = $NJDM or NJDM = 0")
                    -> where("BJDM = $BJDM or BJDM = 0")
					-> where("status","1")
                    -> select();
        //$result[0]是待填写
        //$result[1]是已完成
        //$result[2]是已过期
        $result = [
            "0" => [],
            "1" => [],
            "2" => [],
        ];

        //返回的ID均为configid作为唯一来源
        if (!empty($formFinishedList)) {

            foreach ($formFinishedList as $key => $value) {
                $temp = [
                    "id"    =>  $value["ID"],
                    "title" => $value["title"],
                    "desc"  => $value["desc"],
                    "finished_time" => date("Y-m-d H:i",$value["timestamp"]),
                ];
                $result[1][] = $temp;
            }
        }
        $now_time = time();
        //查询未完成且过期列表
        $formTimeoutList = Db::view("form")
                    -> view("form_config","form_id,start_time,ID,end_time,YXDM,NJDM,BJDM,status","form.ID = form_config.form_id")
                    -> where("YXDM = $YXDM or YXDM = 0")
                    -> where("NJDM = $NJDM or NJDM = 0")
                    -> where("BJDM = $BJDM or BJDM = 0")
                    -> where('form_id','NOT IN',function($query) use($stu_id){
                        $query->table('fa_form_result')->where('user_id',$stu_id)->field('form_id');
                    })
                    // -> where("start_time","<=",$now_time)
                    -> where("end_time","<=",$now_time)
                    -> where("status","1")
                    -> select();
        if (!empty($formTimeoutList)) {
            foreach ($formTimeoutList as $key => $value) {
                $temp = [
                    "id"      =>  $value["ID"],
                    "title"   =>  $value["title"],
                    "desc"    =>  $value["desc"],
                    "end_time"=>  date("Y-m-d H:i",$value["end_time"]),
                ];
                $result[2][] = $temp;
            }
        }
        
        $now_time = time();
        //查询未完成且未过期列表
        $formAllList = Db::view("form")
                        -> view("form_config","form_id,start_time,ID,end_time,YXDM,NJDM,BJDM,status","form.ID = form_config.form_id")
                        -> where("YXDM = $YXDM or YXDM = 0")
                        -> where("NJDM = $NJDM or NJDM = 0")
                        -> where("BJDM = $BJDM or BJDM = 0")
                        -> where('form_id','NOT IN',function($query) use($stu_id){
                            $query->table('fa_form_result')->where('user_id',$stu_id)->field('form_id');
                        })
                        -> where("start_time","<=",$now_time)
                        -> where("end_time",">=",$now_time)
                        -> where("status","1")
                        -> select();
        if (!empty($formAllList)) {
            foreach ($formAllList as $key => $value) {
                $temp = [
                    "id"            => $value["ID"],
                    "title"         => $value["title"],
                    "desc"          => $value["desc"],
                    "start_time"    => date("Y-m-d H:i",$value["start_time"]),
                    "end_time"      => date("Y-m-d H:i",$value["end_time"]),
                ];
                $result[0][] = $temp;
            
            }
        }
		return ["status" => true,"data" => $result,"msg" => "查询成功"];
    }


    /**
     * 获取表单详细内容
     */

    public function detail($param)
    {
        $open_id = $param['openid'];
        $safe = Db::name('wx_user') -> where('open_id',$open_id) -> field('portal_id') -> find();
        if (empty($safe)) {
            return ['status' => 'false', 'msg' => "请求非法","data" => []];
        }
        // $stu_id = $safe["portal_id"];
        $config_id = $param["id"];
        $form_id = Db::name("form_config")->where("ID",$config_id)->field("form_id")->find()["form_id"];
        $questionList = Db::name("form_questionnaire")->where("form_id",$form_id)->select();
        $questionArray = [];
        foreach ($questionList as $k => $v) {
            $temp_back = [
                "title"		=> $v["title"],
                "type"		=> $v["type"],
                "extra"		=> $v["extra"],
                "options"	=> json_decode($v["options"],true),
                "status"	=> $v["status"] == 1 ? true : false,
                "must"		=> $v["must"]   == 1 ? true : false,
                "placeholder"=> $v["placeholder"],
                "validate"	=> $v["validate"],
            ];
            $questionArray[] = $temp_back;
        }
        return ["status" => true, "data" => $questionArray,"msg" => "查询成功"];
    }

    public function submit($param)
    {
        $open_id = $param['openid'];
        $safe = Db::name('wx_user') -> where('open_id',$open_id)->field("portal_id")-> find();
        if (empty($safe)) {
            return ['status' => false, 'msg' => "请求非法","data" => []];
        }
        $stu_id = $safe["portal_id"];
        $form_id = Db::name("form_config")->where("ID",$param["id"])->field("form_id")->find();
        if (empty($form_id)) {
            return ['status' => false, 'msg' => "请求错误","data" => []];
        }
        $form_id = $form_id["form_id"];

        $check = Db::name("form_result") 
                -> where("user_id",$stu_id)
                -> where("form_id",$form_id)
                -> field("ID")
                -> find();
        if (!empty($check)) {
            return ['status' => false, 'msg' => "请勿重复提交","data" => []];            
        }
        $questionList = Db::name("form_questionnaire")
                    ->where("form_id",$form_id)
                    ->field("title")
                    ->select();
        $insertData = [];
        foreach ($param["data"] as $key => $value) {
            $valueArray = "";
            if (gettype($value) == "array") {
                foreach ($value as $k => $v) {
                    $valueArray = $valueArray.$v.",";
                }
                $valueArray = substr($valueArray, 0, -1);
            }
            
            $temp = [
                "config_id"  => $param["id"],
                "form_id"    => $form_id,
                "user_id"    => $stu_id,
                "open_id"    => $param["openid"],
                "title"      => $questionList[$key]["title"],
                "value"      => gettype($value) == "array" ? $valueArray : $value,      
            ];
            $insertData[]    = $temp;
        }
        $return = Db::name("form_result")->insertAll($insertData);
        if ($return) {
            return ["status" => true,"msg" => "提交成功","data" => []];
        } else {
            return ["status" => false,"msg" => "提交失败","data" => []];
        }

    }
}