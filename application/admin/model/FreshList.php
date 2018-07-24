<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use think\Cache;

class FreshList extends Model
{
    // 表名
    protected $name = 'fresh_list';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    // 返回
    public function getTableData()
    {
        $college = Db::name('dict_college') ->field('YXDM, YXJC') -> select();
        array_pop($college);
        $cache_expire_time = 0;
        $cache_model = new Cache();//实例化缓存模型
        $cache_key = 'college_bed_num';
        $bed_num = $cache_model -> get($cache_key);//获取缓存
        if ($bed_num) {
            $college = $bed_num;
        } else {
            foreach ($college as $key => $value) {
                $num = 0;
                $info = Db::name('fresh_dormitory') 
                                -> where('YXDM', $value['YXDM'])
                                -> field('CPXZ')
                                -> select();
                foreach ($info as $k => $v) {
                   $num = $num + strlen($v['CPXZ']);
                }
                $college[$key]['bed_num'] = $num;
               
            }
            $cache_model -> set($cache_key, $college, $cache_expire_time);//设置缓存
        }

        
        foreach ($college as $key => $value) {
            $college_id = $value['YXDM'];
            $finished_num = $this -> where('YXDM', $college_id) -> where('status', 'finished') -> count();
            $college[$key]['finished_num'] = $finished_num;
            $college[$key]['rest_num'] = $value['bed_num'] - $finished_num;
        }
        return $college;
    }
    
    /**
     * 获取统计信息根据楼号
     */
    public function getBuilding()
    {
        $info = array();
        $building = Db::name('fresh_dormitory') ->field('LH')-> group('LH') -> select();
        foreach ($building as $key => $value) {
            $info[$key]['building'] = $value['LH'];
            $dormitory = Db::name('fresh_dormitory') 
                            -> where('LH', $value['LH']) 
                            -> where('SYRS', '>=', '1')
                            -> field('SYRS')
                            -> select();
            $rest_dormitory_num = count($dormitory);
            $rest_bed_num = 0;
            foreach ($dormitory as $k => $v) {
                $rest_bed_num += $v['SYRS']; 
            }
            $info[$key]['rest_dormitory_num'] = $rest_dormitory_num;
            $info[$key]['rest_bed_num'] = $rest_bed_num;
           // $info[$key]['dormitory_number'] = 
        }
        return $info;

    }
    /**
     * 获取所有订单列表
     */

    public function getList($keys, $filter)
    {
        $info =  array();
        $list = Db::view('fresh_info') 
                //-> view('fresh_info','XM, XH, SYD, XBDM','fresh_list.XH = fresh_info.XH')
                -> view('dict_college','YXJC,YXDM','fresh_info.YXDM = dict_college.YXDM')
                -> select();
        foreach ($list as $key => $value) {
            $data = Db::name('fresh_list') -> where('XH', $value['XH']) ->  where('status','finished') -> find();
            $info[$key] = $value;
            if (empty($data)) {
                $info[$key]['option'] = '否';
                $info[$key]['LH'] = '-';
                $info[$key]['SSH'] = '-';
                $info[$key]['CH'] = '-';
                
            } else {
                $info[$key]['LH'] = explode('#', $data['SSDM'])[0];
                $info[$key]['SSH'] = explode('#', $data['SSDM'])[1];
                $info[$key]['CH'] = $data['CH'];
                $info[$key]['option'] = '是';
            }
            $info[$key]['SYD'] =  $info[$key]['SYD'];
            $info[$key]['MZ'] =  $info[$key]['MZ'];
            $info[$key]['XB'] = $info[$key]['XBDM'] == 1 ? '男' : '女';
        }
        //遍历进行筛选
        if (empty($keys) || empty($filter)) {
            return ['data' => $info, 'count' => count($info)];
        } else {
            foreach ($info as $key => $value) {
                foreach ($keys as $v) {
                    $map = $filter[$v];
                    if ($value[$v] != $map) {
                        unset($info[$key]);
                    }
                }
            }
            $list = array();
            foreach ($info as $k => $v) {
                $list[] = $v;
            }
            
            return ['data' => $list, 'count' => $list];
        }
    }

    public function getStatusList()
    {
        return ['waited' => __('Waited'),'finished' => __('Finished')];
    }     


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
