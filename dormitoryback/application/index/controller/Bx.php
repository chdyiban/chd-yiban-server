<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use think\Db;
use fast\Http;
use think\cache;


class Bx extends Frontend
{
    // protected $noNeedLogin = ['detail','getAccessToken'];
    // protected $noNeedRight = ['detail','getAccessToken'];
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    const GET_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
    const GET_TICKET_URL = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=';
    const APPID = "wx7127494fe62b5813";
    const APPSECRIPT = "8ceca4754c5225323bcddf71469dfd3a";
    const DOMAIN = "https://yiban.chd.edu.cn";

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Repairlist');
        //获取总控的id
        $this -> control_id = Db::view('auth_group') 
                -> view('auth_group_access','uid,group_id','auth_group.id = auth_group_access.group_id')
                -> where('name','报修管理员') 
                -> find()['uid'];
    }
    /**
     * 
     */
    public function wxrouter()
    {
        $appid = self::APPID;
        $domain = self::DOMAIN;
        $listId = $this->request->param('list_id');
        $func = $this->request->param('func');
        $type = $this->request->param('type');
        if (empty($func) || empty($listId)) {
            $this -> error();
        } else {     
            $this->view->assign([
                'appid' => $appid,
                'domain' => $domain,
                'listId' => $listId,
                'type' => $type,
                'func' => $func,
            ]);
            return $this->view->fetch();
        }
    }
    /**
     * 订单详情界面,向工人展示
     * @param int open_id
     * @param int list_id
     */
    public function distribute()
    {
        $list_id = $this->request->param('list_id');
        $code  = $this->request->param('code');
        $type  = $this->request->param('type');
        $appid = self::APPID;
        $appsecript = self::APPSECRIPT;
        /**
         * 如果获取到code参数，请求https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
         * 检测返回JSON 有openid 则说明网页授权成功
         * 拿openid 和listid去查，有则返回 完工页面，{{status}}，无则显示当前无派工任务。
         */
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecript&code=$code&grant_type=authorization_code";
        if (!empty($code)) {
            $result = Http::get($url);
            $result = json_decode($result,true);
            if (!empty($result['openid'])) {
                $openId = $result['openid'];
                $checkInfo = Db::name("repair_bind") -> where('open_id',$openId) -> find();
                if (!empty($checkInfo)) {
                    $listInfo = Db::name('repair_list') 
                            -> where('id',$list_id)
                            -> find();
                    $companyList = array();
                    $com_id = Db::name('auth_group') -> where('name','报修单位') -> field('id') -> find()['id'];
                    //获取公司名称
                    $company = Db::view('auth_group_access') 
                                -> view('admin','nickname,id','auth_group_access.uid = admin.id')
                                -> where("group_id = $com_id") 
                                -> select();
                    $this->view->assign("companyInfo",$company);
                    $this->view->assign("listInfo",$listInfo);
                    return $this->view->fetch();
                }
            }
        }
    }
    /**
     * 订单详情界面,向工人展示
     * @param int open_id
     * @param int list_id
     */
    public function detail()
    {
        $list_id = $this->request->param('list_id');
        $code  = $this->request->param('code');
        $type  = $this->request->param('type');
        if ($type == "finish") {
            $res = $this -> finishWork($list_id);
            $listInfo = $this->model->get($list_id);
            $this->view->assign("listInfo",$listInfo);
            return $this->view->fetch();
        }
        $appid = self::APPID;
        $appsecript = self::APPSECRIPT;
        /**
         * 如果获取到code参数，请求https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
         * 检测返回JSON 有openid 则说明网页授权成功
         * 拿openid 和listid去查，有则返回 完工页面，{{status}}，无则显示当前无派工任务。
         */
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecript&code=$code&grant_type=authorization_code";
        if (!empty($code)) {
            $result = Http::get($url);
            $result = json_decode($result,true);
            if (!empty($result['openid'])) {
                $openId = $result['openid'];
                $checkInfo = Db::name("repair_bind") -> where('type',2) -> where('open_id',$openId) -> find();
                if (!empty($checkInfo)) {
                    $listInfo = Db::name('repair_list') 
                            -> where('id',$list_id)
                            // -> where('status','dispatched') 
                            -> where('dispatched_id',$checkInfo['user_id']) 
                            -> find();
                    $this->view->assign("listInfo",$listInfo);
                    return $this->view->fetch();
                }
            }
        }
    }
    /**
     * 后勤等公司派工界面
     * 此处采取前端渲染方式供后勤派工
     */
    public function dispatch()
    {
        $list_id = $this->request->param('list_id');
        $code  = $this->request->param('code');
        $appid = self::APPID;
        $appsecript = self::APPSECRIPT;
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecript&code=$code&grant_type=authorization_code";
        if (!empty($code)) {
            $result = Http::get($url);
            $result = json_decode($result,true);
            if (!empty($result['openid'])) {
                $openId = $result['openid'];
                $checkInfo = Db::name("repair_bind")->where('type',1) -> where('open_id',$openId) -> find();
                if (!empty($checkInfo)) {
                    $listInfo = Db::name('repair_list') 
                            -> where('id',$list_id) 
                            // -> where('status','distributed')
                            -> where('distributed_id',$checkInfo['user_id'])
                            -> find();
                    $workerInfo = Db::name('repair_worker')->where('distributed_id',$checkInfo['user_id'])->select();
                    $this->view->assign("workerInfo",$workerInfo);
                    $this->view->assign("listInfo",$listInfo);
                    $this->view->assign("adminId",$checkInfo['user_id']);
                    return $this->view->fetch();
                }
            }
        }
    }
    /**
     * 为公司分配人员
     */
     public function dispatchWorker(){  
        $workerId = $this->request->param('workerId');
        $listId = $this->request->param('listId');
        $res = model("Repairlist")->dispatch($listId, $workerId);
        return $res;
    }
    /**
     * 微信端完成工单 
     * @param int list_id
     */
    public function finishWork($listId)
    {
        if (empty($listId)) {
            $this->error();
        }
        $res = model("Repairlist")->finish($listId);
        return json($res);
    }
    /**
     * 总控分配工人或者单位
     */
    public function distributeWorker()
    {
        if ($this->request->isPost()){
            $ids = $this->request->param("listId");
            $company_id = $this->request->param('companyId');
            $worker_id = $this->request->param('workerId');
            if (empty($company_id)) {
                $this -> error("请选择派遣单位");
            } else {
                if (empty($worker_id)) {
                    $this->model->accept($ids,$this -> control_id);
                    $res = $this->model->distribute($ids, $company_id);
                    return $res;
                } else {
                    $this->model->accept($ids,$this -> control_id);
                    $res = $this->model->distribute($ids, $company_id);
                    $re  =  $this->model->dispatch($ids, $worker_id);
                    return $res&&$re;
                }
            }
        } else {
            $this -> error('请求错误');
        }
    }

    //驳回订单
    public function refuse(){
        if ($this->request->isPost()){
            $ids = $this->request->post('listId');
            $content = $this->request->post('content');
            $res = $this->model->refuse($ids, $content);
            return $res;
        }else{
            $this->error('请求失败');
        }
    }

    /**
     * 获取工人列表
     * @param int adminId
     * @return json 
     */
    public function getWorkerJson($companyId)
    {
        //判断是否自修
        $workerList = array();
        $companyName = Db::name('admin') -> where('id',$companyId) -> field('nickname')->find()['nickname'];
        //将自修id换成总控id
        if ($companyName == "自修") {
            $control_id = $this -> control_id;
            $worker = Db::name('repair_worker') -> where('distributed_id',$control_id) -> select();
            foreach ($worker as $key => $value) {
                $tempArray = array();
                $tempArray['value'] = $value['id'];
                $tempArray['name'] = $value['name']."-".$value['mobile'];
                $workerList[] = $tempArray;
            }
            //后勤不分配工人
        } elseif ($companyName == "后勤") {
            $workerList = [];
        } else {
            $control_id = $companyId;
            $worker = Db::name('repair_worker') -> where('distributed_id',$control_id) -> select();
            foreach ($worker as $key => $value) {
                $tempArray = array();
                $tempArray['value'] = $value['id'];
                $tempArray['name'] = $value['name'];
                $workerList[] = $tempArray;
            }
        }
        $this->success('', null, $workerList);
    
    }
}
