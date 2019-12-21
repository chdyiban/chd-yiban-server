<?php

namespace app\api\model;
use think\Db;
use think\Model;

class Message extends Model
{
    // 表名
    protected $name = 'fa_vote';

    /**
     * 获取常用电话
     */
    public function getPhone()
    {
        $list = Db::name("school_phone")->select();
        $data = [];
        $help = [];
        foreach ($list as $key => $value) {
            if ($value["parent_id"] == 0) {
                $temp = [
                    "id"    =>  $value["ID"],
                    "name"  =>  $value["name"],
                    "department"    =>  [],
                ];
                $data[] = $temp;
            } else {
                $temp = [
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
