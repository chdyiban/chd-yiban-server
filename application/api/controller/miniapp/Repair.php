<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use \WeChat\Template;
use think\Db;
use think\Cache;
use fast\Http;
use fast\Random;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Repairlist as RepairlistModel;
use app\common\library\Sms as Smslib;
use app\common\library\Token;
/**
 * 报修
 */
class Repair extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    //为工人发出的模板消息id
    const WORKER_TEMPLATE_ID = "BNtZm-iUDytuPjYpo1iu1fLC0LfMEbH9lhKWeE99yeo";
    //为公司发的模板消息id
    const COMPANY_TEMPLATE_ID = "hyHcF_da4GLq1_4-SxIejrl1O92eMQkJzkc8mw3LImU";
    //模板消息跳转url
    const TEMPLATE_URL = " https://yiban.chd.edu.cn/index/Bx/";

    public function submit(){
        $key = json_decode(base64_decode($this->request->post('key')),true);

        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        $key["openid"] = $userInfo["open_id"];

        //将数据写入数据库
        $repair = new RepairlistModel;
        $listId = $repair->saveData($key);
        // 发送短信功能
        // $this->isNotice();
        // $mobile = '15991651685';
        // $msg = "[宿舍管理系统]通知：刚有新的订单产生，请前往处理";
        // $res = Smslib::notice($mobile, $msg);
        // 微信模板消息
        $adminControllerInfo = Db::name('admin')->where('nickname',"总控")->field('nickname,id') -> find();
        $bindInfo = Db::name('repair_bind') -> where('type',1) -> where('user_id',$adminControllerInfo['id']) -> find();
        if (!empty($bindInfo)) {
            $url = self::TEMPLATE_URL."wxrouter?func=distribute&list_id=$listId";
            $open_id = $bindInfo['open_id'];
            $template_id = self::COMPANY_TEMPLATE_ID;
            $data = [
                'name' => [
                    'value' => $adminControllerInfo['nickname'],
                    'color' => '#000066',
                ],
                'time' => [
                    'value' => date('Y-m-d H:i',time()),
                    'color' => '#173177',
                ],
            ];	
            $res = $this -> sendTemplate($template_id,$url,$data,$open_id);
        }

        $this->success("success");
    }

    private function isNotice()
    {
        $configInfo = Db::name('repair_config') -> where('name','sms_zk') -> find();
        if($configInfo['status'] == "1"){
            $mailTime = Cache::get('mail');
            if (empty($mailTime)) {
                $mobile = $configInfo['object'];
                $msg = $configInfo['content'];
                $time = $configInfo['wait_time'] * 60;
                $res = Smslib::notice($mobile, $msg);
                Cache::set('mail',$msg,$time);
            } 
        }
    }

    public function get_pic(){
        Http::post(Wxuser::LOGIN_URL, $params);
    }
    //获取报修工单的列表
    public function get_list(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];
        $key["id"] = $userInfo["portal_id"];
        $repair = new RepairlistModel;
        $data = $repair->getRepairList($key['id']); 
        $this->success("success",$data);
    }

    //获取报修工单的详细信息
    public function get_repair_detail(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];

        $repair = new RepairlistModel;
        //dump($key);
        $data = $repair->getDetailList($key['bxID']); 

        $this->success("success",$data);
    }

    public function get_repair_type(){
        $repair = new RepairlistModel;
        $data = $repair->getRepairType(); 
        $this->success("success",$data);
    }

    //获取报修区域的信息
    public function get_repair_areas(){
        $list = Db::name('repair_areas')
                ->select();
        $info = array();
        $data = array();
        foreach($list as $val){
            $info['Name'] = $val['name'];
            $info['Id'] = $val['id'];
            $data[] = $info;
        }
        $this->success("success",$data);
    }

    public function submit_rate(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];
        $key["id"] = $userInfo["portal_id"];
        $repair = RepairlistModel::get((int)$key['bxID']);
        $repair -> star = $key['star'];
        $repair -> message = $key['message'];
        $res = $repair -> save();
        $this->success("success");
    }
    /**
     * 发送微信模板消息
     * @param string template_id
     * @param string url
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

    // public function upload(){
    //     $file = $this->request->file('upload_repair_pic');
    //     if (empty($file))
    //     {
    //         $info = [
    //             'message' => 'No file upload or server upload limit exceeded',
    //         ];
    //         return json($info);
    //     }

    //     //判断是否已经存在附件
    //     $sha1 = $file->hash();

    //     $upload = Config::get('upload');

    //     preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
    //     $type = strtolower($matches[2]);
    //     $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
    //     $size = (int) $upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
    //     $fileInfo = $file->getInfo();
    //     $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
    //     $suffix = $suffix ? $suffix : 'file';

    //     $mimetypeArr = explode(',', $upload['mimetype']);
    //     $typeArr = explode('/', $fileInfo['type']);
    //     //验证文件后缀
    //     if ($upload['mimetype'] !== '*' && !in_array($suffix, $mimetypeArr) && !in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr))
    //     {
    //         $info = [
    //             'message' => 'No file upload or server upload limit exceeded',
    //         ];
    //         return json($info);
    //     }
    //     $replaceArr = [
    //         '{year}'     => date("Y"),
    //         '{mon}'      => date("m"),
    //         '{day}'      => date("d"),
    //         '{hour}'     => date("H"),
    //         '{min}'      => date("i"),
    //         '{sec}'      => date("s"),
    //         '{random}'   => Random::alnum(16),
    //         '{random32}' => Random::alnum(32),
    //         '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
    //         '{suffix}'   => $suffix,
    //         '{.suffix}'  => $suffix ? '.' . $suffix : '',
    //         '{filemd5}'  => md5_file($fileInfo['tmp_name']),
    //     ];
    //     $savekey = $upload['savekey'];
    //     $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

    //     $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
    //     $fileName = substr($savekey, strripos($savekey, '/') + 1);
    //     //
    //     $splInfo = $file->validate(['size' => $size])->move(ROOT_PATH . '/public' . $uploadDir, $fileName);
    //     if ($splInfo)
    //     {
    //         $imagewidth = $imageheight = 0;
    //         if (in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']))
    //         {
    //             $imgInfo = getimagesize($splInfo->getPathname());
    //             $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
    //             $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
    //         }
    //         $params = array(
    //             'filesize'    => $fileInfo['size'],
    //             'imagewidth'  => $imagewidth,
    //             'imageheight' => $imageheight,
    //             'imagetype'   => $suffix,
    //             'imageframes' => 0,
    //             'mimetype'    => $fileInfo['type'],
    //             'url'         => $uploadDir . $splInfo->getSaveName(),
    //             'uploadtime'  => time(),
    //             'storage'     => 'local',
    //             'sha1'        => $sha1,
    //         );
    //         $attachment = model("attachment");
    //         $attachment->data(array_filter($params));
    //         $attachment->save();
    //         \think\Hook::listen("upload_after", $attachment);

    //         $info = [
    //             'url'=> $this->request->domain() . $this->request->root() .$uploadDir . $splInfo->getSaveName()
    //         ];
    //         return json($info);
    //     }
    //     else
    //     {
    //         $info = [
    //             'message' => $file->getError(),
    //         ];
    //         return json($info);
    //     }   
    // }

}