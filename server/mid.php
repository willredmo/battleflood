<?php
//this is the entry point to our architecture.  All goes through this...
// input
//	a - for area (ex: chat, game, login, x)
//	method - which method to call in the area...
//	data (optional) - if there is data, send it along...

if(!empty($_GET['method']) || !empty($_POST['method'])){
	session_name("gameLogin");
	session_start();
	require_once("Service/ServiceUtils.php");

	// Need to sanatize data
	$cleanRequest = sanitize($_REQUEST);
	
	$service;

	// DB name
	$_SERVER["DB_NAME"] = "battleflood";

	if (!loggedIn()) {
		// Must be logged in to use service
		if ($cleanRequest['a'] == "user" && ($cleanRequest['method'] == "login" || $cleanRequest['method'] == "createUser")) {
			require_once("Service/ServiceUser.class.php");
			$service = new ServiceUser();
		} else {
			return "Authorized Access.";
		}
		
		
	} else {
		if ($cleanRequest['a'] == "chat") {
			require_once("Service/ServiceChat.class.php");
			$service = new ServiceChat();
		} elseif ($cleanRequest['a'] == "user") {
			require_once("Service/ServiceUser.class.php");
			$service = new ServiceUser();
		} elseif ($cleanRequest['a'] == "game") {
			require_once("Service/ServiceGame.class.php");
			$service = new ServiceGame();
		} else {
			return "Authorized Access.";
		}
	}

	
	$arguments = (array) json_decode($cleanRequest["data"]);
	$method = $cleanRequest["method"];
	
	//I have loaded all of the scripts in the 'a' (area)
	$result = call_user_func_array([$service, $method], $arguments);
	
	if ($result == 0) {
		$result = "" . $result;
	}
	$result = json_encode($result);
	
	if($result){
		header('Content-Type:text/plain');
		echo $result;
	}  else {
		
	}
}
?>