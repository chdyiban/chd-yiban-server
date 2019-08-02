<?php
namespace app\api\controller\miniapp;

use app\common\controller\Api;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Vote as VoteModel;

/**
 * 投票api
 */
class Vote extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    public function init() {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid'])) {
            $info = [
                'status' => 500,
                'msg' => '参数有误',
            ];
            return json($info);
        }else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $info = [
                    'status' => 500,
                    'msg' => 'authority error',
                ];
                return json($info);
            }
        }
        // $key = ["XH" => "2017902148"];
        $id = 1;
        $VoteModel = new VoteModel;
        $data = $VoteModel -> getInitData($key,$id);
        $info = [
            'status' => 200,
            'msg' => 'success',
            'data' => $data,
        ];
        return json($info);
    }

    public function submit() {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        // dump($key);
        $VoteModel = new VoteModel;
        $data = $VoteModel -> submit($key);

        $info = [
            'status' => 200,
            'msg' => $data["msg"],
            'data' => [
                "voteStatus" => $data["status"],
                "data" => $data["data"]
            ],
        ];

        return json($info);
    }
    
}