<?php 
include_once 'conn.php';
include_once 'defines.php';
include_once 'wechatCallbackapi.php';
include_once 'sharenyouxi_main.php';

/**
 * wechat php test
 */


$fromUsername;
$keyword;

$toUsername;
$user_me;
$user_last_action;
$user_last_log_time;
$user_role;
$user_sign;
$user_nickName;
$user_status;
$user_vote_remain;
$user_checked;
$room_id;
$room_first_in;
$user_is_looker;

$room_logs;
$atUser;
$users_at_room;
$users_at_game;

$room_status;
$room_game_status;
$my_room;
$user_ready_to;
$keyword;

$dateTime=new DateTime();



$wechatObj = new wechatCallbackapi ();
$wechatObj->init();
//$wechatObj->valid();
//$wechatObj->responseMsg ();

$sharenyouxi=new sharenyouxi_main();
$sharenyouxi->init();


//$wechatObj->responseMsg ();
?>