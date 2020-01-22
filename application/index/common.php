<?php

use think\config;
/**
 * @method 获取当前周数
 * @return 
 */
function get_weeks(){
    // return date('W') - 8;
    return date('W') - 34;
}

/**
 * @method 生成随机字符串
 * @return DSG前缀的10位字符串
 */
function rand_str_10(){
    $str="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
    $str='DSG'.substr(str_shuffle($str),5,7);
    return $str;
}
//对称加密算法
function _token_encrypt($data, $key)  
{
    $char = '';
    $str = '';
    $key    =   md5($key);  
    $x      =   0;  
    $len    =   strlen($data);  
    $l      =   strlen($key);  
    for ($i = 0; $i < $len; $i++)  
    {  
        if ($x == $l)   
        {  
            $x = 0;  
        }  
        $char .= $key{$x};  
        $x++;  
    }  
    for ($i = 0; $i < $len; $i++)  
    {  
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);  
    }  
    return base64_encode($str);  
} 
//对应解密算法
function _token_decrypt($data, $key)
{
    $char = '';
    $str = '';
	$key = md5($key);
    $x = 0;
    $data = base64_decode($data);
    $len = strlen($data);
    $l = strlen($key);
    for ($i = 0; $i < $len; $i++){
        if ($x == $l){
        	$x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++){
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))){
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }else{
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return $str;
}

/**
 * 根据实际情况，若unix时间戳为每天零点，则只返回“YYYY-mm-dd”，其他则返回详细时间
 * @param $unix_time unix时间戳
 * @return $str_time 字符串类型时间戳
 */
function formatTime($unix_time){
    if(!is_numeric($unix_time)){
        return $unix_time;
    }
    if(date('H',$unix_time) == 0 && date('i',$unix_time) == 0 && date('H',$unix_time) == 0){
        //0点0分0秒的情况
        return date('Y-m-d', $unix_time); 
    }else{
        return date('Y-m-d H:i:s', $unix_time); 
    }
}

//返回样例:{"err_no":0,"err_str":"OK","pic_id":1662228516102,"pic_str":"8vka","md5":"35d5c7f6f53223fbdc5b72783db0c2c0","str_debug":""}
function recognize_captcha($base64_str){
	$url = 'http://upload.chaojiying.net/Upload/Processing.php' ; 
	$fields = array( 
        'user' => Config::get('cjy.user'),
        'pass2' => md5(Config::get('cjy.pass')),
        'softid' => Config::get('cjy.softid') ,
        'codetype' => Config::get('cjy.codetype') ,
	    'file_base64'=>$base64_str
	); 
	
	$ch = curl_init() ;  
	curl_setopt($ch, CURLOPT_URL,$url) ;  
	curl_setopt($ch, CURLOPT_POST,count($fields)) ;   
	curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回  
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回  
	curl_setopt($ch, CURLOPT_REFERER,'') ; 
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3') ;
	$result = curl_exec($ch); 
	curl_close($ch) ;
		
    return $result ;
  }
    