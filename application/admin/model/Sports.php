<?php

namespace app\admin\model;
use think\Db;
use think\Model;

class Sports extends Model
{
    // 表名
    protected $name = 'sports_score_detail';
    
    //表关联获取宿舍名称
    public function geteventname(){
        return $this->belongsTo('Sportstype', 'event_id')-> setEagerlyType(0);
    }

    //表关联获取院系名称
    public function getcollege(){
        return $this->belongsTo('College', 'YXDM')->setEagerlyType(0);
    }

    /**
     * 获取学院代码对应名称的json
     */
    public function getCollegeJson()
    {
        $list = Db::name('dict_college') -> select();
        $collegeJson = array();
        foreach ($list as $key => $value) {
            if ($value['YXDM'] != 1500 && $value['YXDM'] != 1400 && $value['YXDM'] != 1700 && $value['YXDM'] != 1800 && $value['YXDM'] != 1801 &&  $value['YXDM'] != 5100 && $value['YXDM'] != 9999 && $value['YXDM'] != 7100) {
                $collegeJson[$value['YXJC']] = $value['YXJC'];
            }
        }
        return $collegeJson;
    }
    /**
     * 单个插入排名得分记录
     */

    public function addScoreDetail($params)
    {

        $tempDetail = [
            'YXDM'   =>  $params['row']['college'],
            'event_id' => $params['event'],
            'ranking'  =>  $params['row']['ranking'],
            'score'  =>  $params['row']['score'],
            'remark'  =>  $params['row']['remark'],
        ];
        $collegeInfo = Db::name('sports_score') 
                    -> where('YXDM',$params['row']['college'])
                    -> find();

        $newScore = $collegeInfo['total_score'] + $params['row']['score'];
        $temp = [
            'total_score'   => $newScore,
        ];
        // 启动事务
        Db::startTrans();     
        $insertFlag = false;
        $updateFlag = false;       
        try{
            $insertFlag = Db::name('sports_score_detail') -> insert($tempDetail);
            $updateFlag = Db::name('sports_score') -> where('YXDM',$params['row']['college'])-> update($temp);
            // 提交事务
            Db::commit();  
        } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        if ($insertFlag && $updateFlag) {
            return ['status'=> true, 'msg' => "录入成功"];
        } else {
            return ['status'=> false, 'msg' => "录入失败"];
        }
    }
    /**
     * 插入多个排名得分记录
     */

    public function addScoreDetailMulti($params)
    {   
        // 启动事务
        Db::startTrans();     
        $insertFlag = false;
        $updateFlag = false;       
        try{
            foreach ($params['row'] as $k => $v) {
                if ($v['ranking'] != '' && $v['score'] != '') {
                    $tempDetail = [
                        'YXDM'     => $v['college'],
                        'event_id' => $params['event'],
                        'ranking'  => $v['ranking'],
                        'score'    => $v['score'],
                        'remark'   => $v['remark'],
                    ];
                    $insertFlag = Db::name('sports_score_detail') -> insert($tempDetail);
                    $collegeInfo = Db::name('sports_score') 
                                -> where('YXDM',$v['college'])
                                -> find();

                    $newScore = $collegeInfo['total_score'] + $v['score'];
                    $temp = [
                        'total_score'   => $newScore,
                    ];
                    $updateFlag = Db::name('sports_score') -> where('YXDM',$v['college'])-> update($temp);
                }
            }
            // 提交事务
            Db::commit();  
        } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        if ($insertFlag && $updateFlag) {
            return ['status'=> true, 'msg' => "录入成功"];
        } else {
            return ['status'=> false, 'msg' => "录入失败"];
        }
    }
//注意这里得加上换了学院删除原来分数
    public function editDetail($params)
    {
        $tempDetail = [
            'YXDM'   =>  $params['row']['college'],
            'event_id' => $params['event'],
            'ranking'  =>  $params['row']['ranking'],
            'score'  =>  $params['row']['score'],
            'remark'  =>  $params['row']['remark'],
        ];
        if ($params["row"]["college"] != $params['row']['old_college']) {
            return ['status'=> false, 'msg' => "请勿修改学院"];
        }
        if ($params['row']['score'] == $params['row']['old_score']) {
            $insertFlag = Db::name('sports_score_detail') ->where('id',$params['row']['detail_id'])-> update($tempDetail);
            return $insertFlag == 1 ? ["status" => true, "msg" => "录入成功"] : ["status" => false, "msg" => "录入失败"];
        } else {

            $collegeInfo = Db::name('sports_score') 
                    -> where('YXDM',$params['row']['college'])
                    -> find();

            $newScore = $collegeInfo['total_score'] + $params['row']['score'] - $params['row']['old_score'];
            $temp = [
                'total_score'   => $newScore,
            ];
            // 启动事务
            Db::startTrans();     
            $insertFlag = false;
            $updateFlag = false;       
            try{
                $insertFlag = Db::name('sports_score_detail') ->where('id',$params['row']['detail_id'])-> update($tempDetail);
                $updateFlag = Db::name('sports_score') -> where('YXDM',$params['row']['college'])-> update($temp);
                // 提交事务
                Db::commit();  
            } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            if ($insertFlag && $updateFlag) {
                return ['status'=> true, 'msg' => "录入成功"];
            } else {
                return ['status'=> false, 'msg' => "录入失败"];
            }
        }

    }

}
