<?php

namespace app\api\controller\yiban;
use app\common\controller\Api;
use app\api\controller\yiban\YB_Uis;
use think\Db;
use app\common\library\Token;

class Fresh extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        header("Content-Type: text/html; charset=utf-8");
        session_start();
    
        $token = $this->request->get("token");
        $type = $this->request->get("type");
        $data = Token::get($token);
        if (empty($data)) {
            return json(["code" => 1,"msg" => "请先登录", "data" => null]);
        }
        $user_id = intval($data['user_id']);
        $userInfo = Db::view("fresh_info")
				-> view("dict_college","YXDM,yb_group_id,YXMC","fresh_info.YXDM = dict_college.YXDM")
				-> view("fresh_questionnaire_first","BRDH,XH","fresh_info.XH = fresh_questionnaire_first.XH")
                -> where("fresh_info.ID",$user_id)
                -> find();
        if (empty($userInfo)) {
            return json(["code" => 1,"msg" => "非法请求", "data" => null]);            
        }
		if (empty($userInfo["BRDH"])) {
			return json(["code" => 1,"msg" => "请先完成家庭经济问卷调查", "data" => null]);            
		}
    
        $infoArr = array(
        //所有身份必填项
            'name'			=> $userInfo["XM"],//姓名
            'student_id'	=> $userInfo["XH"],//学号
            'role'			=> '0',//身份（0-学生、1-辅导员、2-教师、3-其他）
            'build_time'	=> time(),//Unix时间戳
            'status'		=> 0,
            'schooling'		=> '4',
            'education'		=> '0',
            'sex'			=> $userInfo["XBDM"],
            'college'     	=> $userInfo["yb_group_id"],//学院
            'phone'       	=> $userInfo["BRDH"],
            'enter_year'  	=> '2019',
            'specialty'   	=> '',
            'eclass'      	=> '',
            'native_place'	=> '',
            'instructor_id'	=> '',//辅导员工号
        );
        // $goto = 'https://www.yiban.cn/forum/article/show/channel_id/70896/puid/5370552/article_id/45394140/group_id/0';
        if ($type == "join") {
            // $goto = "https://www.yiban.cn/forum/article/show/channel_id/70896/puid/5370552/article_id/87061346/";
            $goto = "https://www.yiban.cn/forum/article/show/article_id/131872564/channel_id/70896/puid/5370552";
        } else {
            $goto = 'http://www.yiban.cn/Org/orglistShow/type/forum/puid/5370552';
        }
        YB_Uis::getInstance()->run($infoArr,'',false,$goto);
    }
    
}