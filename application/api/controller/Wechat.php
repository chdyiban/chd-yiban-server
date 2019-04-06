<?php
/**
  * wechat php test
  */
namespace app\api\controller;

use think\Db;
//define your token
define("TOKEN", "mytoken");
// $wechatObj = new Wechat();
// $wechatObj->valid();
 
class Wechat
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];
        if (!empty($echoStr)) {
            //valid signature , option
            if($this->checkSignature()){
                echo $echoStr;//这里你把它正确输出了，就完成了开发者验证
                exit;
            }
        }
    }
 
    public function responseMsg()
    {
        // $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");
      	//extract post data
		if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";             
            if(!empty( $keyword ))
            {
                $msgType = "text";
                $contentStr = "请勿重复绑定!";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }else{//初次绑定
                $bindName = $this->insertInfo($postObj);
                $msgType = "text";
                $contentStr = "绑定成功，请及时留意推送!";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }
 
        }else {
        	echo "";
        	exit;
        }
    }
    /**
     * 将初次绑定的用户的微信号以及姓名记录到数据库中
     */
    private function insertInfo($postObj)
    {
        $bindArray = $postObj->EventKey;//示例：qrscene_2_1
        $bindArray = explode("_", $bindArray);
        $bindType = $bindArray[1];
        $bindId = $bindArray[2];
        $FromUserName = $postObj->FromUserName;
        $temp = [
            'user_id'    => $bindId,
            'open_id' => $FromUserName,
            'type'    => $bindType,
            'time'    => time(),
        ];
        $checkBind = Db::name("repair_bind") 
                    -> where('type',$bindType)
                    -> where('user_id',$bindId)
                    -> find();
        if (!empty($checkBind)) {
            $res = Db::name('repair_bind') 
                -> where('type',$bindType)
                -> where('user_id',$bindId)
                -> update($temp);
        } else {
            $res = Db::name('repair_bind') -> insert($temp);
        }
        return $res;

    }
		
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}
 
?>