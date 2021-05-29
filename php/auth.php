<?php

$session_standing = 10800;
$level_access = 2;
$currentUser = null;
$auth = false;

if(isset($_GET['token'])){
    $token = $_GET['token'];
    if( $query = dbQueryOne("SELECT account.* FROM account, account_session WHERE account_session.session_key = '{$token}' AND account.id = account_session.account_id") ){

        if((intval($query['session_time'])+$session_standing) > time()){
        	$level_access = ($query["type"] === "moderator") ? 1 : 0;
	        $currentUser = $query;
	        $auth = true;
        } else {
        	load_error(401, "Срок действия сессия истёк");
        }
    } else {
        load_error(401, "Неуспешная авторизация");
    }
}

function create_session($userId){
    return hash("sha256", $userId.time());
}
