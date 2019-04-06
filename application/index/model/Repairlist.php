<?php

namespace app\index\model;

use think\Model;
use think\Db;
use think\Config;
use \WeChat\Template;

class Repairlist extends Model{
    // 表名
    protected $name = 'repair_list';
    //为工人发的模板消息id
    const WORKER_TEMPLATE_ID = "BNtZm-iUDytuPjYpo1iu1fLC0LfMEbH9lhKWeE99yeo";
    //为公司发的模板消息id
    const COMPANY_TEMPLATE_ID = "hyHcF_da4GLq1_4-SxIejrl1O92eMQkJzkc8mw3LImU";
    //模板消息跳转url
    const TEMPLATE_URL = " https://yiban.chd.edu.cn/index/Bx/";

    //自修和总控在写入时都按照自修的id来写
    public function finish($ids)
    {
        $info = Db::name('repair_list') -> where('id',$ids) -> where('status','dispatched') -> find();
        if (empty($info)) {
            return ['status' => false, 'msg'=>"param error"];
        }
        $time = time();
        $res = $this->where('id ='.$ids)->update(['status' => 'finished']);
        $this->where('id = '.$ids)->update(['finished_time' => $time]);
        $data = Db::view('repair_list') 
                -> view('repair_worker','id,name','repair_list.dispatched_id = repair_worker.id')
                -> view('admin','id,nickname','repair_list.distributed_id = admin.id')
                -> where('repair_list.id',$ids)
                -> field('repair_list.id,repair_list.dispatched_id,finished_time,repair_list.distributed_id') 
                -> find();
        $insertData['repair_id'] = $data['id'];
        $insertData['worker_name'] = $data['name'];
        $insertData['distributed_name'] = $data['nickname'];
        $insertData['finished_time'] = $data['finished_time'];
        $result = Db::name('repair_log') -> insert($insertData);
        return ['status' => true, 'msg' => "success"];
    }
    //受理订单
    public function accept($ids,$admin_id)
    {
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
            if (!empty($bindInfo)) {
                $url = self::TEMPLATE_URL."wxrouter?func=dispatch&list_id=".$ids;
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
        //分配工人逻辑
        $res = $this -> where('id ='.$ids)-> update(['status' => 'dispatched', 'dispatched_id' => $worker_id]);
        $this->where('id = '.$ids)->update(['dispatched_time' => $time]);
        //为工人发送微信消息
        if (!empty($bindInfo)) {
            $open_id = $bindInfo['open_id'];
            $template_id = self::WORKER_TEMPLATE_ID;
            //跳转的地址
            $url = self::TEMPLATE_URL."wxrouter?func=detail&list_id=".$ids;
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
        return $res;
    }

    //驳回申请
    public function refuse($ids,$content){
        $time = time();
        $res = $this->where('id ='.$ids)->update(['status' => 'refused', 'refused_content' => $content]);
        $this->where('id = '.$ids)->update(['refused_time' => $time]);
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
}