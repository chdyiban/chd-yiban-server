<?php

namespace app\api\model;

use think\Model;
use fast\Http;

class Books extends Model
{
    // 表名
    protected $name = 'wx_user';
    //个人借阅情况
    const POST_BOOKS_URL = 'http://202.117.64.236:8000/booklst';
    //个人历史借阅情况
    const POST_HISTORY_URL = "http://202.117.64.236:8000/history";
    //查询欠费
    const POST_DBET_URL = "http://202.117.64.236:8000/fine";
    //续借地址
    const RENEW_URL = "http://202.117.64.236:8000/fine";


    /**
     * 获取用户借阅相关信息
     */
    public function get_books_data($key){
        $return_data = [];
        if (empty($key['id']) || empty($key['openid'])) {
            return ['data' => '','status' => false];
        } else {
            $username = $key['id'];
            $info = $this->where('open_id',$key['openid'])
                    -> where('portal_id',$username)
                    -> field('open_id,portal_pwd')
                    -> find();
            $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
            $get_data = [
                'username' => $username,
                'password' => $password
            ];
            $personal_result = Http::post(self::POST_BOOKS_URL,$get_data);
            $personal_result = json_decode($personal_result,true);
            if ($personal_result['msg'] == true) {
                $books_num = count($personal_result['booklst']);
    
                if ($books_num) {
                    $return_data['nothing'] = true;
                    $return_data['book_list'] = [];
                    foreach ($personal_result['booklst'] as  $value) {
                        $temp = array();
                        $temp['book'] = $value['book'];
                        $temp['bar_code'] = $value['bar_code'];
                        $temp['jsrq'] = $value['borrow_date'];
                        $temp['yhrq'] = $value['return_date'];
                        $temp['check'] = $value['check'];
                        $return_data['book_list'][] = $temp;
                    }
                    $return_data['books_num'] = $books_num;
                } else {
                    $return_data['books_num'] = $books_num;
                    $return_data['nothing'] = false;
                    $return_data['book_list'] = [];
                }
                $history_data = $this -> get_history_data($key);
                $dbet_data = $this -> get_dbet_data($key);
                $return_data['history'] =  $history_data['data']['history_count'];
                $return_data['dbet'] = $dbet_data['data']['dbet'];
                
            } 
            return ['data' => $return_data,'status' => true];
        }
        
    }
    /**
     * 获取用户的历史借阅信息
     */
    public function get_history_data($key)
    {
        if (empty($key['id']) || empty($key['openid'])) {
            return ['data' => '','status' => false];
        } else {
            $username = $key['id'];
            $info = $this->where('open_id',$key['openid'])
                    ->where('portal_id',$username)
                    ->field('open_id,portal_pwd')
                    ->find();
            $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
            $get_data = [
                'username' => $username,
                'password' => $password
            ];
            $history_result = Http::post(self::POST_HISTORY_URL,$get_data);
            $history_result = json_decode($history_result,true);
            $return_data = [];
            
            if ($history_result['msg'] == true) {
                $history_books_num = count($history_result['history']);
                if ($history_books_num) {
                    $return_data['history_count'] = $history_books_num;

                    foreach ($history_result['history'] as $value) {
                        $temp = array();
                        $temp['book'] = $value['name'];
                        $temp['bar_code'] = $value['bar_code'];
                        $temp['jsrq'] = $value['borrow_date'];
                        $temp['yhrq'] = $value['return_date'];
                        $return_data['history_list'][] = $temp;
                    }
                } else {
                    $return_data['history_count'] = 0;                
                }
            }

            return ['status' => true,'data'=>$return_data];
        }
    }

    /**
     * 获取用户欠费信息
     */
    public function get_dbet_data($key)
    {  
        if (empty($key['id']) || empty($key['openid'])) {
            return ['data' => '','status' => false];
        } else {    
            $username = $key['id'];
            $info = $this->where('open_id',$key['openid'])
                    ->where('portal_id',$username)
                    ->field('open_id,portal_pwd')
                    ->find();
            $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
            $get_data = [
                'username' => $username,
                'password' => $password
            ];
            $dbet_result = Http::post(self::POST_DBET_URL,$get_data);
            $dbet_result = json_decode($dbet_result,true);
            $return_data = [];
            $return_data['dbet'] = 0;
            if ($dbet_result['msg'] == true) {
                $dbet_count = count($dbet_result['fine']);
                if ($dbet_count) {
                    foreach ($dbet_result['fine'] as  $value) {
                        if ($value['status'] != "处理完毕") {
                            $return_data['dbet'] += $value['pay'];
                        }
                    }
                }
            }

            return ['status' => true, 'data' => $return_data];
        }
    }
    /**
     * 续借图书方法
     * @param $key['id'],$key['bar_code'],$key['check']
     */
    public function renew_books($key)
    {
        if (empty($key['id']) || empty($key['check']) || empty($key['bar_code'])) {
            return ['data' => '', 'status' => false];
        } else {
            $username = $key['id'];
            $bar_code = $key['bar_code'];
            $check = $key['check'];
            $info = $this->where('open_id',$key['openid'])
                    ->where('portal_id',$username)
                    ->field('open_id,portal_pwd')
                    ->find();
            $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
            $get_data = [
                'username' => $username,
                'password' => $password,
                'bar_code' => $bar_code,
                'check'   =>  $check,
            ];
            $result = Http::post(self::RENEW_URL,$get_data);
            $result = json_decode($result,true);
            return $result;
        }
    }
    


}