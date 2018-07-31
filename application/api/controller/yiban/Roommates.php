<?php
namespace app\api\controller\yiban;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;

class Roommates extends Oauth
{
	protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

	public function find(){
    	//$code = $this->request->param('verify_request');
    	$token = $this->getToken();

    	$initData = array();
    	//未获得授权
    	if($token === null){
			$this->success('未获得易班授权，跳转中','','oauth');
    	}
    	
    	//判断是否进行校级认证

    	//
    	$uri = $this->url_real_me.'?access_token='.$token;


		$result = Http::get($uri);

		$result = json_decode($result,true);

		$result['info']['yb_studentid'] = '2018900001';

		if(empty($result['info']['yb_studentid'])){
			$this->error('用户信息不存在');
		}


		if(!$this->checkGrade($result['info']['yb_studentid'],'2018')){
			$this->error('暂时仅2018级学生使用');
    	}

		$result['info']['yb_studentid'] = '2017900716';
    	//找到室友信息并返回

    	$stuInfo = Db::name('fresh_list')->where('XH',$result['info']['yb_studentid'])->find();
    	if($stuInfo['SSDM']){
    		$roommates = Db::view('fresh_list') 
                -> view('fresh_info','XM ,SYD, XH','fresh_list.XH = fresh_info.XH')
                -> where('SSDM', $stuInfo['SSDM'])
                -> where('fresh_list.XH', '<>', $result['info']['yb_studentid'])
                -> where('status','finished')
                -> select();
    		$data = [
    			'personal' => $result['info'],
    			'roommates' => $roommates,
    		];
    		$this->success('success',$data);
    	}else{
    		$this->error('尚无住宿信息');
    	}

    	
		
    }
}