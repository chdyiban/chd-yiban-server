<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Sports as SportsModel;

/**
 * 运动会接口
 */
class Sports extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';

    public function index()
    {
        date_default_timezone_set('PRC');
        $SportsModel = new SportsModel;  
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
        $info = [];
        $collegeRankList = $SportsModel -> getCollegeScoreRank();
        $info['score']['update_time'] =  date("Y-m-d H:i",time());
        $info['score']['list'] = $collegeRankList;
        $info['hot']['me'] = $SportsModel -> getStuInfo($key);
        $info['hot']['list'] = $SportsModel -> getCollegeStepsRank();
        $result = [
            'status' => 200,
            'msg'    => 'success',
            'data'   => $info,
        ];
        return json($result);
    }

    public function detail()
    {
        $SportsModel = new SportsModel;
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid']) || empty($key['collegeid'])) {
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
        
        $returnData = $SportsModel -> getCollegeDetail($key);
        if (!$returnData['status']) {
            $info = [
                'status' => 200,
                'msg' => 'error',
                'data' => $returnData['data'],
            ];
        } else {
            $info = [
                'status' => 200,
                'msg' => 'success',
                'data' => $returnData['data'],
            ];
        }
        return json($info);

    }

    public function donate()
    {
        
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid'])) {
            $info = [
                'status' => 500,
                'msg' => '参数有误',
            ];
            return json($info);
        } else {
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
        $stu_id = $dbResult['portal_id'];
        $checkResult = $this->checkDonate($stu_id);
        if ($checkResult['status']) {
            $info = [
                'status' => 200,
                'msg'  => '今日已经捐过',
                'data' => $checkResult['data'],
            ];
            return json($info);
        }
        $appid = Config::get('wx.appId');
        $sessionKey = Db::name('wx_user') -> where('open_id',$key['openid']) -> field('session_key') ->find()['session_key'];
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($key['encryptedData'], $key['iv'], $data );
        if ($errCode == 0) {
            $user = new WxuserModel;
            $data = json_decode($data,true);
            $stepList = $data['stepInfoList'];
            $stepListToday = end($stepList)['step'];
            if ($stepListToday < 10000) {
                $info = [
                    'status' => 200,
                    'msg'  => '超过10000步再捐吧',
                    'data' => [
                        'today_steps' => $stepListToday,
                    ],
                ];
                return json($info);
            } else {
                $stuInfo = Db::name('stu_detail') -> where('XH',$stu_id) -> field('YXDM')->find();
                $tempDetail = [
                    'YXDM'   =>  $stuInfo['YXDM'],
                    'stu_id' =>  $stu_id,
                    'steps'  =>  $stepListToday,
                    'time'   =>  time(),
                ];
                $collegeInfo = Db::name('sports_score') 
                    -> where('YXDM',$stuInfo['YXDM'])
                    -> find();
                $newSteps = (int)$collegeInfo['total_steps'] + (int)$stepListToday;
                $newPerson = $collegeInfo['total_person'] + 1;
                $temp = [
                    'total_steps'   => $newSteps,
                    'total_person'  => $newPerson,
                    'average_steps' => $newSteps/$newPerson,
                ];
                // 启动事务
                Db::startTrans();     
                $insertFlag = false;
                $updateFlag = false;       
                try{
                    $insertFlag = Db::name('sports_steps_detail') -> insert($tempDetail);
                    $updateFlag = Db::name('sports_score')->where('YXDM',$stuInfo['YXDM']) -> update($temp);
                    // 提交事务
                    Db::commit();  
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }

                if ($insertFlag && $updateFlag) {
                    $SportsModel = new SportsModel;
                    $info = [
                        'status' => 200,
                        'msg'  => '捐献成功',
                        'data' => [
                            'total_steps' =>  $newSteps,
                            'today_steps' => $stepListToday,
                            'heat'        => $SportsModel -> getHeat($newSteps/$newPerson),
                        ],
                    ];
                    return json($info);
                } else {
                    $info = [
                        'status' => 500,
                        'msg'  => '捐献失败，请稍后再试',
                        'data' => [],
                    ];
                    return json($info);
                }

            }
        } else {
            $info = [
                'status' => 200,
                'code'   => $errCode,
                'msg' => '获取步数失败',
            ];
        }
        return json($info);
    }

    /**
     * 判断当天是否已经捐赠过
     * @param int $stu_id
     * @return array 
     */
    private function checkDonate($stu_id) {
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $donateList = Db::name('sports_steps_detail') 
                        -> where('stu_id',$stu_id)
                        -> where('time','>',$beginToday)
                        -> find();
        if (empty($donateList)) {
            //未进行捐赠
            return ['status'=> false,'data' => '' ];
        } else {
            return ['status'=> true,'data' => $donateList];
        }
    }

}