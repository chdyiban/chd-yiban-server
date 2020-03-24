<?php

namespace app\admin\model;

use think\Model;

class PolicePeople extends Model
{
    // 表名
    protected $name = 'police_people';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'sex_text',
        'type_text',
        'status_text',
        'police_station_text'
    ];

    //获取所属分类名称
    public function getcategoryname(){
        return $this->belongsTo('PoliceCategory', 'category_id');
    }

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    
    public function getSexList()
    {
        return ['0' => __('Sex 0'),'1' => __('Sex 1')];
    }     

    public function getTypeList()
    {
        return ['0' => __('Type 0'),'1' => __('Type 1'),'2' => __('Type 2'),'3' => __('Type 3'),'4' => __('Type 4')];
    }     

    public function getStatusList()
    {
        return ['1' => __('Status 1'),'2' => __('Status 2'),'0' => __('Status 0')];
    }     


    public function getSexTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['sex'];
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['type'];
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getPoliceStationList()
    {
        return ["0" => "双槐中心警务室","1" => "太和中心警务室","2" => "白马杨中心警务室","3" => "白王村中心警务室"];
    }

    public function getPoliceStationTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getPoliceStationList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
