<?php

// Login session
session_name("gameLogin");
session_start();

require_once("htmlUtils.php");
require_once("Service/ServiceUtils.php");

$content = "";
if (!loggedIn()) {
    // Go to login
    header('Location: ./login.php');
}

// html
echo html_header("Game");

echo html_mainContainer(html_game(getUsername()));
echo html_footer(true);

?>