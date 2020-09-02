<?php 


namespace app\api\validate;

use think\Validate;

class Userinfo extends Validate 
{
    protected $rule =   [
        'XH'    => 'require',
        'SFGC'  => 'require',
        'RXQHK' => 'require|checkHK:',
        'BRDH'  => 'require|/^[1][3,4,5,6,7,8,9][0-9]{9}$/',
        'JTDH'  => 'require|/^[1][3,4,5,6,7,8,9][0-9]{9}$/',
		'BRQQ'  => 'number|length:5,14',
		
        'BRSG'  => 'require|number|between:100,230',
		'BRTZ'  => 'require|float|between:30,300 ',
		
        'qq'    => 'number|length:5,14',
        'SZDQ'  => 'require',
		'XXDZ'  => 'require|min:5',
		
        'CXCY'  => 'require|between: 1,4',
        'FQZY'  => 'require|between: 1,5', 
        'FQLDNL'=> 'require|between: 1,5',
        'MQZY'  => 'require|between: 1,5',
        'MQLDNL'=> 'require|between: 1,5',
        
        "JTRK"  => 'require|between: 1,10',
        "JTNSR" => 'require|between: 1,5',
        "JTZF"  => 'require|between: 1,7',
        "JTZC"  => 'checkquestionJTZC:',
        "JDQK"  => 'require|between: 1,3',
        "SYQK"  => 'require|between: 1,5',
        'YLZC'  => 'require|between: 1,4',
        'SZQK'  => 'require|between: 1,4',
        'JTBG'  => 'require|between: 1,3',
        'ZCYF'  => 'checkquestion:',  
    ];
    
    protected $message  =   [
        'XH' 	=> '学号不能为空',
        'SFGC' 	=> '请选择是否孤残',
        'RXQHK' => "请选择入学前户口",
        'BRDH' 	=> "请输入有效联系方式",
        'JTDH' 	=> "请输入有效的家庭联系方式",
        'BRQQ'  => '请填写正确的QQ号码',
        'BRSG.require'  => '身高不可以为空',
        'BRSG.between'  => '请填写正确的身高数值',
        'BRTZ.require'  => '体重不可以为空',
        'BRTZ.between'  => '请填写正确的体重数值',
        'qq'  	=> '请填写正确的QQ号码',
        'SZDQ' 	=> '所在地区不能为空',
        'XXDZ' 	=> '详细地址最少5字',

        'CXCY.require'      => "第一题不可以为空",
		'CXCY.between'      => "第一题选项在1-5之间",
		
        'FQZY.require'      => "第二题不可以为空",
		'FQZY.between'      => "第二题选项在1-5之间",
		
        'FQLDNL.require'    => "第三题不可以为空",
		'FQLDNL.between'    => "第三题选项在1-5之间",
		
        'MQZY.require'      => "第四题不可以为空",
		'MQZY.between'      => "第四题选项在1-5之间",
		
        'MQLDNL.require'    => "第五题不可以为空",
		'MQLDNL.between'    => "第五题选项在1-5之间",
		
        'JTRK.require'      => "第六题不可以为空",
		'JTRK.between'      => "第六题选项在1-10之间",
		
        'JTNSR.require'     => "第七题不可以为空",
		'JTNSR.between'     => "第七题选项在1-5之间",
		
        'JTZF.require' 		=> "第八题不可以为空",
		'JTZF.between' 		=> "第八题选项在1-5之间",
		
        "JTZC"          	=>	"请正确填写第九题",
        'JDQK.require' 		=> "第十题不可以为空",
        'JDQK.between' 		=> "第十题选项在1-3之间",
        'SYQK.require' 		=> "第十一题不可以为空",
        'SYQK.between' 		=> "第十一题选项在1-5之间",
        'YLZC.require' 		=> "第十二题不可以为空",
		'YLZC.between' 		=> "第十二题选项在1-4之间",
		
        'SZQK.require' 		=> "第十三题不可以为空",
        'SZQK.between' 		=> "第十三题选项在1-5之间",
        'JTBG.require' 		=> "第十四题不可以为空",
        'JTBG.between' 		=> "第十四题选项在1-5之间",
        'ZCYF' 				=> "正确填写第十五题",
    ];
    protected $scene = [
        'qq'     =>  ['qq'],
        'user'   =>  ['XH','SFGC','RXQHK','BRDH','JTDH','BRQQ','BRSG','BRTZ', 'SZDQ','XXDZ','CXCY', 'FQZY','FQLDNL','MQZY','MQLDNL',"JTRK","JTNSR","JTZF","JTZC","JDQK","SYQK",'YLZC','SZQK','JTBG','ZCYF', ],
    ];
     // 自定义验证规则
     protected function checkHK($value)
     {
         return in_array($value, ['城镇', '农村']);
     }

    protected function checkquestion($value)
    {
        if (empty($value)) {
            return true;
        } else {
            $array = explode(',', $value);
            foreach ($array as $key => $value) {
                if ($value < 1 || $value > 5) {
                    return false;
                } 
            }   
            return true;
        }
    }

    protected function checkquestionJTZC($value)
    {
        if (empty($value)) {
            return true;
        } else {
            $array = explode(',', $value);
            foreach ($array as $key => $value) {
                if ($value < 1 || $value > 7) {
                    return false;
                }
            }   
            return true;
        }
   }

    


}
