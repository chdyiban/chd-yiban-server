<?php

namespace app\api\model;
use think\Db;
use think\Model;
use pinyin\Pinyin;

class Message extends Model
{
    // 表名
    protected $name = 'fa_vote';

    /**
     * 获取常用电话
     */
    public function getPhone($param)
    {
        $pinyin = new Pinyin();
        $XH = $param["XH"];
        $check = Db::name("teacher_detail") -> where("ID",$XH)->find();
        if (empty($check)) {
            $list = Db::name("school_phone")
                    -> where("power","all")
                    -> whereOr("power","")
                    -> select();
        } else {
            $list = Db::name("school_phone")
                    -> where("power", "all")
                    -> whereOr("power","teacher")
                    -> whereOr("power","")
                    -> select();
        }
        $data = [];
        $help = [];
        foreach ($list as $key => $value) {
            if ($value["parent_id"] == 0) {
                $temp = [
                    "key"   =>  $value["JC"],
                    // "key"   =>  $pinyin->pinyin($value["name"])[0],
                    "id"    =>  $value["ID"],
                    "name"  =>  $value["name"],
                    "department"    =>  [],
                ];
                $data[] = $temp;
            } else {
                $temp = [
                    "key"   =>  $pinyin->pinyin($value["name"])[0],
                    "name"  =>  $value["name"],
                    "phone" =>  $value["phone"],
                    "extra" =>  $value["extra"],
                ];
                $help[$value["parent_id"]][] = $temp;
            }
        }

        foreach ($data as $k => &$v) {
            $v["department"] = $help[$v["id"]];
            $v["id"]    =   $k;
        }
        return ["status" => true,"msg" => "success", "data" => $data];
    }
}
