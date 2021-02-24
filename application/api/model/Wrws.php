<?php

namespace app\api\model;
use think\Db;
use think\Model;

class Wrws extends Model{
    protected $name = '';

    //生成题目函数
    public function getquestion($id,$num){
        $return=[];
        $classlist=Db::name('wrws_class')
                    ->where('GH',$id)
                    ->select();
        $stulist=[];
        foreach($classlist as $key =>$value){
            $classinfo=Db::view('wrws_stuinfo','XM,XB,MZDM,ZYDM,BJDM,SYD,PKCD,ZZMM,ZP')//姓名，性别，民族代码，专业代码，班级代码，生源地代码，贫困程度，政治面貌，照片
                        ->view("dict_nation","MZDM,MZMC","wrws_stuinfo.MZDM = dict_nation.MZDM")
                        ->view("dict_major","ZYDM,ZYMC","wrws_stuinfo.ZYDM = dict_major.ZYDM")
                        ->view("dict_area","SYDDM,SYDM","wrws_stuinfo.SYD = dict_area.SYDDM")
                        ->view("dict_zzmm","ZZMMDM,ZZMMMC","wrws_stuinfo.ZZMM = dict_zzmm.ZZMMDM")
                        ->view("dict_sex","XBDM,XBMC","wrws_stuinfo.XB = dict_sex.XBDM")
                        ->where('BJDM',$value['BJH'])
                        ->select();
            foreach($classinfo as $info){
                array_push($stulist,$info);
            }
            
        }
        mt_srand(time());
        $maxnum=count($stulist);
        for($key=1;$key<=$num;$key++){
            $randlist=[];
            while(count($randlist)<4){
                $randnum=mt_rand(0,$maxnum);
                $flag=TRUE;
                foreach($randlist as $value){
                    if($value==$randnum){
                        $flag=FALSE;
                        break;
                    }
                }
                if($flag){
                    array_push($randlist,$randnum);
                }
            }
            $ques=[];
            $answer=mt_rand(0,3);
            $clues=[];
            $typelist=['XBMC','SYDM','BJDM','MZMC','ZZMMMC',"ZYMC"];
            $namelist=['性别','生源地','班级',"民族","政治面貌","专业"];
            for($i=0;$i<3;$i++){
                $clue=[];
                $clue['type']=$namelist[$i];
                $clue['value']=$stulist[$randlist[$answer]][$typelist[$i]];
                array_push($clues,$clue);
            }
            $ques['clues']=$clues;
            $ques['answers']=[$stulist[$randlist[0]]['XM'],$stulist[$randlist[1]]['XM'],$stulist[$randlist[2]]['XM'],$stulist[$randlist[3]]['XM']];
            $ques['answer']=$answer;
            $ques['analysis']="姓名:".$stulist[$randlist[$answer]]['XM'].
                            ",性别:".$stulist[$randlist[$answer]]['XBMC'].
                            ",民族:".$stulist[$randlist[$answer]]['MZMC'].
                            ",生源地:".$stulist[$randlist[$answer]]['SYDM'].
                            ",专业:".$stulist[$randlist[$answer]]['ZYMC'].
                            ",班级:".$stulist[$randlist[$answer]]['BJDM'];
            array_push($return,$ques);
        }
        return $return;
    }

    //提交结果的函数
    public function submit($id,$data){
        $userinfo=Db::name("wrws_userinfo")
                ->where('GH',$id)
                ->find();
        $userinfo['DTZS']+=$data['right']+$data['wrong'];
        $userinfo['ZQS']+=$data['right'];

        Db::name('wrws_userinfo')
            ->where('id',$userinfo['id'])
            ->update(['DTZS'=>$userinfo['DTZS'],'ZQS'=>$userinfo['ZQS']]);
        
        return TRUE;
        
    }

    //获取用户信息函数
    public function getuserinfo($id){
        $return=[];
        $return=Db::name("wrws_userinfo")
                ->where('GH',$id)
                ->find();
        
        
        return $return;
    }

    public function bind($id){
        $user=Db::name("wrws_userinfo")
            ->where('GH',$id)
            ->find();
        if(empty($user)){
            $userinfo=Db::name('teacher_detail')
                ->where('ID',$id)
                ->find();
        
            Db::name('wrws_userinfo')
                ->insert(['GH'=>$id,'XM'=>$userinfo['XM'],'DTZS'=>0,'ZQS'=>0]);
            
        }
    }
}