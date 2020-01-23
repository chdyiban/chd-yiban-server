<?php
namespace app\ids\controller;

use think\Db;
use think\Loader;
use think\Config;
use think\Session;
// use app\common\controller\Backend;
use think\Controller;

/**
 * cas认证登录公众号
 */
class Offiaccount extends Controller {
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        header("Content-Type: text/html; charset=utf-8");
        // session_start();
        Loader::import('CAS.phpCAS');
        $phpCAS = new \phpCAS();
        $phpCAS->client(CAS_VERSION_2_0,'ids.chd.edu.cn',80,'authserver',false);
        $phpCAS->setNoCasServerValidation();
        $phpCAS->handleLogoutRequests();
        $phpCAS->forceAuthentication(); 
        // if ($type == "logout") {
        //     // $param = array('service'=>'http://ids.chd.edu.cn/authserver/login?service=https%3A%2F%2Fyiban.chd.edu.cn%2Fids%2Fadmin%2Findex');
        //     $phpCAS->logout();
        // }
        $user = $phpCAS->getUser();
        $openid = $this->request->param("openid");
        $url    =   base64_decode($this->request->param("url"));
        if (empty($openid) || empty($url)) {
            $this->error("request error!");
        }
        $params = [
            "portal_id" =>  $user,
            "openid"    =>  $openid
        ];
        $check = $this->bindPortalInfo($params);
        if ($check) {
            $this->yiban($user,$url);
        }

    }

    /**
     * 登录易班
     * @param $user 学号
     * @param $url 跳转到的地址
     */
    public function yiban($user,$url)
    {
        $ismobile = '';
        if(isset($_GET['mobile'])){
            $ismobile = ($_GET['mobile'] == '1') ? true : false;
        }
        if($user == ''){
            die('unkown error');
        }
        // $user=$this->request->param("ID");
        //如果为老师
        if(strlen($user) == 6){
            $row = Db::view("teacher_detail")
                    -> view("dict_college","YXDM,yb_group_id","teacher_detail.YXDM = dict_college.YXDM")
                    -> where("ID",$user)
                    -> find();

            if (!empty($row) && !empty($row["SJH"]) ) {
                $this->uis($user,$ismobile,$url);
            } else {
                // $row["role"] = "teacher";
                $collegeList = $this->getCollege();
                $assignMap = [
                    "collegeList" => $collegeList,
                    "infoList"    => $row,
                    "user"        => $user,
                    "role"        => "teacher",
                    "url"         => $url,
                ];
                $this->view->assign($assignMap);
                return $this->fetch("index");
                // echo "工号为$user的老师您好，易班后台还未同步您的数据，请联系信息学院杨加玉处理。联系电话：15029484116，感谢您对易班工作的支持！";
            }

        } else {
        //请求者为学生
            $row = Db::view("stu_detail")
                    -> view("dict_college","YXDM,yb_group_id","stu_detail.YXDM = dict_college.YXDM")
                    -> where("XH",$user)
                    -> find();
            if(!empty($row) && !empty($row["SJH"]) ){
                $this->uis($user,$ismobile,$url);
            } else {
                $collegeList = $this->getCollege();
                $assignMap = [
                    "collegeList" => $collegeList,
                    "infoList"    => $row,
                    "user"        => $user,
                    "role"        => "student",
                    "url"         => $url,
                ];
                $this->view->assign($assignMap); 
                return $this->fetch("index");
            }
        }
    }

    /**
     * 完善用户信息接口
     */
    public function updateInfo()
    {

        $params = $this->request->param();
        if (empty($params["phone"]) || empty($params["college"]) || empty($params["user"]) ) {
            return json(["code" => 1, "msg" => "param error!","data" => null]);
        }
        $college = $params["college"];
        $phone   = $params["phone"];
        $user    = $params["user"];
        $url    =   $params["url"];
        if (strlen($user) == 6) {
            $result = Db::name("teacher_detail")->where("ID",$user)->update(["YXDM" => $college,"SJH" => $phone]);
            if ($result) {
                $ismobile = '';
                if(isset($_GET['mobile'])){
                    $ismobile = ($_GET['mobile'] == '1') ? true : false;
                }
                $this->uis($user,$ismobile,$url);
                // return json(["code" => 0, "msg" => "success","data" => null]);
            }
        } else {
            $result = Db::name("stu_detail")->where("XH",$user)->update(["YXDM" => $college,"SJH" => $phone]);
            if ($result) {
                $ismobile = '';
                if(isset($_GET['mobile'])){
                    $ismobile = ($_GET['mobile'] == '1') ? true : false;
                }
                $this->uis($user,$ismobile,$url);
                // return json(["code" => 0, "msg" => "success","data" => null]);
            }
        }

        return json(["code" => 1, "msg" => "error","data" => null]);
    }

    /**
     * 添加新用户信息接口
     */
    public function insertInfo()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if (empty($params["name"]) || empty($params["phone"]) || empty($params["college"]) || empty($params["user"]) ) {
                return json(["code" => 1, "msg" => "param error!","data" => null]);
            }
            $college = $params["college"];
            $phone   = $params["phone"];
            $user    = $params["user"];
            $name    = $params["name"];
            $sex     = $params["sex"];
            $role    = $params["role"];
            $url    =   $params["url"];
            if (strlen($user) == 6) {
                $insertData = [
                    "ID" => $user,
                    "XM"    => $name,
                    "XBDM"  => $sex,
                    "YXDM"  => $college,
                    "role"  => 1,
                    "SJH"   => $phone,
                ];
                $result = Db::name("teacher_detail")->insert($insertData);
                if ($result) {
                    $ismobile = '';
                    if(isset($_GET['mobile'])){
                        $ismobile = ($_GET['mobile'] == '1') ? true : false;
                    }
                    $this->uis($user,$ismobile,$url);
                    // return json(["code" => 0, "msg" => "success","data" => null]);
                }
            } else {
                $insertData = [
                    "XH"    => $user,
                    "XM"    => $name,
                    "XBDM"  => $sex,
                    "YXDM"  => $college,
                    "XSLBDM"=> $role,
                    "SJH"   => $phone,
                ];
                $result = Db::name("stu_detail")->insert($insertData);
                if ($result) {
                    $ismobile = '';
                    if(isset($_GET['mobile'])){
                        $ismobile = ($_GET['mobile'] == '1') ? true : false;
                    }
                    $this->uis($user,$ismobile,$url);
                }
            }
        }
        return json(["code" => 1, "msg" => "error","data" => null]);
    }
    
    /**
     * 绑定门户信息，将portal_id存至对应人
     * @param $params["openid"]
     * @param $params["portal_id"]
     * @return bool
     */
    private function bindPortalInfo($params)
    {
        $result = Db::name("wx_unionid_user")->where("open_id",$params["openid"])->update(["portal_id" => $params["portal_id"]]);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /**
     * 向易班发送数据包
     * @param int  $user 学号
     * @param bool $ismobile 是否为移动端
     * @param string url
     */
    public function uis($user,$ismobile,$url)
    {
        if(strlen($user) == 6){
            $row = Db::view("teacher_detail")
                    -> view("dict_college","YXDM,yb_group_id","teacher_detail.YXDM = dict_college.YXDM")
                    -> where("ID",$user)
                    -> find();
            if (!empty($row)) {
                $infoArr = array(
                    //所有身份必填项
                    'name'      =>$row['XM'],//姓名
                    'teacher_id'=>$user,//学号
                    'role'      =>$row['ROLE'],//身份（0-学生、1-辅导员、2-教师、3-其他）
                    'build_time'=>time(),//Unix时间戳
                    //认证项，至少填一项，建议学工号
                    "phone"     =>$row["SJH"],
                    'sex'       =>$row['XBDM'],
                    'college'   =>$row['yb_group_id'],//学院
                );
                // YB_Uis::getInstance()->run($infoArr,'',$ismobile);
                YB_Uis::getInstance()->run($infoArr,'',$ismobile,$url);
            } else {
                echo "工号为$user的老师您好，易班后台还未同步您的数据，请联系信息学院杨加玉处理。联系电话：15029484116，感谢您对易班工作的支持！";
            }

        } else {
        //请求者为学生
            $row = Db::view("stu_detail")
                    -> view("dict_college","YXDM,yb_group_id","stu_detail.YXDM = dict_college.YXDM")
                    -> where("XH",$user)
                    -> find();
            if(!empty($row)){
                //入学年份取学号前四位
                $enter_year = substr($user,0,4);

                $infoArr = array(
                    //所有身份必填项
                    'name'      =>$row['XM'],//姓名
                    'student_id'=>$user,//学号
                    'enter_year'=>$enter_year,//入学年份
                    'role'      =>'0',//身份（0-学生、1-辅导员、2-教师、3-其他）
                    'build_time'=>time(),//Unix时间戳
                    //认证项，至少填一项，建议学工号

                    //学生身份必填项
                    "phone"     =>$row["SJH"],
                    //'instructor_id'=>'',//辅导员工号
                    'status'    =>'0',//（0-在读、1-休学、2-离校）
                    'schooling' =>'4',//（2.5/3/4/5/7/8）
                    'education' =>'0',//（0-本科、1-大专、2-硕士、3-博士、4-中职/中专）
                    //选填项，如无法提供某项数据，移除该项
                    'sex'       =>$row['XBDM'],
                    'college'   =>$row['yb_group_id'],//学院
                    "specialty" =>"",
                    "eclass"    =>"",
                    "native_place"=>"",
                    "instructor_id"=>"",
                );
                
                //YB_Uis::getInstance()->run($infoArr,'',$ismobile,'http://f.yiban.cn/iapp195437');
                YB_Uis::getInstance()->run($infoArr,'',$ismobile,$url);
            }else{
                die('暂不支持认证');
            }
        }
    }

    private function getCollege()
    {
        $collegeList = Db::name("dict_college")
                    -> where("YXDM","<>","1500")
                    -> where("YXDM","<>","1700")
                    -> where("YXDM","<>","1800")
                    -> where("YXDM","<>","1801")
                    -> where("YXDM","<>","2101")
                    -> where("YXDM","<>","5100")
                    -> where("YXDM","<>","9999")
                    -> select();
        return $collegeList;
    }

}