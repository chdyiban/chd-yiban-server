<?php

namespace app\api\model;
use think\Db;
use think\Model;

class Repairlist extends Model
{
    // 表名
    protected $name = 'repair_list';
    // 自动写入时间戳字段
    // protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    // protected $createTime = 'create_time';
    // protected $updateTime = 'last_visit_time';


    //将表单数据写入数据库
    public function saveData($array){
        $this->data([
            'stu_name' => $array['Name'],
            'phone' => $array['Phone'],
            'stu_id' => $array['Id'],
            'title' => $array['Title'],
            'content' => $array['Content'],
            'address_id' => $array['AddressId'],
            'address' => $array['Address'],
            'submit_time' => $array['timestamp'],
            'service_id' => $array['CategoryId'],
            'specific_id' => $array['SpecificId'],
        ]);
        $res = $this->save();
        return $res;
    }

    //获取数据库中的报修信息，并整理格式。
    public function getRepairList($stu_id){
        $res = Db::view('repair_list')
                ->view('repair_type',['id' => 'type_id','name'],'repair_list.service_id = repair_type.id')
                ->group('id')
                ->where('repair_list.stu_id',$stu_id)
                ->select();
        $info =array();
        $data = array();
        foreach($res as $val){
            switch($val['status']){
                case 'waited':
                    $val['status'] = '未受理';
                    break;
                case 'accepted':
                    $val['status'] = '已受理';
                    break;
                case 'dispatched':
                    $val['status'] = '已派工';
                    break;
                case 'finished':
                    $val['status'] = '已完工';
                    break;
                case 'refused':
                    $val['status'] = '已驳回';
                    break;
            }
            $info['bxID'] = (string)$val['id'];
            $info['wx_wxztm'] = $val['status'];
            $info['wx_bt'] = $val['title'];
            $info['wx_bxlxm'] = $val['name'];
            $info['wx_bxsj'] = date('Y-m-d H:i:s', $val['submit_time']);
            $info['xysj'] = '35分钟';
            $data[] = $info;
        }
        return $data;
    }

    public function getDetailList($id){
        //获取详情，这里需要根据状态的不同来进行不同条件的查询
        $res = $this->where('id', $id)->find();
        $data = array();
        switch($res['status']){
            case 'waited':
                $res['status'] = '未受理';
                $res['info'] =  Db::view('repair_list')
                                ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                ->where('repair_list.id',$id)
                                ->find();
                $res['worker_name'] = "无";
                $res['nickname'] = "无";
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];
                break;
            case 'accepted':
                $res['status'] = '已受理';
                $res['info'] = Db::view('repair_list')
                                ->view('admin','id,nickname','repair_list.admin_id = admin.id')
                                ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                ->where('repair_list.id',$id)
                                ->find();
                $res['worker_name'] = "无";
                $res['nickname'] = $res['info']['nickname'];
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];
                break;
            case 'dispatched':
                $res['status'] = '已派工';
                $res['info'] = Db::view('repair_list')
                                ->view('admin','id,nickname','repair_list.admin_id = admin.id')
                                ->view('repair_worker',['id'=>'id','name'=>'worker_name'],'repair_list.dispatched_id = repair_worker.id')
                                ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                ->where('repair_list.id',$id)
                                ->find();
                $res['nickname'] = $res['info']['nickname'];
                $res['worker_name'] = $res['info']['worker_name'];   
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];         
                break;
            case 'finished':
                $res['status'] = '已完工';
                $res['info'] = Db::view('repair_list')
                                ->view('admin','id,nickname','repair_list.admin_id = admin.id')
                                ->view('repair_worker',['id'=>'id','name'=>'worker_name'],'repair_list.dispatched_id = repair_worker.id')
                                ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                ->where('repair_list.id',$id)
                                ->find();
                $res['nickname'] = $res['info']['nickname'];
                $res['worker_name'] = $res['info']['worker_name']; 
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];
                break;
            case 'refused':
                $res['status'] = '已驳回';
                break;
        }
        $data['wx_bt'] = $res['title'];
        $data['wx_bxnr'] = $res['content'];
        $data['wx_wxztm'] = $res['status'];
        $data['wx_wxgm'] = $res['worker_name'];
        $data['wx_slr'] = $res['nickname'];
        $data['wx_shr'] = $res['nickname'];
        $data['wx_bxr'] = $res['stu_name'];
        $data['wx_bxrrzm'] = $res['stu_id'];
        $data['wx_bxdh'] = $res['phone'];
        $data['wx_bxlxm'] = $res['type_name'];
        $data['wx_fwqym'] = $res['areas_name'];
        $data['wx_bxdd'] = $res['address'];
        $data['wx_cxbmm']= '后勤处';
        $data['wx_bxsj'] = date('Y-m-d H:i:s', $res['submit_time']);
        $data['xysj'] = '35分钟';
        return $data;
    }

    //获取报修的类型，并且返回固定格式数据
    public function getRepairType(){
        $list = Db::name('repair_type')
                ->group('name')
                ->select();
            $data = array();
        foreach($list as $key => $val){
            $info = array();         
            $listDetail = Db::name('repair_type')
                        ->where('name',$val['name'])
                        ->select();
            $name = $val['name'];
            foreach($listDetail as $key => $value){
                $info[$key]['Name'] = $value['specific_name'];
                $info[$key]['Id'] = $value['specific_id'];
                $info[$key]['CategId'] = $value['id'];
            }    
            $data[$name] = $info;
            
        }
        return $data;
    }

}
