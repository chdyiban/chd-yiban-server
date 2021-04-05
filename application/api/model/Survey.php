<?php

namespace app\api\model;
use think\Db;
use think\Model;

class Survey extends Model
{
    // 表名
    protected $name = 'fa_survey';

    public function getInitData($key)
    {
        $return=[];
        $surveyinfo=Db::name('survey')->where('status','1')->find();
        $options=json_decode($surveyinfo['options']);
        $res=[];
        for($i=0;$i<count($options);++$i){
            $res[$i]=0;
        }
        $return=[
            'title' => $surveyinfo['title'],
            'answered' => false,
            'answer' => -1,
            'res' => $res,
            'options' => $options,
            'survey_id' => $surveyinfo['survey_id']
        ];

        $log=Db::name("survey_log")->where("survey_id",$surveyinfo['survey_id'])->where('user_id',$key['id'])->find();
        if(!empty($log)){
            $servey_res=Db::name("survey_res")->where("survey_id",$surveyinfo['survey_id'])->select();
            $return['answered']=true;
            $return['answer']=$log['option_id'];
            foreach($servey_res as $key =>$value){
                $return['res'][$value['option_id']]=$value['result'];
            }

        }
        return $return;
    }

    public function submit($key){
        $return=[];

        $insertdata=[
            'survey_id' => $key['survey_id'],
            'user_id' => $key['id'],
            'option_id' => $key['option_id'],
            'time' => time()
        ];
        $optionres=Db::name("survey_res")->where('survey_id',$key['survey_id'])->where('option_id',$key['option_id'])->find();
        $updateflag=0;
        if(empty($optionres)){
            $optionres=[
                'survey_id' => $key['survey_id'],
                'option_id' => $key['option_id'],
                'result' => 1
            ];
            $updateflag=Db::name("survey_res")->insert($optionres);
        }else{
            $updateflag=Db::name("survey_res")->where('id',$optionres['id'])->update(['result' => $optionres['result']+1]);
        }

        $insertflag=Db::name("survey_log")->insert($insertdata);

        if($insertflag==1&&$updateflag==1){
            $return=['status'=>true,'data'=>'提交成功'];
        }else{
            $return=['status'=>false,'data'=>'提交失败'];
        }

        return $return;

    }

}