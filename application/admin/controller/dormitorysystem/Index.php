<?php

namespace app\admin\controller\dormitorysystem;
use think\Db;
use think\Config;
use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{
    
    /**
     * Dormitorylist模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Dormitory');
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index()
    {
        //获取当前管理员id的方法
        $now_admin_id = $this->auth->id;
        //总床位数
        $allBedNums = $this -> model -> getAllBedNums('all');
        //总入住人数以及男女
        $allUsedNumsList = $this -> model -> getAllStuNums('all');

        $buildingInfoList = $this-> model -> getBuildingList();


        $this->view->assign([
            'allBedNums'        => $allBedNums,
            'allBoyNums'        => $allUsedNumsList['boy'],
            'allGirlNums'       => $allUsedNumsList['girl'],
            'allStuNums'        => $allUsedNumsList['all'],
            'buildingInfoList'  => $buildingInfoList,
        ]);

        


        
        return $this->view->fetch();
    }

    public function buildingdetail()
    {
        $LH = $this -> request -> get('LH');

        $buildingMaps = Config::get('chd_building_ws_'.$LH);
        if (empty($buildingMaps)) {
            $this -> error('配置出错');
        }
        //获取已经住了人的宿舍以及住的人数
        $dormitoryUsedBedList = Db::query("SELECT SSH,COUNT(*) AS used FROM `fa_dormitory_system` WHERE LH = $LH AND status = '1' GROUP BY SSH ");
        //获取每个宿舍的总人数
        $dormitoryAllList = Db::query("SELECT SSH ,COUNT(*) AS allbed FROM `fa_dormitory_system` WHERE LH = $LH GROUP BY SSH");
        //把住了人的宿舍处理以宿舍号下标
        $dormitoryUsedBedArray = array();
        foreach ($dormitoryUsedBedList as $key => $value) {
            $k = $value['SSH'];
            $dormitoryUsedBedArray[$k] = $value;
        }
        //dump($dormitoryUsedBedArray);

        //把所有宿舍处理以宿舍号下标
        $dormitoryAllArray = array();
        foreach ($dormitoryAllList as $key => $value) {
            $k = $value['SSH'];
            $dormitoryAllArray[$k] = $value;
        }

        //dump($dormitoryAllArray);
        foreach ($buildingMaps as $key => $value) {
            foreach ($value as $k => $v) {
                if (is_numeric($v['value'])) {
                    $buildingMaps[$key][$k]['all'] = $dormitoryAllArray[$v['value']]['allbed'];
                    $buildingMaps[$key][$k]['used'] = array_key_exists($v['value'],$dormitoryUsedBedArray)? $dormitoryUsedBedArray[$v['value']]['used'] : 0;
                    $buildingMaps[$key][$k]['status'] =  $buildingMaps[$key][$k]['used'] .'/'. $buildingMaps[$key][$k]['all'];
                    if ($buildingMaps[$key][$k]['used'] ==  $buildingMaps[$key][$k]['all']) {
                        $buildingMaps[$key][$k]['color'] = 'red';
                    } elseif ($buildingMaps[$key][$k]['used'] == 0) {
                        $buildingMaps[$key][$k]['color'] = 'green';
                    } else {
                        $buildingMaps[$key][$k]['color'] = 'yellow';
                    }
               } else {
                    $buildingMaps[$key][$k]['color'] = 'rgb(84, 193, 243)';
               }
            }
        }


        $this ->view -> assign([
            'buildingMaps' => $buildingMaps,
        ]);
        return $this->view->fetch();
    }
}
