<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use think\Db;
use app\api\model\Wxuser as WxuserModel;

/**
 * 
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 获取小程序前端banner list
     */
    public function banner_list(){
        // sleep(3);
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $data = Db::name("miniapp_banner")->order("ID")->select();
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = [
                "id"    =>  $value["ID"],
                "type"  =>  $value["type"],
                "color" =>  $value["color"],
                "url"   =>  $value["url"],
            ];
        }
        $info = [
            'status' => 200,
            'msg' => 'success',
            'data' => $data,
        ];
        return json($info);
    }

    /**
     * 获取小程序前端应用list
     */

    public function app_list()
    {
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $appInfo = Db::name("miniapp_index")->select();
        foreach ($appInfo as $key => $value) {
            $temp = [
                "id"   =>   $value["item_id"],
                "icon" =>   $value["icon"],
                "color"=>   $value["color"],
                "badge"=>   $value["badge"] == "0" ? 0 : $value["badge"],
                "name" =>   $value["name"],
                "permissions" => [
                    "unauthorized"  => $value["unauthorized"] == 0 ? false : true , 
                    "teacher"       => $value["teacher"] == 0 ? false : true,
                ],
                "usable"    => $value["usable"] == 0 ? false : true,
                "errMsg"    =>  empty($value["errMsg"]) ? "" : $value["errMsg"],
            ];
            $data[] = $temp;
        }

        $info = [
            'status' => 200,
            'msg' => 'success',
            // 'data' => [
            
            //     [
            //         "icon" =>  'medalfill',
            //         "color"=>  'orange',
            //         "badge"=>   0,
            //         "name" =>  '运动会',
            //     ],
            
            //     [
            //         "icon" =>  'favorfill',
            //         "color"=>  'mauve',
            //         "badge"=>   0,
            //         "name" =>  '最佳辅导员',
            //     ],
            
            // ],
            'data'  =>  $data,
        ];
        return json($info);
    }
}