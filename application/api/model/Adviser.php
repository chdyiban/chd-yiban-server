<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;

class Adviser extends Model
{
    // 表名
    protected $name = 'adviser';

    
    public function getStatus($key){
        //判断有无班主任
        $stu_id = $key['stu_id'];
        $BJDM = Db::name('stu_detail') -> where('XH',$stu_id) -> field('BJDM') -> find()['BJDM'];
        if (empty($BJDM)) {
            return ['status' => '500','msg' => "未找到相应班级"];
        }
        $adviserInfoList = $this -> where('class_id', $BJDM) ->find();
        if (empty($adviserInfoList)) {
            return ['status' => '0','msg' => "未获取班主任信息"];
        }
        //判断班主任提交问卷
        $adviser_name = $adviserInfoList['XM'];
        $adviser_college = Db::name('dict_college') 
                        -> where('YXDM',$adviserInfoList['YXDM']) 
                        -> field('YXJC') 
                        -> find()['YXJC'];
        $adviser_class = $adviserInfoList['class_id'];
        if (empty($adviserInfoList['timestamp'])) {
            return [
                'status' => '2', 
                'data' => [
                    'adviser_name' => $adviser_name,
                    'adviser_college' => $adviser_college,
                    'adviser_class' => $adviser_class,
                ],
            ];
        }
        //判断学生完成评价
        $stuResult = Db::name('result') 
                    -> where('stu_id',$stu_id) 
                    -> where('q_id',1)
                    -> find();
        if (empty($stuResult)) {
            //未完成评价
            $questionList = Db::name('questionnaire') -> where('q_id',1) -> where('status',1) -> select();
            $questionnaire = array();
            foreach ($questionList as $value) {
                $temp = array();
                $temp['title'] = $value['title'];
                $temp['options'] = $value['options'];
                $temp['type'] = $value['type'];
                $questionnaire[] = $temp;
            }
            return [
                'status' => '1', 
                'data' => [
                    'adviser_name' => $adviser_name,
                    'adviser_college' => $adviser_college,
                    'adviser_class' => $adviser_class,
                    'questionnaire' => $questionnaire,
                ],
            ];
        } else {
            return [
                'status' => '3', 
                'data' => [
                    'adviser_name' => $adviser_name,
                    'adviser_college' => $adviser_college,
                    'adviser_class' => $adviser_class,
                ],
                'msg' => "已完成评价",
            ];
        }
        
    }

    public function submit($key)
    {
        $stu_id = $key['stu_id'];
        $form_data = $key['form_data'];
        $stuInfo = Db::name('stu_detail') -> where('XH',$stu_id) -> find();
        if (empty($stuInfo)) {
            return ['status' => '2', 'msg' => "未获取对应学生信息"];
        }
        if (empty($stuInfo['BJDM'])) {
            return ['status' => '2', 'msg' => "未获取学生班级信息"];
        }

        $adviserInfoList = $this -> where('class_id',$stuInfo['BJDM']) -> find();
        if (empty($adviserInfoList['id'])) {
            return ['status' => '2', 'msg' => "未获取班主任信息"];            
        }
        $oldResult = Db::name('result') -> where('stu_id',$stu_id) -> find();
        if (empty($oldResult)) {
            $res = Db::name('result') -> insert([
                'q_id'       => 1,
                'stu_id'     => $stu_id,
                'class_id'   => $stuInfo['BJDM'],
                'adviser_id' => $adviserInfoList['id'],
                'raw_data'   => $form_data,
                'timestamp'  => time(),
            ]);
            if ($res) {
                return ['status' => '1', 'msg' => "评价成功"];      
            } else {
                return ['status' => '2', 'msg' => "网络原因，评价失败"];      
            }
        } else {
            return ['status' => '2', 'msg' => "已经填写过问卷，请勿重复操作"];
        }
    }
}