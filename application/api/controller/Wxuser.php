<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;


/**
 * 微信小程序登录接口
 */
class Wxuser extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';

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
                    //bindInfo为学号，通过学号来查询学生信息.
                    $info = Db::connect('chd_config')
                    ->view('chd_stu_detail')
                    ->where('XH', $bindInfo)
                    ->view('chd_dict_nation','MZDM,MZMC','chd_stu_detail.MZDM = chd_dict_nation.MZDM')
                    ->view('chd_dict_major','ZYDM,ZYMC','chd_stu_detail.ZYDM = chd_dict_major.ZYDM')
                    ->view('chd_dict_college','YXDM,YXMC,YXJC','chd_stu_detail.YXDM = chd_dict_college.YXDM')
                    ->find();
                    //年级将学号的前四位截取
                    $info['NJ'] = substr($info['XH'],0,4);
                    $data = [
                        'is_bind' => true,
                        'user' => [
                            'openid' => $result['openid'],
                            'type' => (strlen($bindInfo) == 6) ? '教职工' : '学生',
                            'id' => $bindInfo,
                            'info'=>[
                                'yxm'=>$info['YXMC'],
                                //如果注释掉这两个，则跳转到完善信息界面
                                'ssh'=>'12#6128',
                                'sjh'=>'13700000000'
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
                            'day' => '2'
                        ],
                        'token' => 'just a token',
                        'status' => 200,
                    ];
                }else{
                    $data = [
                        'is_bind' => false,
                        'status' => 200,
                    ];
                }
                
                $data['data'] = base64_encode(json_encode($data));
                $data['msg'] = 'success';
                return json($data);
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
    }

    public function set(){
        
    }

    public function bind(){
        $key = json_decode(base64_decode($this->request->post('key')),true);

        $bindInfo = $this->checkBind($key['stuid'],$key['passwd']);
        if($bindInfo['status'] === true){
            $user = new WxuserModel;
            $bindStatus = $user->save([
                'portal_id' => $key['stuid'],
                'portal_pwd' => $key['passwd']
            ],['open_id' => $key['openid']]);
            if($bindStatus){
                $info = [
                    'status' => 200,
                    'message' => '绑定成功'
                ];
            }else{
                $info = [
                    'status' => 200,
                    'message' => '请稍后再试'
                ];
            }
        }else{
            $info = [
                'status' => 404,
                'message' => $bindInfo['message']
            ];
        }
        
        return json($info);
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

    //模拟登录验证用户名密码正确性，暂时未考虑验证码的情况
    private function checkBind($username, $password, $captcha = ''){

        $params[CURLOPT_COOKIEJAR] = RUNTIME_PATH .'/cookie/cookie_'.$username.'.txt';

        if($captcha == ''){
            //无验证码情况下

            //1.获取lt es
            $response = Http::get(self::PORTAL_URL,'',$params);
            $lt = explode('name="lt" value="', $response);
        	$lt = explode('"/>', $lt[1]);
        	$lt = $lt[0];
	
        	$es = explode('name="execution" value="', $response);
        	$es = explode('"/>', $es[1]);
            $es = $es[0];
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
            //时间原因，暂时不考虑验证码的情况
            return false;
        }
    }

    private function getTime(){
        $time = time();
        $d = date('d', $time);
        $m = date('m', $time);
        $y = date('Y', $time);
    }

}
