<?php

namespace app\admin\model\form;

use think\Model;
use think\Db;

class Form extends Model
{

    // 表名
    protected $name = 'form_result';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    /**
     * 动态获取问卷列数
     * @param int formId
     */
    public function getColumn($formId)
    {
        $titleList = Db::name("form_questionnaire")
                    ->  where("form_id",$formId)
                    ->  field("title")
                    ->  select();
        if (!empty($titleList)) {
            return ["status" => true, "msg" => "success", "data" => ["list" => $titleList]];
        } else {
            return ["status" => false, "msg" => "error", "data" => ["list" => ""]];
        }
    }
    /**
     * 表关联获取学生的基本信息
     */
    
    //表关联获取院系名称
    public function getstuname(){
        return $this->belongsTo('app\admin\model\Studetail', 'user_id')->setEagerlyType(0);
    }
}
