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
    /**
     * 分配空床
     */
    /*
    public function index()
    {
        set_time_limit(0);
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
            echo "\n";
            
        };
    }
    */
    
    /**
     * 向床位分配学生
     */
    /*
    public function insert()
    {
        set_time_limit(0);
        $info = Db::name('dormitory_system_info') -> select();
        foreach ($info as $key => $value) {
            if (empty($value['XH']) || empty($value['LH']) || empty($value['SSH']) || empty($value['CH'])) {
                echo "该数据有误";
            } else {
                $YXDM = Db::name('stu_detail') -> where('XH',$value['XH']) -> find()['YXDM'];
                $res = Db::name('dormitory_system') 
                    -> where('LH',$value['LH']) 
                    -> where('SSH',$value['SSH']) 
                    -> where('CH',$value['CH']) 
                    -> update([
                        'XH' => $value['XH'],
                        'NJ' => substr($value['XH'],0,4),
                        'YXDM' => $YXDM,
                        'status' => '1',
                    ]);
                echo $res;
            }

        }
    }
    */
}