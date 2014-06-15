<?php
class SceneObj_User{
	static public $_self;
	public $user;
	public $user_AtStory_me;
	public function init(){
		self::$_self=$this;	
	}
	//获取用户
	public function getUser(){
		global $user_me;
		$result = mysql_query ( "SELECT * FROM sceneMode_user WHERE wx_id ='" . $user_me['wx_id'] . "'" );
		$user = mysql_fetch_array ( $result );
		if ($user) {
			$this->user=$user;					
			return true;
		} else {
			//$this->postText("new id");			
			$this->newJoin ();			
			$this->postText(sprintf(sceneObj_intro_newJoin,$user_me['user_nickName']));
		}
	}
	//第一次加入
	public function newJoin() {
		global $dateTime,$user_me;
		$sql = "INSERT INTO `sharenyouxi_wx`.`sceneMode_user` (`wx_id`, `join_time`,`last_log_time`,`user_nickName`) VALUES ('" . $user_me['wx_id'] . "', '" . $dateTime . "' ,'" . $dateTime . "','" . $user_me['user_nickName'] . "')";
		
		if (! mysql_query ( $sql )) {
			//die ( 'Error: ' . mysql_error () );
		}
	}
	//参与故事
	public function joinStoryAt($story_id){					
		$this->user['currentStoryId']=$story['id'];
		$this->updateUser($this->user, array('currentStoryId'));
	}
	//更新用户信息
	public function updateUser($user, $pragrams) {	
		global $dateTime;	
		$str = "";
		$i = 0;
		foreach ( $pragrams as $pra ) {
			if ($i) {
				$str = $str . ",`" . $pra . "`='" . $user [$pra]."'";
			} else {
				$str = "`". $pra . "`='" . $user [$pra]."'";
			}
			$i += 1;
		}
		
	//	$str="UPDATE  `sharenyouxi_wx`.`sceneMode_user` SET  ".$str." WHERE  `sceneMode_user`.`id` =".$user['id'];
		//$this->postText($str);
		mysql_query ( "UPDATE  `sharenyouxi_wx`.`sceneMode_user` SET  ".$str." WHERE  `sceneMode_user`.`id` =".$user['id']);
	//	mysql_query ( "UPDATE `sharenyouxi_wx`.`sceneMode_user` SET " . $str . " WHERE `sceneMode_user`.`id` = " . $user [id] );
	}
	public function clearLastAction(){
		$this->user['ready_to']=0;
		$this->user['last_action']="";
		$this->updateUser($this->user, array('ready_to','last_action'));
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