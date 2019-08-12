<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class Adviser extends Model
{
    // 表名
    protected $name = 'bzr_adviser';

    public function insertAdviser($params)
    {
        $res = $this -> where('GH' , $params['GH']) 
                -> update([
                    'BHCS' => $params['BHCS'],
                    'FDXS' => $params['FDXS'],
                    'HDCS' => $params['HDCS'],
                    'timestamp' => time(),
                ]);

        if ($res) {
            return ['status' => true, 'msg' => "填写成功"];
        } else {
            return ['status' => false, 'msg' => "失败"];
        }
    }


}
