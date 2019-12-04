<?php

namespace app\api\controller\miniapp\news;

use app\common\controller\Api;
use think\Config;
use fast\Http;


/**
 * 资讯栏目控制器
 */
class Yiban extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index(){
        $page = ($this->request->get('page')) ? $this->request->get('page') : 1;
        $group = ($this->request->get('group')) ? ($this->request->get('group')) : '0';
        $openid = $this->request->post('openid');
        $url = 'https://www.yiban.cn/forum/article/listAjax';
        $post_data = array (
            'channel_id' => '70896',
            'puid' => '5370552',
            'page' => $page,
            'size' => '10',
            'orderby' => 'updateTime',
            'Sections_id' => '-1',
            'need_notice' => '0',
            'group_id' => $group,
            'my' => '0', 
        );

        $result = json_decode(Http::post($url, $post_data),true);
        $ret_data = [];
        foreach($result['data']['list'] as $key => $val){
            $ret_data[$key]['id'] = $val['id'];
            $ret_data[$key]['type'] = 'yiban';
            $ret_data[$key]['title'] = $val['title'];
            $ret_data[$key]['style'] = '0';
            $ret_data[$key]['time'] = $val['updateTime'];
            $ret_data[$key]['views'] = $val['clicks'];
        }
        $info = [];
        if($result['code'] == 200){
            $this->success("success",$ret_data);
            // $info = [
            //     'status' => 200,
            //     'message' => 'success',
            //     'data'=>$ret_data
            // ];
        } else {
            $this->error("error");
            // $info = [
            //     'status' => -1,
            //     'message' => 'error',
            // ];
        } 
        // return json($info);
    }
}