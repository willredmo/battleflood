<?php

require("../server/BizData/DBUser.class.php");
require("../server/BizData/DBChat.class.php");
require("../server/BizData/DBGame.class.php");

require("../server/Service/ServiceGame.class.php");


// $db = new DBUser();
// print_r($db->login("test", "test2"));
// if ($db->login("testtest3", "testtest")) {
//     echo "Good";
// } else {
//     echo "Bad";
// }

// print_r($db->newLobby());
// $db->createChallenge("testtest4", "testest3");
// $db->createChallenge("testtest4", "testest2");
// if ($db->checkUserChallenged("testest3", "testtest4")) {
//     echo "true";
// } else {
//     echo "false";
// }
// $db->clearOneChallenge("testest3", "testtest4");
// $db->clearChallengesWithOfflineUser();
// $db->clearOldChallenges();
// $db->clearAllChallenges("testtest4");
// print_r($db->getChallengeStatus("testtest4"));

// print_r($db->removeEmptyLobby());

// echo $db->getUserLobby("testtest");

// echo $db->moveUsersFromEmptyLobby();

// echo $db->moveUserToLobby("testest2", 0);

// echo $db->createUser("testtest2", "testtest2");

$db = new DBGame();
// print_r($db2->getChat("testtest4"));



if ($db->isInGame("testtest1")) {
    echo "In game";
} else {
    echo "Not in game";
}

echo "<br/>";

// print_r($db->getGameData("testtest1"));


$service = new ServiceGame();

print_r($service->getBoard());
