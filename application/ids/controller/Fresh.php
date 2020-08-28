<?php
namespace app\ids\controller;

use think\Db;
use think\Loader;
use think\Config;
use think\Session;
use think\Controller;

/**
 * 新生选宿舍系统跳转
 */
class Fresh extends Controller {
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
        $ismobile = false;
        // if(isset($_GET['mobile'])){
        //     $ismobile = ($_GET['mobile'] == '1') ? true : false;
        // }
        if($user == ''){
            die('unkown error');
        }
        // $user=$this->request->param("ID");
        //请求者为学生
        $row = Db::view("fresh_info")
                -> view("dict_college","YXDM,yb_group_id","fresh_info.YXDM = dict_college.YXDM")
                -> where("XH",$user)
                -> find();
        if(!empty($row) && !empty($row["LXDH"]) ){
            $this->uis($user,$ismobile,$url);
        } else {
            return ["status" => "false","msg" => "请完成家庭问卷调查表"];
        }
        
    }
    /**
     * 向易班发送数据包
     * @param int  $user 学号
     * @param bool $ismobile 是否为移动端
     * @param string url
     */
    public function uis($user,$ismobile,$url)
    {

        //请求者为学生
        $row = Db::view("fresh_info")
                -> view("dict_college","YXDM,yb_group_id","fresh_info.YXDM = dict_college.YXDM")
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
                "phone"     =>$row["LXDH"],
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