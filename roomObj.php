<?php
class roomObj {
	
	public function valid() {
		$echoStr = $_GET ["echostr"];
		
		//valid signature , option
		if ($this->checkSignature ()) {
			echo $echoStr;
			exit ();
		}
	}
	//----转换表情命令
	private function changeEmotionToString($text) {
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
	public function responseMsg() {
		global $dateTime, $keyword, $user_me;
		$dateTime = date ( "Y-m-d H:i:s", time () );
		if (! $this->checkSignature ()) {
			//echo $echoStr;
		//exit ();
		}
		//get post data, May be due to the different environments
		$postStr = $GLOBALS ["HTTP_RAW_POST_DATA"];
		//echo $postStr;
		//exit();
		//extract post data
		if (! empty ( $postStr )) {
			//checkWxId();
			

			$postObj = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
			
			global $fromUsername, $toUsername, $keyword;
			
			$fromUsername = $postObj->FromUserName;
			$toUsername = $postObj->ToUserName;
			$keyword = trim ( $postObj->Content );
			$keyword = $this->changeEmotionToString ( $keyword );
			
			$inputType = trim ( $postObj->MsgType );
			$time = time ();
			
			$this->checkWxId ();
			$this->checkLastAction ();
			
			$contentStr;
			if (! empty ( $keyword )) {
				if ($keyword == "?" || $keyword == "？" || $keyword == "帮助" || $keyword == "[疑问]" || $keyword == "规则" || $keyword == "怎么玩" || $keyword == "怎么玩？" || $keyword == "怎么玩?" || $keyword == "怎么玩啊" || $keyword == "怎么玩啊？" || $keyword == "help") {
					$this->postText ( help_basic );
				} else if ($keyword == "杀人游戏" || $keyword == "[杀人游戏]") {
					$this->postPic ( "http://wx.sharen.4view.cn/getqrcode.jpg" );
				} else if ($keyword == "you") {
				
		//$this->postText($toUsername);
				} else if ($keyword == "人" || $keyword == "[人]") {
					$this->postText ( $this->showAllUsers () );
				} else if ($keyword == "me") {
					//$this->postText($fromUsername);
				} else {
					//$this->postText("游戏即将上线！加紧测试中..");				
					$this->testText ( $keyword );
				}
			} else {
				if ($inputType == "event") {
					if (trim ( $postObj->MsgType ) == "unsubscribe") {
						$user_me [user_status] = - 1; //取消关注	
						$this->updateUser ( $user_me, array ("user_status" ) );
					}
					if (trim ( $postObj->MsgType ) == "subscribe") {
						//$user_me[user_status]=0;//关注						
					}
					$contentStr = hello_world;
				} else if ($inputType == "image") {
					$contentStr = "图片作为强大证据，不便在游戏中使用";
				
		//$contentStr = "好图! 收下了!" . $postObj->PicUrl;
				} else if ($inputType == "voice") {
					$contentStr = "打字更健康！";
				} else {
					$keyword = "[闭嘴]";
					
					$this->testText ( $keyword );
				
		//$contentStr = "自从得了精神病，整个人精神多了！";
				}
			}
			$this->postText ( $contentStr );
		} else {
			$this->postText ( "___" );
			exit ();
		}
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
	//-----获取房间
	private function getMyRoom() {
		global $my_room, $room_id;
		if (! $room_id) {
			return;
		}
		if ($my_room) {
			return;
		}
		$result = mysql_query ( "SELECT * FROM room WHERE room_id ='" . $room_id . "'" );
		if ($row = mysql_fetch_array ( $result )) {
			$my_room = $row;
		} else {
			$this->postText ( "该小镇已经不存在，" . help_room_join );
		}
	}
	//监测命令
	private function testText($text) {
		global $fromUsername, $room_id, $room_logs, $user_role, $user_nickName, $user_last_log_time, $dateTime, $atUser, $only_partner, $user_me, $user_ready_to, $room_game_status;
		//----房间命令
		//$this->postText($user_nickName);
		

		$order;
		$content;
		
		$first_index = stripos ( $text, "[" );
		$index = stripos ( $text, "]" );
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
		if ($text == "杀人游戏" || $text == "[杀人游戏]") {
			$this->postPic ( "http://wx.sharen.4view.cn/getqrcode.jpg" );
		} else if ($text == "[勾引]") { //------创建房间
			

			if ($room_id) {
				$this->postText ( "你目前定居在" . $room_id . "号小镇，输入:[再见] 离开小镇" );
			} else {
				$this->createRoom ();
			}
		} else if ($text == "[拥抱][拥抱]") { //------快速进入房间
			//$this->postText("你太有才了，输入：".$text." 就是游戏就要开始的意思。");	
			if ($room_id) {
				$this->postText ( "你目前定居在" . $room_id . "号小镇，输入:[再见] 离开房间" );
			} else {
				$this->quickEnterRoom ();
			}
		} else if ($text == "[再见]" || $text == "离开小镇" || $text == "离开" || $text == "出去" || $text == "出" || $text == "退出房间" || $text == "退出") { //－－－－－－离开房间
			//---离开房间
			

			if ($room_id && $room_id != '0') {
				$this->saveReadyAction ( "10003", "" );
				$this->postText ( "确定要离开这里？确认输入:[OK]" );
			} else {
				$this->postText ( "你目前还没定居在任何一个小镇," . help_room_join );
			}
		} else if ($text == "@t@") {
			$this->myTest ();
		} else {
			//监测命令@对象模式		
			if ($index && $index < 10 && $first_index == 0) {
				$order = substr ( $text, 0, $index + 1 );
				$content = substr ( $text, $index + 1 );
				//--------
				if ($order == "[拥抱]" && $content) {
					if ($room_id) {
						$this->postText ( "你目前定居在" . $room_id . "号小镇，输入:[再见] 离开小镇" );
					} else {
						$room_id = $content;
						$this->enterRoom ();
					}
				} elseif ($order == "[NO]") {
				
				} elseif ($order == "[none]") {
				
				} elseif ($order == "[房]" || $order == "[房间]") {
					$this->tellRoomId ();
				} elseif ($order == "[人]") {
					$this->postText ( $this->showAllUsers () );
				} elseif ($order == "[我]" || $order == "[我是谁]" || $order == "[角色]") {
					$this->whoAmI ();
				} elseif ($order == "[刀]" || $order == "[菜刀]") {
					//$atUser=$content;
				//$this->killSomeOne();
				} elseif ($order == "[闪电]") {
					//$atUser=$content;
				//$this->checkSomeOne();
				} elseif ($order == "[闭嘴]") {
					if ($room_id) {
						$this->getMyRoom ();
						$this->refreshRoomLogs ();
						$this->refreshGameStatus ();
						$this->postText ( $room_game_status . "\n" . $room_logs );
					
		//$this->refreshRoomLogs();	
					//$this->postText($room_logs);
					}
				} elseif ($order == "[嘘]") {
					$this->tellPartner ( $content );
				
				}
			}
			if ($room_id) {
				$this->roomTest ( $text );
			} else {
				//$this->postText("[奋斗]努力学习地球语言中..");
				$this->postText ( "你目前还没定居在任何一个小镇," . help_room_join );
			}
		}
	}
	//------发表秘密消息
	private function tellPartner($text) {
		global $room_id, $user_nickName, $room_logs, $room_status, $my_room, $room_game_status, $user_role;
		
		$this->getMyRoom ();
		
		if ($room_id) {
			//------秘密发言
			//$this->postText("秘密发言");
			if ($user_role == "-1") {
				$this->postText ( "你还没有参加游戏" );
			}
			if ($user_role == "0") {
				$this->postText ( "你是旁观者，你没有队友！" );
			}
			if ($user_role == "3") {
				$this->postText ( "你是平民，你的言行都是公开透明的！" );
			}
			if ($user_role == "2") {
				if ($my_room [room_status] == '10020') {
					$this->postText ( "发牌阶段不能向队友发密语" );
				}
				if ($my_room [room_status] == '10021') {
					$this->postText ( "杀手杀人阶段只有杀手可以发密语" );
				}
				$this->saveRoomLog ( "100041", $user_nickName . "说:" . $text, 2 );
			}
			if ($user_role == "1") {
				if ($my_room [room_status] == '10020') {
					$this->postText ( "发牌阶段不能向同伙发密语" );
				}
				if ($my_room [room_status] == '10022') {
					$this->postText ( "警察验人阶段只有警察可以发密语" );
				}
				$this->saveRoomLog ( "100041", $user_nickName . "说:" . $text, 1 );
			}
			
			$this->refreshRoomLogs ();
			$this->postText ( $room_logs );
		} else {
			$this->postText ( "你还没有在任何房间" );
		}
	}
	//------房间中动作检测
	private function roomTest($text) {
		global $user_nickName, $room_logs, $room_status, $my_room, $room_game_status, $user_role, $user_is_looker, $user_me;
		$this->getMyRoom ();
		//旁观者
		if ($user_role == "0" && $my_room [room_status] != '10020') {
			$user_is_looker = 1;
			$this->refreshRoomLogs ();
			$this->refreshGameStatus ();
			$this->postText ( "游戏已经开始，旁观者不可以说话，游戏结束后方可加入，查看情况请输入0\n" . $room_logs );
		}
		//已经死亡
		if ($user_me [user_status] == 2 && $my_room [room_status] != '10020') {
			$user_is_looker = 1;
			$this->refreshRoomLogs ();
			$this->refreshGameStatus ();
			
			$this->postText ( "你已经死亡不可以说话，查看情况请输入0\n" . $room_logs );
		}
		//杀手
		if ($user_role == "1" && ctype_digit ( $text ) && $my_room [room_status] == "10021") {
			$this->saveReadyAction ( "100051", $text );
			//mysql_query("UPDATE user SET user_ready_to = '".$action."'WHERE wx_id = '" .$fromUsername. "'");
			if ($user_me [user_sign] == $text) {
				$this->postText ( "你准备要干掉你自己？确认请输入[OK]" );
			} else {
				$this->postText ( "你准备要干掉" . $text . "号？确认请输入[OK]" );
			}
		}
		//警察
		if ($user_role == "2" && ctype_digit ( $text ) && $my_room [room_status] == "10022") {
			
			//mysql_query("UPDATE user SET user_ready_to = '".$action."'WHERE wx_id = '" .$fromUsername. "'");
			if ($user_me [user_sign] == $text) {
				$this->postText ( "警官，不可以自己验自己！" );
			} else {
				$this->saveReadyAction ( "100052", $text );
				$this->postText ( "你准备要验" . $text . "号？确认请输入[OK]" );
			}
		}
		//投票
		if (ctype_digit ( $text ) && $my_room [room_status] == "10023") {
			//mysql_query("UPDATE user SET user_ready_to = '".$action."'WHERE wx_id = '" .$fromUsername. "'");
			if ($user_me [user_sign] == $text) {
				$this->postText ( "客官，不可以自己给自己投票" );
			} else {
				$this->saveReadyAction ( "100053", $text );
				$this->postText ( "你认为" . $text . "号是凶手？确认请输入[OK]" );
			}
		}
		//---游戏开始前
		if ($my_room [room_status] == '10020') {
			$this->saveRoomLog ( "100040", $user_nickName . "说:" . $text, 0 );
			$this->refreshRoomLogs ();
			$this->refreshGameStatus ();
			$this->postText ( $room_game_status . "\n" . $room_logs );
		}
		if ($my_room [room_status] == '10021' || $my_room [room_status] == '10022') {
			$this->saveRoomLog ( "100042", "<匿名>:" . $text, 0 );
			$this->refreshRoomLogs ();
			$this->refreshGameStatus ();
			$this->postText ( $room_game_status . "\n" . $room_logs );
		}
		if ($my_room [room_status] == '10023') {
			$this->saveRoomLog ( "10004", "<" . $user_me [user_sign] . ">" . $user_nickName . "说:" . $text, 0 );
			$this->refreshRoomLogs ();
			$this->refreshGameStatus ();
			$this->postText ( $room_game_status . "\n" . $room_logs );
		}
		//－－－－普通发言
		if ($user_me [user_sign] > 0) {
			$this->saveRoomLog ( "10004", "<" . $user_me [user_sign] . ">" . $user_nickName . "说:" . $text, 0 );
		} else {
			$this->saveRoomLog ( "10004", $user_nickName . "说:" . $text, 0 );
		}
		
		$this->refreshRoomLogs ();
		$this->postText ( $room_logs );
	}
	//------保存提示信息
	private function saveReadyAction($action, $last_action) {
		global $fromUsername;
		mysql_query ( "UPDATE user SET user_ready_to = '" . $action . "', last_action = '" . $last_action . "' WHERE wx_id = '" . $fromUsername . "'" );
	}
	
	//------确认上次提示	[OK] 键
	private function checkLastAction() {
		global $user_nickName, $user_ready_to, $fromUsername, $keyword, $my_room, $room_id, $user_me, $atUser;
		
		if ($keyword != "[OK]") {
			return false;
		}
		//改名
		if ($user_ready_to == "100050") {
			//$user_nickName=$keyword;
			$user_nickName = $user_me [last_action];
			$this->clearLastAction ();
			$this->changeNickName ();
		}
		
		if (! $user_ready_to) {
			if (! $room_id) {
				return false;
			}
			$this->getMyRoom ();
			if (! $my_room ['room_status'] || $my_room ['room_status'] == "10020") {
				if (! $user_me [user_checked]) {
					$this->saveRoomLog ( "100060", $user_me [user_nickName] . "已经准备好了", 3 );
				}
				$this->assignRoles ();
			}
			
			return false;
		}
		
		if ($user_ready_to == "10003") {
			$this->clearLastAction ();
			$this->leaveRoom ();
		}
		//杀手杀人
		if ($user_ready_to == "100051") {
			$atUser = $user_me [last_action];
			$this->clearLastAction ();
			$this->testKilling ();
		
		//$user_nickName=$keyword;
		}
		//警察验人
		if ($user_ready_to == "100052") {
			$atUser = $user_me [last_action];
			$this->clearLastAction ();
			$this->testChecking ();
		
		//$user_nickName=$keyword;
		}
		//投票
		if ($user_ready_to == "100053") {
			$atUser = $user_me [last_action];
			$this->clearLastAction ();
			$this->testVoting ();
		
		//$user_nickName=$keyword;
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
	//第一次加入
	private function newJoin() {
		global $fromUsername, $con, $dateTime;
		$sql = "INSERT INTO `sharenyouxi_wx`.`user` (`wx_id`, `join_time`,`last_log_time`) VALUES ('" . $fromUsername . "', '" . $dateTime . "' ,'" . $dateTime . "')";
		
		if (! mysql_query ( $sql, $con )) {
			//die ( 'Error: ' . mysql_error () );
		}
	
	}
	//---显示当前房间号
	private function tellRoomId() {
		global $room_id;
		$this->postText ( "你所在房间号为:" . $room_id );
	}
	//----我是
	private function whoAmI() {
		global $room_id, $user_nickName;
		$str = "";
		$str = $str . "你的角色是:" . $str . $this->tellRole () . "\n";
		if ($user_nickName) {
			$str = $str . "你的大名:" . $user_nickName . "\n";
		} else {
			$str = $str . "你还没有为自己起一个大名,输入:[名]+你的大名 起一个名字,比如张三，请输入：[名]张三\n";
		}
		if ($room_id) {
			$str = $str . "所在房间:" . $room_id . "\n";
		} else {
			$str = $str . "你未加入任何房间，" . help_room_join . "\n";
		}
		$this->postText ( $str );
	}
	//－－－显示当前角色
	private function tellRole() {
		global $user_role;
		$str = "";
		switch ($user_role) {
			case 0 :
				$str = "旁观者";
				break;
			case 1 :
				$str = "[嘘]杀手[闭嘴]";
				break;
			case 2 :
				$str = "[酷]警察[强][抱拳]";
				break;
			case 3 :
				$str = "[抠鼻]平民";
				break;
			default :
				$str = "未分配";
				break;
		}
		return $str;
	}
	//-----房间状态
	

	//----发牌阶段
	private function assignRoles() {
		global $fromUsername, $users_at_room, $my_room, $room_id, $user_role;
		
		mysql_query ( "UPDATE user SET user_checked ='1' WHERE wx_id = '" . $fromUsername . "'" );
		
		$this->getMyRoom ();
		
		$this->getUsersInRoom ();
		$users_at_game = array ();
		$checked_user = "";
		$unChecked_user = "";
		$sign = 1;
		
		$role;
		$num_killer = 0;
		$num_police = 0;
		$num_people = 0;
		//-----根据人数安排角色数量
		if ($my_room [game_user_num] <= 8) {
			$role = array (1, 1, 2, 2 );
			$num_police = 2;
			$num_killer = 2;
		} elseif ($my_room [game_user_num] < 12) {
			$role = array (1, 1, 1, 2, 2, 2 );
			$num_police = 3;
			$num_killer = 3;
		} else {
			$role = array (1, 1, 1, 1, 2, 2, 2, 2 );
			$num_police = 4;
			$num_killer = 4;
		}
		$num_people = $my_room [game_user_num] - $num_killer - $num_police;
		
		while ( count ( $role ) < $my_room [game_user_num] ) {
			array_push ( $role, 3 );
		}
		shuffle ( $role );
		
		//$this->postText(json_encode($role));
		

		$allKiller = "";
		$allPolice = "";
		
		foreach ( $users_at_room as $user ) {
			if ($user ['user_checked'] == "1") {
				
				if ($sign > $my_room [game_user_num] + 1) {
					break;
				}
				
				$user [user_sign] = $sign;
				$user [user_role] = $role [$sign - 1];
				//杀手名单
				if ($role [$sign - 1] == 1) {
					if ($allKiller) {
						$allKiller = $allKiller . "\n<" . $user [user_sign] . ">" . $user [user_nickName];
					} else {
						$allKiller = $allKiller . "<" . $user [user_sign] . ">" . $user [user_nickName];
					}
				}
				//警察名单
				if ($role [$sign - 1] == 2) {
					if ($allPolice) {
						$allPolice = $allPolice . "\n<" . $user [user_sign] . ">" . $user [user_nickName];
					} else {
						$allPolice = $allPolice . "<" . $user [user_sign] . ">" . $user [user_nickName];
					}
				}
				
				if ($user [wx_id] == $fromUsername) {
					$user_role = $role [$sign - 1]; //------给自己分配到的角色
					$user_sign = $sign;
				}
				$user_at_game = array ('user_nickName' => $user ["user_nickName"], 'wx_id' => $user ["wx_id"], 'user_sign' => $user ["user_sign"], 'user_role' => $user ["user_role"], 'user_status' => $user ["user_status"], 'user_gotVoteNum' => 0 );
				
				array_push ( $users_at_game, $user_at_game );
				$checked_user = $checked_user . $user ["user_nickName"] . "\n";
				
				$sign += 1;
			} else {
				$unChecked_user = $unChecked_user . $user ["user_nickName"] . "\n";
			}
		}
		$my_room [game_checked_num] = count ( $users_at_game );
		$this->updateRoom ( $my_room, array ("game_checked_num" ) );
		if (count ( $users_at_game ) >= $my_room [game_user_num]) {
			//-----游戏正式发牌
			

			$checked_user = "";
			foreach ( $users_at_game as $user ) {
				
				$checked_user = $checked_user . $this->changeToSign ( $user [user_sign] ) . ":" . $user ["user_nickName"] . "\n";
				$user [user_status] = "1";
				$user [user_checked] = "0";
				$user [user_vote_remain] = "1";
				$user [target_user] = "0";
				$this->updateUser ( $user, array ("user_sign", "user_role", "user_status", "user_checked", "target_user", "user_vote_remain" ) );
			}
			
			//$this->postText(json_encode($users_at_game));
			$my_room [users_at_game] = json_encode ( $users_at_game );
			$my_room [room_status] = '10021';
			$game_id = $my_room [game_id];
			$game_id += 1;
			$my_room [game_id] = $game_id;
			//$this->postText($my_room[room_status]);
			$this->updateRoom ( $my_room, array ("users_at_game", "room_status", "game_id" ) );
			$this->saveRoomLog ( "100201", "根据安全部门消息，有" . $num_killer . "名匪徒在小镇上出现，镇上的" . $num_police . "名便衣警察已经展开侦查行动", 3 );
			$this->saveRoomLog ( "10021", "安全部门发言人:匪徒正在伺机作案", 3 );
			
			$this->saveRoomLog ( "100071", "来，兄弟，认识一下，这是我们的名单:\n" . $allKiller . "\n泄漏名单者杀无赦", 1 );
			$this->saveRoomLog ( "100072", "\n揪出匪徒的重任落在了我们肩上，\n这是弟兄们的名单:" . $allPolice . "\n注意:匪徒手段凶残，名单看完即烧化", 2 );
			
			//mysql_query("UPDATE room SET users_at_game ='".json_encode($users_at_game)."',room_status='10021' WHERE room_id = '" .$room_id. "'");			
			$this->postText ( "游戏正式开始," . intro_10021 . "\n" . cutOffLine . "你的编号是:" . $this->changeToSign ( $user_sign ) . "\n你的角色是:" . $this->roleStr ( $user_role ) . "\n" . cutOffLine . "所有成员为:\n" . $checked_user );
		
		} else {
			$this->postText ( "游戏开始需要" . $my_room [game_user_num] . "名成员确认，目前还缺少" . ($my_room [game_user_num] - count ( $users_at_game )) . "位,\n 已经确认的成员为:\n" . $checked_user . "未确认的成员为:\n" . $unChecked_user . "\n 注:小镇上" . $my_room [game_user_num] . "位成员输入[OK]即为开始" );
		}
	
		//$this->postText(json_encode($users_at_room));
	//json_decode($users_at_room,true)
	}
	//----取得当前游戏里面成员
	private function getUserAtGame() {
		global $users_at_game, $my_room;
		if ($users_at_game) {
			return $users_at_game;
		}
		$this->getMyRoom ();
		$users_at_game = json_decode ( $my_room [users_at_game], true );
		
		return $users_at_game;
	}
	//---当前游戏中成员
	private function showUsersAtGame() {
		global $users_at_game;
		$this->getUserAtGame ();
		$i = 0;
		$str = "";
		foreach ( $users_at_game as $user ) {
			//http://www.cnblogs.com/xcxc/archive/2012/09/10/2678424.html		
			$nickName = $this->decodeStr ( $user [user_nickName] );
			if ($i == 0) {
				$str = "<" . $user [user_sign] . ">" . $nickName;
			} else {
				$str = $str . "\n" . "<" . $user [user_sign] . ">" . $nickName;
			}
			$i += 1;
		}
		return $str;
	}
	//---转换中文编码
	private function decodeStr($text) {
		$str = preg_replace ( "#u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $text );
		return $str;
	}
	//－－－代码转角色
	private function roleStr($id) {
		$str = "";
		switch ($id) {
			case "1" :
				$str = "杀手";
				break;
			case "2" :
				$str = "警察";
				break;
			case "3" :
				$str = "平民";
				break;
			case "0" :
				$str = "旁观者";
				break;
		}
		return $str;
	}
	//---更新用户信息
	private function updateUser($user, $pragrams) {
		
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
	//－－更新房间信息
	private function updateRoom($room, $pragrams) {
		$str = "";
		$i = 0;
		foreach ( $pragrams as $pra ) {
			if ($i) {
				$str = $str . "," . $pra . "='" . $room [$pra] . "'";
			} else {
				$str = $str . $pra . "='" . $room [$pra] . "'";
			}
			$i += 1;
		}
		//$this->postText($str);
		mysql_query ( "UPDATE room SET " . $str . " WHERE room_id = '" . $room [room_id] . "'" );
	}
	//---杀人
	private function killSomeOne() {
		global $user_role, $atUser;
		switch ($user_role) {
			case 0 :
				$this->postText ( "作为游戏旁观者，你没有杀人的功力" );
				break;
			case 1 :
				$this->testKilling ();
				break;
			case 2 :
				$this->postText ( "你是警察啊，怎么可以杀人？！" );
				break;
			case 3 :
				$this->postText ( "当一个安份的平民挺好不是么？杀人这种事还是让杀手去干吧" );
				break;
			default :
				$this->postText ( "你不是杀手" );
				break;
		}
	}
	//---检测杀人是否有效
	private function testKilling() {
		global $atUser, $users_at_room, $users_at_game, $fromUsername, $user_me, $my_room;
		
		$this->getMyRoom ();
		$this->getUsersInRoom ();
		$this->getUserAtGame ();
		
		$sign = $this->changeSignToInt ( $atUser );
		
		$user_me [target_user] = $sign;
		
		$target_user;
		$partner = 0;
		
		$targetStr = "";
		$different = 0;
		foreach ( $users_at_game as $user ) {
			$foundUser = $this->getUserAtRoom ( $user [wx_id] );
			if ($foundUser) {
				if ($foundUser [user_sign] == $atUser) {
					$target_user = $foundUser;
					if ($target_user [user_status] == '2') {
						$this->postText ( "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "已经死亡" );
					}
				}
				//杀手同伙
				if ($foundUser [user_role] == 1 && $foundUser [wx_id] != $fromUsername && $foundUser [user_status] == 1) {
					array_push ( $partner, $foundUser );
					$targetAt = "";
					if ($foundUser [target_user]) {
						$targetAt = "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "准备杀" . $foundUser [target_user] . "号";
					} else {
						$targetAt = "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "还没动手";
					}
					if ($targetStr) {
						$targetStr = $targetStr . "\n" . $targetAt;
					} else {
						$targetStr = $targetStr . $targetAt;
					}
					if ($foundUser [target_user] != $sign) {
						//$sameTargetNum+=1;
						$different += 1;
					} else {
					
					}
				}
			} else {
				if ($user [user_sign] == $atUser) {
					$this->postText ( "<" . $user [user_sign] . ">" . $this->decodeStr ( $user [user_nickName] ) . "已经失踪了！" );
				}
			}
		}
		if (! $target_user) {
			$this->postText ( "没有找到该编号" . $atUser . "\n" . cutOffLine . "成员编号:\n" . $this->showUsersAtGame () );
		}
		if ($different > 0) {
			//$this->updateUser($user_me,array("target_user"));	
		

		//$this->postText("目前还有其他杀手".(count($partner)+1)."位\n".$different."位跟你指向不一致,\n你的同伙指向为:\n".$targetStr);
		}
		
		//－－－－杀人成功
		$this->saveRoomLog ( "1000510", $user_me [user_nickName] . "说:我干掉了" . "<" . $target_user [user_sign] . ">" . $target_user [user_nickName], 1 );
		$this->saveRoomLog ( "100211", "不幸的消息，有位民众被匪徒杀害，目前还未确认死者身份", 3 );
		
		$target_user [user_status] = 2;
		$this->updateUser ( $target_user, array ("user_status" ) );
		$user_me [target_user] = 0;
		//$user_me[last_action]=0;	
		

		if ($this->getRoleNum ( 2 ) == 0) {
			
			$this->saveRoomLog ( "100061", "死者身份确认:<" . $target_user [user_sign] . ">" . $target_user [user_nickName] . "被杀害,据称是一名警察", 3 );
			$this->saveRoomLog ( "100063", intro_100063, 3 ); //杀手赢;	
			$this->resetGame ();
			$this->postText ( "对手已经全部消灭！" );
		}
		
		$my_room [room_status] = "10022";
		$my_room [user_been_killed] = $target_user [wx_id];
		$this->updateRoom ( $my_room, array ("room_status", "user_been_killed" ) );
		
		foreach ( $partner as $pUser ) {
			$pUser [target_user] = 0;
			//$pUser[last_action]=0;	
			$this->updateUser ( $pUser, array ("target_user" ) );
		}
		if ($user_me [wx_id] == $target_user [wx_id]) {
			$this->postText ( "你自己挂掉了" );
		} else {
			$this->updateUser ( $user_me, array ("user_status" ) );
			$this->postText ( "干掉了" . "<" . $target_user [user_sign] . ">" . $target_user [user_nickName] );
		}
	}
	
	//-------检测验人是否有效
	private function testChecking() {
		global $atUser, $users_at_room, $users_at_game, $fromUsername, $user_me, $my_room;
		
		$this->getMyRoom ();
		$this->getUsersInRoom ();
		$this->getUserAtGame ();
		
		$sign = $this->changeSignToInt ( $atUser );
		
		$user_me [target_user] = $sign;
		
		$target_user;
		$partner = 0;
		
		$targetStr = "";
		$different = 0;
		foreach ( $users_at_game as $user ) {
			$foundUser = $this->getUserAtRoom ( $user [wx_id] );
			if ($foundUser) {
				if ($foundUser [user_sign] == $atUser) {
					$target_user = $foundUser;
					if ($target_user [user_status] == '2') {
						$this->postText ( "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "已经死亡" );
					}
				}
				//警察搭档
				if ($foundUser [user_role] == 2 && $foundUser [wx_id] != $fromUsername && $foundUser [user_status] == 1) {
					if ($foundUser [user_sign] == $sign) {
						$this->postText ( "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "是你的搭档！请重新指认" );
					}
					array_push ( $partner, $foundUser );
					$targetAt = "";
					if ($foundUser [target_user]) {
						$targetAt = "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "指认" . $foundUser [target_user] . "号";
					} else {
						$targetAt = "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "未指认";
					}
					if ($targetStr) {
						$targetStr = $targetStr . "\n" . $targetAt;
					} else {
						$targetStr = $targetStr . $targetAt;
					}
					if ($foundUser [target_user] != $sign) {
						//$sameTargetNum+=1;
						$different += 1;
					} else {
					
					}
				}
			} else {
				if ($user [user_sign] == $atUser) {
					$this->postText ( "<" . $user [user_sign] . ">" . $this->decodeStr ( $user [user_nickName] ) . "已经自主死亡(离开房间)！" );
				}
			}
		}
		if (! $target_user) {
			$this->postText ( "没有找到该编号" . $atUser . "\n" . cutOffLine . "成员编号:\n" . $this->showUsersAtGame () );
		}
		if ($different > 0) {
			//$this->updateUser($user_me,array("target_user"));			
		//$this->postText("目前还有警察".(count($partner)+1)."位\n".$different."位跟你指向不一致,\n你的搭档指向为:\n".$targetStr);
		}
		
		//－－－－指认成功
		$user_me [target_user] = 0;
		//$user_me[last_action]=0;	
		

		$user_been_killed = $this->getUserAtRoom ( $my_room [user_been_killed] );
		if ($user_been_killed) {
			$this->saveRoomLog ( "100061", "死者身份确认:<" . $user_been_killed [user_sign] . ">" . $user_been_killed [user_nickName] . "被杀害,警察表示已经掌握相关证据", 3 );
			$this->saveRoomLog ( "100061", "由于警察不便透露自己身份，所有民众开始投票指认凶手，希望能将凶手绳之以法", 3 );
		}
		$my_room [room_status] = "10023";
		$my_room [user_been_killed] = "0";
		
		$this->updateRoom ( $my_room, array ("room_status", "user_been_killed" ) );
		
		foreach ( $partner as $pUser ) {
			$pUser [target_user] = 0;
			//$pUser[last_action]=0;	
			$this->updateUser ( $pUser, array ("target_user" ) );
		}
		$this->updateUser ( $user_me, array ("user_status" ) );
		if ($target_user [user_role] == 1) {
			$this->saveRoomLog ( "1000522", "经过调查已经确认:\n<" . $target_user [user_sign] . ">" . $target_user [user_nickName] . "就是杀手", 2 );
			$this->postText ( "没错！<" . $target_user [user_sign] . ">" . $target_user [user_nickName] . "就是杀手" );
		} else {
			$this->saveRoomLog ( "1000521", "通告:确认\n<" . $target_user [user_sign] . ">" . $target_user [user_nickName] . "并不是凶手", 2 );
			$this->postText ( "抱歉，<" . $target_user [user_sign] . ">" . $target_user [user_nickName] . "不是杀手" );
		}
	}
	//-------检测投票是否有效
	private function testVoting() {
		global $atUser, $users_at_room, $users_at_game, $fromUsername, $user_me, $my_room;
		
		$sign = $this->changeSignToInt ( $atUser );
		$user_me [target_user] = $sign;
		$this->updateUser ( $user_me, array ("user_status", "target_user" ) );
		
		$this->getMyRoom ();
		$this->getUsersInRoom ();
		$this->getUserAtGame ();
		
		$alivers=array();
		
		$targetStr = "";
		$notVoted = 0;
		$target_user="";
		foreach ( $users_at_game as $user ) {
			$foundUser = $this->getUserAtRoom ( $user [wx_id] );
			if ($foundUser) {
				if ($foundUser [user_sign] == $atUser) {
					$target_user = $foundUser;
					if ($foundUser [user_status] == '2') {
						$this->postText ( "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "已经死亡" );
					}
				}
				//活着的人
				if ($foundUser [user_status] == 1) {
					array_push ( $alivers, $foundUser );
					$targetAt = "";
					if ($foundUser [target_user]) {
						
						$UserNum = $this->getUserNum ( $foundUser [target_user] );
						if ($UserNum) {
							$UserNum -= 1;
							if ($users_at_game [$UserNum] [user_gotVoteNum]) {
								$n = $users_at_game [$UserNum] [user_gotVoteNum];
								$users_at_game [$UserNum] [user_gotVoteNum] = $n + 1;
							} else {
								$users_at_game [$UserNum] [user_gotVoteNum] = 1;
							
		//$this->postText("人".$beenVotedUser[user_sign]);
							}
						}
						
						$targetAt = "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "投给" . $foundUser [target_user] . "号";
					} else {
						$notVoted += 1;
						$targetAt = "<" . $foundUser [user_sign] . ">" . $foundUser [user_nickName] . "未投票";
					}
					if ($targetStr) {
						$targetStr = $targetStr . "\n" . $targetAt;
					} else {
						$targetStr = $targetStr . $targetAt;
					}
				}
			} else {
				if ($user [user_sign] == $atUser) {
					$this->postText ( "<" . $user [user_sign] . ">" . $this->decodeStr ( $user [user_nickName] ) . "已经自主死亡(离开房间)！" );
				}
			}
		}
		if (! $target_user) {
			$this->postText ( "没有找到该编号" . $atUser . "\n" . cutOffLine . "成员编号:\n" . $this->showUsersAtGame () );
		}
		
		$this->saveRoomLog ( "100053", "<" . $user_me [user_sign] . ">" . $user_me [user_nickName] . "认为" . $target_user [user_sign] . "号" . $target_user [user_nickName] . "是凶手", 3 );
		
		if ($notVoted > 0.5 * count ( $alivers )) {
			$this->updateUser ( $user_me, array ("target_user" ) );
			$this->postText ( "目前还有" . $notVoted . "位成员没有投票\n" . "所有成员投票为:\n" . $targetStr );
		}
		
		//-----唱票
		$allVoteStr = "";
		foreach ( $users_at_game as $key => $row ) {
			$allVoteStr = $allVoteStr . "<" . $row [user_sign] . ">" . $this->decodeStr ( $row [user_nickName] ) . ":" . $row [user_gotVoteNum] . "票\n";
			$sortWx_id [$key] = $row ['wx_id'];
			$sortSign [$key] = $row ['user_sign'];
			$sortVote [$key] = $row ['user_gotVoteNum'];
		}
		array_multisort ( $sortVote, SORT_DESC, $sortSign, $sortWx_id );
		
		$mostVote = $sortVote [0];
		$theSameNum = 0;
		$theSameStr = "";
		foreach ( $sortVote as $key => $row ) {
			if ($sortVote [$key] >= $mostVote) {
				$theSameNum += 1;
				$theSameUser = $this->getUserAtRoom ( $sortWx_id [$key] );
				if ($theSameStr) {
					$theSameStr = $theSameStr . "和" . $theSameUser [user_nickName];
				} else {
					$theSameStr = $theSameStr . $theSameUser [user_nickName];
				}
			
			}
		}
		if ($theSameNum > 1) {
			$this->postText ( "目前有两位成员得票数相同:\n" . $theSameStr . "都有" . $mostVote . "票\n请重新投票\n" . cutOffLine . "所有人得票:\n" . $allVoteStr );
		}
		//$this->postText(var_export($sortVote,true).var_export($sortSign,true));
		//$this->postText("所有人得票:\n".$allVoteStr);
		

		//－－－－投票成功
		$target_user = $users_at_game [$this->getUserNum ( $sortSign [0] ) - 1];
		$target_user [user_status] = 2;
		$this->updateUser ( $target_user, array ("user_status" ) );
		
		$this->saveRoomLog ( "100062", "快报:" . $target_user [user_sign] . "号" . $this->decodeStr ( $target_user [user_nickName] ) . "被判决，立即执行", 3 );
		//是否游戏结束
		if ($this->getRoleNum ( 2 ) == 0) {
			$this->saveRoomLog ( "100063", intro_100063, 3 ); //杀手赢;	
			$this->resetGame ();
			$this->postText ( intro_100063 );
		}
		if ($this->getRoleNum ( 1 ) == 0) {
			$this->saveRoomLog ( "100064", intro_100064, 3 ); //杀手失败;
			$this->resetGame ();
			$this->postText ( intro_100064 );
		}
		//$user_me[target_user]=0;
		//---游戏继续
		$my_room [room_status] = "10021";
		$this->updateRoom ( $my_room, array ("room_status" ) );
		
		$this->saveRoomLog ( "100065", intro_100065, 3 ); //游戏继续;
		

		foreach ( $users_at_room as $pUser ) {
			$pUser [target_user] = 0;
			//$pUser[last_action]=0;	
			$this->updateUser ( $pUser, array ("target_user" ) );
		}
		$this->refreshRoomLogs ();
		$this->postText ( $room_logs );
	}
	private function resetGame() {
		global $users_at_room, $my_room;
		$this->getMyRoom ();
		$this->getUsersInRoom ();
		$my_room [room_status] = "10020";
		$this->updateRoom ( $my_room, array ("room_status" ) );
		foreach ( $users_at_room as $pUser ) {
			$pUser [target_user] = 0;
			$pUser [user_role] = 0;
			$pUser [user_status] = 0;
			$pUser [user_sign] = 0;
			$pUser [user_checked] = 0;
			$this->updateUser ( $pUser, array ("target_user", "user_role", "user_status", "user_sign", "user_checked" ) );
		}
	}
	private function getRoleNum($role) {
		global $users_at_game;
		$num = 0;
		foreach ( $users_at_game as $aUser ) {
			if ($aUser [user_role] == $role) {
				$num += 1;
			}
		}
		return $num;
	}
	private function my_sort($a, $b) {
		if ($a [user_gotVoteNum] == $b [user_gotVoteNum])
			return 0;
		return ($a [user_gotVoteNum] > $b [user_gotVoteNum]) ? - 1 : 1;
	}
	private function getUserAtRoom($id) {
		global $users_at_room;
		$theUser;
		
		foreach ( $users_at_room as $user ) {
			if ($user ['wx_id'] == $id) {
				$theUser = $user;
			}
		}
		return $theUser;
	}
	private function getUserNum($sign) {
		global $users_at_game;
		$theNum = 0;
		$i = 1;
		foreach ( $users_at_game as $user ) {
			if ($user ['user_sign'] == $sign) {
				$theNum = $i;
				return $theNum;
			}
			$i += 1;
		}
		
		return $theNum;
	}
	//-----转换成员编号
	private function changeToSign($id) {
		$str = "";
		if ($id == "-1") {
			return $str;
		}
		//$str=substr(user_sign_str,$id,1);		
		return $id;
	
		//return $str;
	}
	//---编号转换成数字
	private function changeSignToInt($id) {
		
		//$id=stripos(user_sign_str,strtoupper($sign));
		

		return $id;
	}
	//----
	private function checkSomeOne() {
		global $user_role, $atUser;
		switch ($user_role) {
			case 0 :
				$this->postText ( "作为游戏旁观者，你没有验人的机会" );
				break;
			case 1 :
				$this->postText ( "你要自己验自己？你要去抢警察的饭碗吗？" );
				break;
			case 2 :
				$this->testChecking ();
				break;
			case 3 :
				$this->postText ( "警察已经出动了！作为平民，你有投票的权利！" );
				break;
			default :
				$this->postText ( "你不是警察" );
				break;
		}
	}
	//搜索房间是否存在
	private function searchRoom() {
		global $fromUsername, $con, $room_id;
		
		$result = mysql_query ( "SELECT * FROM room WHERE room_id ='" . $room_id . "'" );
		
		if (mysql_fetch_array ( $result )) {
			return true;
		}
		return false;
	}
	//进入房间
	private function enterRoom() {
		global $fromUsername, $room_id, $user_nickName, $users_at_room, $my_room, $room_first_in, $room_logs;
		
		if ($this->searchRoom ()) {
			$this->getMyRoom ();
			if ($my_room [user_num] >= $my_room [user_num_max]) {
				$this->postText ( "该小镇人已经够多了，不再接受外来人口" );
			}
			mysql_query ( "UPDATE user SET room_id ='" . $room_id . "' WHERE wx_id = '" . $fromUsername . "'" );
			
			$this->saveRoomLog ( "10002", $user_nickName . "搬到了小镇", 3 );
			
			if (sizeOf ( $users_at_room ) < 1) {
				mysql_query ( "UPDATE room SET admin_user ='" . $fromUsername . "' WHERE room_id = '" . $room_id . "'" );
			}
			$room_first_in = 1;
			
			$this->refreshRoomLogs ();
			
			$this->postText ( "欢迎来到:" . $room_id . "号小镇，请输入[OK]开始游戏\n镇上有成员:\n" . $this->showAllUsers () . $room_logs );
			return true;
		} else {
			$this->postText ( "没有该小镇:" . $room_id . "，" . help_room_join );
		}
		return false;
	}
	
	//快速进入房间
	private function quickEnterRoom() {
		global $room_id;
		
		$result = mysql_query ( "SELECT * FROM room WHERE `room_isFull` = '0' AND `room_status` =10020 ORDER BY  `game_checked_num` DESC" );
		$room = mysql_fetch_array ( $result );
		if ($room) {
			$room_id = $room [room_id];
			$this->enterRoom ();
		} else {
			$this->createRoom ();
		}
	}
	private function recommendRoom() {
	
	}
	//离开房间
	private function leaveRoom() {
		global $fromUsername, $con, $room_id, $user_nickName, $my_room, $users_at_room;
		mysql_query ( "UPDATE user SET user_sign='0',user_checked ='0',user_status ='0',room_id ='0',user_role='0',target_user='0' WHERE wx_id = '" . $fromUsername . "'" );
		
		$this->getUsersInRoom ();
		
		$this->getMyRoom ();
		
		if ($my_room [admin_user] == $fromUsername && sizeOf ( $users_at_room ) > 0) {
			mysql_query ( "UPDATE room SET admin_user ='" . $users_at_room [0] [wx_id] . "' WHERE room_id = '" . $room_id . "'" );
		}
		
		$this->saveRoomLog ( "10003", $user_nickName . "离开了小镇", 3 );
		
		$this->postText ( "你离开了" . $room_id . "号小镇," . help_room_join );
	}
	//建立新的房间
	private function createRoom() {
		global $fromUsername, $con, $room_id, $dateTime, $user_nickName;
		
		$sql = "INSERT INTO `sharenyouxi_wx`.`room` (`admin_user`, `create_time`) VALUES ('" . $fromUsername . "', '" . $dateTime . "' )";
		
		$room = mysql_query ( $sql, $con );
		if ($room) {
			$room_id = mysql_insert_id ();
			mysql_query ( "UPDATE user SET room_id ='" . $room_id . "' WHERE wx_id = '" . $fromUsername . "'" );
			$this->saveRoomLog ( "10002", $user_nickName . "第一个来到这里并且创建了这个小镇", 3 );
			$this->postText ( "你真是一位勇敢的开拓者," . $room_id . "号小镇由你一手创建" . "\n快复制该消息至你的微信好友邀请TA来参加这个小镇吧，加入方法：关注[杀人游戏]微信号:sharenyouxi_wx," . help_room_join . "。\n 输入:[杀人游戏] 或者 杀人游戏 可获得更多信息，输入:[疑问]查看帮助" );
		}
	
		//room_id
	

	}
	//保存房间信息
	private function saveRoomLog($action_tag, $log_content, $log_type) {
		global $fromUsername, $room_id, $user_role, $dateTime, $my_room;
		mysql_query ( "INSERT INTO `sharenyouxi_wx`.`room_logs` (`wx_id`, `room_id`, `create_time`, `user_role`, `log_content`, `game_id`,`action_tag`, `log_type`) VALUES ('" . $fromUsername . "', '" . $room_id . "', '" . $dateTime . "','" . $user_role . "','" . $log_content . "','" . $my_room [game_id] . "','" . $action_tag . "','" . $log_type . "' )" );
	
	}
	//----获取当前房间所有用户
	private function getUsersInRoom() {
		global $room_id, $users_at_room, $my_room;
		$this->getMyRoom ();
		if ($users_at_room) {
			return true;
		}
		$result = mysql_query ( "SELECT * FROM user WHERE room_id ='" . $room_id . "'" );
		$i = 0;
		while ( $user = mysql_fetch_array ( $result ) ) {
			//array_push($users_at_room,$user);
			$users_at_room [$i] = $user;
			$i += 1;
		}
		//$this->postText(count($users_at_room));
		$isFull = 0;
		if (count ( $users_at_room ) >= $my_room ['user_num_max']) {
			$isFull = 1;
		
		//$this->postText(count($users_at_room));
		}
		mysql_query ( "UPDATE room SET user_num ='" . $i . "',room_isFull =" . $isFull . " WHERE room_id = '" . $room_id . "'" );
		//$this->postText(var_export($users_at_room,true));
		return true;
	}
	//----查看当前房间所有人
	private function showAllUsers() {
		global $room_id, $users_at_room, $my_room;
		if (! $room_id) {
			$str = "未加入任何小镇" . help_room_join;
			return $str;
		}
		
		$this->getUsersInRoom ();
		$this->getMyRoom ();
		
		//$str="";
		foreach ( $users_at_room as $user ) {
			//$sign="(".$this->changeToSign($user['user_sign']).")";	
			if ($my_room [admin_user] == $user ['wx_id']) {
				//$sign=$sign."[管理员]";
			}
			if ($user ["user_sign"] && $user ["user_sign"] != - 1) {
				
				$str = $str . "<" . $user ["user_sign"] . ">" . $user ["user_nickName"];
				if ($user ["user_status"] == 2) {
					$str = $str . "(死亡)";
				}
				$str = $str . "\n";
			
			} else {
				if ($user ["user_checked"]) {
					$str = $str . "<等待发牌>" . $user ["user_nickName"] . "\n";
				} else {
					$str = $str . "<未游戏>" . $user ["user_nickName"] . "\n";
				}
			
			}
		
		}
		return $str;
	}
	//获取房间当前状态
	private function refreshGameStatus() {
		global $my_room, $room_game_status, $user_me;
		$sign_str = "";
		if ($user_me [user_sign] > 0) {
			$sign_str = "<" . $user_me [user_sign] . ">";
		}
		switch ($my_room ["room_status"]) {
			case "10020" : //等待发牌阶段
				$room_game_status = intro_10020 . "(还差" . ($my_room [game_user_num] - $my_room [game_checked_num]) . "位确认)" . "\n(" . $user_me [user_nickName] . ")\n" . cutOffLine . "输入[OK]参与游戏，当参与人数达到" . $my_room ['game_user_num'] . "位时，将为每位成员分配角色,该阶段每位成员均可自由发言";
				break;
			case "10021" : //杀手杀人阶段
				$room_game_status = intro_10021 . "\n(" . $sign_str . $user_me [user_nickName] . " " . $this->roleStr ( $user_me [user_role] ) . ")\n" . cutOffLine . "杀手输入编号可杀指定成员,被指定成员即被杀害,\n该阶段发言将被做匿名处理，杀手向同伙发言可输入[嘘]+发言，查看编号输入:人";
				break;
			case "10022" : //警察验人阶段
				$room_game_status = intro_10022 . "\n(" . $sign_str . $user_me [user_nickName] . " " . $this->roleStr ( $user_me [user_role] ) . ")\n" . cutOffLine . "警察输入编号指认成员，系统回复指认结果\n该阶段发言将被做匿名处理，警察向队友发言可输入[嘘]+发言，查看编号输入:人";
				break;
			case "10023" : //投票阶段
				$room_game_status = intro_10023 . "\n(" . $sign_str . $user_me [user_nickName] . " " . $this->roleStr ( $user_me [user_role] ) . ")\n" . cutOffLine . "输入编号可投票指认成员，超过半数的活着成员投票结束后得票最多的人将被判决，查看编号输入:人";
				break;
			default :
				break;
		}
		return $room_game_status;
	}
	//---刷新房间动态
	private function refreshRoomLogs() {
		global $room_logs, $dateTime, $room_id, $user_last_log_time, $user_role, $room_first_in, $room_first_in, $user_is_looker, $my_room, $user_me;
		
		$room_logs = "";
		//$this->postText($this->getLogsForNews());
		

		$log_news = $this->getLogsForNews ();
		
		$room_logs = $room_logs . "===《城市新闻》===\n" . $this->getAllNews () . "\n";
		$room_logs = $room_logs . "===《小镇新闻》===\n" . $log_news . "\n";
		
		if ($user_me [user_role] == 1) {
			$log_killer = $this->getLogsForKiller ();
			$room_logs = $room_logs . "==《杀手内部消息》==\n" . $log_killer . "\n";
		}
		if ($user_me [user_role] == 2) {
			$log_police = $this->getLogsForPolice ();
			$room_logs = $room_logs . "==《警察内部消息》==\n" . $log_police . "\n";
		}
		$room_logs = $room_logs . "===《小镇广播》===\n" . $this->getLogsAtRoom ();
	
	}
	//------房间动态
	private function getLogsAtRoom() {
		global $dateTime, $room_id, $user_last_log_time, $user_role, $room_first_in, $room_first_in, $user_is_looker, $my_room;
		$logs = "";
		$result;
		if ($room_first_in || $user_is_looker) {
			$result = mysql_query ( "SELECT * FROM room_logs WHERE room_id ='" . $room_id . "' AND `log_type` = '0' ORDER BY  `id` DESC  LIMIT 0, 10" );
		} else {
			$result = mysql_query ( "SELECT * FROM room_logs WHERE room_id ='" . $room_id . "'AND `log_type` = '0' AND  `create_time` >='" . $user_last_log_time . "'ORDER BY  `id` DESC  LIMIT 0, 30" );
		}
		$i = 0;
		while ( $row = mysql_fetch_array ( $result ) ) {
			if ($row [action_tag] == "100041" && $row [user_role] != $user_role) {
				//$room_logs=$room_logs."------"."秘密敏秘密";
				continue;
			}
			if ($i == "0") {
				$logs = substr ( $row [create_time], 10 ) . " " . $row [log_content];
			} else {
				$logs = $logs . "\n" . substr ( $row [create_time], 10 ) . " " . $row [log_content];
			}
			$i += 1;
		}
		if ($logs == "") {
			$logs = "没有最新动态";
		}
		return $logs;
	}
	//-------城市新闻
	private function getAllNews() {
		global $dateTime, $room_id, $user_last_log_time, $user_role, $room_first_in, $room_first_in, $user_is_looker, $my_room;
		$logs = "";
		$result="";
		/*
		if ($room_first_in||$user_is_looker){
			$result = mysql_query ( "SELECT * FROM room_logs ORDER BY  `id` DESC  LIMIT 0, 5" );			
		}else {
			$result = mysql_query ( "SELECT * FROM room_logs WHERE `create_time` >='".$user_last_log_time."'ORDER BY  `id` DESC  LIMIT 0, 5" );
		}*/
		if ($room_id) {
			$result = mysql_query ( "SELECT * FROM room_logs WHERE  `room_id` !=" . $room_id . " ORDER BY  `id` DESC  LIMIT 0, 3" );
		} else {
			$result = mysql_query ( "SELECT * FROM room_logs ORDER BY  `id` DESC  LIMIT 0, 3" );
		}
		
		$i = 0;
		while ( $row = mysql_fetch_array ( $result ) ) {
			$str = "";
			if ($row [room_id]) {
				$str = "<" . $row [room_id] . "号小镇>";
			}
			if ($row [log_type] == 2) {
				$str = $str . "<警察内部消息:***>";
			} elseif ($row [log_type] == 1) {
				$str = $str . "<杀手内部消息:***>";
			} else {
				$str = $str . $row [log_content];
			}
			
			if ($i == "0") {
				$logs = substr ( $row [create_time], 10 ) . " " . $str;
			} else {
				$logs = $logs . "\n" . substr ( $row [create_time], 10 ) . " " . $str;
			}
			$i += 1;
		}
		if ($logs == "") {
			$logs = "";
		}
		return $logs;
	}
	//------新闻信息
	private function getLogsForNews() {
		global $dateTime, $room_id, $user_last_log_time, $user_role, $room_first_in, $room_first_in, $user_is_looker, $my_room, $user_me;
		$logs = "";
		
		$result = mysql_query ( "SELECT * FROM room_logs WHERE room_id ='" . $room_id . "' AND `log_type` = '3' AND `create_time` >='" . $user_me [last_log_time_news] . "' ORDER BY  `id` DESC  LIMIT 0, 10" );
		$i = 0;
		while ( $row = mysql_fetch_array ( $result ) ) {
			if ($i == "0") {
				$logs = substr ( $row [create_time], 10 ) . " " . $row [log_content];
			} else {
				$logs = $logs . "\n" . substr ( $row [create_time], 10 ) . " " . $row [log_content];
			}
			$i += 1;
		}
		$user_me ["last_log_time_news"] = "'" . $dateTime . "'";
		$this->updateUser ( $user_me, array ("last_log_time_news" ) );
		if ($logs == "") {
			$logs = "没有最新动态";
		}
		return $logs;
	}
	//------杀手信息
	private function getLogsForKiller() {
		global $dateTime, $room_id, $user_last_log_time, $user_role, $room_first_in, $room_first_in, $user_is_looker, $my_room, $user_me;
		$logs = "";
		$result="";;
		$result = mysql_query ( "SELECT * FROM room_logs WHERE room_id ='" . $room_id . "' AND log_type = '1' AND `create_time` >='" . $user_me [last_log_time_killer] . "' ORDER BY  `id` DESC  LIMIT 0, 10" );
		$i = 0;
		while ( $row = mysql_fetch_array ( $result ) ) {
			if ($i == "0") {
				$logs = substr ( $row [create_time], 10 ) . " " . $row [log_content];
			} else {
				$logs = $logs . "\n" . substr ( $row [create_time], 10 ) . " " . $row [log_content];
			}
			$i += 1;
		}
		if ($logs == "") {
			$logs = "没有最新动态";
		}
		
		$user_me [last_log_time_killer] = "'" . $dateTime . "'";
		$this->updateUser ( $user_me, array ("last_log_time_killer" ) );
		
		return $logs;
	}
	//------警察信息
	private function getLogsForPolice() {
		global $dateTime, $room_id, $user_last_log_time, $user_role, $room_first_in, $room_first_in, $user_is_looker, $my_room, $user_me;
		$logs = "";
		$result="";;
		$result = mysql_query ( "SELECT * FROM room_logs WHERE room_id ='" . $room_id . "' AND `log_type` =  '2' AND `create_time` >='" . $user_me [last_log_time_police] . "' ORDER BY  `id` DESC  LIMIT 0, 10" );
		$i = 0;
		while ( $row = mysql_fetch_array ( $result ) ) {
			if ($i == "0") {
				$logs = substr ( $row [create_time], 10 ) . " " . $row [log_content];
			} else {
				$logs = $logs . "\n" . substr ( $row [create_time], 10 ) . " " . $row [log_content];
			}
			$i += 1;
		}
		if ($logs == "") {
			$logs = "没有新通告";
		}
		
		$user_me [last_log_time_police] = "'" . $dateTime . "'";
		$this->updateUser ( $user_me, array ("last_log_time_police" ) );
		
		return $logs;
	}
	//发布文字信息
	public function postText($text) {
		global $con;
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
		mysql_close ( $con );
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
	//测试信息
	private function myTest() {
		
		$arr = array ('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 );
		$this->postText ( json_encode ( $arr ) );
	
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