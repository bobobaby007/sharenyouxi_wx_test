<?php
class wechatCallbackapi {
	static public $_self;
	public function valid() {
		$echoStr = $_GET ["echostr"];
		
		//valid signature , option
		if ($this->checkSignature ()) {
			echo $echoStr;
			exit ();
		}
	}
	public function init(){
		self::$_self=$this;
		//get post data, May be due to the different environments
		$postStr = $GLOBALS ["HTTP_RAW_POST_DATA"];
		
		if (! empty ( $postStr )) {
			$postObj = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );			
			global $fromUsername, $toUsername;			
			$fromUsername = $postObj->FromUserName;
			$toUsername = $postObj->ToUserName;
		}
	}
	//----转换表情命令
	public function changeEmotionToString($text) {
		$text = str_replace ( "/::Q", "[抓狂]", $text );
		$text = str_replace ( "/::O", "[惊讶]", $text );
		$text = str_replace ( "/:?", "[疑问]", $text );
		$text = str_replace ( "/::X", "[闭嘴]", $text );
		$text = str_replace ( "/:hug", "[拥抱]", $text );
		$text = str_replace ( "/:bye", "[再见]", $text );
		$text = str_replace ( "/:li", "[闪电]", $text );
		$text = str_replace ( "/:kn", "[刀]", $text );
		$text = str_replace ( "/:pd", "[菜刀]", $text );
		$text = str_replace ( "/:,@x", "[嘘]", $text );
		$text = str_replace ( "/:ok", "[OK]", $text );
		$text = str_replace ( "/:jj", "[勾引]", $text );
		$text = str_replace ( "/:handclap", "[鼓掌]", $text );
		
		//$this->postText($text);
		return $text;
	}
	//发布文字信息
	public function postText($text) {
		//global $con;
		
		$textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";
		$time = time ();
		global $fromUsername, $toUsername;
		
		$resultStr = sprintf ( $textTpl, $fromUsername, $toUsername, $time, "text", $text );
		echo $resultStr;
		//mysql_close ( $con );
		exit ();
	}
	//发布二维码信息
	public function postPic($picUlr) {
		global $con;
		$textTpl = "<xml>
 <ToUserName><![CDATA[%s]]></ToUserName>
 <FromUserName><![CDATA[%s]]></FromUserName>
 <CreateTime>%s</CreateTime>
 <MsgType><![CDATA[news]]></MsgType>
 <ArticleCount>1</ArticleCount>
 <Articles>
 <item>
 <Title><![CDATA[[杀人游戏]\n(微信帐号:sharenyouxi_wx)]]></Title>
 <Description><![CDATA[微信也可以玩杀人游戏啦，不受时间、地点限制，随时随地推理、辩护！扫瞄二维码关注[杀人游戏]帐号,或直接输入微信帐号:sharenyouxi_wx加好友。]]></Description>
 <PicUrl><![CDATA[%s]]></PicUrl>
 <Url><![CDATA[http://wx.sharen.4view.cn/qrcode.html]]></Url>
 </item>
 </Articles>
 <FuncFlag>1</FuncFlag>
 </xml> ";
		$time = time ();
		global $fromUsername, $toUsername;
		$resultStr = sprintf ( $textTpl, $fromUsername, $toUsername, $time, $picUlr );
		echo $resultStr;
		mysql_close ( $con );
		exit ();
	}
	
	private function checkSignature() {
		$signature = $_GET ["signature"];
		$timestamp = $_GET ["timestamp"];
		$nonce = $_GET ["nonce"];
		
		$token = TOKEN;
		$tmpArr = array ($token, $timestamp, $nonce );
		sort ( $tmpArr );
		$tmpStr = implode ( $tmpArr );
		$tmpStr = sha1 ( $tmpStr );
		
		if ($tmpStr == $signature) {
			return true;
		} else {
			return false;
		}
	}
}
?>