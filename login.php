<?php

// Login session
session_name("gameLogin");
session_start();

require_once("htmlUtils.php");
require_once("Service/ServiceUtils.php");

// $service = new ServiceUser();

$content = "";

if (loggedIn()) {
	header("Location: ./index.php");
} 

$content .= html_loginForm();

// html
echo html_header("Login");

echo html_mainContainer($content);
echo html_footer(false);

?>