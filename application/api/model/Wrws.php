<?php

namespace app\api\model;
use think\Db;
use think\Model;

class Wrws extends Model{
    protected $name = '';

    //生成题目函数
    public function getquestion($id,$num){
        $return=[];
        $start=time();
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
        $return['time']=time()-$start;
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


    //生成题目函数
    public function getquestiontest($id,$num){
        $return=[];
        
        $start=time();
        //获取辅导员所带班级
        $classlist=Db::name('wrws_class')
                    ->where('GH',$id)
                    ->select();
        
        $quesnum=$num;
        mt_srand(time());
        $stulist=[];

        $class=[];
        foreach($classlist as $value){
            array_push($class,$value['BJH']);
        }
        
        // foreach($classlist as $value){
        //     $stuclass=Db::name('wrws_stuinfo')//XB&ZYDM>BJDM>SYD&MZDM
        //     ->where('BJDM',$value['BJH'])
        //     ->select();
        //     foreach($stuclass as $info){
        //         array_push($stulist,$info);
        //     }
        // }

        $stulist=Db::name('wrws_stuinfo')//XB&ZYDM>BJDM>SYD&MZDM
                ->where('BJDM','in',$class)
                ->select();
        
        
        
        while($quesnum--){//生成题目数
            $stuanswer=mt_rand(0,count($stulist)-1);
            
            $sydormz=$stulist[$stuanswer]['MZDM']!='01'?1:0;
            //选项性别，生源地前2位，民族
            $stulist1=[];
            if($sydormz==1){
                foreach($stulist as $key =>$value){
                    if($value['XB']==$stulist[$stuanswer]['XB']&&$value['MZDM']==$stulist[$stuanswer]['MZDM']){
                        array_push($stulist1,$value);
                    }
                }
            }else{
                foreach($stulist as $key =>$value){
                    if($value['XB']==$stulist[$stuanswer]['XB']&&$this->sydcmp($value['SYD'],$stulist[$stuanswer]['SYD'])){
                        array_push($stulist1,$value);
                    }
                }
            }
            do{
                $stu3=mt_rand(0,count($stulist1)-1);
            }while($stu3==$stuanswer);

            //选项性别，政治面貌
            $stulist2=[];
            foreach($stulist as $key =>$value){
                if($value['XB']==$stulist[$stuanswer]['XB']&&$value['ZZMM']==$stulist[$stuanswer]['ZZMM']){
                    array_push($stulist2,$value);
                }
            }
            do{
                $stu2=mt_rand(0,count($stulist2)-1);
            }while($stu2==$stuanswer||$stu2==$stu3);
            //选项随机
            do{
                $stu1=mt_rand(0,count($stulist)-1);
            }while($stu1==$stuanswer||$stu1==$stu2||$stu1==$stu3);
            
            $choice=[$stulist[$stuanswer]['XM'],$stulist[$stu1]['XM'],$stulist[$stu2]['XM'],$stulist[$stu3]['XM']];
            $info=[];
            $name=[];
            $name['MZMC']='民族';
            $name['SYDM']='生源地';
            $name['XBMC']='性别';
            $name['ZZMMMC']='政治面貌';
            $name['BJDM']='班级代码';
            $name['ZYMC']='专业';
            array_push($info,$sydormz==1?'MZMC':'SYDM');
            if($sydormz==1){
                array_push($info,'SYDM');
            }else{
                $xborzzmm=mt_rand(0,1);
                array_push($info,$sydormz==1?'XBMC':'ZZMMMC');
            }

            $bjorzy=mt_rand(0,1);
            array_push($info,$bjorzy==1?'BJDM':'ZYMC');
            
            $stuobj=$stulist[$stuanswer];
            $stuobj['XBMC']=$stuobj['XB']=='1'?'男':'女';
            $stuobj['SYDM']=(Db::view("dict_area","SYDDM,SYDM")->where('SYDDM',$stuobj['SYD'])->find())['SYDM'];
            $stuobj['MZMC']=(Db::view("dict_nation","MZDM,MZMC")->where('MZDM',$stuobj['MZDM'])->find())['MZMC'];
            $stuobj['ZYMC']=(Db::view("dict_major","ZYDM,ZYMC")->where('ZYDM',$stuobj['ZYDM'])->find())['ZYMC'];
            $stuobj['ZZMMMC']=(Db::view("dict_zzmm","ZZMMDM,ZZMMMC")->where('ZZMMDM',$stuobj['ZZMM'])->find())['ZZMMMC'];

            $analysis="姓名:".$stuobj['XM'].
            ",性别:".$stuobj['XBMC'].
            ",民族:".$stuobj['MZMC'].
            ",生源地:".$stuobj['SYDM'].
            ",专业:".$stuobj['ZYMC'].
            ",班级:".$stuobj['BJDM'];

            $clues=[];
            foreach($info as $value){
                $item=[];
                $item['type']=$name[$value];
                $item['value']=$stuobj[$value];
                array_push($clues,$item);
            }

            $question=[];
            $question['clues']=$clues;
            shuffle($choice);
            $question['answers']=$choice;
            $answer=array_search($stulist[$stuanswer]['XM'],$choice);
            $question['answer']=$answer;
            $question['analysis']=$analysis;
            array_push($return,$question);
        }
        $return['time']=time()-$start;
        return $return;

    }

    //生源地比较
    public function sydcmp($a,$b){
        if($a[0]==$b[0]&&$a[1]==$b[1]){
            return TRUE;
        }else{
            return FALSE;
        }
    }


    //获取辅导员代班级
    public function getclasslist($id){
        $return=[];
        $classlist=Db::name('wrws_class')
                    ->where('GH',$id)
                    ->select();
        foreach($classlist as $value){
            array_push($return,$value['BJH']);
        }
        return $return;
    }

    //获取班级学生信息
    public function getstuinfo($id){
        $return=[];
        // $stuinfo=Db::view('wrws_stuinfo','XM,XH,XB,MZDM,ZYDM,BJDM,SYD,ZZMM,ZP')//姓名，性别，民族代码，专业代码，班级代码，生源地代码，贫困程度，政治面貌，照片
        //                 ->view("dict_nation","MZDM,MZMC","wrws_stuinfo.MZDM = dict_nation.MZDM")
        //                 ->view("dict_major","ZYDM,ZYMC","wrws_stuinfo.ZYDM = dict_major.ZYDM")
        //                 ->view("dict_area","SYDDM,SYDM","wrws_stuinfo.SYD = dict_area.SYDDM")
        //                 ->view("dict_zzmm","ZZMMDM,ZZMMMC","wrws_stuinfo.ZZMM = dict_zzmm.ZZMMDM")
        //                 ->view("dict_sex","XBDM,XBMC","wrws_stuinfo.XB = dict_sex.XBDM")
        //                 ->where('BJDM',$id)
        //                 ->select();
        //
                        
        $stuinfo=Db::view('wrws_stuinfo','XM,XH,XB,MZDM,ZYDM,BJDM,SYD,ZZMM,ZP')
                        //->view("dict_major","ZYDM,ZYMC","wrws_stuinfo.ZYDM = dict_major.ZYDM")
                        //->view("dict_area","SYDDM,SYDM","wrws_stuinfo.SYD = dict_area.SYDDM")
                        ->where("BJDM",$id)
                        ->select();
        foreach($stuinfo as $value){
            $obj=[];
            array_push($obj,array('type'=>'姓名','value'=>$value['XM']));
            array_push($obj,array('type'=>'性别','value'=>($value['XB']=='1'?'男':'女')));
            $MZMC='';
            if($value['MZDM']=='01'){
                $MZMC='汉族';
            }else{
                $MZMC=(Db::name('dict_nation')->where('MZDM',$value['MZDM'])->find())['MZMC'];
            }
            array_push($obj,array('type'=>'民族','value'=>$MZMC));
            $ZZMMMC='';
            if($value['ZZMM']=='03'){
                $ZZMMMC='共青团员';
            }else if($value['ZZMM']=='13'){
                $ZZMMMC='群众';
            }else{
                $ZZMMMC=(Db::name('dict_zzmm')->where('ZZMMDM',$value['ZZMM'])->find())['ZZMMMC'];
            }
            array_push($obj,array('type'=>'政治面貌','value'=>$ZZMMMC));
            array_push($obj,array('type'=>'学号','value'=>$value['XH']));
            array_push($obj,array('type'=>'班级','value'=>$value['BJDM']));
            $ZYMC=(Db::name('dict_major')->where('ZYDM',$value['ZYDM'])->find())['ZYMC'];
            array_push($obj,array('type'=>'专业','value'=>$ZYMC));
            $SYDM=(Db::name('dict_area')->where('SYDDM',$value['SYD'])->find())['SYDM'];
            array_push($obj,array('type'=>'生源地','value'=>$SYDM));
            array_push($return,$obj);
        }
        return $return;
    }
}