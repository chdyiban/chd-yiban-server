<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use app\common\library\Token;
use think\Validate;
use app\api\model\Dormitory as DormitoryModel;

use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
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
    public function getinfo()
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
                $info['SSDM'] = !empty($data['SSDM']) ? $data['SSDM'] : $this->error('参数有误');
                $info['CH'] = !empty($data['CH']) ? $data['CH'].'号床' : $this->error('参数有误');
                $info['YYDM'] = $data['YXDM'];
                $info['YXMC'] = $data['YXMC'];
                $type = Db::name('fresh_dormitory') -> where('SSDM', $info['SSDM'])-> field('CPXZ') -> find();
                $type = !empty($type['CPXZ']) ? strlen($type['CPXZ']) : $this->error('参数有误');
                $money = $type == 4 ? 1200 : 700;
                $info['ZSF'] = $money;
                $info['status'] = $data['status'];
                switch ($data['status']) {
                    case 'waited':
                        $this -> success('你有尚待确认的宿舍', $info);
                        break;       
                    case 'finished':
                        $this -> success('你已经选好了宿舍', $info);
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
