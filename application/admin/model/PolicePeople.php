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
        'police_station_text',

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
        return [
            '0' => __('正常'),
            '1' => __('易肇事肇祸精神病人'),
            '2' => __('两牢释放'),
            '3' => __('在逃'),
            '4' => __('流氓'),
            '5' => __('党员'),
            '6' => __('留守儿童'),
            '7' => __('孤残老人'),
            '8' => __('打击处理人员家属'),
            '9' => __('贫困人员'),
            '10' => __('吸毒人员'),
            '11' => __('涉访重点人'),
        ];
    }     

    public function getStatusList()
    {
        return ['1' => __('Status 1'),'2' => __('Status 2'),'0' => __('Status 0')];
    }    

    public function getPoliceStationList()
    {
        return ["1" => "双槐中心警务室","2" => "太和中心警务室","3" => "白马杨中心警务室","4" => "白王村中心警务室"];

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

    public function getPoliceStationTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['police_station'];
        $list = $this->getPoliceStationList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
