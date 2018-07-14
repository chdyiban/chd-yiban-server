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
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

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
            $this->error('认证失败','请检查姓名、身份证号及准考证号等信息是否填写完成');
        }

        
        //dump($this->_token);
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
        $info = Db::name('fresh_info')
                    -> where('XH', $user['XH'])
                    -> where('ZKZH', $user['ZKZH'])
                    ->find(); 
        if (empty($info)) {
            return false;
        } else {
            $userid = $info['ID'];
            return $userid;
        }
    }

    protected function getSteps($userId){
        $personalMsg = Db::name('fresh_info') -> where('ID', $userId) ->find();
        $stu_id = $personalMsg['XH'];
        //判断信息是否完善
        $isInfoExist = Db::name('fresh_info_add') -> where('XH', $stu_id) -> find();
        $isListExist = Db::name('fresh_list') -> where('XH', $stu_id) -> find();
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