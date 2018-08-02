<?php

namespace app\api\controller;

use app\common\controller\Api;
use wechat\wxBizDataCrypt;
use think\Db;
/**
 * 首页接口
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $array = array(
            'XH' => '2018900001',
            'SFZH' => '270734',
        );
        dump(base64_encode(json_encode($array)));
        $college_id = 2400;
        $sex = 1;
        $data = DB::name('fresh_dormitory') -> where('YXDM',$college_id)
                -> where('XB', $sex)
                -> group('LH')
                -> field('LH')
                -> select();
        //$data = array_values($data);
        dump($data);
        $this->success('请求成功');
    }

}
