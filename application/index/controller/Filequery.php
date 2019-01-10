<?php
namespace app\index\controller;
use think\Controller;
use app\index\model\Fileuser;
use think\captcha\Captcha;

class Filequery extends Controller
{

    public function index()
    {

        return $this->fetch();
    }

    function checkChinese($string)
{
    if(preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $string) === 1){
        //全是中文
        return 1;
    }else{
        return 0;
    }
}
 
    // public function choose()
    // {
    //     $user=new User(); 

    //     if(isset($_POST["submit"])){ 
    //         $captcha=input('post.verify');
    //         if(!captcha_check($captcha)){
    //             dump('cuowu');
    //             return $this->error('验证码错误！','index');
    //         }
    //         $user->number=input('post.number');
    //         if(!$user->number){
    //             return $this->error('未提交数据','index');
    //         }
    //         if(!is_numeric($user->number)){
    //             $result = $user->all(['name' =>  $user->number]);
                
    //             if(!$result)return $this->error('查无此人！','index');
    //             $this->assign('result',$result);

    //         }else{
    //             $result = $user->all(['number' =>  $user->number]);
    //             if(!$result)return $this->error('查无此人！','index');
    //             $this->assign('result',$result);
    //         }
    //         return $this->fetch();
    //     }else{ 
    //             return $this->error('未提交数据','index');
    //     }
    // }
    public function search($id)
    {
        $user=new Fileuser(); 
        $result = $user->get($id);
        $this->assign('result',$result);
        return $this->fetch();
    }

    public function choose($number,$verify){

        if(!captcha_check($verify)){
            return ['result'=>"验证码错误",'flag'=>1];
        }
        if(!$number){
            return ['result'=>"未输入学号或姓名",'flag'=>1];
        }
        $user=new Fileuser(); 
    	if($this->checkChinese($number)){
            $result = $user->all(['name' =>  $number]);   
            if(!$result) 
                return ['result'=>"对不起，没有查到您的遗留档案记录，学工部掌握的毕业生遗留档案为各院（系）提交的，如查询未果，请与当时所在学院或辅导员（班主任）联系。",'flag'=>1];
            $this->assign('result',$result);
    	}else{
			$result = $user->all(['number' => $number]);
            if(!$result)
                return ['result'=>"对不起，没有查到您的遗留档案记录，学工部掌握的毕业生遗留档案为各院（系）提交的，如查询未果，请与当时所在学院或辅导员（班主任）联系。",'flag'=>1];
            $this->assign('result',$result);
        }
        return $this->fetch();
    }


    //验证码
    public function verify()
    {
        $captcha = new Captcha();
        return $captcha->entry();
    }
}
