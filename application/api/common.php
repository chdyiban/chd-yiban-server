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