<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\common\library\Token;
use fast\Random;
use think\Hook;
use think\Cache;

/**
 * 微信小程序登录接口
 */
class Wxuser extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $_token = '';
    //Token默认有效时长
    protected $keeptime = 2592000;


    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const TEST_URL = "http://202.117.64.236:8080/auth/login";
    const GET_INFO_URL = "http://202.117.64.236:8007/userinfo";
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';
    const CAPTCHA_URL = 'http://ids.chd.edu.cn/authserver/captcha.html';
    /**
     * 描述：2018.05.13 微信版本更新后，修改了登录方法，对此方法做出修改
     * @url https://developers.weixin.qq.com/blogdetail?action=get_post_info&lang=zh_CN&token=&docid=000e2aac1ac838e29aa6c4eaf56409
     * @author Yang
     */
    public function init(){
        $code = $this->request->post('code');
        $appid = Config::get('wechat.miniapp_chdyiban')["appId"];
        $appsecret = Config::get('wechat.miniapp_chdyiban')["appSecret"];
        $retData = [];

        $params = [
            'appid' => $appid,
            'secret' => $appsecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        
        $result = json_decode(Http::get(Wxuser::LOGIN_URL, $params),true);
        //dump($code);
        if($result['openid'] != ''){
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $result['openid'])->find();
            if($dbResult){
                $user->save([
                    'session_key' => $result['session_key'],
                ],['open_id' => $result['openid']]);
            }else{
                $user->data([
                    'open_id'  =>  $result['openid'],
                    'session_key' =>  $result['session_key'],
                ]);
                $user->save();
            }
            $data = $this->queryStuInfoByOpenId($result['openid']);
            $data['user']['info']['wxmobile'] = $dbResult['iswxbind'] == "1" ? true : false;
            $retData['status'] = 200;
            $retData['data'] = base64_encode(json_encode($data));
            $retData['msg'] = 'success';
        }else{
            $retData['status'] = 404;
            $retData['msg'] = 'open_id missed';
        }

        return json($retData);
    }
    /**
     * 过渡阶段，将open_id存储本地，不返回open_id,只返回其余信息
     * @time 2019/11/17
     */

    public function init_openid(){
        $openid = $this->request->post('openid');
        $retData = [];
        $user = new WxuserModel;
        $dbResult = $user->where('open_id', $openid)->find();
        if (empty($dbResult)) {
            $this->error("info missed","");
        } else {
            $data = $this->queryStuInfoByOpenId($openid);
            $data['user']['info']['wxmobile'] = $dbResult['iswxbind'] == "1" ? true : false;
            $returnData = base64_encode(json_encode($data));
            $retData['msg'] = 'success';
            $this->success("success",$returnData);
        }
    }
    /**
     * 过渡阶段，将token存储本地，不返回token,只返回其余信息
     * @time 2019/11/28
     */
    public function init_token(){
        $token = $this->request->param()["token"];
        $retData = [];
        $user = new WxuserModel;
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $dbResult = $user->where('id',$tokenInfo['user_id'])->find();
        $openid = $dbResult["open_id"];
        if (empty($dbResult)) {
            $this->error("info missed");
        } else {
            $data = $this->queryStuInfoByOpenId($openid);
            $data['user']['info']['wxmobile'] = $dbResult['iswxbind'] == "1" ? true : false;
            $returnData = base64_encode(json_encode($data));
            $retData['msg'] = 'success';
            $this->success("success",$returnData);
        }
    }

    /**
     * 修改返回值，code为0表示错误code为1表示正确
     */
    public function login() {
        $code = $this->request->post('code');
        $appid = Config::get('wechat.miniapp_chdyiban')["appId"];
        $appsecret = Config::get('wechat.miniapp_chdyiban')["appSecret"];

        $retData = [];

        $params = [
            'appid' => $appid,
            'secret' => $appsecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        
        $result = json_decode(Http::get(Wxuser::LOGIN_URL, $params),true);
        if(!empty($result['openid'])){
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $result['openid'])->find();
            if($dbResult){
                $user->save([
                    'session_key' => $result['session_key'],
                ],['open_id' => $result['openid']]);
            }else{
                $user->data([
                    'open_id'  =>  $result['openid'],
                    'session_key' =>  $result['session_key'],
                ]);
                $user->save();
            }
            //验证该用户是否有token
            // $open_id = $result["openid"];
            // $token_old = Cache::get("wx_user_$open_id");
            // if ($token_old) {
            //     $info = Token::delete($token_old);
            // }
            $userId = $user->where('open_id', $result['openid'])->field("id")->find()["id"];
            $this->_token = Random::uuid();
            Token::set($this->_token, $userId, $this->keeptime);
            // Cache::set("wx_user_$open_id",$this->_token, $this->keeptime);
            // $data = ["openid" => $result["openid"]];
            $data = ["token" => $this->_token];
            $this->success("success",$data);
        }else{
            $this->error("open_id missed");
        }
    }

    /**
     * 验证token有效性
     * @param token
     */
    public function check_token() {
        $param = $this->request->param();
        if (empty($param["token"])) {
            $this->error("param error");
        }
        $openid = Token::get($param["token"]);
        if (empty($openid)) {
            $this->error("Token expired");
        }
        $this->success("success");
    }


    /**
    * 预感要废弃
    */
    /*
    public function info()
    {
        
        $code = $this->request->post('code');
        $encryptedData = $this->request->post('key');
        $iv = $this->request->post('iv');

        $appid = Config::get('wx.appId');
        $appsecret = Config::get('wx.appSecret');

        $params = [
            'appid' => $appid,
            'secret' => $appsecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];

        $result = json_decode(Http::get(Wxuser::LOGIN_URL, $params),true);

        $dbResult = '';
        if($result['openid'] != ''){
            //授权成功
            $pc = new WXBizDataCrypt($appid, $result['session_key']);
            $errCode = $pc->decryptData($encryptedData, $iv, $data);

            if(0 === $errCode){
                //更新数据信息
                $user = new WxuserModel;
                $dbResult = $user->where('open_id', $result['openid'])->find();
                if($dbResult){
                    $dbResult = $user->save([
                        'session_key' => $result['session_key'],
                        'skey' => $iv,
                        'user_info' => $data
                    ],['open_id' => $result['openid']]);
                }else{
                    $user->data([
                        'open_id'  =>  $result['openid'],
                        'session_key' =>  $result['session_key'],
                        'skey' => $iv,
                        'user_info' => $data
                    ]);
                    $user->save();
                }

                //返回数据
                unset($data);
                $bindInfo = $this->checkBindByOpenId($result['openid']);
                if($bindInfo){
                    $appendInfo = $user->where('open_id',$result['openid'])->field('build,room,mobile')->find();
                    //bindInfo为学号，通过学号来查询学生信息.
                    $info = Db::connect('chd_config')
                    ->view('chd_stu_detail')
                    ->where('XH', $bindInfo)
                    ->view('chd_dict_nation','MZDM,MZMC','chd_stu_detail.MZDM = chd_dict_nation.MZDM')
                    ->view('chd_dict_major','ZYDM,ZYMC','chd_stu_detail.ZYDM = chd_dict_major.ZYDM')
                    ->view('chd_dict_college','YXDM,YXMC,YXJC','chd_stu_detail.YXDM = chd_dict_college.YXDM')
                    ->find();
                    //先判断是教职工还是学生
                    if(strlen($bindInfo) == 6){
                        
                        $data = [
                            'is_bind' => true,
                            'user' => [
                                'openid' => $result['openid'],
                                'type' => '教职工',
                                'id' => $bindInfo,
                                'info'=>[
                                    'yxm'=>$info['YXMC'],
                                    //如果注释掉这两个，则跳转到完善信息界面
                                    'build'=>' ',
                                    'room'=>' ',
                                    'mobile'=>$appendInfo['mobile']
                                ],
                                'name' => $info['XM']
                            ],
                            'time' => [
                                'term' => '2017-2018 第2学期',
                                'week' => get_weeks(),
                                'day' => date("w")
                            ],
                            'token' => rand_str_10(),
                            'status' => 200,
                        ];

                    }else{
                        //年级将学号的前四位截取
                        $info['NJ'] = substr($info['XH'],0,4);
                        $data = [
                            'is_bind' => true,
                            'user' => [
                                'openid' => $result['openid'],
                                'type' => '学生',
                                'id' => $bindInfo,
                                'info'=>[
                                    'yxm'=>$info['YXMC'],
                                    'build'=>$appendInfo['build'],
                                    'room'=>$appendInfo['room'],
                                    'mobile'=>$appendInfo['mobile']
                                ],
                                'more' => [
                                    'zym'=>$info['ZYMC'],
                                    'nj'=>$info['NJ'],
                                    'bj'=>$info['BJDM'],
                                    'sex' => ($info['XBDM'] == 1) ? '男' : '女',
                                ],
                                'name' => $info['XM']
                            ],
                            'time' => [
                                'term' => '2017-2018 第2学期',
                                'week' => get_weeks(),
                                'day' => date("w")
                            ],
                            'token' => rand_str_10(),
                            'status' => 200,
                        ];
                    }
                }else{
                    $data = [
                        'is_bind' => false,
                        'user' => [
                            'openid' => $result['openid'],
                        ],
                        'status' => 200,
                    ];
                }
                $retData['status'] = 200;
                $retData['data'] = base64_encode(json_encode($data));
                $retData['msg'] = 'success';
                return json($retData);
            }
        }else{
            //未获取到openid
            $data = [
                'status' => 404,
                'errcode' => $errCode
            ];
            $data['data'] = base64_encode(json_encode($data));
            return json($data);
        }

        //$this->success("ok",$data,$errCode);
    }*/

    public function append(){
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

        $user = new WxuserModel;
        $appendStatus = $user->save([
            'build' => $key['build'],
            'room' => $key['room'],
            'mobile'=> $key['mobile']
        ],['open_id' => $key['openid']]);

        if($appendStatus){
            // $data = [
            //     'status' => 200,
            //     'message' => '更新成功',
            // ];
            $this->success("更新成功");
        }else{
            // $data = [
            //     'status' => 404,
            //     'message' => '更新错误',
            // ];
            $this->error("更新失败");
        }
        

        // return json($data);
    }

    public function bind(){
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

        $bindInfo = $this->checkBind($key['stuid'],$key['passwd']);
        $pwd =  _token_encrypt($key['passwd'], $key['openid']);
        if($bindInfo['status'] === true){
            $user = new WxuserModel;
            $roomData = Db::view('dormitory_beds')
                    -> view('dormitory_rooms','XQ,LH,SSH','dormitory_beds.FYID = dormitory_rooms.ID') 
                    -> where('XH',$key['stuid'])
                    -> find();
            $bindStatus = $user->save([
                'portal_id'  => $key['stuid'],
                'portal_pwd' => $pwd,
                'build'      => $roomData['LH'],
                'room'      => $roomData['SSH'],
            ],['open_id' => $key['openid']]);
            if($bindStatus){
                // $info = [
                //     'status' => 200,
                //     'message' => '绑定成功'
                // ];
                $this->success("绑定成功");
            }else{
                // $info = [
                //     'status' => 200,
                //     'message' => '请稍后再试'
                // ];
                $this->success("请稍后再试");
            }
        }else{
            // $info = [
            //     'status' => 404,
            //     'message' => $bindInfo['message']
            // ];
            $this->error($bindInfo["msg"]);
        }
        
        // return json($info);
    }
    /**
     * 获取用户联系方式api
     */
    public function wxmobile()
    {
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

        $appid = Config::get('wechat.miniapp_chdyiban')["appId"];
        $sessionKey = Db::name('wx_user') -> where('open_id',$key['openid']) -> field('session_key') ->find()['session_key'];
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($key['encryptedData'], $key['iv'], $data );
        if ($errCode == 0) {
            $user = new WxuserModel;
            $data = json_decode($data,true);
            $bindStatus = $user->save([
                'mobile'      => $data['phoneNumber'],
                'iswxbind'    => 1,
            ],['open_id' => $key['openid']]);
            if($bindStatus){
                $this->success("绑定成功",$data["phoneNumber"]);
            } else {
                $this->success("请稍后再试");
            }
        } else {
            $this->success("绑定失败");
        }
    }

    /**
     * 更新用户头像以及昵称API
     * @param $key["openid"]
     * @param $key["avatarUrl"]
     * @param $key["nickName"]
     */
    public function userinfo()
    {
        //   解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        // $pc = new WXBizDataCrypt($appid, $result['session_key']);
        // $errCode = $pc->decryptData($encryptedData, $iv, $data);

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
        $open_id = $key["openid"];

        $appid = Config::get('wechat.miniapp_chdyiban')["appId"];
        $sessionKey = $userInfo["session_key"];
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($key['encryptedData'], $key['iv'], $data );

        if ($errCode == 0) {
            $user = new WxuserModel;
            $data = json_decode($data,true);
            $bindStatus = $user->save([
                'avatar'        =>  $data['avatarUrl'],
                'nickname'      =>  $data['nickName'],
                "update_time"   =>  time(),
            ],['open_id' => $open_id]);
            if($bindStatus){
                $this->success("更新成功");
            } 
            $this->success("请稍后再试");
        } 

        $this->error("获取授权失败，请稍后再试");

        // $avatar = empty($key["avatarUrl"]) ? "" : $key["avatarUrl"]; 
        // $nickName = empty($key["nickName"]) ? "" : $key["nickName"]; 
        // $user = new WxuserModel;
        // $result = $user->where("open_id",$open_id)->update(["avatar" => $avatar,"nickname" => $nickName,"update_time" => time()]);
        
        // if ($result) {
        //     $this->success("更新成功");
        // } 
        // $this->error("请稍后再试");
        
    }


    /**
     * 根据用户的微信openid获取数据库里存在的基本信息
     * @param $open_id 微信open_id
     * @return $data 数据库中用户的基本信息
     * @time 2019/4/11 将chd_teacher_detail表内容迁移至fa_teacher_detail
     */
    private function queryStuInfoByOpenId($open_id){
        $data = [];
        $bindInfo = $this->checkBindByOpenId($open_id);
        if($bindInfo){
            $user = new WxuserModel;
            $appendInfo = $user->where('open_id',$open_id)->field('build,room,mobile')->find();
            //bindInfo为学号，通过学号来查询学生信息.

            //先判断是教职工还是学生
            if(strlen($bindInfo) == 6){
                $info = Db::view('teacher_detail')
                    ->where('ID',$bindInfo)
                    //->view('chd_dict_nation','MZDM,MZMC','chd_teacher_detail.MZDM = chd_dict_nation.MZDM')
                    ->view('dict_college','YXDM,YXMC,YXJC','teacher_detail.YXDM = dict_college.YXDM')
                    ->find();
                // $info = Db::connect('chd_config')
                //     ->view('chd_teacher_detail')
                //     ->where('ID',$bindInfo)
                //     //->view('chd_dict_nation','MZDM,MZMC','chd_teacher_detail.MZDM = chd_dict_nation.MZDM')
                //     ->view('chd_dict_college','YXDM,YXMC,YXJC','chd_teacher_detail.YXDM = chd_dict_college.YXDM')
                //     ->find();
                //若数据库中不存在教师信息
                if (empty($info)) {
                    $data = [
                        'is_bind' => true,
                        'user' => [
                            'openid' => $open_id,
                            'type' => '教职工',
                            'id' => $bindInfo,
                            'info'=>[
                                'yxm'=>"",
                                //如果注释掉这两个，则跳转到完善信息界面
                                'build'=>' ',
                                'room'=>' ',
                                'mobile'=>$appendInfo['mobile']
                            ],
                            'name' => "暂无数据",
                        ],
                        'time' => [
                            'term' => '2019-2020 第2学期',
                            'week' => get_weeks(),
                            'day' => date("w")
                        ],
                        // 'token' => rand_str_10(),
                        // 'status' => 200,
                    ];
                } else {
                    $data = [
                        'is_bind' => true,
                        'user' => [
                            'openid' => $open_id,
                            'type' => '教职工',
                            'id' => $bindInfo,
                            'info'=>[
                                'yxm'=>$info['YXMC'],
                                //如果注释掉这两个，则跳转到完善信息界面
                                'build'=>' ',
                                'room'=>' ',
                                'mobile'=>$appendInfo['mobile']
                            ],
                            'name' => $info['XM']
                        ],
                        'time' => [
                            'term' => '2019-2020 第2学期',
                            'week' => get_weeks(),
                            'day' => date("w")
                        ],
                        // 'token' => rand_str_10(),
                        // 'status' => 200,
                    ];
                }
            } else {
                //此处由于目前18级新生没有专业代码，因此联查时需要少查一个表。
                $nj = substr($bindInfo,0,4);
                // if ($nj == '2018') {
                //     $info = Db::connect('chd_config')
                //         ->view('chd_stu_detail')
                //         ->where('XH', $bindInfo)
                //         // ->view('chd_dict_nation','MZDM,MZMC','chd_stu_detail.MZDM = chd_dict_nation.MZDM')
                //         ->view('chd_dict_college','YXDM,YXMC,YXJC','chd_stu_detail.YXDM = chd_dict_college.YXDM')
                //         ->find();
                //     $info['ZYMC'] = '';
                // } else {
                //     $info = Db::connect('chd_config')
                //         ->view('chd_stu_detail')
                //         ->where('XH', $bindInfo)
                //         // ->view('chd_dict_nation','MZDM,MZMC','chd_stu_detail.MZDM = chd_dict_nation.MZDM')
                //         // ->view('chd_dict_major','ZYDM,ZYMC','chd_stu_detail.ZYDM = chd_dict_major.ZYDM',"LEFT")
                //         ->view('chd_dict_college','YXDM,YXMC,YXJC','chd_stu_detail.YXDM = chd_dict_college.YXDM')
                //         ->find();
                //         $info['ZYMC'] = '';
                //         //研究生没有班级
                //         if (empty($info["BJDM"])) {
                //             $info["BJDM"] = "";
                //         }
                // }

                $info = Db::view('stu_detail')
                        ->where('XH', $bindInfo)
                        // ->view('chd_dict_nation','MZDM,MZMC','chd_stu_detail.MZDM = chd_dict_nation.MZDM')
                        ->view('dict_college','YXDM,YXMC,YXJC','stu_detail.YXDM = dict_college.YXDM')
                        ->find();
                $info['ZYMC'] = '';
                if (empty($info["BJDM"])) {
                    $info["BJDM"] = "";
                }

                //如果数据库中没有数据
                if (empty($info["XH"])) {
                    $data = [
                        'is_bind' => true,
                        'user' => [
                            'openid' => $open_id,
                            'type' => '学生',
                            'id' => $bindInfo,
                            'info'=>[
                                'yxm'=> "" ,
                                'build'=>$appendInfo['build'],
                                'room'=>$appendInfo['room'],
                                'mobile'=>$appendInfo['mobile']
                            ],
                            'more' => [
                                'zym'=>"",
                                'nj'=>"",
                                'bj'=>"",
                                'sex' => "",
                            ],
                            'name' => "暂无数据",
                        ],
                        'time' => [
                            'term' => '2019-2020 第2学期',
                            'week' => get_weeks(),
                            'day' => date("w")
                        ],
                        // 'token' => rand_str_10(),
                        // 'status' => 200,
                    ];
                } else {
                    //年级将学号的前四位截取
                    $info['NJ'] = substr($info['XH'],0,4);
                    $data = [
                        'is_bind' => true,
                        'user' => [
                            'openid' => $open_id,
                            'type' => '学生',
                            'id' => $bindInfo,
                            'info'=>[
                                'yxm'=>empty($info["YXMC"]) ? "" : $info["YXMC"],
                                'build'=>$appendInfo['build'],
                                'room'=>$appendInfo['room'],
                                'mobile'=>$appendInfo['mobile']
                            ],
                            'more' => [
                                'zym'=>empty($info['ZYMC']) ? "" : $info["ZYMC"],
                                'nj'=>$info['NJ'],
                                'bj'=>$info['BJDM'],
                                'sex' => ($info['XBDM'] == 1) ? '男' : '女',
                            ],
                            'name' => $info['XM']
                        ],
                        'time' => [
                            'term' => '2019-2020 第2学期',
                            'week' => get_weeks(),
                            'day' => date("w")
                        ],
                        // 'token' => rand_str_10(),
                        // 'status' => 200,
                    ];
                }
            }
        }else{
            $data = [
                'is_bind' => false,
                'user' => [
                    'openid' =>  $open_id,
                ],
                // 'status' => 200,
            ];
        }
        
        return $data;
    }

    /**
     * 根据open_id判断是否已经绑定
     * @param $open_id
     * @return 学号/工号
     */
    private function checkBindByOpenId($open_id){
        $user = new WxuserModel;
        return $user->where('open_id',$open_id)->value('portal_id');
    }
    /**
     * 由于通过模拟登陆判断 账号正确性效率低容易出错，
     * 修改为LDAP判断账号正确性。
     * @time 2019/1/10
     */
    //模拟登录验证用户名密码正确性，暂时未考虑验证码的情况
    /*
    private function checkBind($username, $password){

        $params[CURLOPT_COOKIEJAR] = RUNTIME_PATH .'/cookie/cookie_'.$username.'.txt';
        //判断是否需要验证码
        $need_url = "http://ids.chd.edu.cn/authserver/needCaptcha.html?username=".$username;
        $need = Http::get($need_url,'',$params);
        //$need值为true或者false
        //1.获取lt es
        $response = Http::get(self::PORTAL_URL,'',$params);
        $lt = explode('name="lt" value="', $response);
        $lt = explode('"/>', $lt[1]);
        $lt = $lt[0];

        $es = explode('name="execution" value="', $response);
        $es = explode('"/>', $es[1]);
        $es = $es[0];
        //等于6说明为false
        if(strlen($need) == 6){
           
            //无验证码情况下
            $captcha = '';
            // 2.post
            $post_data = [
                "username" => $username,
                "password" => $password,
                "captchaResponse" => $captcha, 
                "btn" => "登录",
                "lt" => $lt,
                "dllt" => "userNamePasswordLogin",
                "execution" => $es,
                "_eventId" => "submit",
                "rmShown" => "1"
            ];
            $params[CURLOPT_COOKIEFILE] = $params[CURLOPT_COOKIEJAR];
            $params[CURLOPT_FOLLOWLOCATION] = 1;
            $response = Http::post(self::PORTAL_URL,$post_data,$params);

            $return = [];

            if(stripos($response,'auth_username') === false){
                //未绑定成功
                preg_match_all('/<span.*?id=\"msg\".*?>(.*?)<\/span?>/si', $response, $errMsg);       
                $return['status'] = false;
                $return['message'] = $errMsg[1][0];
            }else{
                //绑定成功
                $return['status'] = true;
            }  
            return $return;
        }else{
            //有验证码情况下
            $return = [];
            //需要验证码
           
            
            $res = Http::get(self::CAPTCHA_URL,'',$params);
            $base64_str = base64_encode($res);
            $code = recognize_captcha($base64_str);
            $code = json_decode($code,true);
            if($code['err_no'] != 0){
                $return['status'] = false;
                $return['message'] = "识别验证码失败，请刷新后重试！";
            }else{
               $captcha = $code['pic_str'];
            }
            // 2.post
            $post_data = [
                "username" => $username,
                "password" => $password,
                "captchaResponse" => $captcha, 
                "btn" => "登录",
                "lt" => $lt,
                "dllt" => "userNamePasswordLogin",
                "execution" => $es,
                "_eventId" => "submit",
                "rmShown" => "1"
            ];
            $params[CURLOPT_COOKIEFILE] = $params[CURLOPT_COOKIEJAR];
            $params[CURLOPT_FOLLOWLOCATION] = 1;
            $response = Http::post(self::PORTAL_URL,$post_data,$params);
            if(stripos($response,'auth_username') === false){
                //未绑定成功
                preg_match_all('/<span.*?id=\"msg\".*?>(.*?)<\/span?>/si', $response, $errMsg);       
                $return['status'] = false;
                $return['message'] = $errMsg[1][0];
            }else{
                //绑定成功
                $return['status'] = true;
            }  
            return $return;
        }
    }

    //这个函数用来当有验证码的时候带着lt，es等参数进行请求
    private function captcha_checkbind($username, $password, $captcha, $lt, $es){
        $params[CURLOPT_COOKIEJAR] = RUNTIME_PATH .'/cookie/cookie_'.$username.'.txt';
        $post_data = [
            "username" => $username,
            "password" => $password,
            "captchaResponse" => $captcha, 
            "btn" => "登录",
            "lt" => $lt,
            "dllt" => "userNamePasswordLogin",
            "execution" => $es,
            "_eventId" => "submit",
            "rmShown" => "1"
        ];
        //$params[CURLOPT_COOKIEFILE] = $params[CURLOPT_COOKIEJAR];
       // $params[CURLOPT_FOLLOWLOCATION] = 1;
        $response = Http::post(self::PORTAL_URL,$post_data,$params);
        $return = [];

        if(stripos($response,'auth_username') === false){
            //未绑定成功
            preg_match_all('/<span.*?id=\"msg\".*?>(.*?)<\/span?>/si', $response, $errMsg);
            $return['status'] = false;
            $return['message'] = $errMsg[1][0];

        }else{
            //绑定成功
            $return['status'] = true;
        }  
        return $return;
    }
    */

    private function checkBind($username, $password){
        //判断数据库中有没有教师信息
        if (strlen($username) == 6) {
            $info = Db::name('teacher_detail')
                    ->where('ID',$username)
                    ->find();
            //为空则请求接口获取学院以及性别
            if (empty($info)) {
                //初始化
                $curl = curl_init();
                //设置抓取的url
                curl_setopt($curl, CURLOPT_URL, self::GET_INFO_URL);
                //设置头文件的信息作为数据流输出
                curl_setopt($curl, CURLOPT_HEADER, 0);
                //设置获取的信息以文件流的形式返回，而不是直接输出。
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                //设置post方式提交
                curl_setopt($curl, CURLOPT_POST, 1);
                //设置post数据
                $post_data = array(
                    "username" => $username,
                    "password" => $password
                    );
                curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
                //执行命令
                $responseInfo = curl_exec($curl);
                //关闭URL请求
                curl_close($curl);
                // $post_data = [
                //     "username" => $username,
                //     "password" => $password
                // ];
                // $response = Http::post(self::GET_INFO_URL,$post_data);
                $responseInfo = json_decode($responseInfo,true);
                if ($responseInfo['status'] == "success") {
                    $college_id = Db::name('dict_college') 
                            -> where("YXMC",$responseInfo['data']['college_name'])
                            -> field("YXDM")
                            -> find()["YXDM"];
                    $sex = $responseInfo['data']['sex'] == "男" ? 1 : 2;
                    $insertData = [
                        "ID"   =>  $username,
                        "XM"   =>  $responseInfo['data']["name"],
                        "XBDM" =>  $sex,
                        "MZDM" =>  "1",
                        "YXDM" =>  $college_id,
                        "SJH"  =>  "",
                        "LXDH" =>  "",
                        "ROLE" =>  "1"
                    ];
                    $res = Db::name("teacher_detail") -> insert($insertData);
                }
            } 
        }
                
        // 2020-06-11 LDAP验证更新，需要新传入type字段。
        $type = "";
        if (strlen($username) == 6) {
            $type = "JZG";
        } else {
            $userInfo = Db::name("stu_detail")->where("XH",$username)->field("XSLBDM")->find();
            if (empty($userInfo)) {
                $return["status"] = "false";
                $return["msg"] = "信息缺失，请联系管理员。";
                return $return;
            }
            $type = $userInfo["XSLBDM"] == '3' ? "BZKZ" : "YJS";
        }

        $post_data = [
            'userName' => $username,
            'pwd' => $password,
            'type' => $type,
        ];
        $return = [];
        $response = Http::post(self::TEST_URL,$post_data);
        $response = json_decode($response,true);
        $return['status'] = $response['success'] == "true" ? true:false;
        if ($return['status']) {
            $return['msg'] = "绑定成功!";
        } else {
            $return['msg'] = "绑定失败，请检查用户名或密码!";
            
        }
        return $return;

    }
    private function getTime(){
        $time = time();
        $d = date('d', $time);
        $m = date('m', $time);
        $y = date('Y', $time);
    }

}
