<?php
namespace app\api\controller\yiban;

use app\common\controller\Api;
use think\Config;
use think\Db;

class Ids extends Api 
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
    	header('Access-Control-Allow-Origin:*');
        header("Content-type:text/html;charset=utf-8");
        $token = $this -> request -> param('token');
        if (empty($token)) {
            $this -> error('参数非法');
        } else {
            $token_self = "65f6c288-2339-4380-8fe0-eaa0ab27ff01";
            if ($token != $token_self) {
                $this -> error('权限不足');
            }
        }
    }
    

    public function register(){
        $stu_id = $this -> request -> param('XH');
        if (empty($stu_id)) {
            $this -> error('参数非法');
        } else {
            $result = $this -> getphone($stu_id);
            if (!$result['status']) {
                $this -> error($result['msg'],$result['data']);
            } else {
                $phone = $result['data'];
                $info = array();
                $data = Db::view('fresh_info') 
                        -> view('dict_college','YXJC,yb_group_id','fresh_info.YXDM = dict_college.YXDM')
                        -> where('XH',$stu_id) 
                        -> field('ID,XH,XM,XBDM,SYD')
                        -> find();
                $info['name'] = $data['XM'];
                $info['student_id'] = $data['XH'];
                $info['role'] = '0';
                $info['build_time'] = time();
                $info['status'] = '0';
                $info['schooling'] = '4';
                $info['education'] = '0';
                $info['sex'] = $data['XBDM'];
                $info['college'] = $data['yb_group_id'];
                $info['phone'] = $phone;
                $info['enter_year'] = '2018';
                $info['specialty'] = '';
                $info['eclass'] = '';
                $info['native_place'] = $data['SYD'];
                $info['instructor_id'] = '';
                $this -> success('获取成功',$info);
            }
        }
    }
    /**
     * 获取联系方式
     * @param int stu_id
     * @return array
     */
    private function getphone($stu_id){
        $phone = Db::name('fresh_questionnaire_first') -> where('XH', $stu_id) -> field('BRDH') -> find();
        if (empty($phone)) {
            return ['status' => false, 'msg' => '请先在选宿舍系统填写调查问卷' , 'data' => null];
        } else {
            return ['status' => true, 'msg' => '查询成功' , 'data' => $phone['BRDH']];
        }
    }


}