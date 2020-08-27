<?php

namespace app\api\controller\dormitory2020;

use app\common\library\Token;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Loader;
use think\Request;
use think\Response;
use app\api\model\dormitory2020\User as userModel;
use app\api\model\dormitory2020\Wxuser as wxUserModel;
use think\Config;
use think\Db;

/**
 * 订阅号API控制器基类
 */
class Api
{

    /**
     * @var Request Request 实例
     */
    protected $request;

    /**
     * @var bool 验证失败是否抛出异常
     */
    protected $failException = false;

    /**
     * @var bool 是否批量验证
     */
    protected $batchValidate = false;

    /**
     * 无需绑定门户的方法
     * @var array
     */
    protected $noNeedBindPortal = [];

    /**
     * 默认响应输出类型,支持json/xml
     * @var string 
     */
    protected $responseType = 'json';

    /**
     * 用户信息
     * @var array
     */
    protected $_user = NULL;

    /**
     * 用户绑定状态信息
     * @var array
     */
    protected $_bindStatus = NULL;

    /**
     * 用户token
     * @var string
     */
    protected $_token = '';
    protected $requestUri = '';
    //默认配置
    protected $config = [];
    protected $options = [];


    /**
     * 构造方法
     * @access public
     * @param Request $request Request 对象
     */
    public function __construct(Request $request = null)
    {
        $this->request = is_null($request) ? Request::instance() : $request;

        // 控制器初始化
        $this->_initialize();
    }

    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
            header('Access-Control-Allow-Origin:*');  
            header('Access-Control-Allow-Methods:POST,GET,OPTIONS'); 
            header('Access-Control-Allow-Headers: Authorization');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 600');
            exit;
        }
        header('Access-Control-Allow-Origin:*');  
        // header('Access-Control-Max-Age: 600');
        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());

        $token = $this->request->header('Authorization');

        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->setRequestUri($path);

        //判断方法是否需要绑定门户
        if (!$this->match($this->noNeedBindPortal)) {
            //初始化
            $ret = $this->init($token);
            if (!$ret) {
                $this->error('Token Expired', null, 0,null,["statuscode" => "403"]);
            }
            if (!$this->isBindPortal())
            {
                $this->error('Please bind Portal Account first', null, 0,null,["statuscode" => "403"]);
            } 
        } else {
            // 如果有传递token才验证是否登录状态
            if ($token) {
                $this->init($token);
            }
        }
    }

     /**
     * 判断用户是否绑定信息门户
     * @return boolean
     */
    public function isBindPortal()
    {
        if ($this->_bindStatus["is_bind"])
        {
            return true;
        }
        return false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 获取当前请求的URI
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * 设置当前请求的URI
     * @param string $uri
     */
    public function setRequestUri($uri)
    {
        $this->requestUri = $uri;
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr)
        {
            return FALSE;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr))
        {
            return TRUE;
        }

        // 没找到匹配
        return FALSE;
    }

    /**
     * 根据Token初始化
     *
     * @param string       $token    Token
     * @return boolean
     */
    public function init($token)
    {
        $userModel = new userModel();
        $data = Token::get($token);
        /**data
         *  ["token"] =string(36) "b24d3f6a-e433-4144-9e00-8680cbad7bfb"
         *  ["user_id"] = int(1)
         *  ["createtime"] = int(1562827900)
         *  ["expiretime"] = int(1565419900)
         *  ["expires_in"] = int(2591068) 
         * */ 
        if (!$data)
        {
            return FALSE;
        }
        $user_id = $data['user_id'];
        if (!empty($user_id))
        {
            $is_bind = false;
            $is_bind_mobile = false;
            $userInfo = $userModel->where("id",$user_id)->find();
            if (empty($userInfo))
            {
                $this->error('Please Bind Portal Correct Account first', null, 0,null,["statuscode" => "403"]);
            }
            // 验证用户是否绑定微信
            if (!$this->checkWechatAuth($userInfo["unionid"]))
            {
                $this->error(__('Please finish WeChat authorization first'), null, 0,null,["statuscode" => "401"]);
            }

            $is_bind_mobile = $this->checkMobileBind($userInfo["unionid"]);
            $openid = Db::name("wx_unionid_user")->where("unionid",$userInfo["unionid"])->field("open_id")->find();
            $userInfo["openid"] = $openid["open_id"];
            $userInfo["XQ"] = $userInfo["type"] == 0 ? "north" : "south";
            $userInfo["step"] = $this->getStep($userInfo);
            $this->_user    = $userInfo;
            $this->_token   = $token;
            $this->_bindStatus = [
                "is_bind"  =>  true,
                "is_bind_mobile" => $is_bind_mobile,
            ];

            return TRUE;
        } else {
            $this->error(__('Please bind Portal Account first'), null, 0,null,["statuscode" => "403"]);
        }
    }

     /**
     * 获取学生当前步骤
     * @param array userinfo
     * @return array 
     */
    public function getStep($param)
	{
		$XQ = $param["XQ"];
        $temp = [];
        $timeList = Config::get("dormitoryStep.$XQ"); 
        foreach ($timeList as $key => $value) {
            $start_time = strtotime($value["start"]);
            $end_time   = strtotime($value["end"]);
            $now_time   = strtotime('now');
            if ($now_time <= $end_time && $now_time >= $start_time) {
                $YXDM = $param["YXDM"];
                if ($value["step"] == "NST") {
                    $temp = [
                        "step"       => $value["step"],
                        "msg"        => $value["msg"],
                        "start_time" => Config::get("dormitory.$YXDM"),
                    ];
                } elseif ($value["step"] == "FML") {
                    $YXDM = $param["YXDM"];
					$start_college_time = Config::get("dormitory.$YXDM");
					$start_college_time_back = strtotime($start_college_time);
                    if ($now_time < $start_college_time_back) {
                        $temp = [
                            "step"  => "NST",
                            "msg"   => "未开始",
                            "start_time" => $start_college_time,
                        ];
                    } else {
                        $temp = [
                            "step"  => $value["step"],
                            "msg"   => $value["msg"],
                        ];
                    }
                } else {
                    $temp = [
                        "step"  => $value["step"],
                        "msg"   => $value["msg"],
                    ];
                }
                break;
			}
		}
		return $temp;
	}
    /**
     * 操作成功返回的数据
     * @param string $msg   提示信息
     * @param mixed $data   要返回的数据
     * @param int   $code   错误码，默认为 1
     * @param string $type  输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function success($msg = '', $data = null, $code = 1, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据
     * @param string $msg   提示信息
     * @param mixed $data   要返回的数据
     * @param int   $code   错误码，默认为0
     * @param string $type  输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function error($msg = '', $data = null, $code = 0, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed  $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型，支持json/xml/jsonp
     * @param array  $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result($msg, $data = null, $code = 0, $type = null, array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => Request::instance()->server('REQUEST_TIME'),
            'data' => $data,
        ];
        // 如果未设置类型则自动判断
        $type = $type ? $type : ($this->request->param(config('var_jsonp_handler')) ? 'jsonp' : $this->responseType);

        if (isset($header['statuscode']))
        {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        }
        else
        {
            //未设置状态码,根据code值判断
            $code = $code >= 1000 || $code < 200 ? 200 : $code;
        }
        $response = Response::create($result, $type, $code)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @param  mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate))
        {
            $v = Loader::validate();
            $v->rule($validate);
        }
        else
        {
            // 支持场景
            if (strpos($validate, '.'))
            {
                list($validate, $scene) = explode('.', $validate);
            }

            $v = Loader::validate($validate);

            !empty($scene) && $v->scene($scene);
        }

        // 批量验证
        if ($batch || $this->batchValidate)
            $v->batch(true);
        // 设置错误信息
        if (is_array($message))
            $v->message($message);
        // 使用回调验证
        if ($callback && is_callable($callback))
        {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data))
        {
            if ($this->failException)
            {
                throw new ValidateException($v->getError());
            }

            return $v->getError();
        }

        return true;
    }

     /**
     * 验证用户是否微信授权
     * @access public
     * @param string $unionid 用户unionid
     * @return bool
     */
    public function checkWechatAuth($unionid)
    {
        if (empty($unionid) ){
            return false;
        }
        $wxUserModel = new wxUserModel;
        $userCount = $wxUserModel->where("unionid",$unionid) -> field("id") -> count();
        if($userCount == 1) {
            return true;
        }
        return false;
    }
     /**
     * 验证用户是否绑定手机
     * @access public
     * @param string $unionid 用户unionid
     * @return bool
     */
    public function checkMobileBind($unionid)
    {
        if (empty($unionid) ){
            return false;
        }
        $wxUserModel = new wxUserModel;
        $user_mobile = $wxUserModel->where("unionid",$unionid) -> field("mobile") -> find();
        if(!empty($user_mobile["mobile"])) {
            return true;
        }
        return false;
    }


}
