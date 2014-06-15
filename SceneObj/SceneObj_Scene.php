<?php
class SceneObj_Scene{
	
	public $scene;
	static public $_self;
	public function init(){
		self::$_self=$this;	
	}
	public function getRound(){
		
	}
	public function checkLastAction($keyword) {	
		
		if (SceneObj_User::$_self->user['ready_to']=='2000101'){				
			if ($keyword=="[OK]"){
				
				$this->_creatDescription(SceneObj_User::$_self->user['last_action']);
			}
			if ($keyword=="[拳头]"){
				$this->letMeEdite();
			}			
			$this->_checkDes($keyword);						
		}	
		if (SceneObj_User::$_self->user['ready_to']=='2000111'){				
			if ($keyword=="[OK]"){
				$this->_creatQuestion(SceneObj_User::$_self->user['last_action']);
			}
			if ($keyword=="[拳头]"){
				$this->letMeEdite();
			}		
			$this->_checkQuestion($keyword);						
		}	
			
		if (SceneObj_User::$_self->user['ready_to']=='2000121'){				
			if ($keyword=="[OK]"){
				$this->_creatTruth(SceneObj_User::$_self->user['last_action']);
			}
			if ($keyword=="[拳头]"){
				$this->letMeEdite();
			}		
			$this->_checkTruth($keyword);						
		}
		
		if (SceneObj_User::$_self->user['ready_to']=='2000131'){				
			if ($keyword=="[OK]"){
				$this->_describeTruth(SceneObj_User::$_self->user['last_action']);
			}	
			$this->_checkDesTruth($keyword);						
		}
		
		if ($keyword=="人"){
			$this->viewAllUsers();
		}
		
		$this->testOrder($keyword);	
	}
	//----监测命令
	private function testOrder($text){
		$first_index = stripos ( $text, "[" );
		$index = stripos ( $text, "]" );
				
		if ($index && $index < 10 && $first_index == 0) {
			$order = substr ( $text, 0, $index + 1 );
			$content = substr ( $text, $index + 1 );
			
			if ($order == "[刀]") {
				$this->killTest($content);
			}
			
		
		}
	}
	//----杀人
	private function killTest($text){
		if (!ctype_digit ( $text )){
			$this->postText("请输入数字编号杀人");
		}
		$this->postText("你确定杀".$text."号");
	}
	//---先占位置
	public function letMeEdite(){
		global $dateTime;
		$this->scene['editing_user_id']=SceneObj_User::$_self->user['id'];
		$this->scene['last_change_time']=$dateTime;
		$this->updateScene($this->scene, array('editing_user_id','last_change_time'));	
		$this->postText("抢占成功");
	}
	//----添加描述
	public function _creatDescription($keyword){
		if (!$keyword){
			$this->postText("描述不可为空");
		}	
		global $dateTime;	
		
		$this->scene['description']=$keyword;
		$this->scene['scene_status']=1;
		$this->scene['last_change_time']=$dateTime;
		$this->scene['editing_user_id']=0;
		$this->updateScene($this->scene, array('description','scene_status'));				
		SceneObj_User::$_self->user['ready_to']='2000111';
		SceneObj_User::$_self->user['last_action']='0';		
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to','last_action'));
		$this->postText("场景描述创立成功");	
	}
	public function _checkDes($keyword){
		global $dateTime;
		SceneObj_User::$_self->user['last_action']=$keyword;	
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('last_action'));
		$this->scene['editing_user_id']=SceneObj_User::$_self->user['id'];
		$this->scene['last_change_time']=$dateTime;
		$this->updateScene($this->scene, array('editing_user_id','last_change_time'));		
		$this->postText("确认这就是你要建立的场景描述？[OK]确认，不是重新输入");	
	}
	//----添加问题
	public function _creatQuestion($keyword){	
		if (!$keyword){
			$this->postText("问题不可为空");
		}
		global $dateTime;	
		$this->scene['question']=$keyword;
		$this->scene['scene_status']=2;
		$this->scene['last_change_time']=$dateTime;
		$this->scene['editing_user_id']=0;
		$this->updateScene($this->scene, array('question','scene_status'));				
		SceneObj_User::$_self->user['ready_to']='2000121';
		SceneObj_User::$_self->user['last_action']='0';		
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to','last_action'));
		$this->postText("问题创立成功");	
	}
	public function _checkQuestion($keyword){
		
		global $dateTime;
		SceneObj_User::$_self->user['last_action']=$keyword;	
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('last_action'));
		$this->scene['editing_user_id']=SceneObj_User::$_self->user['id'];
		$this->scene['last_change_time']=$dateTime;
		$this->updateScene($this->scene, array('editing_user_id','last_change_time'));		
		$this->postText("确认这就是你要建立的问题？[OK]确认，不是重新输入");	
	}

	//----添加真相
	public function _creatTruth($keyword){	
		if (!$keyword){
			$this->postText("真相不可为空");
		}
		global $dateTime;	
		$this->scene['truth']=$keyword;
		$this->scene['scene_status']=3;
		$this->scene['last_change_time']=$dateTime;
		$this->scene['editing_user_id']=0;
		$this->updateScene($this->scene, array('truth','scene_status'));				
		SceneObj_User::$_self->user['ready_to']='0';
		SceneObj_User::$_self->user['last_action']='0';		
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to','last_action'));
		$this->postText("真相创立成功");	
	}
	public function _checkTruth($keyword){
		global $dateTime;
		SceneObj_User::$_self->user['last_action']=$keyword;	
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('last_action'));
		$this->scene['editing_user_id']=SceneObj_User::$_self->user['id'];
		$this->scene['last_change_time']=$dateTime;
		$this->updateScene($this->scene, array('editing_user_id','last_change_time'));		
		$this->postText("确认这就是你要建立的真相？[OK]确认，不是重新输入");	
	}
	

	//----描述真相
	public function _describeTruth($keyword){	
		if (!$keyword){
			$this->postText("对真相的描述不可为空");
		}
		SceneObj_UserAtStory::$_self->getUser();
		
		SceneObj_UserAtStory::$_self->user['truth']=$keyword;
		SceneObj_UserAtStory::$_self->updateUser(SceneObj_UserAtStory::$_self->user, array('truth'));
		
		
		SceneObj_User::$_self->user['ready_to']='0';
		SceneObj_User::$_self->user['last_action']='0';		
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to','last_action'));
		$this->postText("真相创立成功");	
	}
	public function _checkDesTruth($keyword){
		global $dateTime;
		SceneObj_User::$_self->user['last_action']=$keyword;	
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('last_action'));
			
		$this->postText("确认这就是你要描述的真相？[OK]确认，不是重新输入");	
	}
	//查看所有人
	public function viewAllUsers(){
		SceneObj_UserAtStory::$_self->getAllUsers();
		$users=SceneObj_UserAtStory::$_self->allUsers;
		$text="";
		foreach ($users as $user) {
			$text.="<".$user['sign'].">".$user['user_nickName']."是这样描述真相的：".$user['truth']."\n";
		}
		
		$this->postText($text);
	}
	public function statusContent(){
		
		$content='';
		$user=SceneObj_User::$_self->user;
		switch ($this->scene['scene_status']){
			case 0:
				SceneObj_User::$_self->user['ready_to']='2000101';									
				$content=sprintf(sceneObj_scene_need_description,$this->scene['sceneNum']);//还没有描述
				SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to'));
				break;
			case 1:
				SceneObj_User::$_self->user['ready_to']='2000111';									
				$content=sprintf(sceneObj_scene_need_question,$this->scene['sceneNum']);//还没有问题
				SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to'));
				break;
			case 2:
				SceneObj_User::$_self->user['ready_to']='2000121';									
				$content=sprintf(sceneObj_scene_need_truth,$this->scene['sceneNum']);//还没有真相	
				SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to'));			
				break;
			case 3:
				//SceneObj_User::$_self->user['ready_to']='2000131';
				SceneObj_UserAtStory::$_self->getUser();
				//-----如果上次的场景不是当前场景
				if (SceneObj_UserAtStory::$_self->user['sceneNum']!=$this->scene['sceneNum']){
					
				}
				
				if (SceneObj_UserAtStory::$_self->user['sceneNum']!=$this->scene['sceneNum']||!SceneObj_UserAtStory::$_self->user['role']||!SceneObj_UserAtStory::$_self->user['sign']){
					
					$this->asignRole(0);					
				}
								
				$content.="你的编号:<".SceneObj_UserAtStory::$_self->user['sign'].">";
				$content.="\n你的角色:".$this->_RoleName(SceneObj_UserAtStory::$_self->user['role']);
				
				$content.="\n"."【场景】".$this->scene['description'];
				$content.="\n"."【提问】".$this->scene['question'];				
				
				if (SceneObj_UserAtStory::$_self->user['role']==1){
					$content.="\n【真相】真相对杀手不可见";
				}else{
					$content.="\n【真相】".$this->scene['truth'];
				}
				//---如果没有描述真相
				if (!SceneObj_UserAtStory::$_self->user['truth']){
					SceneObj_User::$_self->user['ready_to']='2000131';
					SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to'));
					$content.="\n"."输入你对真相的描述，将做为别人对你投票的参考";
				}else{
					$content.="\n你已经描述成功";					
				}
				
				
				//$content=sprintf(sceneObj_scene_new,$this->scene['sceneNum']);//真相完毕，新建立场景
				$content.="\n".SceneObj_UserAtStory::$_self->user['last_log_time'];
				//$content=sceneObj_scene_new;//真相完毕，新建立场景
				break;
		}
		
		return $content;
	}
	private function testUser(){
		
	}
	private function UserContent(){
		$content='你还没有加入';
		return $content;
	}
	//分配角色
	public function asignRole($role){
		if ($role){
			
		}else{
			$rand=rand(0, 10);
			if ($rand<2){
				$role=1;
			}elseif ($rand<4){
				$role=2;
			}else{
				$role=3;
			}
		}
		
		SceneObj_UserAtStory::$_self->user['role']=$role;
		SceneObj_UserAtStory::$_self->user['sceneNum']=$this->scene['sceneNum'];
		
		$sign=$this->scene['lastSign'];
		$sign+=1;
		$this->scene['lastSign']=$sign;
		$this->updateScene($this->scene, array('lastSign'));
		
		SceneObj_UserAtStory::$_self->user['sign']=$sign;
		
		SceneObj_UserAtStory::$_self->updateUser(SceneObj_UserAtStory::$_self->user, array("role","sceneNum","sign"));
		
	}
	
	//---角色名称
	public function _RoleName($role){
		$name="";
		switch ($role){
			case 1:
				$name="杀手";
				break;
			case 2:
				$name="警察";
				break;
			case 3:
				$name="平民";
				break;
			case 0:
				$name="未分配";
				break;
		}
		return $name;
	}
//更新用户信息
	public function updateScene($scene, $pragrams) {		
		$str = "";
		$i = 0;
		foreach ( $pragrams as $pra ) {
			if ($i) {
				$str = $str . ",`" . $pra . "`='" . $scene [$pra]."'";
			} else {
				$str = "`". $pra . "`='" . $scene [$pra]."'";
			}
			$i += 1;
		}
		mysql_query ( "UPDATE  `sharenyouxi_wx`.`sceneMode_scenes` SET  ".$str." WHERE  `sceneMode_scenes`.`id` =".$scene['id']);
	}	
	//场景信息
	public function getContent(){
		$_contents=$this->statusContent();			
		return $_contents;
	}
		//发布文字信息
	public function postText($text) {
		wechatCallbackapi::$_self->postText($text);
	}
}
?>