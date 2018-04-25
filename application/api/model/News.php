<?php

namespace app\api\model;

use think\Model;

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



}