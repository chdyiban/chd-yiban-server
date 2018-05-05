<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;
use fast\Http;
use fast\Random;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Repairlist as RepairlistModel;

/**
 * 获取课表
 */
class Repair extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function submit(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        //将数据写入数据库
        $repair = new RepairlistModel;
        $res = $repair->saveData($key);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $key
        ];
        return json($info);
    }

    public function get_pic(){

        Http::post(Wxuser::LOGIN_URL, $params);
    }
    //获取报修工单的列表
    public function get_list(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $repair = new RepairlistModel;
        $data = $repair->getRepairList($key['id']); 
        //dump($list);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $data,
        ];
        return json($info);
    }
    //获取报修工单的详细信息
    public function get_repair_detail(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $repair = new RepairlistModel;
        //dump($key);
        $data = $repair->getDetailList($key['bxID']); 
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $data,
        ];

        /**
         * 刘涛看这里，评价函数你来补充
         * 没有评价时：$info['data']['comment']['status'] = false;
         * 有评价时见如下：
         */
        $info['data']['comment']['status'] = false;
        // $info['data']['comment']['status'] = true;
        // $info['data']['comment']['star'] = 5;
        // $info['data']['comment']['message'] = '响应及时，终于维修好了，感谢！';
        
        return json($info);
    }

    public function get_repair_type(){
        $repair = new RepairlistModel;
        $data = $repair->getRepairType(); 
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $data,
        ];
        return json($info);
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
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $data,
        ];
        return json($info);
    }

    public function submit_rate(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $key,
        ];
        return json($info);
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