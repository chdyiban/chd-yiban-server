<?php

namespace app\api\model;
use think\Db;
use think\Model;

class Sports extends Model
{
    // 表名
    protected $name = '';
    // 自动写入时间戳字段

    /**
     * 获取每个学院的信息以及排名情况
     */
    public function getCollegeScoreRank()
    {
        $return = [];
        $collegeList = Db::view('sports_score')
                        -> view('dict_college','YXDM,YXMC','dict_college.YXDM = sports_score.YXDM')
                        -> order('total_score desc')
                        -> select();
       $temp = [];
        foreach ($collegeList as $key => $value) {
            $temp['college_id'] = $value['YXDM'];
            $temp['college_name'] = $value['YXMC'];
            $temp['ranking'] = $key + 1;
            $temp['total'] = $value['total_score'];
            $return[] = $temp;
        }

        return $return;
    }
    /**
     * 获取每个学院的信息以及步数排名情况
     */
    public function getCollegeStepsRank()
    {
        $return = [];
        $collegeList = Db::view('sports_score')
                        -> view('dict_college','YXDM,YXJC','dict_college.YXDM = sports_score.YXDM')
                        -> order('total_steps desc')
                        -> select();
       $temp = [];
        foreach ($collegeList as $key => $value) {
            $temp['college_id'] = $value['YXDM'];
            $temp['college_name'] = $value['YXJC'];
            $temp['college_logo_url'] = "";
            $temp['total_donate_steps'] = $value['total_steps'];
            $temp['total_donate_person'] = $value['total_person'];
            $temp['ranking'] = $key + 1;
            $temp['heat'] = $this -> getHeat($value['average_steps']);
            $return[] = $temp;
        }

        return $return;
    }

    /**
     * 获取热度计算公式以及学院的热度情况
     */

    public function getHeat($averageSteps){
        $maxSteps = Db::name('sports_score') -> max('average_steps');
        $minSteps = Db::name('sports_score') -> min('average_steps');
        $k = ($maxSteps - $minSteps)/(pow(1.005,99.9) - pow(1.005,60));
        $b = $minSteps - $k*pow(1.005,60);
        $c = ($averageSteps - $b)/$k;
        $heat = log10($c)/log10(1.005);

        return (float)number_format($heat,2);
    

    }

     /**
     * 获取学生信息
     */
    public function getStuInfo($key)
    {
        $today = strtotime(date("Y-m-d"),time());
        $return = [];
        $infoList = Db::name('wx_user') -> where('open_id',$key['openid'])->find();
        $stuInfo = Db::name('stu_detail') -> where('XH',$infoList['portal_id']) ->field('YXDM') -> find();
        $return['college_id'] = empty($stuInfo['YXDM']) ? "未获取" : $stuInfo['YXDM'];
        
        $collegeGrowSteps = Db::name('sports_steps_detail')
                    -> where('YXDM',$stuInfo['YXDM']) 
                    -> where('time','>=',$today) 
                    -> sum('steps');
        $return['today_grow_steps'] = $collegeGrowSteps;
        $stuSteps = Db::name('sports_steps_detail') 
                    -> where('stu_id',$infoList['portal_id']) 
                    -> sum('steps');
        $return['my_total_steps'] = $stuSteps;
        return $return;
    }

    /**
     * 获取学院比赛得分详细信息
     */
    public function getCollegeDetail($key)
    {
        $return = [];
        $collegeInfoList = Db::view('sports_score_detail') 
                            -> view('sports_type','id,event_name,type_id,type_name','sports_score_detail.event_id = sports_type.id')
                            -> where('YXDM',$key['collegeid'])
                            -> order('type_id asc')
                            -> select();
        if (empty($collegeInfoList)) {
            return ['status' => false,'data' => ''];
        }
        foreach ($collegeInfoList as $k => $v) {
            $temp = [
                'id' => $v['id'],
                'event_name' => $v['event_name'],
                'ranking' => $v['ranking'],
                'remark' => $v['remark'],
                'score' => $v['score'],
            ];
            $return[(int)$v['type_id']][] = $temp;
        }
        return ['status' => true,'data' => $return];
    }


}
