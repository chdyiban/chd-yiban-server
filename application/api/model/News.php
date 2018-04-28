<?php

namespace app\api\model;

use think\Model;
use fast\Http;

class News extends Model
{
    // 表名
    protected $name = 'school_news';

    public function getNews($type = '',$page = 1){

        $map['status'] = '1';
        $map['source_type'] = $type;
        $data = $this->where($map)->limit(10)->page($page)->field('id,source_type as type,title,create_time,author')->select();
        return $data;
    }

    public function getNewsDetail($type = '',$id = 1){
        if($type == 'yiban'){
            return $this->getYibanDetail($id);
        }else{
            
            $map['status'] = '1';
            $map['source_type'] = $type;
            $map['id'] = $id;
            $data = $this->where($map)->field('id,source_type as type,block_type as block,title,create_time,author,content,attachment')->find();
            return $data;
        }
    }

    public function getSourceName($type){
        switch ($type) {
            case 'xfjy':
                $type = '先锋家园';
                break;

            case 'yiban':
                $type = '长安大学易班工作站';
                break;
            
            case 'portal':
                $type = '信息门户';
                break;

            case 'news':
                $type = '长安大学新闻网';
                break;
            default:
                $type = '长安大学';
                break;
        }

        return $type;
    }
    private function getYibanDetail($yb_id){

        $url = 'https://www.yiban.cn/forum/article/showAjax';
        $post_data = array (
            'channel_id' => '70896',
            'puid' => '5370552',
            'article_id' => $yb_id,
            'origin' => '0'
        );

        $result = json_decode(Http::post($url, $post_data),true);
        $ret_data = [];

        $ret_data['id'] = $result['data']['article']['id'];
        $ret_data['type'] = 'yiban';
        $ret_data['title'] = $result['data']['article']['title'];
        $ret_data['create_time'] = $result['data']['article']['createTime'];
        $ret_data['author'] = $result['data']['article']['author']['name'];
        $ret_data['content'] = $result['data']['article']['content'];

        return $ret_data;
    }




}