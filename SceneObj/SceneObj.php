<?php
include_once 'SceneObj/define_texts.php';
include_once 'SceneObj/SceneObj_Story.php';
include_once 'SceneObj/SceneObj_User.php';
include_once 'SceneObj/SceneObj_Scene.php';
include_once 'SceneObj/SceneObj_UserAtStory.php';
class SceneObj{
	static public $_self;
	public $contentStr;
	public $current_story;
	public $current_scene;
	public $user;
	public $Obj_story;
	public $Obj_user;
	public $Obj_user_atStory;
	public $Obj_scene;
	
	public function init(){
		global $dateTime;		
		self::$_self=$this;	
		$this->Obj_story=new SceneObj_Story();
		$this->Obj_story->init();
		$this->Obj_user=new SceneObj_User();
		$this->Obj_user->init();
		$this->Obj_scene=new SceneObj_Scene();
		$this->Obj_scene->init();
		$this->Obj_user_atStory=new SceneObj_UserAtStory();
		$this->Obj_user_atStory->init();
		
	}
	public function responseMsg(){							
		$postStr = $GLOBALS ["HTTP_RAW_POST_DATA"];
		if (! empty ( $postStr )) {
			$postObj = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );			
			
			$keyword = trim ( $postObj->Content );				
			$keyword = wechatCallbackapi::$_self->changeEmotionToString ( $keyword );			
			$inputType = trim ( $postObj->MsgType );	
			
			$this->Obj_user->getUser();				
			$this->user=$this->Obj_user->user;
			$this->testBasicOrder($keyword);
			$this->getCurrentStory();
			$this->getCurrentScene();
			
			if (sharenyouxi_main::$_self->is_from_main){//来自大厅的选择指令，到这里忽略指令，只是刷新状态
				$keyword = "[闭嘴]";					
				$this->testText ( $keyword );
			}			
			$this->checkLastAction ($keyword);				
			
			
			
			
			if (! empty ( $keyword )) {					
				$this->testText ( $keyword );				
			} else {
				if ($inputType == "image") {
					$contentStr = "图片作为强大证据，不便在游戏中使用";	
				} else if ($inputType == "voice") {
					$contentStr = "打字更健康！";
				} else {
					$keyword = "[闭嘴]";
					$this->testText ( $keyword );
				}
			}
			$this->postText ( $contentStr );
		} else {
			$this->postText ( "___" );
			exit ();
		}
	}
	private function checkLastAction($keyword) {
		SceneObj_Story::$_self->checkLastAction($keyword);
		SceneObj_Scene::$_self->checkLastAction($keyword);
		
	}
	//--基本命令
	public function testBasicOrder($text){
		
		if ($text == "[疑问]") {
			$this->postText(sceneObj_help);
		}
		if ($text == "[勾引][勾引]") {
			$this->Obj_story->createStory();
		}
		if ($text == "[勾引]") {	
			$this->getCurrentStory();		
			$this->Obj_story->creatNewScene();
		}
		
		$first_index = stripos ( $text, "[" );
		$index = stripos ( $text, "]" );
		if ($index && $index < 10 && $first_index == 0) {
					$order = substr ( $text, 0, $index + 1 );
					$content = substr ( $text, $index + 1 );
					//--------
					if ($order == "[拥抱]" && $content) {
						$this->viewStoryAt($content);
					}					
		}
		
	}
	//---基本文字录入
	public function testText($text){
		if (!$this->current_story){
			$this->postText(sceneObj_intro_none_story);	
		}
		if (!$this->current_scene){			
			
		}
		
		
		$user=$this->Obj_user->user;
		$this->viewStoryAt($user['currentStoryId']);
		
	}
	
	//---直接获取当前故事
	private function getCurrentStory(){
		if ($this->current_story){
			return $this->current_story;
		}		
		$this->current_story=$this->Obj_story->getStory($this->user['currentStoryId']);			
	}
	//----直接获取当前场景
	private function getCurrentScene(){
		if ($this->current_scene){
			return $this->current_scene;
		}
		$this->getCurrentStory();
				
		$this->current_scene=$this->Obj_story->getScene();
		
	}	
	//查看故事
	private function viewStoryAt($the_id){
		global $dateTime;
		$this->current_story=$this->Obj_story->getStory($the_id);
		SceneObj_User::$_self->user['currentStoryId']=$the_id;	
		//SceneObj_User::$_self->user['ready_to']='0';
		//SceneObj_User::$_self->user['last_action']='0';	
		//SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('currentStoryId'));
		$this->Obj_story->getScene();
		
		$content=$this->getContent();
		
		SceneObj_User::$_self->user['last_log_time']=$dateTime;	
		SceneObj_User::$_self->updateUser(SceneObj_User::$_self->user, array('last_log_time','currentStoryId','ready_to','last_action'));
		
		$this->postText($content);
		
	}
	//返回信息
	public function getContent(){
		$content="";
		$content=$this->Obj_story->getContent();
		return $content;
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
