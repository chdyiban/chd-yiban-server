<?php

namespace app\api\controller\yiban;
use app\common\controller\Api;
use app\api\controller\yiban\YB_Uis;
use think\Db;
use app\common\library\Token;

class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        header("Content-Type: text/html; charset=utf-8");
        session_start();
        
        require_once dirname(__FILE__)."/CAS/CAS.php";
        require_once dirname(__FILE__)."/YB_Uis.php";
        
        phpCAS::client(CAS_VERSION_2_0,'ids.chd.edu.cn',80,'authserver',false);
        phpCAS::setNoCasServerValidation();
        phpCAS::handleLogoutRequests();
        phpCAS::forceAuthentication();
        
        if(isset($_GET['logout'])){
            $param = array('service'=>'http://ids.chddata.com/');
            phpCAS::logout($param);
            exit;
        }
        $ismobile = '';
        if(isset($_GET['mobile'])){
            $ismobile = ($_GET['mobile'] == '1') ? true : false;
        }
        
        $user = phpCAS::getUser();
        if($user == ''){
            die('unkown error');
        }
        
        //if(substr($user,0,4) == '2018'){
        if(true){
            $url = 'https://yiban.chd.edu.cn/api/yiban/ids/register?XH='.$user.'&token=65f6c288-2339-4380-8fe0-eaa0ab27ff01';
            $data = curl($url);
            $data = json_decode($data,true);
            if($data['code'] == 0){
                echo '<script>alert("'.$data['msg'].'")<script>';
                exit();
            }elseif($data['code'] == 1){
                $infoArr = $data['data'];
                //var_dump($infoArr);exit();
                //YB_Uis::getInstance()->run($infoArr,'',$ismobile,'http://f.yiban.cn/iapp195437');
                YB_Uis::getInstance()->run($infoArr,'',$ismobile,'http://proj.yiban.cn/project/invest/test.php');
            }
        }else{
            //默认其他年级
            $PDO = new PDO('mysql:host=localhost;dbname=chddata_v2', 'root', '69431589');
            if(strlen($user) == 6){
                $sql = "SELECT DETAIL.ID,DETAIL.XM,DETAIL.XBDM,DETAIL.ROLE,COLLEGE.yb_group_id FROM `chd_teacher_detail` AS DETAIL,`chd_dict_college` AS COLLEGE WHERE DETAIL.YXDM = COLLEGE.YXDM AND DETAIL.id = '$user'";
                $result = $PDO->query($sql);
                $row = $result->fetch(PDO::FETCH_ASSOC);
        
                if($row){
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
                }else{
                    echo "工号为$user的老师您好，易班后台还未同步您的数据，请联系信息学院杨加玉处理。联系电话：15029484116，感谢您对易班工作的支持！";
                }
            }else{
                $sql = "SELECT DETAIL.XH,DETAIL.XM,DETAIL.XBDM,COLLEGE.yb_group_id FROM `chd_stu_detail` AS DETAIL,`chd_dict_college` AS COLLEGE WHERE DETAIL.YXDM = COLLEGE.YXDM AND DETAIL.XH = '$user'";
                $result = $PDO->query($sql);
                $row = $result->fetch(PDO::FETCH_ASSOC);
        
                if($row){
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
    function curl($url){
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $data;
    }
    
}