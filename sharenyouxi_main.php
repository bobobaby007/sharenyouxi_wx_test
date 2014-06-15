<?php
include_once 'roomObj.php';
include_once 'SceneObj/SceneObj.php';

$roomObj=new roomObj();
$roomObj->init();

$sceneObj=new SceneObj();
$sceneObj->init();

class sharenyouxi_main{
	static public $_self;
	public $user_me;
	public $is_from_main;
	public function init(){		
		self::$_self=$this;
		$this->responseMsg();	
	}
	public function responseMsg() {
		global $dateTime, $keyword, $user_me;				
		$dateTime = date ( "Y-m-d H:i:s", time () );	
		$postStr = $GLOBALS ["HTTP_RAW_POST_DATA"];		
		if (! empty ( $postStr )) {
			$postObj = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );			
			global $fromUsername, $toUsername, $keyword;			
			$fromUsername = $postObj->FromUserName;
			$toUsername = $postObj->ToUserName;
			$keyword = trim ( $postObj->Content );					
			$keyword = wechatCallbackapi::$_self->changeEmotionToString ( $keyword );			
			$inputType = trim ( $postObj->MsgType );
			$time = time ();
			$this->checkWxId ();			
			$this->checkLastAction ();	
			$this->selectLastAction($keyword);				
			if (! empty ( $keyword )) {	
				$this->testOder	($keyword);								
			} else {
				if ($inputType == "event") {
					if (trim ( $postObj->MsgType ) == "unsubscribe") {
						$user_me [user_status] = - 1; //取消关注	
						$this->updateUser ( $user_me, array ("user_status" ) );
					}
					if (trim ( $postObj->MsgType ) == "subscribe") {
						//$user_me[user_status]=0;//关注						
					}
					$this->postText ( hello_world );
				}
			}
		} else {
			$this->postText ( "___" );
			exit ();
		}
		
