<?php

namespace app\admin\controller\dormitory;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Dormitorylist extends Backend
{
    
    /**
     * FreshList模型对象
     * @var \app\admin\model\FreshList
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('FreshList');
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
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            set_time_limit(0);
            $filter = urldecode($this -> request -> request('filter'));
            $op = urldecode($this -> request -> request('op'));
            $filter = json_decode($filter, true);
            $op = json_decode($op, true);
            $keys_op = array_keys($op);
            $keys_filter = array_keys($filter);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $info = $this -> model -> getList($keys_op, $op, $keys_filter, $filter);

            $total = $info['count'];
            $data = $info['data'];

            
            //判断是否是导出文件
            if (empty($offset) && empty($limit)) {
                $result = array("total" => $total, "rows" => $data);
                return json($result);
            } else {
                //遍历进行分页
                $list = array();
                foreach ($data as $key => $value) {
                    if ($key >=  $offset && $key < ($offset + $limit) ) {
                        $list[] = $value;
                    }
                } 
                $result = array("total" => $total, "rows" => $list);
                return json($result);
            }
        }
        return $this->view->fetch();
    }


    public function college()
    {
        $info = array();
        $data = Db::name('dict_college') -> field('YXDM, YXJC') -> select();
        foreach ($data as $key => $value) {
            if ($value['YXDM'] == 9999 || $value['YXDM'] == 5100 || $value['YXDM'] == 1800 || $value['YXDM'] == 1801 || $value['YXDM'] == 1700) {
                unset($data[$key]);
            } else {
                $info[] = $value;
            }
            // $info[$key]['value'] = $value['YXDM'];
        }
        $total = count($info);
        $result = array("total" => $total, "rows" => $info);
        return json($result);
    }

    public function building()
    {
        $info = array();
        $data = Db::name('fresh_dormitory') -> group('LH') -> field('LH') -> select();
        foreach ($data as $key => $value) {
            $info[] = $value;
        }
        $total = count($info);
        $result = array("total" => $total, "rows" => $info);
        return json($result);
    }

    public function sex()
    {
        $info = array(
            ['XB' => '男'],
            ['XB' => '女'],
        );
        $result = array('total' => 2, 'rows' => $info);
        return json($result);
    }

    public function nation()
    {
        $nation = Db::name('fresh_info') -> group('MZ') ->field('MZ') -> select();
        $info = array();
        foreach ($nation as $key => $value) {
            $info[] = $value;
        }
        $total = count($info);
        $result = array('total' => $total, 'rows' => $info);
        return json($result);
    }

    public function place()
    {
        $nation = Db::name('fresh_info') -> group('SYD') ->field('SYD') -> select();
        $info = array();
        foreach ($nation as $key => $value) {
            $info[] = $value;
        }
        $total = count($info);
        $result = array('total' => $total, 'rows' => $info);
        return json($result);
    }
    public function option()
    {
        $info = array(
            ['option' => '是'],
            ['option' => '否'],
        );
        $result = array('total' => 2, 'rows' => $info);
        return json($result);
    }
    

}
