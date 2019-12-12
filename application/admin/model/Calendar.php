<?php

namespace app\admin\model;

use think\Model;

class Calendar extends Model
{

    // 表名
    protected $name = 'calendar';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'starttime_text',
        'endtime_text',
        'status_text'
    ];

    public function getStatuslist()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden'), 'expired' => __('Expired'), 'completed' => __('Completed')];
    }

    public function getStarttimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['starttime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['endtime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getRenderAttr($value, $data)
    {
        $allDay = ($data['starttime'] === $data['endtime'] && date("H:i:s", $data['starttime']) == '00:00:00');
        return [
            'id'              => $data['id'],
            'title'           => $data['title'],
            'start'           => date("c", $data['starttime']),
            'end'             => date("c", $data['endtime']),
            'backgroundColor' => "{$data['background']}",
            'borderColor'     => "{$data['background']}",
            'allDay'          => $allDay,
            'url'             => $data['url'],
            'className'       => "{$data['classname']} fc-{$data['status']}" . (($allDay ? $data['endtime'] + 86400 : $data['endtime']) < time() ? ' fc-expired' : '')
        ];
    }

    protected function setStarttimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setEndtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

}
