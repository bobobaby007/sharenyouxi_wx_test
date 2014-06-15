<?php
class SceneObj_UserAtStory{
	static public $_self;
	public $user;
	
	public $allUsers;
	public function init(){
		self::$_self=$this;	
	}
	//获取用户
	public function getUser(){
		if ($this->user){
			return $this->user;
		}
		$result = mysql_query ( "SELECT * FROM sceneMode_userAtStory WHERE user_id ='" . SceneObj_User::$_self->user['id'] . "' AND story_id ='" . SceneObj_Story::$_self->story['id'] . "'" );
		$user = mysql_fetch_array ( $result );
		if ($user) {
			$this->user=$user;			
			return true;
		} else {
			//$this->postText("new id");			
			$this->newJoin ();
			$this->getUser();
		}
		return false;
	}
	public function getUserAtStory($story_id,$user_id){
		$result = mysql_query ( "SELECT * FROM sceneMode_userAtStory WHERE wx_id ='" . $user_me['wx_id'] . "'" );
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
	public function getAllUsers(){
		if ($this->allUsers){
			return $this->allUsers;
		}
		$result = mysql_query ( "SELECT * FROM sceneMode_userAtStory WHERE sceneNum ='" . SceneObj_Story::$_self->story['sceneNum'] . "' AND story_id ='" . SceneObj_Story::$_self->story['id'] . "'" );
		$users;
		$i=0;
		while ($user=mysql_fetch_array ( $result )){
			//array_push($users, $user);
			$users[$i]=$user;
			$i++;
		}
		
		if ($users) {
			$this->allUsers=$users;			
			return $this->allUsers;
		} else {
			
		}
		return false;
	}
	//第一次加入
	public function newJoin() {
		global $dateTime,$user_me;
		$sql = "INSERT INTO `sharenyouxi_wx`.`sceneMode_userAtStory` (`wx_id`, `user_id`, `user_nickName`, `join_time`,`last_log_time`,`sceneNum`,`story_id`) VALUES ('" . $user_me['wx_id'] . "', '" . SceneObj_User::$_self->user['id'] . "' , '" . SceneObj_User::$_self->user['user_nickName'] . "' , '" . $dateTime . "' ,'" . $dateTime . "','" . SceneObj_Scene::$_self->scene['sceneNum']. "','" . SceneObj_Story::$_self->story['id']. "')";
		
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
		mysql_query ( "UPDATE  `sharenyouxi_wx`.`sceneMode_userAtStory` SET  ".$str." WHERE  `sceneMode_userAtStory`.`id` =".$user['id']);
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