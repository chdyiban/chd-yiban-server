<?php

namespace app\api\controller\yiban;
use app\common\controller\Api;
use app\api\controller\yiban\YB_Uis;
use think\Db;
use app\common\library\Token;

class Yiban extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        header("Content-Type: text/html; charset=utf-8");
        session_start();
        
        // require_once dirname(__FILE__)."/CAS/CAS.php";
        require_once dirname(__FILE__)."/YB_Uis.php";
        $XH = $this->request->get("ID");
        $nj = substr($XH,0,4);
        $info = array();

        if (strlen($XH) == 6) {
            $data = Db::view('teacher_detail') 
                    -> view('dict_college','YXJC,yb_group_id','teacher_detail.YXDM = dict_college.YXDM')
                    -> where('ID',$XH) 
                    -> field('ID,XM,XBDM')
                    -> find();
            if (empty($data)) {
                $this->error("param error");
            }
            $phone = $data["SJH"];
            $info['name'] = $data['XM'];
            $info['teacher_id'] = $data["ID"];
            $info['role'] = '2';
            $info['build_time'] = time();
            $info['sex'] = $data['XBDM'];
            $info['college'] = $data['yb_group_id'];
            $info['phone'] = $phone;
        } else {
            $data = Db::view('stu_detail') 
                    -> view('dict_college','YXJC,yb_group_id','stu_detail.YXDM = dict_college.YXDM')
                    -> where('XH',$XH) 
                    -> field('XH,XM,XBDM')
                    -> find();
            if (empty($data)) {
                $this->error("param error");
            }
            $phone = $data["SJH"];
            $info['name'] = $data['XM'];
            $info['student_id'] = $data["XH"];
            $info['role'] = '0';
            $info['build_time'] = time();
            $info['status'] = '0';
            $info['schooling'] = '4';
            $info['education'] = '0';
            $info['sex'] = $data['XBDM'];
            $info['college'] = $data['yb_group_id'];
            $info['phone'] = $phone;
            $info['enter_year'] = $nj;
            $info['specialty'] = '';
            $info['eclass'] = '';
            $info['native_place'] = "";
            $info['instructor_id'] = '';
        } 
        $ismobile = '';
        if(isset($_GET['mobile'])){
            $ismobile = ($_GET['mobile'] == '1') ? true : false;
        }
        YB_Uis::getInstance()->run($info,'',$ismobile);
    }
}