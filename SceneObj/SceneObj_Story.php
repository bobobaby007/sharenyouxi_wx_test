<?php
class SceneObj_Story{
	public $story;
	public $scene;
	public $user_AtStory;
	static public $_self;
	public function init(){
		self::$_self=$this;	
		
	}
	public function testBasicOrder($keyword) {	
			
	}
	public function checkLastAction($keyword) {			
		if (SceneObj_User::$_self->user['ready_to']=='2000201'){	
			//$this->postText($keyword);			
			if ($keyword=="[OK]"){		
				//$this->postText(SceneObj_User::$_self->user['last_action']);		
				$this->_creatTitle(SceneObj_User::$_self->user['last_action']);
			}			
			$this->_checkTitle($keyword);						
		}
		if (!$this->story['title'])	{
			SceneObj_User::$_self->user['ready_to']='2000201';
			SceneObj_User::$_self->user['last_action']='0';		
			SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to','last_action'));
			$this->postText("还没添加故事标题");
		}
			
	}
	//----添加故事标题
	public function _creatTitle($keyword){
		//$this->postText("标题描述创立成功");
		if (!$keyword){
			$this->postText("标题不可为空");
		}
				
		$this->story['title']=$keyword;	
		
		$this->updateStory($this->story, array('title'));
		
		SceneObj_User::$_self->user['ready_to']='0';
		SceneObj_User::$_self->user['last_action']='0';		
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to','last_action'));
		
		$this->postText("标题描述创立成功");
	}

	public function _checkTitle($keyword){	
		if (!$keyword){
			$this->postText("标题不可为空");
		}		
		SceneObj_User::$_self->user['last_action']=$keyword;	
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('last_action'));
		
		$this->postText("确认这就是你要建立的标题？[OK]确认，不是重新输入");	
	}
	//根据id获取故事
	public function getStory($the_id){	
		
		if ($this->story&&$this->story['id']==$the_id){
			return $this->story;
		}		
		$result = mysql_query ( "SELECT * FROM sceneMode_stories WHERE id ='" . $the_id . "'" );
		$story = mysql_fetch_array ( $result );
		if ($story) {
			$this->story=$story;
			
			return $story;							
		} else {
			//$this->postText(sceneObj_story_gone);
		}
			
		return false;
	}		
	//故事信息
	public function storyContent(){	
		$contents="";
		if (!$this->story['title']){
			$contents="故事还没设置标题";
			return $contents;
		}
		$contents.="【故事".$this->story['id']."】";		
		$contents.="\n《".$this->story['title']."》";
		$contents.="\n--作者:".$this->story['user_nickName'];		
		return $contents;
	}
	//返回信息
	public function getContent(){
		
		$content=$this->storyContent()."\n".SceneObj_Scene::$_self->getContent();
		return $content;
	}
	//创建故事
	public function createStory(){
		global $dateTime,$user_me;
		$user=SceneObj_User::$_self->user;
		
		$sql = "INSERT INTO `sharenyouxi_wx`.`sceneMode_stories` (`wx_id`, `createdTime`,`user_id`,`user_nickName`) VALUES ('" . $user_me[wx_id] . "', '" . $dateTime . "' ,'" . $user['id']  . "','" . $user['user_nickName']  . "')";
		mysql_query ($sql );	
		$story_id= mysql_insert_id ();
		
		//$story[id]=$story_id;
		//$this->current_story=$story;
		
		SceneObj_User::$_self->user['currentStoryId']=$story_id;		
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('currentStoryId'));
		
		$resultStr = sprintf ( sceneObj_create_new_story,$story_id);
		
		SceneObj_User::$_self->user['ready_to']='2000201';
		SceneObj_User::$_self->user['last_action']='0';		
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('ready_to','last_action'));
		
		$this->postText($resultStr."请输入故事标题");
	}
	public function creatNewScene(){
		global $dateTime,$user_me;		
		if (!$this->story){
			$this->postText("没有故事");
		}
		
		if ($this->story['sceneStatus']){
			$this->getScene();
			if ($this->scene){
				$this->postText(sceneObj_scene_live);
			}			
		}
		$user=SceneObj_User::$_self->user;
		$the_sceneNum=$this->story['sceneNum']+1;
		$sql = "INSERT INTO `sharenyouxi_wx`.`sceneMode_scenes` (`wx_id`, `createdTime`,`user_id`,`story_id`,`sceneNum`,`last_change_time`) VALUES ('" . $user_me[wx_id] . "', '" . $dateTime . "' ,'" . $user['id']  . "','" . $this->story['id']  . "','" . $the_sceneNum  . "','" .$dateTime. "')";
		mysql_query ($sql );			
		$scene_id= mysql_insert_id ();
		//$story[id]=$story_id;			
		$this->story['sceneNum']=$the_sceneNum;
		$this->story['currentSceneId']=$scene_id;
		$this->story['sceneStatus']=1;
		$this->updateStory($this->story, array("sceneNum","sceneStatus","currentSceneId"));
		$resultStr = sprintf (sceneObj_create_new_scene,$the_sceneNum);
		$this->postText($resultStr);
	}
	public function getScene(){	
		if ($this->scene){
			return $this->scene;
		}
		if (!$this->story){
			$this->postText("没有故事");
		}
		if (!$this->story['currentSceneId']){
			return false;
			$this->postText(sprintf(sceneObj_story_none_scene,$this->story['id']));
		}
		$result = mysql_query ( "SELECT * FROM sceneMode_scenes WHERE id ='" . $this->story['currentSceneId'] . "'" );
		$scene = mysql_fetch_array ( $result );
		$this->scene=$scene;
		SceneObj_Scene::$_self->scene=$scene;
		return $scene;		
	}
	//更新故事信息信息
	public function updateStory($the_story, $pragrams) {
		
		$str = "";
		$i = 0;
		foreach ( $pragrams as $pra ) {
			if ($i) {
				$str = $str . ",`" . $pra . "`='" . $the_story [$pra]."'";
			} else {
				$str = "`". $pra . "`='" . $the_story [$pra]."'";
			}
			$i += 1;
		}
		mysql_query ( "UPDATE  `sharenyouxi_wx`.`sceneMode_stories` SET  ".$str." WHERE  `sceneMode_stories`.`id` =".$the_story['id']);
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