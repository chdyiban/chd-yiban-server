<?php

namespace app\api\controller\answer;

use app\common\controller\Api;
use think\Db;
use think\Config;


/**
 * 微信语音自助问答
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 根据给出的部门获取部门下属科室
     * @param depart 部门名称/学工部
     * @way get
     */
    public function getDepartment()
    {
        $param = $this->request->param();
        if (empty($param["depart"])) {
            $array = [
                "err_code"  =>  -1,
                "err_msg"       =>  "param error",
                "data_list" =>  [],
            ];
            return json($array);
        }
        $parentId = Db::name("school_phone")
                    ->  where("name","LIKE","%".$param['depart']."%")
                    ->  value('id');
        if (empty($parentId)) {
            $array = [
                "err_code"  =>  -1,
                "err_msg"       =>  "未查到部门信息",
                "data_list" =>  [],
            ];
            return json($array);
        }

        $result = Db::name("school_phone")
                ->  where("parent_id",$parentId)
                ->  order("weigh")
                ->  select();
        foreach ($result as $key => &$value) {
            unset($value["parent_id"]);
            unset($value["ID"]);
            unset($value["weigh"]);
        }
        $array = [
            "err_code"  =>  0,
            "err_msg"       =>  "success",
            "data_list" =>  $result,
        ];
        return json($array);
    }
    
    /**
     * 根据给出的部门以及科室获取电话
     * @param depart 部门名称/学工部
     * @param office 科室名称/工科试验班
     * @way get
     */
    public function getTel()
    {
        $param = $this->request->param();
        if (empty($param["depart"]) || empty($param["office"]) ) {
            $array = [
                "err_code"  =>  -1,
                "err_msg"       =>  "param error",
                "data_list" =>  [],
            ];
            return json($array);
        }
        $parentId = Db::name("school_phone")
                    ->  where("name","LIKE","%".$param['depart']."%")
                    ->  value('id');
        if (empty($parentId)) {
            $array = [
                "err_code"  =>  -1,
                "err_msg"       =>  "部门电话未找到",
                "data_list" =>  [],
            ];
            return json($array);
        }

        $result = Db::name("school_phone")
                ->  where("parent_id",$parentId)
                ->  where("name",$param["office"])
                ->  value("phone");

        if (empty($result)) {
            $array = [
                "err_code"  =>  -2,
                "err_msg"       =>  "科室电话未找到",
                "data_list" =>  [],
            ];
            return json($array);
        }
        $array = [
            "err_code"  =>  0,
            "err_msg"       =>  "success",
            "data_list" =>  [["phone" => $result],],
        ];
        return json($array);
    }


}