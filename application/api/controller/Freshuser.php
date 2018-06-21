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
        //$key = json_decode(base64_decode($this->request->post('key')),true);
        $user['XM'] = $this -> request -> get('XM');
        $user['XH'] = $this -> request -> get('XH');
        $user['SFZH'] = $this -> request -> get('SFZH');
        $user['ZKZH'] = $this -> request -> get('ZKZH');
        // $user['XM'] = '杨加玉';
        // $user['XH'] = '2018900005';
        // $user['SFZH'] = '610602199106150315';
        // $user['ZKZH'] = '6100123456';
        $userid = $this->check($user);
        if($userid){
            $this->_token = Random::uuid();
            Token::set($this->_token, $userid, $this->keeptime);
            Hook::listen("user_login_successed", $userid);

            $this->success('认证成功',$this->_token);
        }
        else{
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
        $info = Db::name('fresh_info') -> where('XH', $user['XH'])
                                       -> where('SFZH', $user['SFZH'])
                                       -> where('ZKZH', $user['ZKZH'])
                                       ->find(); 
        if (empty($info)) {
            return false;
        } else {
            $userid = $info['ID'];
            return $userid;
        }
    }
}