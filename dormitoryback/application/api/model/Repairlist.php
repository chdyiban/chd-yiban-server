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
            'image' => json_encode($array['ImgUrl']),
            'address_id' => $array['AddressId'],
            'address' => $array['Address'],
            'submit_time' => time(),
            'service_id' => $array['CategoryId'],
            'specific_id' => $array['SpecificId'],
        ]);
        $res = $this->save();
        return $this->id;
    }

    //获取数据库中的报修信息，并整理格式。
    public function getRepairList($stu_id){
        $res = Db::name('repair_list')
                    ->where('stu_id',$stu_id)
                    ->order('id DESC')
                    //->field('id,status,title,submit_time,accepted_time,service_id,refused_content')
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
                case 'distributed':
                    $val['status'] = '已指派';
                    break;
                case 'dispatched':
                    $val['status'] = '已派工';
                    break;
                case 'finished':
                    $val['status'] = '已完工';
                    break;
                case 'refused':
                    $val['status'] = '驳回';
                    break;
            }
            $info['bxID'] = (string)$val['id'];
            $info['wx_wxztm'] = $val['status'];
            $info['wx_bt'] = $val['title'];
            if (!empty($val['service_id'])) {
                $type_name = Db::name('repair_type') -> where('id',$val['service_id']) -> group('id') -> find()['name'];
            } else {
                $type_name = "未知";
            }

            if (empty($val['accepted_time']) && empty($val['refused_time'])) {
                $info['wx_xysj'] = '-';
            } else if(!empty($val['refused_time'])) {
                $wx_xysj = ($val['refused_time'] -$val['submit_time']);
                if ($wx_xysj < 60) {
                    $info['wx_xysj'] = $wx_xysj.'秒';
                } else {
                    $wx_xysj = intval($wx_xysj/60);
                    $info['wx_xysj'] = $wx_xysj.'分钟';
                }
                //响应时间
            } else if(!empty($val['accepted_time'])){
                $wx_xysj = ($val['accepted_time'] - $val['submit_time']);
                //响应时间
                if ($wx_xysj < 60) {
                    $info['wx_xysj'] = $wx_xysj.'秒';   
                } else {
                    $wx_xysj = intval($wx_xysj/60);
                    $info['wx_xysj'] = $wx_xysj.'分钟';
                }
            }

            if (empty($val['finished_time'])) {
                $info['wx_wgsj'] = '-';
            } else {
                $wx_wgsj = intval(($val['finished_time'] -$val['submit_time'])/60);
                //响应时间
                $info['wx_wgsj'] = $wx_wgsj.'分钟';
            }
            //拒绝原因
            if (empty($val['refused_content'])) {
                $info['wx_jjyy'] = ' ';
            } else {
                $info['wx_jjyy'] = $val['refused_content'];
            }
            $info['wx_bxlxm'] = $type_name;
            $info['wx_bxsj'] = date('Y-m-d H:i:s', $val['submit_time']);
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
                $res['status'] = 'waited';
                $res['info'] =  Db::view('repair_list')
                                ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                ->where('repair_list.id',$id)
                                ->find();
                $res['worker_name'] = "";
                $res['nickname'] = "";
                $res['worker_phone'] = "";
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];
                break;
            case 'accepted':
                $res['status'] = 'accepted';
                $res['info'] = Db::view('repair_list')
                                ->view('admin','id,nickname','repair_list.admin_id = admin.id')
                                ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                ->where('repair_list.id',$id)
                                ->find();
                $res['worker_name'] = "";
                $res['worker_phone'] = "";
                $res['nickname'] = $res['info']['nickname'];
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];
                break;
            case 'distributed':
                $res['status'] = 'distributed';
                $res['info'] = Db::view('repair_list')
                                ->view('admin','id,nickname','repair_list.admin_id = admin.id')
                                ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                ->where('repair_list.id',$id)
                                ->find();
                $res['worker_name'] = "";
                $res['worker_phone'] = "";
                $res['nickname'] = $res['info']['nickname'];
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];
                break;
            case 'dispatched':
                $res['status'] = 'dispatched';
                $res['info'] = Db::view('repair_list')
                                ->view('admin','id,nickname','repair_list.admin_id = admin.id')
                                ->view('repair_worker',['id'=>'id','name'=>'worker_name','mobile'],'repair_list.dispatched_id = repair_worker.id')
                                ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                ->where('repair_list.id',$id)
                                ->find();
                $res['nickname'] = $res['info']['nickname'];
                $res['worker_name'] = $res['info']['worker_name'];   
                $res['worker_phone'] = $res['info']['mobile'];
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];         
                break;
            case 'finished':
                $res['status'] = 'finished';
                $res['info'] = Db::view('repair_list')
                                ->view('admin','id,nickname','repair_list.admin_id = admin.id')
                                ->view('repair_worker',['id'=>'id','name'=>'worker_name','mobile'],'repair_list.dispatched_id = repair_worker.id')
                                ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                ->where('repair_list.id',$id)
                                ->find();
                $res['nickname'] = $res['info']['nickname'];
                $res['worker_name'] = $res['info']['worker_name'];
                $res['worker_phone'] = $res['info']['mobile']; 
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];
                break;
            case 'refused':
                $res['status'] = 'refused';
                $res['info'] = Db::view('repair_list')
                                    ->view('repair_areas',['id'=>'id','name'=>'areas_name'],'repair_list.address_id = repair_areas.id')
                                    ->view('repair_type',['id'=>'id','name'=>'type_name'],'repair_list.service_id = repair_type.id')
                                    ->where('repair_list.id',$id)
                ->find();
                $res['nickname'] = "";
                $res['worker_name'] = ""; 
                $res['worker_phone'] = ""; 
                $res['type_name'] = $res['info']['type_name']; 
                $res['areas_name'] = $res['info']['areas_name'];
                break;
        }
        if(empty($res['star']) && empty($res['message'])){
            $data['comment']['status'] = false; 
        }else{
            $data['comment']['status'] = true; 
            $data['comment']['star'] = $res['star'];
            $data['comment']['message'] = $res['message'];  
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
        $data['wx_wxgdh'] = $res['worker_phone'];
        $data['wx_bxlxm'] = $res['type_name'];
        $data['wx_fwqym'] = $res['areas_name'];
        $data['wx_bxdd'] = $res['address'];
        //拒绝原因
        if (empty($res['refused_content'])) {
            $data['wx_jjyy'] = ' ';
        } else {
            $data['wx_jjyy'] = $res['refused_content'];
        }
        $data['wx_wxzp'] = json_decode($res['image']);
        //承修部门
        if (!empty($res['distributed_id'])) {
            $cxbmm = Db::name('admin') -> where('id',$res['distributed_id']) -> field('nickname') -> find()['nickname'];
        } else {
            $cxbmm = "自修";
        }
        $data['wx_cxbmm']= $cxbmm;

        if (empty($res['accepted_time'])) {
            $data['wx_xysj'] = '-';
        } else {
            $wx_xysj = ($res['accepted_time'] -$res['submit_time'])%60;
            //响应时间
            $data['wx_xysj'] = $wx_xysj.'分钟';
        }

        if (empty($res['finished_time'])) {
            $data['wx_wgsj'] = '-';
        } else {
            $wx_wgsj = ($res['finished_time'] -$res['submit_time'])%60;
            //响应时间
            $data['wx_wgsj'] = $wx_wgsj.'分钟';
        }
        $data['wx_bxsj'] = date('Y-m-d H:i:s', $res['submit_time']);
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
