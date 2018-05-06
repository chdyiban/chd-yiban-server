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