		$this->checkObj();		
		$this->testText ( $keyword );	
	}	
	//查询用户信息
	private function checkWxId() {
		global $fromUsername, $con, $room_id, $user_last_action, $user_last_log_time, $user_role, $dateTime, $user_nickName, $atUser, $user_statu, $user_ready_to, $user_me;
		$result = mysql_query ( "SELECT * FROM user WHERE wx_id ='" . $fromUsername . "'" );
		$user = mysql_fetch_array ( $result );
		if ($user) {
			$user_me = $user;
			$user_last_action = $user ['last_action'];
			$room_id = $user ['room_id'];
			$user_last_log_time = $user ['last_log_time'];
			$user_role = $user ['user_role'];
			$user_statu = $user ['user_statu'];
			$user_nickName = $user ['user_nickName'];
			$user_ready_to = $user ['user_ready_to'];
			mysql_query ( "UPDATE user SET last_log_time = '" . $dateTime . "'WHERE wx_id = '" . $fromUsername . "'" );
			//$this->postText("has id");		
			return true;
		} else {
			//$this->postText("new id");
			$this->newJoin ();
		}
		return false;
	}
	public function getUser($wx_id){
		$result = mysql_query ( "SELECT * FROM user WHERE wx_id ='" . $wx_id . "'" );
		$user = mysql_fetch_array ( $result );
		if ($user) {	
								
			return $user;
		} else {
			return false;
		}
	}
	//－－－监测插件
	private function checkObj(){
		global $user_me;
				
		if ($user_me['currentObj']=='2'){
			SceneObj::$_self->responseMsg();
		}
		if ($user_me['currentObj']=='1'){
			roomObj::$_self->responseMsg();
		}		
		
	}
	//监测命令---大厅先过滤的命令
	private function testOder($text){
		global $fromUsername, $room_id, $room_logs, $user_role, $user_nickName, $user_last_log_time, $dateTime, $atUser, $only_partner, $user_me, $user_ready_to, $room_game_status;
		//----房间命令
		//$this->postText($user_nickName);
		$order;
		$content;
		
		$first_index = stripos ( $text, "[" );
		$index = stripos ( $text, "]" );
		
		 
		if ($text == "杀人游戏" || $text == "[杀人游戏]") {
			$this->postPic ( "http://wx.sharen.4view.cn/getqrcode.jpg" );
		}	
		if ($index && $index < 10 && $first_index == 0) {
			$order = substr ( $text, 0, $index + 1 );
			$content = substr ( $text, $index + 1 );
			
			if ($order == "[名]" || $order == "[名字]") {
				$user_nickName = $content;
				$this->changeNickName ();
			}		
		}
		if (! $user_nickName) {
			if ($user_ready_to == "100050" && $text != "[闭嘴]") {
				$this->saveReadyAction ( "100050", $text );
				$this->postText ( "\"" . $text . "\"就是您的称呼？确认请输入微信表情[OK],不是请重新输入" );
			}
			if ($user_me [user_ready_to] == "1000501" && $text != "[闭嘴]") {
				$this->saveReadyAction ( "100050", $text );
				$this->postText ( "\"" . $text . "\"就是您的称呼？确认请输入微信表情[OK],不是请重新输入" );
			} else {
				$this->saveReadyAction ( "1000501", "" );
				$this->postText ( "欢迎来到杀人游戏世界，初次见面，请问阁下怎么称呼？" );
			}
		}
		if ($text == "大厅" || $text == "游戏大厅"||$text == "回到大厅") {
			$this->saveReadyAction("100100", "");
			$this->postText("确定你要回到大厅？确认请输入微信表情:[OK]");
		}
	}
	//-----回到大厅时命令
	private function testText($text) {
		global $fromUsername, $room_id, $room_logs, $user_role, $user_nickName, $user_last_log_time, $dateTime, $atUser, $only_partner, $user_me, $user_ready_to, $room_game_status;
		//----房间命令
		//$this->postText($user_nickName);
		$order;
		$content;
		
		$first_index = stripos ( $text, "[" );
		$index = stripos ( $text, "]" );
		
		if ($text == "?" || $text == "？" || $text == "帮助" || $text == "[疑问]" || $text == "规则" || $text == "怎么玩" || $text == "怎么玩？" || $text == "怎么玩?" || $text == "怎么玩啊" || $text == "怎么玩啊？" || $text == "help") {
					$this->postText ( help_basic );
		}	
		if (!$user_me['currentObj']){
			$this->saveReadyAction("100101", "");
			$this->postText(intro_100101);
			return;
		}	
	}
	//------保存提示信息
	private function saveReadyAction($action, $last_action) {
		global $fromUsername;
		mysql_query ( "UPDATE user SET user_ready_to = '" . $action . "', last_action = '" . $last_action . "' WHERE wx_id = '" . $fromUsername . "'" );
	}
	private function selectLastAction($text){
		global $user_me,$keyword;
		if(!ctype_digit ( $text )){
			return;
		}
		if ($user_me['user_ready_to']=='100101'){//选择一个插件进入
			
			if ($text=="1"||$text=="2"){
				$this->clearLastAction();
				$user_me['currentObj']=$text;
				$this->updateUser ($user_me, array ("currentObj") );
				
				$this->is_from_main=1;
				$keyword="";
			}			
		}		
	}
	public function updateUser($user, $pragrams) {		
		$str = "";
		$i = 0;
		foreach ( $pragrams as $pra ) {
			if ($i) {
				$str = $str . "," . $pra . "=" . $user [$pra];
			} else {
				$str = $str . $pra . "=" . $user [$pra];
			}
			$i += 1;
		}
		//$this->postText($str);
		mysql_query ( "UPDATE user SET " . $str . " WHERE wx_id = '" . $user [wx_id] . "'" );
	}
	//------确认上次提示	[OK] 键
	private function checkLastAction() {
		global $user_nickName, $user_ready_to, $fromUsername, $keyword, $my_room, $room_id, $user_me, $atUser;
		
		
		//改名
		if ($user_ready_to == "100050") {
			//$user_nickName=$keyword;
			if ($keyword == "[OK]") {
				$user_nickName = $user_me [last_action];
				$this->clearLastAction ();
				$this->changeNickName ();
			}else{
				
			}
			
		}
		//--回到大厅
		if ($user_ready_to == "100100") {
			//$user_nickName=$keyword;
			if ($keyword == "[OK]") {
				$user_me ['currentObj']='0';
				$this->updateUser ( $user_me, array ("currentObj") );
				$this->clearLastAction ();	
			}
			if($keyword != "[NO]") {
				$this->clearLastAction ();	
				$keyword != "[闭嘴]";				
			}						
		}
		
	}
	private function clearLastAction() {
		global $fromUsername;
		mysql_query ( "UPDATE user SET user_ready_to = '0',last_action = '0' WHERE wx_id = '" . $fromUsername . "'" );
	}
	//改变名字
	private function changeNickName() {
		global $fromUsername, $dateTime, $user_nickName, $room_id;
		
		if ($user_nickName == "") {
			$this->postText ( "对不起，也许是我走神了，没有听到您要设置的大名" );
		}
		if (strlen ( mb_convert_encoding ( $user_nickName, "gb2312", "utf-8" ) ) > 12) {
			//$this->postText(strlen(mb_convert_encoding($user_nickName, "gb2312", "utf-8")));
			$this->postText ( "这个名字有点太长了，是有够引人关注，不过也容易让人喊起来比较辛苦，建议12个英文字母或6个中文字以内" );
		}
		$result = mysql_query ( "SELECT * FROM user WHERE user_nickName	 ='" . $user_nickName . "'" );
		
		if (mysql_fetch_array ( $result )) {
			$this->postText ( "非常抱歉的告诉您，大名:" . $user_nickName . "已经有人用了" );
		}
		mysql_query ( "UPDATE user SET user_nickName = '" . $user_nickName . "'WHERE wx_id = '" . $fromUsername . "'" );
		
		if ($room_id) {
			$this->postText ( "hi " . $user_nickName . "，祝您名声暴噪!" );
		} else {
			$room_id = 0;
			$this->saveRoomLog ( "1000500", $user_nickName . "新加入小城", 4 );
			$this->postText ( "hi " . $user_nickName . ",目前你还未在任何小镇留下足迹，加入一个小镇会一会那里的人们吧！" . help_room_join );
		}
	}
	private function saveRoomLog($action_tag, $log_content, $log_type) {
		global $fromUsername, $room_id, $user_role, $dateTime, $my_room;
		mysql_query ( "INSERT INTO `sharenyouxi_wx`.`room_logs` (`wx_id`, `room_id`, `create_time`, `user_role`, `log_content`, `game_id`,`action_tag`, `log_type`) VALUES ('" . $fromUsername . "', '" . $room_id . "', '" . $dateTime . "','" . $user_role . "','" . $log_content . "','" . $my_room [game_id] . "','" . $action_tag . "','" . $log_type . "' )" );
	
	}
	//第一次加入
	private function newJoin() {
		global $fromUsername, $con, $dateTime;
		$sql = "INSERT INTO `sharenyouxi_wx`.`user` (`wx_id`, `join_time`,`last_log_time`) VALUES ('" . $fromUsername . "', '" . $dateTime . "' ,'" . $dateTime . "')";
		
		if (! mysql_query ( $sql, $con )) {
			//die ( 'Error: ' . mysql_error () );
		}
	
	}
	//发布文字信息
	public function postText($text) {
		wechatCallbackapi::$_self->postText($text);
	}
	//发布二维码信息
	public function postPic($picUlr) {
		wechatCallbackapi::$_self->postPic($picUlr);
	}
}
?>