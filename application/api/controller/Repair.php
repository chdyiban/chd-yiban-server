<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use fast\Random;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;

/**
 * 获取课表
 */
class Repair extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function submit(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
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

    public function get_list(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                ['bxID'=>'110','wx_wxztm'=>'已受理','wx_bt'=>'宿舍门锁坏掉了','wx_bxlxm'=>'1','wx_bxsj'=>'2018-03-18 17:00:00','xysj'=>'35分钟'],
            ]
        ];
        return json($info);
    }

    public function get_repair_detail(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                'wx_bt'=>'23434',
                'wx_bxnr'=>'宿舍门锁坏掉了',
                'wx_wxztm'=>'已完工',
                'wx_wxgm'=>'李师傅',
                'wx_slr'=>'王老师',
                'wx_shr'=>'张老师',
                'wx_cxbmm'=>'后勤处',
                'wx_bxr'=>'杨加玉',
                'wx_bxrrzm'=>'170049',
                'xysj' => '45',

                'wx_bxsj'=>'2018-03-08 17:00:00'
            ]
        ];
        return json($info);
    }

    public function get_repair_type(){

        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                '电路'=> [
                    ['Name'=>'卫生间灯','CategId'=>'9','Id'=>'10'],
                    ['Name'=>'插座','CategId'=>'9','Id'=>'11'],
                ],
                '木工'=> [
                    ['Name'=>'床','CategId'=>'7','Id'=>'13'],
                    ['Name'=>'桌子','CategId'=>'7','Id'=>'14'],
                ],
            ]
        ];
        return json($info);
    }

    public function get_repair_areas(){
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                ['Name' => '1号楼(西区)','Id' => '1'],
                ['Name' => '2号楼(西区)','Id' => '2'],
                ['Name' => '15号楼(东区)','Id' => '15'],
            ]
        ];
        return json($info);
    }

    public function upload(){
        $file = $this->request->file('upload_repair_pic');
        if (empty($file))
        {
            $info = [
                'message' => 'No file upload or server upload limit exceeded',
            ];
            return json($info);
        }

        //判断是否已经存在附件
        $sha1 = $file->hash();

        $upload = Config::get('upload');

        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int) $upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix ? $suffix : 'file';

        $mimetypeArr = explode(',', $upload['mimetype']);
        $typeArr = explode('/', $fileInfo['type']);
        //验证文件后缀
        if ($upload['mimetype'] !== '*' && !in_array($suffix, $mimetypeArr) && !in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr))
        {
            $info = [
                'message' => 'No file upload or server upload limit exceeded',
            ];
            return json($info);
        }
        $replaceArr = [
            '{year}'     => date("Y"),
            '{mon}'      => date("m"),
            '{day}'      => date("d"),
            '{hour}'     => date("H"),
            '{min}'      => date("i"),
            '{sec}'      => date("s"),
            '{random}'   => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
            '{suffix}'   => $suffix,
            '{.suffix}'  => $suffix ? '.' . $suffix : '',
            '{filemd5}'  => md5_file($fileInfo['tmp_name']),
        ];
        $savekey = $upload['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

        $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        $fileName = substr($savekey, strripos($savekey, '/') + 1);
        //
        $splInfo = $file->validate(['size' => $size])->move(ROOT_PATH . '/public' . $uploadDir, $fileName);
        if ($splInfo)
        {
            $imagewidth = $imageheight = 0;
            if (in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']))
            {
                $imgInfo = getimagesize($splInfo->getPathname());
                $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
                $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
            }
            $params = array(
                'filesize'    => $fileInfo['size'],
                'imagewidth'  => $imagewidth,
                'imageheight' => $imageheight,
                'imagetype'   => $suffix,
                'imageframes' => 0,
                'mimetype'    => $fileInfo['type'],
                'url'         => $uploadDir . $splInfo->getSaveName(),
                'uploadtime'  => time(),
                'storage'     => 'local',
                'sha1'        => $sha1,
            );
            $attachment = model("attachment");
            $attachment->data(array_filter($params));
            $attachment->save();
            \think\Hook::listen("upload_after", $attachment);

            $info = [
                'url'=> $this->request->domain() . $this->request->root() .$uploadDir . $splInfo->getSaveName()
            ];
            return json($info);
        }
        else
        {
            $info = [
                'message' => $file->getError(),
            ];
            return json($info);
        }   
    }

}