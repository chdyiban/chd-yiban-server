<?php

/**
 * @method 获取当前周数
 * @return 
 */
function get_weeks(){
    return date('W') - 9;
}
