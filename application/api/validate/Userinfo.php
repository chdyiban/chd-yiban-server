<?php 


namespace app\api\validate;

use think\Validate;

class Userinfo extends Validate 
{
    protected $rule =   [
        'XH' => 'require|number',
        'RXQHK' => 'require|checkHK:',
        'JTRKS' => 'require|between:1,20',
        'YZBM'  => 'require|number|length:6',
        'BRDH' => 'require|/^[1][3,4,5,7,8][0-9]{9}$/',
        'SZDQ' => 'require|chs',
        'XXDZ' => 'require|min:5',
        'ZSR' => 'require|number',
        'RJSR' => 'require|number',
        'ZP' => 'require|url',
        'FQZY' => 'require|between: 1,5',
        'MQZY' => 'require|between: 1,5',
        'FQLDNL' => 'require|between: 1,5',
        'MQLDNL' => 'require|between: 1,5',
        'YLZC' => 'require|between: 1,4',
        'SZQK' => 'require|between: 1,4',
        'JTBG' => 'require|between: 1,3',
        'ZCYF' => 'require|checkquestion:',  
        'XM' =>  'require|chs',
        'NL' => 'require|number|between:1,120',
        'GX' => 'require|chs',
        'ZY' => 'require|chs',
        'NSR' => 'require|number',
        'JKZK' => 'require|chs',
        'LXDH' => 'require|/^[1][3,4,5,7,8][0-9]{9}$/',
    ];
    
    protected $message  =   [
        'XH' => '学号必须为数字',
        'RXQHK' => "请选择正确选项",
        'JTRKS' => "人口数只能在1-20之间",
        'YZBM'  => "邮政编码为6位数字",
        'BRDH' => "请输入有效联系方式",
        'SZDQ' => '所在地区必须为汉字',
        'XXDZ' => '详细地址最少5字',
        'ZSR' => '总收入必须为数字',
        'RJSR' => '人均收入必须为数字',
        'ZP' => "请输入有效照片地址",
        'FQZY' => "选项在1-5之间",
        'MQZY' => "选项在1-5之间",
        'FQLDNL' => "选项在1-5之间",
        'MQLDNL' => "选项在1-5之间",
        'YLZC' => "选项在1-4之间",
        'SZQK' => "选项在1-4之间",
        'JTBG' => "选项在1-3之间",
        'ZCYF' => "请选择正确的选项",
        'XM' =>  '姓名必须为汉字',
        'NL.number' => '年龄必须为数字',
        'NL.between' => '年龄必须在1-120间',
        'GX' => '请填写正确的关系',
        'ZY' => '请填写正确的职业',
        'NSR' => '年收入需要为纯数字',
        'JKZK' => '填写正确的健康状况',
        'LXDH' => '请填写有效的手机号',
    ];
    protected $scene = [
        'user'  =>  ['XH','RXQHK','JTRKS', 'YZBM','BRDH','SZDQ', 'XXDZ','ZSR','RJSR' ,'FQZY' ,'MQZY' ,'FQLDNL','MQLDNL','YLZC' ,'SZQK' ,'JTBG','ZCYF'],
        'family' => ['XM', 'NL','GX','ZY','NSR','JKZK','LXDH'],
    ];
     // 自定义验证规则
     protected function checkHK($value)
     {
         return in_array($value, ['城镇', '农村']);
     }

     protected function checkquestion($value)
     {
        if (empty($value)) {
            return false;
        } else {
            $array = explode(',', $value);
            foreach ($array as $key => $value) {
                if ($value < 1 || $value > 5) {
                    return false;
                } else {
                    return true;
                }
            }   
        }
    }

}
