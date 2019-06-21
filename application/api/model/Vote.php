<?php

namespace app\api\model;
use think\Db;
use think\Model;

class Vote extends Model
{
    // 表名
    protected $name = 'fa_vote';

    /**
     * 获取投票初始化数据
     */
    public function getInitData($key,$id)
    {
        $return = [];
        $infoList = Db::name('wx_user') -> where('open_id',$key['openid'])->find();
        // $infoList = ["portal_id" => "2017902148"];
        $voteList = Db::name("vote") -> where("id",$id)->find();
        if (empty($voteList)) {
            return $return;
        }
        $return = [
            "id"          => $voteList["id"],
            "title"       => $voteList["title"],
            "desc"        => $voteList["desc"],
            "TPDX"        => $voteList["TPDX"],
            "TPGZ"        => $voteList["TPGZ"],
            "start_time"  => date("Y-m-d H:i",$voteList["start_time"]),
            "end_time"    => date("m-d H:i",$voteList["end_time"]),
            "vote"        => [],
            "is_vote"     => "",
        ];

        $optionList = Db::name("vote_options") -> where("vote_id",$id)->select();
        $temp = $optionList[0]["option_meta"];
        foreach ($optionList as $key => &$value) {
            $value["option_meta"] = json_decode($value["option_meta"],true);
        }
        $return["vote"] = $optionList;

        $voteCheck = Db::name("vote_logs") -> where("vote_user",$infoList["portal_id"])->find();
        $return["is_vote"] = empty($voteCheck) ? false:true;
        return $return;
    }

    public function submit($key)
    {
        //对于投票时间的判断
        $key["vote_id"] = 1;
        if (empty($key["vote_id"])) {
            return ["status" => false,"msg" =>"param error!","data"=>null];
        }
        $voteInfo = Db::name("vote") -> where("id",$key["vote_id"]) -> find();
        $nowTime = time();
        if ($nowTime >= $voteInfo["end_time"]) {
            return ["status" => false,"msg" =>"投票时间已经截止了哦!","data"=>null];
        }
        if ($nowTime <= $voteInfo["start_time"]) {
            return ["status" => false,"msg" =>"投票活动还未开始哦!","data"=>null];
        }
        //对于用户投票次数的判断
        $userCheck = Db::name("vote_logs") -> where("vote_user",$key["id"]) -> find();
        if (!empty($userCheck)) {
            return ["status" => false,"msg" =>"你的投票次数已经用完了哦!","data"=>null];
        }

        $insertData = [];
        $insertData["vote_id"] = empty($key["vote_id"]) ? "" : $key["vote_id"];
        $insertData["vote_user"] = empty($key["id"]) ? "" : $key["id"];
        $insertData["vote_openid"] = empty($key["openid"]) ? "" : $key["openid"];
        $insertData["data"] = empty($key["options"]) ? "" : $key["options"];
        $insertData["timestamp"] = empty($key["timestamp"]) ? time() : $key["timestamp"];

        if (count($insertData["data"]) == 0) {
            return ["status" => false,"msg" =>"投票数不可以为空哦!","data"=>null];
        }

        if (count($insertData["data"]) > 3) {
            return ["status" => false,"msg" =>"最多只能投三票哦!","data"=>null];
        }

        Db::startTrans();
        $insert_flag = false;
        $update_flag = false;
        try{
            foreach ($insertData["data"] as $k => $value) {
                $update_flag = Db::name("vote_options") -> where("vote_id",$key["vote_id"]) -> where("option_id",$value) -> setInc("option_votes");
            }
            $insertData["data"] = json_encode($insertData["data"]);
            $insert_flag = Db::name("vote_logs") -> insert($insertData);
            // 提交事务
            Db::commit();  
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }

        if($insert_flag == 1 && $update_flag == 1){
            return ['status' => true, 'msg' => "投票成功", 'data' => null];
        }else{
            return ['status' => false, 'msg' => "投票失败，请稍后重试", 'data' => null];
        }

    }

}
