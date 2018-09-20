<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Loader;
use think\Db;

/**
 * 竞赛模块
 */
class Competition extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['test1','biu','getRongCloudToken'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2','biu','getRongCloudToken'];

    public function init(){

        $data['page'] = '2';
        $data['tips'] = 'this is a tips';

        $this->success('返回成功', $data);
    }

    /**
     * 与客户端进行长轮询通信
     * 
     */
    public function biu(){
        set_time_limit(0); 
        $prev_val = Db::name('competition_log')->order('id desc')->field('content')->find();
        $next_val = '';
        while(true){
            $next_val = Db::name('competition_log')->order('id desc')->field('content')->find();
            if($prev_val!==$next_val){
                break;
            }
            usleep(3000);
        }

        $this->success('返回成功',$next_val);
    }

    public function manage(){
        $this->success('返回成功', 'manage success');
    }

    public function getRongCloudToken(){
        Loader::import('rongcloud', EXTEND_PATH.'/API');
        $appKey = 'e0x9wycfe45wq';
        $appSecret = 'q0oc3EpVoFkc';
        $jsonPath = "jsonsource/";
        $RongCloud = new \RongCloud($appKey,$appSecret);
        echo ("\n***************** user **************\n");
        // 获取 Token 方法
        $result = $RongCloud->user()->getToken('userId1', 'username', 'http://www.rongcloud.cn/images/logo.png');
        echo "getToken    ";
        print_r($result);
        echo "\n";
    }

}