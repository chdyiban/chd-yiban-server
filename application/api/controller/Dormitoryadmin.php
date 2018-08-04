<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
/**
 * 
 */
class Dormitoryadmin extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = [];

    /**
     * @param ['XH' => number] -> base64加密
     * @return array
     */
    public function info()
    {
        //提示开发公司此时的token具有管理员权限，不可以写到前端
        $info = array();
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (!empty($key['XH'])) {
            $data = Db::view('fresh_list') 
                    -> view('dict_college','YXDM,YXMC', 'fresh_list.YXDM = dict_college.YXDM')
                    -> where('XH', $key['XH']) 
                    -> find();
            if (!empty($data)) {
                $info['XH'] = $data['XH'];
                $info['SSDM'] = !empty($data['SSDM']) ? ("渭水校区 ".$data['SSDM']) : $this->error('参数有误');
                $info['CH'] = !empty($data['CH']) ? $data['CH'].'号床' : $this->error('参数有误');
                $info['YXDM'] = $data['YXDM'];
                $info['YXMC'] = $data['YXMC'];
                $type = Db::name('fresh_dormitory') -> where('SSDM', $info['SSDM'])-> field('CPXZ') -> find();
                $type = !empty($type['CPXZ']) ? strlen($type['CPXZ']) : $this->error('参数有误');
                $money = $type == 4 ? 1200 : 900;
                $info['ZSF'] = $money;
                $info['origin'] = $data['origin'];
                $info['status'] = $data['status'];
                switch ($data['status']) {
                    case 'waited':
                        $this -> success('存在尚待确认的宿舍', $info);
                        break;       
                    case 'finished':
                        if ($info['origin'] == 'selection') {
                            $this -> success('宿舍由学生选择完成', $info);
                        } else {
                            $this -> success('宿舍由系统随机分配完成', $info);
                        }
                        break;
                }
            } else {
                $this -> error('尚未选择宿舍');
            }
        } else {
            $this -> error('参数有误');
        }
    }

}
