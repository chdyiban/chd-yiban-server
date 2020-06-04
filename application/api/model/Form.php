<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;
use think\Validate;

class Form extends Model
{
    // 表名
    protected $name = 'form';
    // const Q_ID = 3;

    public function initForm($key){
        // $stu_id = "2017902148";
        $open_id = $key['openid'];
        $safe = Db::name('wx_user') -> where('open_id',$open_id) -> field('portal_id') -> find();
        if (empty($safe)) {
            return ['status' => 'false', 'msg' => "请求非法"];
        }
        $stu_id = $safe["portal_id"];
        if (strlen($stu_id) == 6) {
            $userInfo = Db::name("teacher_detail")->where("ID",$stu_id)->find();
        } else {
            $userInfo = Db::name("stu_detail")->where("XH",$stu_id)->field("YXDM,BJDM")->find();
        }
        if (strlen($stu_id) == 6) {
            $NJDM = "0";
        } else {
            $NJDM = substr($stu_id,0,4);
        }
        $YXDM = empty($userInfo["YXDM"]) ? "0" : $userInfo["YXDM"];
        $BJDM = empty($userInfo["BJDM"]) ? "0" : $userInfo["BJDM"];
        if (empty($userInfo)) {
            return ['status' => 'false', 'msg' => "NEED_PORTAL_LOGIN","data" => []];
        }
        //查询已完成列表
        $formFinishedList = Db::view("form")
                    -> view("form_config","ID,form_id,start_time,end_time,YXDM,NJDM,BJDM,status","form.ID = form_config.form_id")
                    -> view("form_result","user_id,timestamp","form.ID = form_result.form_id and form_result.user_id = $stu_id")
                    -> where("YXDM = $YXDM or YXDM = 0")
                    -> where("NJDM = $NJDM or NJDM = 0")
                    -> where("BJDM = $BJDM or BJDM = 0")
                    -> group("form_result.config_id")
                    -> order("create_time desc")
                    -> where("status","1")
                    -> select();
                    // dump($formFinishedList);
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
                    // ""
                    "title" => $value["title"],
                    "desc"  => $value["desc"],
                    "create_time"   => $value["create_time"],
                    "finished_time" => date("Y-m-d H:i",$value["timestamp"]),
                ];
                $result[1][] = $temp;
            }
        }
        $now_time = time();
        //查询未完成且过期列表
        $formTimeoutList = Db::view("form")
                    -> view("form_config","ID,form_id,start_time,ID,end_time,YXDM,NJDM,BJDM,status","form.ID = form_config.form_id")
                    -> where("YXDM = $YXDM or YXDM = 0")
                    -> where("NJDM = $NJDM or NJDM = 0")
                    -> where("BJDM = $BJDM or BJDM = 0")
                    -> where('form_id','NOT IN',function($query) use($stu_id){
                        $query->table('fa_form_result')->where('user_id',$stu_id)->field('form_id');
                    })
                    // -> where("start_time","<=",$now_time)
                    -> order("create_time desc")
                    -> where("end_time","<=",$now_time)
                    -> where("status","1")
                    -> select();
        if (!empty($formTimeoutList)) {
            foreach ($formTimeoutList as $key => $value) {
                $temp = [
                    "id"      =>  $value["ID"],
                    "title"   =>  $value["title"],
                    "desc"    =>  $value["desc"],
                    "create_time"   => $value["create_time"],
                    "end_time"=>  date("Y-m-d H:i",$value["end_time"]),
                ];
                $result[2][] = $temp;
            }
        }
        
        $now_time = time();
        //查询未完成且未过期列表即待填写列表
        $formAllList = Db::view("form")
                        -> view("form_config","form_id,start_time,ID,end_time,YXDM,NJDM,BJDM,status","form.ID = form_config.form_id")
                        -> where("YXDM = $YXDM or YXDM = 0")
                        -> where("NJDM = $NJDM or NJDM = 0")
                        -> where("BJDM = $BJDM or BJDM = 0")
                        -> where('form_id','NOT IN',function($query) use($stu_id){
                            $query->table('fa_form_result')->where('user_id',$stu_id)->field('form_id');
                        })
                        -> where("start_time","<=",$now_time)
                        -> order("create_time desc")
                        -> where("end_time",">=",$now_time)
                        -> where("status","1")
                        -> select();
        if (!empty($formAllList)) {
            foreach ($formAllList as $key => $value) {
                $temp = [
                    "id"            => $value["ID"],
                    "title"         => $value["title"],
                    "desc"          => $value["desc"],
                    "create_time"   => $value["create_time"],
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
        $stu_id = $safe["portal_id"];
        $config_id = $param["id"];
        $form_id = Db::name("form_config")->where("ID",$config_id)->field("form_id")->find()["form_id"];
        $questionList = Db::name("form_questionnaire")->where("form_id",$form_id)->select();

        //为表单补充额外的信息
        $extra_info = ["name" => "辅导员"];
        // if ($param["id"] == 3) {
        //     $BJDM = Db::name('stu_detail') -> where('XH',$stu_id) -> field('BJDM') -> find()['BJDM'];
        //     $adviserInfoList = Db::name("bzr_adviser") -> where('class_id', $BJDM)->where("q_id",2) ->find();
        //     //判断班主任提交问卷
        //     $adviser_name = $adviserInfoList['XM'];
        //     $extra_info = [];
        //     $college =   Db::view('stu_detail')
        //                 ->where('XH', $stu_id)
        //                 ->view('dict_college','YXDM,YXMC,YXJC','stu_detail.YXDM = dict_college.YXDM')
        //                 ->find();
        //     $college_name = !empty($college["YXJC"]) ? $college["YXJC"] : "暂未获取到学院信息，请联系负责人员";
        //     $extra_info = ["name" => $adviser_name,"college" => $college_name];
        // }

        //判断用户是否完成该表单
        $userResult   = Db::name("form_result")
                    ->where("user_id",$stu_id)
                    ->where("config_id",$config_id)
                    ->select();
        
        $userResultArray = [];
        if (!empty($userResult)) {
            foreach ($userResult as $key => $value) {
                $userResultArray[$value["title"]] = $value["value"];
            } 
        }
        $questionArray = [];
        foreach ($questionList as $k => $v) {
            //如果问题的类型为text
            if ($v["type"] == "text" ) {
                $temp_back = [
                    "title"		=> $v["title"],
                    "type"		=> $v["type"] == "selector" ? $v["extra"] : $v["type"],
                    "options"	=> $v["options"],
                    "status"	=> $v["status"] == 1 ? true : false,
                    "must"		=> $v["must"]   == 1 ? true : false,
                    "placeholder"=> $v["placeholder"],
                    "validate"	=> $v["validate"],
                    //当选项为第一个选项时，下标为0，empty会判断为空
                    "value"     => empty($userResultArray[$v["title"]])  ? "" : $userResultArray[$v["title"]],
                    // "value"     => empty($userResultArray[$v["title"]]) ? "" : $userResultArray[$v["title"]],
                ];
                $questionArray[] = $temp_back;
                
            } elseif ($v["type"] == "checkbox") {
                //题目类型为多选
                $options = json_decode($v["options"],true);

                //当用户填写过问卷时
                $returnOptions = [];
                if (!empty($userResultArray[$v["title"]])) {
                 
                    $checkBox = substr_count($userResultArray[$v["title"]],",");
                    if ($checkBox >= 1) {
                        $checkBoxArray = explode(",",$userResultArray[$v["title"]]);
                        foreach ($checkBoxArray as $m => $n) {
                            $temp[] = array_search($n,$options);
                        }
                        $userResultArray[$v["title"]] = $temp;
                    } else {
                        $temp[] = array_search($userResultArray[$v["title"]], $options);
                        $userResultArray[$v["title"]] = $temp;
                    }

                    foreach ($options as $key => $value) {
                        $arrayTemp = [
                            "value"    => $value,
                        ];
                        $returnOptions[] = $arrayTemp;
                    }
                    $useroption =   $userResultArray[$v["title"]];
                    if (gettype($useroption) == "array") {
                        foreach ($useroption as $m => $n) {
                            $returnOptions[$n]["checked"]  = true;
                        }
                    } else {
                        $returnOptions[$useroption]["checked"] = true;
                    }
                } else {
                    $userResultArray[$v["title"]] = "";
                    foreach ($options as $key => $value) {
                        $arrayTemp = [
                            "value"    => $value,
                        ];
                        $returnOptions[] = $arrayTemp;
                    }
                }

                $temp_back = [
                    "title"		    => $v["title"],
                    // "type"		=> $v["type"],
                    "type"		    => $v["type"] == "selector" ? $v["extra"] : $v["type"],
                    // "extra"		=> $v["extra"],
                    // "options"	=> json_decode($v["options"],true),
                    "options"	    => $returnOptions,
                    "status"	    => $v["status"] == 1 ? true : false,
                    "must"		    => $v["must"]   == 1 ? true : false,
                    "placeholder"   => $v["placeholder"],
                    "validate"	    => $v["validate"],
                    //当选项为第一个选项时，下标为0，empty会判断为空
                    "value"     => empty($userResultArray[$v["title"]]) && $userResultArray[$v["title"]] != 0 ? "" : $userResultArray[$v["title"]],
                ];
                $questionArray[] = $temp_back;

            } elseif ($v["type"] == "radio" || $v["type"] == "star" ) {
                //题目类型为单选
                $options = json_decode($v["options"],true);
                // dump($v);                
                //当用户填写过问卷时
                $returnOptions = [];
                // dump($userResultArray);
                if (!empty($userResultArray[$v["title"]])) {
                    $userResultArray[$v["title"]] = array_search($userResultArray[$v["title"]],$options);
                    // dump($userResultArray);
                    // dump($options);
                    foreach ($options as $key => $value) {
                        $arrayTemp = [
                            "value"    => $value,
                        ];
                        $returnOptions[] = $arrayTemp;
                    }
                    $useroption =   $userResultArray[$v["title"]];
                    $returnOptions[$useroption]["checked"] = true;
                } else {
                    //用户还未填写问卷

                    $userResultArray[$v["title"]] = "";
                    foreach ($options as $key => $value) {
                        $arrayTemp = [
                            "value"    => $value,
                        ];
                        $returnOptions[] = $arrayTemp;
                    }
                }

                $temp_back = [
                    "title"		    => $v["title"],
                    // "type"		=> $v["type"],
                    "type"		    => $v["type"] == "selector" ? $v["extra"] : $v["type"],
                    "options"	    => $returnOptions,
                    "status"	    => $v["status"] == 1 ? true : false,
                    "must"		    => $v["must"]   == 1 ? true : false,
                    "placeholder"   => $v["placeholder"],
                    "validate"	    => $v["validate"],
                    //当选项为第一个选项时，下标为0，empty会判断为空
                    "value"     => empty($userResultArray[$v["title"]]) && $userResultArray[$v["title"]] != 0 ? "" : $userResultArray[$v["title"]],
                    // "value"         => "",
                ];
                $questionArray[] = $temp_back;

            } elseif ($v["type"] == "selector") {
                //题目类型为选择

                $options = json_decode($v["options"],true);

                //如果extra == selector并且用户完成表单
                if (!empty($options) && !empty($userResultArray[$v["title"]])) {
                    $userResultArray[$v["title"]] = array_search($userResultArray[$v["title"]],$options);
                
                } else {
                    //完成表单
                    $userResultArray[$v["title"]] = "";
                }

                $temp_back = [
                    "title"		    => $v["title"],
                    // "type"		=> $v["type"],
                    "type"		    => $v["type"] == "selector" ? $v["extra"] : $v["type"],
                    "options"	    => $options,
                    "status"	    => $v["status"] == 1 ? true : false,
                    "must"		    => $v["must"]   == 1 ? true : false,
                    "placeholder"   => $v["placeholder"],
                    "validate"	    => $v["validate"],
                    //当选项为第一个选项时，下标为0，empty会判断为空
                    "value"     => empty($userResultArray[$v["title"]]) && $userResultArray[$v["title"]] != 0 ? "" : $userResultArray[$v["title"]],
                ];
                $questionArray[] = $temp_back;
            } 
            // //将数据库文字选项转换为下标
            // $options = json_decode($v["options"],true);
            // if (!empty($options) && !empty($userResultArray[$v["title"]])) {
            //     $checkBox = substr_count($userResultArray[$v["title"]],",");
            //     if ($checkBox >= 1) {
            //         $checkBoxArray = explode(",",$userResultArray[$v["title"]]);
            //         foreach ($checkBoxArray as $m => $n) {
            //             $temp[] = array_search($n,$options);
            //         }
            //         $userResultArray[$v["title"]] = $temp;
            //     } else {
            //         $userResultArray[$v["title"]] = array_search($userResultArray[$v["title"]],$options);
            //     }

            // }
            // $returnOptions = [];
            // if (!empty($options)) {
            //     foreach ($options as $key => $value) {
            //         $arrayTemp = [
            //             "value"    => $value,
            //         ];
            //         $returnOptions[] = $arrayTemp;
            //     }
            //     $useroption =   $userResultArray[$v["title"]];
            //     // foreach ($returnOptions as $key => &$value) {
            //         if (gettype($useroption) == "array") {
            //             foreach ($useroption as $m => $n) {
            //                 // $value[$n]["checked"] == true;
            //                 $returnOptions[$n]["checked"]  = true;
            //             }
            //         } else {
            //             $returnOptions[$useroption]["checked"] = true;
            //         }
            //     // }
            // }

            // $temp_back = [
            //     "title"		=> $v["title"],
            //     // "type"		=> $v["type"],
            //     "type"		=> $v["type"] == "selector" ? $v["extra"] : $v["type"],
            //     // "extra"		=> $v["extra"],
            //     // "options"	=> json_decode($v["options"],true),
            //     "options"	=> $returnOptions,
            //     "status"	=> $v["status"] == 1 ? true : false,
            //     "must"		=> $v["must"]   == 1 ? true : false,
            //     "placeholder"=> $v["placeholder"],
            //     "validate"	=> $v["validate"],
            //     //当选项为第一个选项时，下标为0，empty会判断为空
            //     "value"     => empty($userResultArray[$v["title"]]) && $userResultArray[$v["title"]] != 0 ? "" : $userResultArray[$v["title"]],
            //     // "value"     => empty($userResultArray[$v["title"]]) ? "" : $userResultArray[$v["title"]],
            // ];
            // if (!empty($options)) {
            //     unset($temp_back["value"]);
            // }
            // $questionArray[] = $temp_back;
        }
        return ["status" => true, "data" =>["form_info" => $questionArray,"extra_info" => $extra_info] ,"msg" => "查询成功"];
    }

    

    /**
     * 提交问卷
     */
    public function submit($param)
    {
        $open_id = $param['openid'];
        $config_id = $param["id"];
        $safe = Db::name('wx_user') -> where('open_id',$open_id)->field("portal_id")-> find();
        if (empty($safe)) {
            return ['status' => false, 'msg' => "请求非法","data" => []];
        }
        $stu_id = $safe["portal_id"];
        $form_id = Db::name("form_config")->where("ID",$param["id"])->field("form_id")->find();
        if (empty($form_id)) {
            return ['status' => false, 'msg' => "问卷不存在","data" => []];
        }
        $form_id = $form_id["form_id"];

        $check = Db::name("form_result") 
                -> where("user_id",$stu_id)
                -> where("form_id",$form_id)
                -> where("config_id",$config_id)
                -> field("ID,title")
                -> select();
        $userResultArray = [];
        if (!empty($check)) {
            foreach ($check as $key => $value) {
                $userResultArray[$value["title"]] = $value["ID"];
            }
        }
        $questionList = Db::name("form_questionnaire")
                    ->where("form_id",$form_id)
                    ->field("title,options,validate")
                    ->select();
        $insertData = [];
        $time = time();
        foreach ($param["data"] as $key => $value) {
            $valueArray = "";
            $result = json_decode($questionList[$key]["options"],true);
            $validateDatabase = $questionList[$key]["validate"];
            $title = $questionList[$key]["title"];
            if (!empty($validateDatabase)) {
                $checkSafe = new Validate([$title  => $validateDatabase]);
                if(!$checkSafe->check([$title  => $value])){
                    return ["status" => false,"msg" => $checkSafe->getError(),"data"=>""];
                };
            }
            // dump($result);
            if (gettype($value) == "array") {
                foreach ($value as $k => $v) {
                    if (!empty($result[$v])) {
                        $valueArray = $valueArray.$result[$v].",";
                    }
                }
                $valueArray = substr($valueArray, 0, -1);
            }
            //将下标转为文字内容存进数据库

            if (gettype($value) != "array" && !empty($result[$value])) {
                $value = $result[$value];
            }
        
            $temp = [
                "config_id"  => $param["id"],
                "form_id"    => $form_id,
                "user_id"    => $stu_id,
                "open_id"    => $param["openid"],
                "title"      => $questionList[$key]["title"],
                "value"      => gettype($value) == "array" ? $valueArray : $value,   
                "timestamp"  => $time,   
            ];
            $insertData[]    = $temp;
        }
        if (!empty($check)) {
            foreach ($insertData as $key => $value) {
                $value["ID"] = $userResultArray[$value["title"]];
                $return = Db::name("form_result")->update($value);
            }
        } else {
            $return = Db::name("form_result")->insertAll($insertData);
        }

        if ($return) {
            return ["status" => true,"msg" => "提交成功","data" => []];
        } else {
            return ["status" => false,"msg" => "提交失败","data" => []];
        }

    }
}