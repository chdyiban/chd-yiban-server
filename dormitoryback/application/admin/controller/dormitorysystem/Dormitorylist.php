<?php

namespace app\admin\controller\dormitorysystem;
use think\Db;
use think\Log;
use app\common\controller\Backend;

/**
 * 此表查看以宿舍为单位信息
 * @icon fa fa-circle-o
 */
class Dormitorylist extends Backend
{
    
    /**
     * Dormitorylist模型对象
     */
    protected $model = null;
    // protected $relationSearch = true;
    // protected $searchFields = '';


    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Dormitory');
    }


    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index()
    {
        //获取当前管理员id的方法
        $now_admin_id = $this->auth->id;
        //设置过滤方法
        // $this->relationSearch = true;
        // $this->searchFields = "getcollege.YXJC,studetail.BJDM,studetail.XM";
        
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        
            //dump(json_decode($this->request->param()['filter'],true));
            //dump($where);
            $total = $this->model
                    // ->with('getcollege,getstuname')
                    ->where($where)
                    -> where('status',1)
                    //->group('LH,SSH')
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    // ->with('getcollege,getstuname')
                    ->where($where)
                    //->group('LH,SSH')
                    ->where('status',1)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);           
            return json($result);
        }
         return $this->view->fetch(); 
    }

     /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill)
                {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                //是否采用模型验证
                if ($this->modelValidate)
                {
                    $name = basename(str_replace('\\', '/', get_class($this->model)));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                    $this->model->validate($validate);
                }
                $result = $this->model->addRoom($params,$this->auth->id);
                if ($result['status'] !== false)
                {
                    $this->success($result['msg']);
                }
                else
                {
                    $this->error($result['msg']);
                }
            }
               
            
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds))
        {
            if (!in_array($row[$this->dataLimitField], $adminIds))
            {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {

                //是否采用模型验证
                if ($this->modelValidate)
                {
                    $name = basename(str_replace('\\', '/', get_class($this->model)));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                    $row->validate($validate);
                }
                $result = $this->model -> editRoom($params,$ids,$this->auth->id);
                // $now_time = date("Y-m-d H:m:s");
                // $admin_name = $this->auth->nickname;
                // Log::record("管理员".$admin_name."于".$now_time."修改了宿舍:"."id为".$id."  ".json_encode($params));
                //$result = $row->allowField(true)->save($params);
                if ($result['status'] !== false)
                {
                    $this->success($result['msg']);
                }
                else
                {
                    $this->error($result['msg']);
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */

    public function delete($ids = "")
    {
        if ($ids)
        {
            // $now_time = date("Y-m-d H:m:s");
            // $admin_name = $this->auth->nickname;
            //Log::record("管理员".$admin_name."于".$now_time."删除了宿舍");
            $result = $this -> model -> deleteRoom($ids,$this->auth->id);
            if ($result['status']) {
                $this -> success($result['msg']);
            } else {
                $this -> error($result['msg']);
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 获取院系json,用于在js用searchlist调用
     */
    public function getCollegeJson()
    {
        if ($this->request->isAjax()){
            $result = $this->model -> getCollegeJson();
            return json($result);
        } else {
            return [];
        }
    }
    /**
     * 获取宿舍相关信息，获取床位入住情况以及入住比例
     * @param key:id
     * @param type:situation   入住情况
     * @param type:proportion  入住比例
     */
    public function freebed()
    {
        $param = $this->request->param();
        $roomId = $param['key'];
        $roomDetailInfo = $this -> model -> getDormitoryFreeBedInfo($roomId);

        return json($roomDetailInfo); 
    }
    /**
     * 展示宿舍的详细信息
     */
    public function dormitoryinfo()
    {
        $LH =  $this->request->get('LH');
        $SSH =  $this->request->get('SSH');
        $dormitoryInfoList = $this->model->getDormitoryInfo($LH,$SSH);
        // dump($dormitoryInfoList);
        return view('dormitoryinfo',[
            'dormitoryInfoList' => $dormitoryInfoList,
        ]);
    }

    /**
     * 确认分配界面
     */
    public function confirmdistribute()
    {
         //获取当前管理员id的方法
       $now_admin_id = $this->auth->id;
       //设置过滤方法
       $this->request->filter(['strip_tags']);
       if ($this->request->isAjax())
       {
           //如果发送的来源是Selectpage，则转发到Selectpage
           if ($this->request->request('pkey_name'))
           {
               return $this->selectpage();
           }
       } else {

           $param = $this->request->param();
           $this->view->assign([
               'param' => $param,
           ]);
       }

       return $this->view->fetch('confirmdistribute');
    }
    /**
     * 确认换宿界面
     */
    public function confirmchange()
    {
         //获取当前管理员id的方法
       $now_admin_id = $this->auth->id;
       //设置过滤方法
       $this->request->filter(['strip_tags']);
       if ($this->request->isAjax())
       {
           //如果发送的来源是Selectpage，则转发到Selectpage
           if ($this->request->request('pkey_name'))
           {
               return $this->selectpage();
           }
       } else {

           $param = $this->request->param();
           $this->view->assign([
               'param' => $param,
           ]);
       }

       return $this->view->fetch('confirmchange');
    }
    /**
     * 获取学生住宿信息
     * ajax
     * @param int XH
     * @return string LH-SSH-CH
     */
    public function getStuDormitory()
    {
        if ($this->request->isAjax()) {
            $XH = $this->request->post('XH');
            $stuDormitory = Db::view('dormitory_beds')
                            -> view('dormitory_rooms','XQ,LH,SSH','dormitory_beds.FYID = dormitory_rooms.ID') 
                            -> where('XH',$XH)
                            -> find();
            
            if (empty($stuDormitory)) {
                $result = ['status' => false, 'msg' => '该学生未安排住宿'];
            } else {
                $result = ['status' => true, 'msg' => '查询成功', 'data' => $stuDormitory];
            }
            return json($result);
        } else {
           $this->error('参数错误');
        }
    }

    /**
     * 确认删除界面
     */
    public function confirmdelete()
    {
        $param = $this->request->param();

        $historyOperate = Db::view('dormitory_special','id,operation,admin_id,operate_time,admin_id') 
                        -> view('admin','id,nickname','dormitory_special.admin_id = admin.id')
                        -> where('XH',$param['XH']) 
                        -> order('operate_time desc')
                        -> limit(5)
                        -> select();
      
        if (empty($historyOperate)) {
            return view('confirmdelete',[
                'param' => $param,
            ]);
        } else {
            foreach ($historyOperate as $key => $value) {
                $historyOperate[$key]['operate_time'] = date("Y-m-d",$value['operate_time']);
                $historyOperate[$key]['operation'] = $value['operation'];
            }
            return view('confirmdelete',[
                'param' => $param,
                'historyOperate' => $historyOperate,
            ]);
        }
        
    }
    /**
     * 两人对调宿舍
     */
    public function addChangeRecord()
    {
        if ($this->request->isAjax()){
        //获取当前管理员id的方法
            $now_admin_id = $this->auth->id;
            $param = $this->request->param();
            $res = $this->model->addChangeRecord($param,$now_admin_id);
            return $res;
        } else {
            $this->error('请求错误');
        }
    }

    /**
     * 移除床位对应学生
     */
    public function deleteStuRecord()
    {
        if ($this->request->isAjax()){
        //获取当前管理员id的方法
            $now_admin_id = $this->auth->id;
            $param = $this->request->param();
            $res = $this->model->deleteStuRecord($param,$now_admin_id);
            return $res;
        } else {
            $this->error('请求错误');
        }
    }
    /**
     * 向床位分配学生
     */
    public function addStuRecord()
    {
        if ($this->request->isAjax()){
        //获取当前管理员id的方法
            $now_admin_id = $this->auth->id;
            $param = $this->request->param();
            $res = $this->model->addStuRecord($param,$now_admin_id);
            return json($res);
        } else {
            $this->error('请求错误');
        }
    }

    /**
     * 查找学生信息通过学号
     * @param XH
     */
    public function searchStuByXh()
    {
        if ($this->request->isAjax()) {
            $XH = $this->request->param('XH');
            $stuInfo = $this->model->searchStuByXh($XH);
            return json($stuInfo);
        } else {
            $this->error('请求错误');
        }
    }

    /**
     * 查找学生信息通过姓名
     * @param name
     */
    public function searchStuByName()
    {
        if ($this->request->isAjax()) {
            $name = $this->request->post('name');
            $stuInfo = $this->model->searchStuByName($name);
            return json($stuInfo);
        } else {
            $this->error('请求错误');
        }
    }

    /**
     * 新增宿舍，返回校区用于ajax调用
     */
    public function getXQ()
    {
        $XQList = [
            [
                'value' => '渭水',
                'name'  => '渭水',
            ],
            [
                'value' => '本部',
                'name'  => '本部',
            ]
        ];
        $this->success('', null, $XQList);
    }
    /**
     * 新增宿舍，返回房源类型用于ajax调用
     */
    public function getRoomStatus()
    {
        $statusList = [
            [
                'value' => '1',
                'name'  => '学生用房',
            ],
            [
                'value' => '2',
                'name'  => '公用房',
            ],
            [
                'value' => '0',
                'name'  => '无法使用',
            ]
        ];
        $this->success('', null, $statusList);
    }

}
