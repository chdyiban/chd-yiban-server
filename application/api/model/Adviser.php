<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;

class Adviser extends Model
{
    // 表名
    protected $name = 'bzr_adviser';
    const Q_ID = 2;

    public function getStatus($key){
        //判断有无班主任
        $stu_id = $key['id'];
        // $form_data = $key['openid'];
        // $safe = Db::name('wx_user') -> where('open_id',$open_id) -> where('portal_id',$stu_id) -> find();
        // if (empty($safe)) {
        //     return ['status' => '500', 'msg' => "请求非法"];
        // }
        $q_id = self::Q_ID;
        $BJDM = Db::name('stu_detail') -> where('XH',$stu_id) -> field('BJDM') -> find()['BJDM'];
        if (empty($BJDM)) {
            return ['status' => 200,'step' => 0,'msg' => "未找到相应班级"];
        }
        $adviserInfoList = $this -> where('class_id', $BJDM)->where("q_id",$q_id) ->find();
        if (empty($adviserInfoList)) {
            return ['status' => 200,'step' => 0,'msg' => "未获取班主任信息"];
            // return ['status' => 200,'step' => 0,'msg' => "未获取辅导员信息"];
        }
        //判断班主任提交问卷
        $adviser_name = $adviserInfoList['XM'];
        $adviser_college = Db::name('dict_college') 
                        -> where('YXDM',$adviserInfoList['YXDM']) 
                        -> field('YXJC') 
                        -> find()['YXJC'];
        $adviser_class = $adviserInfoList['class_id'];
        // if (empty($adviserInfoList['timestamp'])) {
        //     return [
        //         'status' => 200, 
        //         'step' => 2, 
        //         'data' => [
        //             'adviser_name' => $adviser_name,
        //             'adviser_college' => $adviser_college,
        //             'adviser_class' => $adviser_class,
        //         ],
        //         'msg' => "待发布",
        //     ];
        // }
        //判断学生完成评价
        $stuResult = Db::name('bzr_result') 
                    -> where('stu_id',$stu_id) 
                    // -> where('q_id',1)
                    -> where('q_id',$q_id)
                    -> find();
        if (empty($stuResult)) {
            //未完成评价
            // $questionList = Db::name('bzr_questionnaire') -> where('q_id',1) -> where('status',1) -> select();
            $questionList = Db::name('bzr_questionnaire') -> where('q_id',$q_id) -> where('status',1) -> select();
            $questionnaire = array();
            foreach ($questionList as $value) {
                $temp = array();
                $temp['title'] = $value['title'];
                $temp['options'] =  json_decode($value['options'],true);
                $temp['type'] = $value['type'];
                $temp['must'] = $value['must'] == 1? true : false;
                $questionnaire[] = $temp;
            }
            return [
                'status' => 200, 
                'step' => 1, 
                'data' => [
                    'adviser_name' => $adviser_name,
                    'adviser_college' => $adviser_college,
                    'adviser_class' => $adviser_class,
                    // 'working_logs' => [
                    //     'input1'  =>  $adviserInfoList['HDCS'],
                    //     'input2'  =>  $adviserInfoList['BHCS'],
                    //     'input3'  =>  $adviserInfoList['FDXS'],
                    // ],
                    'questionnaire' => $questionnaire,
                ],
            ];
        } else {
            return [
                'status' => 200, 
                'step' => 3, 
                'data' => [
                    'adviser_name' => $adviser_name,
                    'adviser_college' => $adviser_college,
                    'adviser_class' => $adviser_class,
                    // 'working_logs' => [
                    //     'input1'  =>  $adviserInfoList['HDCS'],
                    //     'input2'  =>  $adviserInfoList['BHCS'],
                    //     'input3'  =>  $adviserInfoList['FDXS'],
                    // ],
                ],
                'msg' => "已完成评价",
            ];
        }
        
    }

    public function submit($key)
    {
        $q_id = self::Q_ID;
        $stu_id = $key['id'];
        $form_data = json_encode($key['options']);
        $open_id = $key['openid'];
        $safe = Db::name('wx_user') -> where('open_id',$open_id) -> where('portal_id',$stu_id) -> find();
        if (empty($safe)) {
            return ['status' => 500, 'msg' => "请求非法"];
        }

        $stuInfo = Db::name('stu_detail') -> where('XH',$stu_id) -> find();
        if (empty($stuInfo)) {
            return ['status' => 200,'code' => 2, 'msg' => "未获取对应学生信息"];
        }
        if (empty($stuInfo['BJDM'])) {
            return ['status' => 200,'code' => 2, 'msg' => "未获取学生班级信息"];
        }

        $adviserInfoList = $this -> where("q_id",$q_id)->where('class_id',$stuInfo['BJDM']) -> find();
        if (empty($adviserInfoList['id'])) {
            return ['status' => 200,'code' => 2, 'msg' => "未获取班主任信息"];            
            // return ['status' => 200,'code' => 2, 'msg' => "未获取辅导员信息"];            
        }
        $oldResult = Db::name('bzr_result') -> where("q_id",$q_id)->where('stu_id',$stu_id) -> find();
        if (empty($oldResult)) {
            $res = Db::name('bzr_result') -> insert([
                'q_id'       => $q_id,
                'stu_id'     => $stu_id,
                'class_id'   => $stuInfo['BJDM'],
                'adviser_id' => $adviserInfoList['id'],
                'raw_data'   => $form_data,
                'timestamp'  => time(),
            ]);
            if ($res) {
                return ['status' => 200,'code' => 1, 'msg' => "评价成功"];      
            } else {
                return ['status' => 200, 'code' => 2,'msg' => "网络原因，评价失败"];      
            }
        } else {
            return ['status' => 200,'code' => 2, 'msg' => "已经填写过问卷，请勿重复操作"];
        }
    }
}