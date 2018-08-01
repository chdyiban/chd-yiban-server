<?php
namespace app\api\controller\yiban;

use app\common\controller\Api;
use think\Config;
use fast\Http;

class Oauth extends Api 
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

	protected $config = array();
    protected $initData = array();
    protected $verifyRequest = '';
    protected $token = '';

    protected $appId = '';

	protected $url_real_me = 'https://openapi.yiban.cn/user/real_me';
	protected $url_is_verify = 'https://openapi.yiban.cn/user/is_verify';

    protected $url_send_comment = 'https://openapi.yiban.cn/group/send_comment';

	public function _initialize()
    {
    	header('Access-Control-Allow-Origin:*');
        header("Content-type:text/html;charset=utf-8");

        $this->verifyRequest = $this->request->param('verify_request');

    	$this->appId = $this->request->get('appid');
        switch ($this->appId) {
            case 'd53201c6a67e6c8a':
                $this->config = array(
                    'AppID' => 'd53201c6a67e6c8a',
                    'AppSecret' => 'b95ffc5e949dcb1b59d56cae73289966',
                    'CallBack' => 'http://f.yiban.cn/iapp130591',
                    'AppName' => '晚点名签到'
                );
                break;
            case '6f4eebdb34d35396':
                $this->config = array(
                    'AppID' => '6f4eebdb34d35396',
                    'AppSecret' => 'e96eea67f20095248fd485f52245c1c6',
                    'CallBack' => 'http://f.yiban.cn/iapp134541',
                    'AppName' => '2017新生医保填报'
                );
                break;
            case 'efbdd89d3086b213':
                $this->config = array(
                    'AppID' => 'efbdd89d3086b213',
                    'AppSecret' => '37ab0998df1a46a611b6c621e055e1d8',
                    'CallBack' => 'http://f.yiban.cn/iapp146969',
                    'AppName' => '番茄工作法'
                );
                break;
            case '6e0cbf75e97edd64':
                $this->config = array(
                    'AppID' => '6e0cbf75e97edd64',
                    'AppSecret' => '34e2a57627ae06c147ad95604e7e06fe',
                    'CallBack' => 'http://f.yiban.cn/iapp193567',
                    'AppName' => '长大教务'
                );
                break;
            case '1d7d05c8cabf1668':
                $this->config = array(
                    'AppID' => '1d7d05c8cabf1668',
                    'AppSecret' => '23c0a876472a5d8b3f39f00c61dcc66e',
                    'CallBack' => 'http://f.yiban.cn/iapp195437',
                    'AppName' => '运动会报名'
                );
                break;
            case '3575ba8a53fe39f2':
                $this->config = array(
                    'AppID' => '3575ba8a53fe39f2',
                    'AppSecret' => '8a1779ddb92631c53d67cd3b279ea815',
                    'CallBack' => 'http://f.yiban.cn/iapp257565',
                    'AppName' => '2018新生找室友'
                );
                break;
            default:
                break;
        }
        if(empty($this->config)){
            $this->error('invalid appid');
        }
    }

    protected function checkParams(){

        $checkData = array();
        if(empty($this->config)){
            $checkData['status'] = 'error';
            $checkData['info'] = '参数非法';
            $checkData['code'] = '0x1001';
        }else{
            $this->token = $this->getToken($this->verifyRequest);

            //未获得授权
            if($this->token == null){
                $checkData['status'] = 'redirect';
                $checkData['info'] = '未获得易班授权或token非法，跳转中';
                $checkData['code'] = '0x1002';
            }else{
                $checkData['status'] = true;
            }
        }
        return $checkData;
    }

    protected function getToken(){
        $code = $this->verifyRequest;
        $decText = $this->decrypts($code);
        $token = json_decode($decText);

        if(!isset($token->visit_oauth->access_token)){
            return null;
        }
        return $token->visit_oauth->access_token;
    }

    // protected function getToken($code){
    // 	$decText = $this->decrypts($code);
    // 	$token = json_decode($decText);

    // 	if(!isset($token->visit_oauth->access_token)){
    // 		return null;
    // 	}
    // 	return $token->visit_oauth->access_token;
    // }

    /*
    protected function getStudentId($token){
        $result = json_decode(sendRequest($this->url_real_me.'?access_token='.$token));
        if(isset($result->info->yb_studentid)){
            return $result->info->yb_studentid;
        }else{
            return false;
        }
    }

    protected function getStudentInfo($token){
        $result = json_decode(sendRequest($this->url_real_me.'?access_token='.$token));

        if(isset($result->info->yb_studentid)){
            return $result->info;
        }else{
            return false;
        }
    }

    protected function sendComment($content){
        $post_data = array(
            'access_token' => $this->token,
            'organ_id'=>'5370552',
            'topic_id' => '34782690',
            'comment_content' => $content,
            );
        $result = json_decode(sendRequest($this->url_send_comment.'?access_token='.$this->token,$post_data));
        if($result->status == 'success'){
            return true;
        }else{
            return false;
        }
    }*/


	//解密授权信息
    protected function decrypts($code){
    	// $code = input('get.verify_request');

        $encText = addslashes($code);
        $strText = pack("H*", $encText);
        $decText = (strlen($this->config['AppID']) == 16) ? mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->config['AppSecret'], $strText, MCRYPT_MODE_CBC, $this->config['AppID']) : mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->config['AppSecret'], $strText, MCRYPT_MODE_CBC, $this->config['AppID']);
        if (empty($decText)) {
            return false;
        }

        return trim($decText);
    }

    //根据学号判断是否为2017级学生
    protected function checkGrade2017($id){
    	return (substr($id,0,4) == '2017') ? true : false;
    }

    protected function checkGrade($stu_id,$type='2018'){
        return (substr($stu_id,0,4) == $type) ? true : false;
    }


}