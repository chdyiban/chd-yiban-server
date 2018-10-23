<?php

namespace app\admin\controller\dormitorysystem;
use think\Db;
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

        // $buidingMaps = [
        //     [
        //         ['value' => '三层','rowspan' => '2'],
        //         ['value' => '101','status'=>'0/6','color'=>'blue'],
        //         ['value' => '102','status'=>'1/6','color'=>'green'],
        //         ['value' => '103','status'=>'6/6','color'=>'red'],
        //     ],
        //     [
        //         ['value' => '131','status'=>'0/6','color'=>'blue'],
        //         ['value' => '132','status'=>'0/6','color'=>'blue'],
        //         ['value' => '133','status'=>'0/6','color'=>'blue'],
        //     ],
        //     [
        //         ['value' => '二层','rowspan' => '2'],
        //         ['value' => '201','status'=>'0/6','color'=>'blue'],
        //         ['value' => '202','status'=>'1/6','color'=>'green'],
        //         ['value' => '203','status'=>'6/6','color'=>'red'],
        //     ],
        //     [
        //         ['value' => '231','status'=>'0/6','color'=>'blue'],
        //         ['value' => '232','status'=>'0/6','color'=>'blue'],
        //         ['value' => '233','status'=>'0/6','color'=>'blue'],
        //     ],
        // ];

        $this->view->assign([
            'allBedNums'        => $allBedNums,
            'allBoyNums'        => $allUsedNumsList['boy'],
            'allGirlNums'       => $allUsedNumsList['girl'],
            'allStuNums'        => $allUsedNumsList['all'],
            'buildingInfoList'  => $buildingInfoList,


           // 'buildingMaps' => $buidingMaps,
        ]);

        


        
        return $this->view->fetch();
    }
}
