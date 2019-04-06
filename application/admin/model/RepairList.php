<?php

namespace app\admin\model;
use think\Db;
use think\Model;
use \WeChat\Template;
use \WeChat\Qrcode;
use think\Config;

class RepairList extends Model
{
    // 表名
    protected $name = 'repair_list';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    //为工人发的模板消息id
    const WORKER_TEMPLATE_ID = "BNtZm-iUDytuPjYpo1iu1fLC0LfMEbH9lhKWeE99yeo";
    //为公司发的模板消息id
    const COMPANY_TEMPLATE_ID = "hyHcF_da4GLq1_4-SxIejrl1O92eMQkJzkc8mw3LImU";
    //模板消息跳转url
    const TEMPLATE_URL = " https://yiban.chd.edu.cn/index/Bx/";

    public function getStatusList()
    {
        return ['waited' => __('Status waited'),'accepted' => __('Status accepted'),'distributed' => __('Status distributed'),'dispatched' => __('Status dispatched'),'finished' => __('Status finished'),'refused' => __('Status refused')];
    }     


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    //获取受理人名称
    public function getname(){
        return $this->belongsTo('Admin', 'admin_id');
    }
    //获取工人名称
    public function getworkername()
    {
        return $this -> belongsTo('RepairWorker','dispatched_id');
    }

    //获取报修类型
    // public function gettype(){
    //     return $this->belongsTo('RepairType', 'service_id');
    // }
    
    //获取报修类型
    public function gettypename(){
        return $this->belongsTo('RepairType', 'specific_id');
    }

    //获取分配的单位的名称
    public function getcompany(){
        return $this->belongsTo('Admin', 'distributed_id');
    }

    //获取地址名称
    public function getaddress(){
        return $this->belongsTo('RepairAreas', 'address_id');
    }

    //处理数据用来显示
    public function redata($row){
        // $time = time();
        // $res = $this->where('id ='.$ids)->update(['status' => 'accepted', 'admin_id'=> $admin_id]);
        // $this->where('id = '.$ids)->update(['accepted_time' => $time]);
        $row['accepted_time'] = date('Y-m-d H:i:s',$row['accepted_time']);
        $row['distributed_time'] = date('Y-m-d H:i:s',$row['distributed_time']);
        $row['dispatched_time'] = date('Y-m-d H:i:s',$row['dispatched_time']);
        $row['finished_time'] = date('Y-m-d H:i:s',$row['finished_time']);
        $row['refused_time'] = date('Y-m-d H:i:s',$row['refused_time']);
        $row['new_time'] = date('Y-m-d H:i:s',$row['new_time']);
        return $row;
    }
   
    //受理报修内容
    public function accept($ids, $admin_id){
        $time = time();
        $res = $this->where('id ='.$ids)->update(['status' => 'accepted', 'admin_id'=> $admin_id]);
        $this->where('id = '.$ids)->update(['accepted_time' => $time]);
        return $res;
    }

    //分配单位并且如果不是自修则发送微信通知
    public function distribute($ids,$company_id){
        $time = time();
        $companyInfo = Db::name('admin')->where('id',$company_id) -> field('id,nickname') ->find();
        if ($companyInfo['nickname'] != "自修") {
            // 不进行未绑定微信提示
            $bindInfo = Db::name('repair_bind') -> where('type',1) -> where('user_id',$companyInfo['id']) -> find();
            // if (empty($bindInfo)) {
            //     return ['status' => true,'msg' => "该公司尚未绑定微信，请联系。"];
            // }
            if (!empty($bindInfo)) {
                $url = self::TEMPLATE_URL."wxrouter?func=dispatch&list_id=$ids";
                $open_id = $bindInfo['open_id'];
                $template_id = self::COMPANY_TEMPLATE_ID;
                $data = [
                    'name' => [
                        'value' => $companyInfo['nickname'],
                        'color' => '#173177',
                    ],
                    'time' => [
                        'value' => date('Y-m-d H:i',time()),
                        'color' => '#173177',
                    ],
                ];	
                $res = $this -> sendTemplate($template_id,$url,$data,$open_id);
                }
            }
            $res = $this->where('id ='.$ids)->update(['status' => 'distributed', 'distributed_id' => $company_id]);
            $this->where('id = '.$ids)->update(['distributed_time' => $time]);
        return $res;
    }

