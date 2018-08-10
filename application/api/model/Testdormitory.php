<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;

class Testdormitory extends Model
{
    // 表名
    protected $name = 'fresh_dormitory';


    public function setinfo($info, $key){
        
        $exit_info = Db::name('fresh_info_add') -> where('XH', $info['stu_id']) -> field('ID') -> count();
        if ($exit_info) {
            return ['status' => false, 'msg' => "信息已经完善", 'data' => null];
        } else {
            $ZCYF = '';
            if (empty($key['JJDC'][7])) {
                $ZCYF = '';
            } else{
                foreach ($key['JJDC'][7] as $k => $v) {
                    $ZCYF = $k == 0 ? $v : $ZCYF.",".$v;
                }
            }
            if ($key['JTRKS'] == 0) {
                return ['status' => false, 'msg' => "这样子不太好哦！", 'data' => null];
            } else {
                $RJSR = $key['ZSR']/$key['JTRKS'];
                $RJSR = round($RJSR, 2);
            }
            $data['XH'] = $info['stu_id'];
            $data['SFGC'] = !empty($key['SFGC']) ? $key['SFGC'] : null;
            $data['RXQHK'] = !empty($key['RXQHK']) ? $key['RXQHK'] : null;
            $data['JTRKS'] = !empty($key['JTRKS']) ? $key['JTRKS'] : null;
            $data['YZBM'] = !empty($key['YZBM']) ? $key['YZBM'] : null;
            $data['SZDQ'] = !empty($key['SZDQ']) ? $key['SZDQ'] : null;
            $data['XXDZ'] = !empty($key['XXDZ']) ? $key['XXDZ'] : null;
            $data['BRDH'] = !empty($key['BRDH']) ? $key['BRDH'] : null;
            $data['BRQQ'] = !empty($key['BRQQ']) ? $key['BRQQ'] : null;
            $data['ZP'] =  !empty($key['ZP'][0]['url']) ? $key['ZP'][0]['url'] : '';
            $data['ZSR'] = $key['ZSR'];
            $data['RJSR'] = $RJSR;
            if (empty($key['JJDC'][0]) ||empty($key['JJDC'][1]) ||empty($key['JJDC'][2]) ||empty($key['JJDC'][3]) ||empty($key['JJDC'][4]) ||empty($key['JJDC'][5]) ||empty($key['JJDC'][6]) ) {
                return ['status' => false, 'msg' => "请先完成家庭经济情况调查", 'data' => null];
            } else {
                $data['FQZY'] = !empty($key['JJDC'][0][0]);
                $data['MQZY'] = $key['JJDC'][1][0];
                $data['FQLDNL'] = $key['JJDC'][2][0];
                $data['MQLDNL'] = $key['JJDC'][3][0];
                $data['YLZC'] = $key['JJDC'][4][0];
                $data['SZQK'] = $key['JJDC'][5][0];
                $data['JTBG'] = $key['JJDC'][6][0];
                $data['ZCYF'] = $ZCYF; 
            }
            if (empty($key['JTRK']) || empty($key['JTRK'][0]) ) {
                $family_info = array();
                $info_family = array();
            } else {
                $info_family = array(); 
                $family_info = array();
                foreach ($key['JTRK'] as $k => $v) {        
                    $family_info = array(
                        'XH' => $info['stu_id'],
                        'XM' => $v['name'],
                        'NL' => $v['age'],
                        'GX' => $v['relation'],
                        'GZDW' => $v['unit'],
                        'ZY' => $v['job'],
                        'NSR' => $v['income'],
                        'JKZK' => $v['health'],
                        'LXDH' => $v['mobile'],
                    );
                    $info_family[] = $family_info;
                }
            }
            return ['status' => true, 'msg' => "返回成功", 'data' => $data, 'info' => $info_family];
        }
    }
}