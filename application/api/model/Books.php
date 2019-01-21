<?php

namespace app\api\model;

use think\Model;
use fast\Http;

class Books extends Model
{
    // 表名
    protected $name = 'wx_user';
    //个人借阅情况
    const GET_BOOKS_URL = 'http://202.117.64.236:8000/booklst';
    
    public function get_books_data($key){
        $return_data = [];
        $username = $key['id'];
        $info = $this->where('portal_id',$username)->field('open_id,portal_pwd')->find();
        $password = _token_decrypt($info['portal_pwd'], $info['open_id']);
        $get_data = [
            'username' => $username,
            'password' => $password
        ];
        $personal_result = Http::get(self::GET_BOOKS_URL,$get_data);
        $personal_result = json_decode($personal_result,true);
        if ($personal_result['info']['msg'] == true) {
            $books_num = count($personal_result['info']['info']) - 1;

            if ($books_num) {
                $return_data['nothing'] = true;
                $return_data['book_list'] = [];
                for ($i = 1; $i < $books_num+1; $i++) { 
                    $temp = array();
                    $temp['book'] = $personal_result['info']['info'][$i][1];
                    $temp['jsrq'] = $personal_result['info']['info'][$i][2];
                    $temp['yhrq'] = $personal_result['info']['info'][$i][3];
                    $return_data['book_list'][] = $temp;
                }
                $return_data['books_num'] = $books_num;
            } else {
                $return_data['books_num'] = $books_num;
                $return_data['nothing'] = false;
                $return_data['book_list'] = [];
            }

            $return_data['history'] =  $books_num;
            $return_data['dbet'] = 0;
        } 
        return $return_data;
        
    }

}