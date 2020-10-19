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
                        -> where('sports_score.YXDM',"<>","1400") // 没有体育系
                        -> order('total_score desc')
                        -> select();
       $temp = [];
        foreach ($collegeList as $key => $value) {
            $temp['college_id'] = $value['YXDM'];
            $temp['college_name'] = $value['YXMC'];
            $temp['ranking'] = $key;
            $temp['total'] = $value['total_score'];
            $return[] = $temp;
        }

        foreach ($return as $key => $value) {
            if ($key > 0 && $return[$key-1]['total'] == $value['total']) {
                $return[$key-1]['ranking'] = $key;
            } else {
                $return[$key]['ranking'] = $key + 1;
            }
            
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
                        -> order('average_steps desc')
                        -> select();
       $temp = [];
        foreach ($collegeList as $key => $value) {
            $temp['college_id'] = $value['YXDM'];
            $temp['college_name'] = $value['YXJC'];
            $temp['college_logo_url'] = "https://yibancdn.ohao.ren/college_image/".$value['YXDM'].".jpg";
            $temp['total_donate_steps'] = $this -> changeType($value['total_steps']);
            $temp['heat'] = $this -> getHeat($value['average_steps']);
            $temp['total_donate_person'] = $value['total_person'];
            $temp["ranking"] = $key;
            $return[] = $temp;
        }

        foreach ($return as $key => $value) {
            if ($key > 0 && $return[$key-1]['heat'] == $value['heat']) {
                $return[$key-1]['ranking'] = $key;
            } else {
                $return[$key]['ranking'] = $key + 1;
            }
            
        }

        return $return;
    }

    /**
     * 获取热度计算公式以及学院的热度情况
     */

    public function getHeat($averageSteps){
        $bottom = 1.105;
        $maxSteps = Db::name('sports_score') -> max('average_steps');
        $minSteps = Db::name('sports_score') -> min('average_steps');   
        if ($averageSteps > $maxSteps) {
            $heat = 99.9;
        } else {
            // $k = ($maxSteps - $minSteps)/(pow(1.005,99.9) - pow(1.005,60));
            $k = ($maxSteps - $minSteps)/(pow($bottom,99.9) - pow($bottom,60));
            $b = $minSteps - $k*pow($bottom,60);
            if ($k == 0) {
                $heat = 60;
            } else {
                $c = ($averageSteps - $b)/$k;
                $heat = log10($c)/log10($bottom);
            }
        }

        return number_format((float)$heat,1);
    }

     /**
     * 获取学生信息
     * @time 2019/4/11添加获取教职工相关信息
     */
    public function getStuInfo($key)
    {
        $today = strtotime(date("Y-m-d"),time());
        $return = [];
        $infoList = Db::name('wx_user') -> where('open_id',$key['openid'])->find();
        if(strlen($infoList['portal_id']) == 6){
            $stuInfo = Db::view("teacher_detail")
                    ->view('dict_college','YXDM,YXMC,YXJC','teacher_detail.YXDM = dict_college.YXDM')
                    ->where('ID',$infoList['portal_id'])
                    ->find();
        } else {
            $stuInfo = Db::view('stu_detail')
                    -> view('dict_college','YXDM,YXJC','stu_detail.YXDM = dict_college.YXDM') 
                    -> where('XH',$infoList['portal_id']) 
                    -> find();
        }
        $return['college_id'] = empty($stuInfo['YXDM']) ? "未获取" : $stuInfo['YXDM'];
        $return['college_name'] = empty($stuInfo['YXJC']) ? "未获取" : $stuInfo['YXJC'];
        
        $collegeGrowSteps = Db::name('sports_steps_detail')
                    -> where('YXDM',$stuInfo['YXDM']) 
                    -> where('time','>=',$today) 
                    -> sum('steps');
        //学院今天增长的捐献步数
        $return['today_grow_steps'] =  $this -> changeType($collegeGrowSteps);
        $stuSteps = Db::name('sports_steps_detail') 
                    -> where('stu_id',$infoList['portal_id']) 
                    -> sum('steps');
        //学生累积捐献步数
        $return['my_total_steps'] = $this -> changeType($stuSteps);
        $collegeSteps = Db::name('sports_score') 
                    -> where('YXDM',$stuInfo['YXDM']) 
                    -> field('total_steps,total_person,average_steps')
                    -> find();
        //学院总步数
        $return['college_total_steps'] = $this -> changeType($collegeSteps['total_steps']);
        $return['my_total_donate_person'] = $collegeSteps['total_person'];
        $return['my_heat'] = $this ->getHeat($collegeSteps['average_steps']);
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

    /**
     * 转换步数格式
     */
    function changeType($num){
        if($num < 1000) {
           return $num;
        } else if($num >=1000 && $num < 1000000){
           return round($num/1000,1).'k';
        } elseif ($num >= 1000000) {
            return round($num/1000000,1).'M';
        }
    }

    /**
     * 获取赛程
     * @return array $return
     */
    public function getSchedule()
    {
        $return = [
            [
                'date' => "10-21",
                'list' => [],
            ],
            [
                'date' => "10-22",
                'list' => [],
            ],
            [
                'date' => "10-23",
                'list' => [],
            ]
        ];
        $scheduleList = Db::name('sports_date') 
                    -> where('sports_time','>',time())
                    -> order('sports_time','asc')
                    -> select();

        foreach ($scheduleList as $key => $value) {
            $temp = [];
            
            $time = date('Y-m-d H:i',$value['sports_time']);
            $date = substr($time,5,5);
            $temp = [
                'time' => substr($time,11),
                'type' => $value['sports_group'],
                'event' => $value['sports_name'],
            ];
            for ($i=0; $i < 3; $i++) { 
                if ($date == $return[$i]['date']) {
                    $return[$i]['list'][] = $temp;
                    break;
                }
            }
        }

        return $return;
    }
}
