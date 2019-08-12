<?php

namespace app\admin\controller\dormitorysystem;
use think\Db;
use app\common\controller\Backend;

/**
 * 此表查看床位入住学生信息
 * @icon fa fa-circle-o
 */
class Dormitorybedinfo extends Backend
{
    
    /**
     * 展示已经安排床位学生信息
     * Dormitory模型对象
     */
    protected $model = null;
    // protected $relationSearch = true;
    // protected $searchFields = '';


    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Dormitorybeds');
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

            $total = $this->model
                    ->with('getstuname,getrooms,getcollege')
                    ->where($where)
                    //->group('LH,SSH')
                    //->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with('getstuname,getrooms,getcollege')
                    ->where($where)
                    //->group('LH,SSH')
                    //->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);           
            return json($result);
        }
         return $this->view->fetch(); 
    }

    /**
     * 显示导入数据错误界面
     */

    public function showtemp()
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = model("Dormitorybedscache")
                    ->with('getstuname,getrooms,getcollege')
                    ->where($where)
                    ->count();

            $list = model("Dormitorybedscache")
                    ->with('getstuname,getrooms,getcollege')
                    ->where($where)
                    ->limit($offset, $limit)
                    ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);           
            return json($result);
        }
        
        return $this->view->fetch();
    }
    /**
     * 确认是否导入数据
     */

    public function confirmInsert()
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            $type = $this -> request -> param('type');
            if (empty($type)) {
                $this -> error("参数非法");
            }
            if ($type == "insert") {
                $insertList = model("Dormitorybedscache") -> select();
                foreach ($insertList as $key => $value) {
                    $value = $value -> toArray();
                    $res = Db::name('dormitory_beds') 
                        -> where('FYID',$value['FYID']) 
                        -> where('CH',$value['CH']) 
                        -> update([
                            'XH' => $value['XH'],
                            'NJ' => $value['NJ'],
                            'YXDM' => $value['YXDM'],
                            'status' => $value['status'],
                    ]);

                    $resRoom = Db::name('dormitory_rooms') ->where('ID',$value['FYID']) -> setInc('RZS');
                }
                if ($res) {
                    $resDelete = model("Dormitorybedscache") ->where('ID','>',0)-> delete();
                    $result = ['status' => true,'msg' => "导入成功",'type' => 'insert'];
                } else {
                    $result = ['status' => false,'msg' => "导入失败",'type' => 'insert'];
                }
            } else {
                $res = model("Dormitorybedsinsert") ->where('ID','>',0)-> delete();
                if ($res) {
                    $result = ['status' => true,'msg' => "取消成功",'type' => 'cancel'];
                } else {
                    $result = ['status' => false,'msg' => "请稍后再试",'type' => 'cancel'];
                }
            }
            return json($result);
        }
        
        return $this ->error();
    }

    /**
     * 导入方法
     */
    public function import(){
        $file = $this->request->request('file');
        if (!$file)
        {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath))
        {
            $this->error(__('No results were found'));
        }
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath))
        {
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath))
            {
                $PHPReader = new \PHPExcel_Reader_CSV();
                if (!$PHPReader->canRead($filePath))
                {
                    $this->error(__('Unknown data format'));
                }
            }
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v)
        {
            if ($importHeadType == 'comment')
            {
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            }
            else
            {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }     
        $PHPExcel = $PHPReader->load($filePath); //加载文件
        $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
        $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
        $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
        $maxColumnNumber = \PHPExcel_Cell::columnIndexFromString($allColumn);
        for ($currentRow = 1; $currentRow <= 1; $currentRow++)
        {
            for ($currentColumn = 0; $currentColumn < $maxColumnNumber; $currentColumn++)
            {
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                $fields[] = $val;
            }
        }
        //清空缓存记录表
        $res = model("Dormitorybedscache")->where('ID','>',0) -> delete();
        $wrongArray = array();
        $checkItemResult =  $this->checkItem($fields);
        //字段有误
        if (!$checkItemResult['status']) {
            $wrongArray = [
                'row' => 1,
                'msg' => $checkItemResult['msg'],
            ];
            //return json(['data' => $wrongArray,'status' => false,'tpye' => 'itemWrong']);
            return $this->view->fetch('showerror',['wrongItemArray' => $wrongArray]);
        }

        $insert = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++)
        {
            $values = [];
            for ($currentColumn = 0; $currentColumn < $maxColumnNumber; $currentColumn++)
            {
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                $values[] = is_null($val) ? '' : $val;
            }
            $row = [];
            $temp = array_combine($fields, $values);

            $checkRoomResult = $this->checkRoom($temp);
            $checkStuResult = $this->checkStu($temp);
            $checkSafetyResult = $this -> checkSafety($temp);
            $returnResult = array();
            if ($checkSafetyResult['status']) { 
                if (!$checkRoomResult['status']) {
                    $returnResult['row'] = $currentRow;
                    $returnResult['msg'][] = $checkRoomResult['msg'];
                } else {
                    $temp['FYID'] = $checkRoomResult['data']['FYID'];
                }
                if (!$checkStuResult['status']) {
                    $returnResult['row'] = $currentRow;
                    $returnResult['msg'][] = $checkStuResult['msg'];
                } else {
                    $temp['YXDM'] = $checkStuResult['data']['YXDM'];
                }
            } else {
                $returnResult['row'] = $currentRow;
                $returnResult['msg'][] = $checkSafetyResult['msg'];
            }
            if (!empty($returnResult)) {
                $wrongArray[] = $returnResult;
            }
            
            $insert[] = $temp;

        }
        if (empty($wrongArray)) {
            try{
                foreach ($insert as $key => $value) {
                    $temp = [
                        'FYID' => $value['FYID'],
                        'XH'   => $value['XH'],
                        'YXDM' => $value['YXDM'],
                        'CH'   => $value['CH'],
                        'status' => $value['status'],
                    ];
                    $res = Db::name('dormitory_beds_cache') -> insert($temp);
                }
            }
            catch (\think\exception\PDOException $exception)
            {
                $this->error($exception->getMessage());
            }
           //return json(['status' => true]);
           $res = ['data' => null,'status' => true,'msg' => "操作成功，请核查数据"] ;
           return json($res);
        } else {
            //return json(['data' => $wrongArray,'status' => false]);
            return $this->view->fetch('showerror',['wrongArray' => $wrongArray]);
        }
    }
    /**
     * 检查字段问题
     */
    private function checkItem($array)
    {
        $tempItem = array();
        $keyWord = ["XH","SSH","CH","LH","LC","XQ","status"];
        foreach ($keyWord as  $v) {
            if (!in_array($v,$array)) {
                $tempItem[] = $v; 
            }
        }
        if (empty($tempItem)) {
            return ['status' => true];
        } else {
            return ['status' => false,"msg" => "缺少字段提示信息".implode(",",$tempItem)];
        }
    }
    /**
     * 检查数据的合法性
     */

    private function checkSafety($array)
    {
        $pattern = "/^\d*$/";
        if(preg_match($pattern,$array['XH']) && preg_match($pattern,$array['LH']) && preg_match($pattern,$array['LC']) && preg_match($pattern,$array['SSH']) ){
            return ['status' => true];
        } else {
            return ['status' => false,'msg' => "数据不合法"];
        }
    }

    /**
     * 检查插入房间是否存在
     */
    private function checkRoom($array)
    {
        $checkRoom = Db::name('dormitory_rooms') 
                -> where('XQ',$array['XQ'])
                -> where('LH',$array['LH'])
                -> where('LC',$array['LC'])
                -> where('SSH',$array['SSH'])
                -> find();
        if (empty($checkRoom)) {
            return ['status' => false, 'msg' => "该宿舍不存在，请检查床位数据。"];
        } else {
            return [
                'status' => true,
                'data' => [
                    'FYID' => $checkRoom['ID'],
                ]
            ];
        }
        
    }
    /**
     * 检查学生是否已经拥有床位以及该学生信息是否存在
     */
    private function checkStu($array)
    {
        $checkStuMsg = DB::name('stu_detail') -> where('XH',$array['XH']) -> find();
        if (empty($checkStuMsg)) {
            return ['status' => false, 'msg' => "未查找到该学生数据信息"];
        }
        $checkStuRoom = Db::name('dormitory_beds') -> where('XH',$array['XH']) -> find();
        if (!empty($checkStuRoom)) {
            return ['status' => false, 'msg' => "该学生已经拥有床位"];
        } else {
            return [
                'status' => true, 
                'data' => [
                    'YXDM' => $checkStuMsg['YXDM'], 
                ] 
            ];
        }

    }
}
