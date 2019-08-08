<?php

namespace app\api\controller\yiban;

use think\Db;

/**
 * 易班UIS对接SDK（utf-8）
 * @author fangzheng@yiban.cn
 * @version v1.2
 * 需要开启的php扩展
 *    php_openssl
 */
class YB_Uis {
	private static $_handle;
	private $scId = '10002_0';//易班学校ID|由易班下载压缩包中的give.txt中提供
	
	static function getInstance() {
		if(!is_object(self::$_handle)) self::$_handle = new self();
		return self::$_handle;
	}
	
	/**
	 * RSA加密数据-调用易班UIS接口
	 * @param array $infoArr 待加密数据数组
	 *    $infoArr = array(
	 *       //所有身份必填项
	 *       'name'=>'',//姓名
	 *       'role'=>'',//身份（0-学生、1-辅导员、2-教师、3-其他）
	 *       'build_time'=>time(),//Unix时间戳
	 *       //认证项，至少填一项，建议学工号
	 *       'student_id'=>'',//学号
	 *       'teacher_id'=>'',//工号
	 *       'exam_id'=>'',//准考证号
	 *       'enter_id'=>'',//录取编号
	 *       'status_id'=>'',//身份证号
	 *       //学生身份必填项
	 *       'enter_year'=>'',//入学年份
	 *       'instructor_id'=>'',//辅导员工号
	 *       'status'=>'学生状态',//（0-在读、1-休学、2-离校）
	 *       'schooling'=>'学制',//（2.5/3/4/5/7/8）
	 *       'education'=>'在读学历',//（0-本科、1-大专、2-硕士、3-博士、4-中职/中专）
	 *       //选填项，如无法提供某项数据，移除该项
	 *       'college'=>'',//学院
	 *       'sex'=>'',//性别（0-男、1-女）
	 *       'specialty'=>'',//专业
	 *       'eclass'=>'',//班级
	 *       'native_place'=>''//籍贯
	 *    )
	 * @param string $path 证书文件引用路径|默认空，与SDK文件同级目录
	 * @param bool $isMobile 是否H5移动端页面|默认false，对接配置填写移动端认证端地址是为true
	 * @example
	 *    YB_Uis::getInstance()->run($infoArr, $isMobile);
	 */
// 	public function run($infoArr, $path = '', $isMobile = false, $goto = '') {
// 		$say = $this->encodeArr($infoArr, $path);
// 		$type = $isMobile ? '&type=mobile' : '';
// 		if($goto){
// 			$gotoUrl = '&goto='.$goto;
// 		}else{
// 			$gotoUrl = '';
// 		}
// 		$hrefUrl = 'https://o.yiban.cn/uiss/check?scid='.$this->scId.$type.$gotoUrl;
// 		echo <<<EOF
// 			<form style='display:none;' id='run' name='run' method='post' action='{$hrefUrl}'>
// 				<input name='say' type='text' value='{$say}' />
// 			</form>
// 			<script type='text/javascript'>
// 				function load_submit() {
// 					document.run.submit();
// 				}
// 				load_submit();
// 			</script>;
// EOF;
// 		die();
// 	}
	public function run($infoArr, $path = '', $isMobile = false, $goto = '') {
		$say = $this->encodeArr($infoArr, $path);
		$type = $isMobile ? '&type=mobile' : '';
		if($goto){
				$gotoUrl = '&goto='.$goto;
		}else{
				$gotoUrl = '';
		}
		$hrefUrl = 'https://o.yiban.cn/uiss/check?scid='.$this->scId.$type.$gotoUrl;
		echo <<<EOF
				<form style='display:none;' id='run' name='run' method='post' action='{$hrefUrl}'>
						<input name='say' type='text' value='{$say}' />
				</form>
				<script type='text/javascript'>
						function load_submit() {
								document.run.submit();
						}
						load_submit();
				</script>;
EOF;
		die();
	}



	
	private function encodeArr($infoArr, $path) {
		$infoJson = json_encode($infoArr);
		// $privkey = file_get_contents($path.'certification.pem');
		$privkey = file_get_contents(dirname(__FILE__).DS.'certification.pem');
		$pack = "";
		foreach(str_split($infoJson, 245) as $str) {
			$crypted = "";
			openssl_private_encrypt($str, $crypted, $privkey);
			$pack .= $crypted;
		}
		$pack = base64_encode($pack);
		$pack = strtr(rtrim($pack, '='), '+/', '-_');
		return $pack;
	}
}
?>