<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use app\api\model\Dormitory as DormitoryModel;
/**
 * 
 */
class Dormitory extends Freshuser
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    private $loginInfo = null;
    private $token = null;
    private $userInfo = null;

    function _initialize(){
        header('Access-Control-Allow-Origin:*');
        $this -> token = $this->request->param('token');
        $this -> loginInfo = $this->isLogin($this -> token);
        if(!$this->loginInfo){
            $this->error('失败','参数非法');
        }
        $this -> userInfo = $this -> get_info($this -> token);

    }
    /**
     * demo 可以这样实现。
     */
    public function index(){
        dump($this->loginInfo);
        echo 'index method';
    }

    /**
     * 展示可选择宿舍楼以及宿舍号接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * @param string $building 
     * @param string $dormitory
     * @param string $type 有building, dormitory, bed
     * @return array $list 包含楼号/宿舍号/床号
     */
    public function show()
    {
        //$key = json_decode(base64_decode($this->request->post('key')),true);
        $type = $this -> request->get('type');
        $building = $this -> request->get('building');
        $dormitory = $this -> request->get('dormitory');
        $key = [
            'building' => $building,
            'dormitory' => $dormitory,
            'type' => $type,
        ];
        $DormitoryModel = new DormitoryModel;
        $list = $DormitoryModel -> show($this->userInfo,$key);
        if ($list) {
            $this -> success('查询成功', $list);
        } else {
            $this -> error('请求失败', $list);
        }   
    }
    /**
     * 提交选择调用接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * @param string $dormitory_id
     * @param string $bed_id
     * @return array data => true/false 
     */
    public function submit()
    {
        //$key = json_decode(base64_decode($this->request->post('key')),true);
        $key = [
            // 'stu_id' => '2018900001',
            // 'college_id' => '2400',
            // 'sex'  => '1',
            // 'place' => '陕西省',
            'dormitory_id' => '15#101',
            'bed_id' => '2',
        ];
        $DormitoryModel = new DormitoryModel;
        $info = $DormitoryModel -> submit($this -> userInfo, $key);
        if ($info[1]) {
            $this -> success($info[0], $info[1]);
        } else {
            $this -> error($info[0], $info[1]);
        }   
    }

    /**
     * 确认床铺接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * @param string type confirm/cancel
     * 
     */
    public function confirm()
    {
        //$key = json_decode(base64_decode($this->request->post('key')),true);
        $key = [
            'stu_id' => '2018900001',
            'college_id' => '2400',
            'sex'  => '1',
            'place' => '陕西省',
            'dormitory_id' => '15#101',
            'bed_id' => '2',
            'type' => 'confirm',
        ];
        $DormitoryModel = new DormitoryModel;
        $info = $DormitoryModel -> confirm($key);
        if ($info[1]) {
            $this -> success($info[0], $info[1]);
        } else {
            $this -> error($info[0], $info[1]);
        }   

    }

    /**
     * 宿舍确定结束接口
     * @param array $infomation ['stu_id', 'college_id', 'sex', 'place']
     * 
     */
    public function finished()
    {
        //$key = json_decode(base64_decode($this->request->post('key')),true);
        $key = [
            'stu_id' => '2018900001',
            'college_id' => '2400',
            'sex'  => '1',
            'place' => '陕西省',
        ];
        $DormitoryModel = new DormitoryModel;
        $info = $DormitoryModel -> finished($key);
        $this -> success('选择完成，查看室友信息', $info);
    }

    /**
     * 通过token获取学生信息
     * @param string $token
     */
    private function get_info($token)
    {
        $user_id = $this->loginInfo['user_id'];
        $list = Db::name('fresh_info') -> where('ID', $user_id) -> find();
        if ($list) {
            $info['stu_id'] = $list['XH'];
            $info['place'] = $list['SYD'];
            $info['college_id'] = $list['YXDM'];
            $info['sex'] = $list['XBDM'];
            return $info;
        } else {
            return false;
        }
    }

}







