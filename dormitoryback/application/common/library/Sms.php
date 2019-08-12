<?php

namespace app\common\library;

use think\Hook;
use think\Config;
use fast\Http;

/**
 * 验证码类
 */
class Sms
{

    /**
     * 验证码有效时长
     * @var int 
     */
    protected static $expire = 120;

    /**
     * 最大允许检测的次数
     * @var int 
     */
    protected static $maxCheckNums = 10;

    /**
     * 获取最后一次手机发送的数据
     *
     * @param   int       $mobile   手机号
     * @param   string    $event    事件
     * @return  Sms
     */
    public static function get($mobile, $event = 'default')
    {
        $sms = \app\common\model\Sms::
                where(['mobile' => $mobile, 'event' => $event])
                ->order('id', 'DESC')
                ->find();
        Hook::listen('sms_get', $sms, null, true);
        return $sms ? $sms : NULL;
    }

    /**
     * 发送验证码
     *
     * @param   int       $mobile   手机号
     * @param   int       $code     验证码,为空时将自动生成4位数字
     * @param   string    $event    事件
     * @return  boolean
     */
    public static function send($mobile, $code = NULL, $event = 'default')
    {
        $code = is_null($code) ? mt_rand(1000, 9999) : $code;
        $time = time();
        $sms = \app\common\model\Sms::create(['event' => $event, 'mobile' => $mobile, 'code' => $code, 'createtime' => $time]);
        $result = Hook::listen('sms_send', $sms, null, true);
        if (!$result)
        {
            $sms->delete();
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 发送通知
     * 用官方样例对发送短信进行功能实现
     * 2018/11/1
     * @param   mixed     $mobile   手机号,多个以,分隔
     * @param   string    $msg      消息内容
     * @param   string    $template 消息模板
     * @return  array
     */
    // public static function notice($mobile, $msg = '', $template = NULL)
    // {
    //     $params = [
    //         'mobile'   => $mobile,
    //         'msg'      => $msg,
    //         'template' => $template
    //     ];
    //     $result = Hook::listen('sms_notice', $params, null, true);
    //     return $result ? TRUE : FALSE;
    // }
    public static function notice($mobile, $msg = '', $template = NULL)
    {
        $statusStr = array(
            "0"  => "短信发送成功",
            "-1" => "参数不全",
            "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
        );	
        $smsapi = "http://www.smsbao.com/"; //短信网关
        $user = Config::get('sms')['user']; //短信平台帐号
        $pass = md5(Config::get('sms')['password']); //短信平台密码
        $content = $msg;//要发送的短信内容
        $phone = $mobile;
        $sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$phone."&c=".urlencode($content);
        $result =file_get_contents($sendurl) ;
        return ['status'=>$result,'msg'=> $statusStr[$result]];
    }

    /**
     * 校验验证码
     *
     * @param   int       $mobile     手机号
     * @param   int       $code       验证码
     * @param   string    $event      事件
     * @return  boolean
     */
    public static function check($mobile, $code, $event = 'default')
    {
        $time = time() - self::$expire;
        $sms = \app\common\model\Sms::where(['mobile' => $mobile, 'event' => $event])
                ->order('id', 'DESC')
                ->find();
        if ($sms)
        {
            if ($sms['createtime'] > $time && $sms['times'] <= self::$maxCheckNums)
            {
                $correct = $code == $sms['code'];
                if (!$correct)
                {
                    $sms->times = $sms->times + 1;
                    $sms->save();
                    return FALSE;
                }
                else
                {
                    $result = Hook::listen('sms_check', $sms, null, true);
                    return $result;
                }
            }
            else
            {
                // 过期则清空该手机验证码
                self::flush($mobile, $event);
                return FALSE;
            }
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * 清空指定手机号验证码
     *
     * @param   int       $mobile     手机号
     * @param   string    $event      事件
     * @return  boolean
     */
    public static function flush($mobile, $event = 'default')
    {
        \app\common\model\Sms::
                where(['mobile' => $mobile, 'event' => $event])
                ->delete();
        Hook::listen('sms_flush');
        return TRUE;
    }

}
