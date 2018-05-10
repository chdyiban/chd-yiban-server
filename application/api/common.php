<?php

/**
 * @method 获取当前周数
 * @return 
 */
function get_weeks(){
    return date('W') - 9;
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