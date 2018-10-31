<?php

namespace app\admin\controller\dormitorysystem;
use think\Db;
use think\Config;
use app\common\controller\Backend;

/**
 * 
 *
 *
 */
class Insert extends Backend
{
    public function index()
    {
        $info = Db::name('dormitory_system_inner') -> select();
        foreach ($info as $key => $value) {
            $temp = array();
            $temp['XQ'] = '渭水';
            $temp['LH'] = $value['LH'];
            $temp['LC'] = $value['LC'];
            $temp['LD'] = $value['LD'];
            $temp['SSH'] = $value['SSH'];
            $temp['XH'] = '';
            $temp['NJ'] = '';
            $temp['YXDM'] = '';
            $temp['XBDM'] = $value['XBDM'];
            $temp['status'] = $value['status'];
            for ($i = 1; $i <= $value['CHS'] ; $i++) { 
                $temp['CH'] = $i;
                $res = Db::name('dormitory_system') -> insert($temp);
                echo $res;
            }
            
        };
    }
}