    //指派工人并且微信发送通知
    public function dispatch($ids,$worker_id){
    
        $time = time();
        $bindInfo = Db::name('repair_bind') -> where('type',2) -> where('user_id',$worker_id) -> find();
        $repairListInfo = $this-> where('id',$ids) -> field('stu_name,phone,title,content,address_id,address') -> find();
        $worker_name = Db::name('repair_worker') -> where('id',$worker_id)->find()['name'];
        //$adress_name = Db::name('repair_areas') -> where('id',$repairListInfo['address_id']) -> find()['name'];
        $stu_name = $repairListInfo['stu_name'];
        $stu_phone = $repairListInfo['phone'];
        $repair_area = $repairListInfo['address_id']."#".$repairListInfo['address'];
        $repair_content = $repairListInfo['title']."---".$repairListInfo['content'];
        if (!empty($bindInfo)) {
            $open_id = $bindInfo['open_id'];
            $template_id = self::WORKER_TEMPLATE_ID;
            //跳转的地址
            $url = self::TEMPLATE_URL."wxrouter?func=detail&list_id=$ids";
            $data = [
                'worker_name' => [
                    'value' => $worker_name,
                    'color' => '#173177',
                ],
                'stu_name' => [
                    'value' => $stu_name,
                    'color' => '#173177',
                ],
                'stu_phone' => [
                    'value' => $stu_phone,
                    'color' => '#173177',
                ],
                'repair_area' => [
                    'value' => $repair_area,
                    'color' => '#173177',
                ],
                'repair_content' => [
                    'value' => $repair_content,
                    'color' => '#173177',
                ],
            ];	
            $res = $this -> sendTemplate($template_id,$url,$data,$open_id);
        }
        $res = $this->where('id ='.$ids)->update(['status' => 'dispatched', 'dispatched_id' => $worker_id]);
        $this->where('id = '.$ids)->update(['dispatched_time' => $time]);
        return $res;
    }

    /**
     * 发送微信模板消息
     * @param string template_id
     * @param array data
     * @param string open_id
     */
    private function sendTemplate($template_id, $url,$data, $open_id)
    {
        try {
            $config = Config::Get('wechatConfig');
            // 实例对应的接口对象
            $user = new \WeChat\Template($config);
            
            // 调用接口对象方法
            $templateData = [
                'touser' => $open_id,
                'template_id' => $template_id,
                'data'   => $data,
                'url'    => $url,
            ];
            $list = $user->send($templateData);
            
            return $list;
            
        } catch (Exception $e) {
            // 出错啦，处理下吧
            echo $e->getMessage() . PHP_EOL;
        }
    }

    //驳回申请
    public function refuse($ids,$content){
        $time = time();
        $res = $this->where('id ='.$ids)->update(['status' => 'refused', 'refused_content' => $content]);
        $this->where('id = '.$ids)->update(['refused_time' => $time]);
        return $res;
    }

    //报修内容已完工
    public function finish($ids){
        $time = time();
        $res = $this->where('id ='.$ids)->update(['status' => 'finished']);
        $this->where('id = '.$ids)->update(['finished_time' => $time]);
        $data = Db::view('repair_list') 
                -> view('repair_worker','id,name','repair_list.dispatched_id = repair_worker.id')
                -> view('admin','id,nickname','repair_list.distributed_id = admin.id')
                -> where('repair_list.id',$ids)
                -> field('repair_list.id,repair_list.dispatched_id,finished_time,repair_list.distributed_id') 
                -> find();
        $info['repair_id'] = $data['id'];
        $info['worker_name'] = $data['name'];
        $info['distributed_name'] = $data['nickname'];
        $info['finished_time'] = $data['finished_time'];
        $result = Db::name('repair_log') -> insert($info);
        return $result;
    }

     /**
     * 获取评价信息
     */
    public function get_star($offset, $limit,$where)
    {
        
        $list = [];
        $data = Db::view('repair_list') 
                        -> view('repair_worker','id,name','repair_list.dispatched_id = repair_worker.id')
                        -> view('admin','id,nickname','repair_list.distributed_id = admin.id')
                        -> where('repair_list.status','finished')
                        -> where('star','<>','null')
                        -> where($where)
                        -> limit($offset, $limit)
                        -> select();
        foreach ($data as $k => $v) {
            $list[$k]['id'] = $v['id'];
            $list[$k]['stu_id'] = $v['stu_id'];
            $list[$k]['stu_name'] = $v['stu_name'];
            $list[$k]['title'] = $v['title'];
            $list[$k]['content'] = $v['content'];
            $list[$k]['admin']['nickname'] = $v['nickname'];
            $list[$k]['repair_worker']['name'] = $v['name'];
            $list[$k]['finished_time'] = Date('Y-m-d H:i',$v['finished_time']);
            $list[$k]['star'] = $v['star'];
            $list[$k]['message'] = $v['message'];
        }

        $list = collection($list)->toArray();
        return ['data' => $list, 'count' => count($list)];
    }
    
}
