<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Token;
use fast\Random;
use think\Config;
use think\Db;
use think\Hook;
use think\Request;
use think\Validate;

/**
 * 选房会员登录系统，另一套派发，所以原来的权限和是否需要登录完全失效
 * 
 * @author Yang
 */
class Freshuser extends Api
{
    protected $noNeedLogin = ['login','signout'];
    protected $noNeedRight = ['login','signout'];

    protected $_token = '';
    //Token默认有效时长
    protected $keeptime = 2592000;

    public function login(){
        header('Access-Control-Allow-Origin:*');
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $userid = $this->check($key);

        if($userid){
            $this->_token = Random::uuid();
            Token::set($this->_token, $userid, $this->keeptime);
            Hook::listen("user_login_successed", $userid);
            //判断是否是否信息填完并且是否需要修改
            $steps = $this -> getSteps($userid);
            $info = [$steps, $this -> _token];
            $this->success('认证成功',$info);
        } else {
            $this->error('认证失败','请检查学号以及密码是否正确');
        }
        //dump($this->_token);
    }
    /**
     * 随机返回一个用户的信息
     */
    public function testuser()
    {
        header('Access-Control-Allow-Origin:*');
        $count = Db::name('fresh_info') -> count();
        $id = rand(1,$count);
        $info = Db::name('fresh_info') -> where('id',$id) ->field('XH,SFZH') -> find();
        $info['password'] = !empty($info['SFZH']) ? substr($info['SFZH'], -6) : null;
        $this -> success('获取成功', $info);
    }
    
    public function signout()
    {
        header('Access-Control-Allow-Origin:*');
        $token = $this->request->param('token');
        $loginInfo = $this -> isLogin($token);
        $token_status = Token::has($token, $loginInfo['user_id']);
        if ($token_status) {
            $info = Token::rm($token);
            return $this->success('已经退出');
        } else {
            $this->error('账号不存在');
        }
    }

    protected function isLogin($token){
        
        $data = Token::get($token);
        if(count($data)){
            return $data;
        }else{
            return false;
        }
    }

    protected function check($user){
        //新生数据库进行比对，若成功则返回userid ，若不成功返回false
        //身份证号没有提供则登录方式为准考证号登录
        if (empty($user['XH'] || empty($user['SFZH']))) {
            return false;
        } else{
            $info = Db::name('fresh_info')
                        -> where('XH', $user['XH'])
                        -> field('SFZH,ID')
                        ->find(); 
            if (empty($info)) {
                return false;
            } else {
                $id_card = $info['SFZH'];
                $password = substr($id_card, -6);
                if ($user['SFZH'] == $password) {
                    $userid = $info['ID'];
                    return $userid;
                } else {
                    return false;
                }
            }
        }
    }

    protected function getSteps($userId){
        $personalMsg = Db::name('fresh_info') -> where('ID', $userId) ->field('XH') ->find();
        $stu_id = $personalMsg['XH'];
        //判断信息是否完善
        $isInfoExist = Db::name('fresh_info_add') -> where('XH', $stu_id) -> field('ID,XH') -> find();
        $isListExist = Db::name('fresh_list') -> where('XH', $stu_id) -> field('ID,XH,status') -> find();
        if (empty($isInfoExist)) {
            return 'setinfo';
        } elseif (empty($isListExist)) {
            return 'select';
        } else {
            //wait finished
            return $isListExist['status'];
        }
        
    }


}