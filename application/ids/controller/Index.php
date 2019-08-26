<?php
namespace app\ids\controller;

use app\ids\controller\YB_Uis;
use think\Db;
use think\Loader;

class index 
{
    public function index()
    {
        header("Content-Type: text/html; charset=utf-8");
        session_start();
        Loader::import('CAS.phpCAS');
        $phpCAS = new \phpCAS();
        $phpCAS->client(CAS_VERSION_2_0,'ids.chd.edu.cn',80,'authserver',false);
        $phpCAS->setNoCasServerValidation();
        $phpCAS->handleLogoutRequests();
        $phpCAS->forceAuthentication(); 

        if(isset($_GET['logout'])){
            $param = array('service'=>'http://ids.chddata.com/');
            $phpCAS->logout($param);
            exit;
        }
        $ismobile = '';
        if(isset($_GET['mobile'])){
            $ismobile = ($_GET['mobile'] == '1') ? true : false;
        }
        
        $user = $phpCAS->getUser();
        if($user == ''){
            die('unkown error');
        }
        //如果为老师
        if(strlen($user) == 6){
            $row = Db::view("teacher_detail")
                    -> view("dict_college","YXDM,yb_group_id","fa_teacher_detail.YXDM = fa_dict_college.YXDM")
                    -> where("ID",$user)
                    -> find();
            if (!empty($row)) {
                $infoArr = array(
                    //所有身份必填项
                    'name'=>$row['XM'],//姓名
                    'teacher_id'=>$user,//学号
                    'role'=>$row['ROLE'],//身份（0-学生、1-辅导员、2-教师、3-其他）
                    'build_time'=>time(),//Unix时间戳
                    //认证项，至少填一项，建议学工号
                    'sex'=>$row['XBDM'],
                    'college'=>$row['yb_group_id'],//学院
                );
                YB_Uis::getInstance()->run($infoArr,'',$ismobile);
            } else {
                echo "工号为$user的老师您好，易班后台还未同步您的数据，请联系信息学院杨加玉处理。联系电话：15029484116，感谢您对易班工作的支持！";
            }

        } else {
        //请求者为学生
            $row = Db::view("stu_detail")
                    -> view("dict_college","YXDM,yb_group_id","fa_stu_detail.YXDM = fa_dict_college.YXDM")
                    -> where("XH",$user)
                    -> find();
            if(!empty($row)){
                //入学年份取学号前四位
                $enter_year = substr($user,0,4);

                $infoArr = array(
                    //所有身份必填项
                    'name'=>$row['XM'],//姓名
                    'student_id'=>$user,//学号
                    'enter_year'=>$enter_year,//入学年份
                    'role'=>'0',//身份（0-学生、1-辅导员、2-教师、3-其他）
                    'build_time'=>time(),//Unix时间戳
                    //认证项，至少填一项，建议学工号

                    //学生身份必填项
                    //'instructor_id'=>'',//辅导员工号
                    'status'=>'0',//（0-在读、1-休学、2-离校）
                    'schooling'=>'4',//（2.5/3/4/5/7/8）
                    'education'=>'0',//（0-本科、1-大专、2-硕士、3-博士、4-中职/中专）
                    //选填项，如无法提供某项数据，移除该项
                    'sex'=>$row['XBDM'],
                    'college'=>$row['yb_group_id'],//学院
                );

                //YB_Uis::getInstance()->run($infoArr,'',$ismobile,'http://f.yiban.cn/iapp195437');
                YB_Uis::getInstance()->run($infoArr,'',$ismobile,'http://proj.yiban.cn/project/invest/test.php');
            }else{
                die('暂不支持认证');
            }
        }

    }
}






