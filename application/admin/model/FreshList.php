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
