<?php

if (session_status() == PHP_SESSION_NONE) {
    return;
}

require_once(__DIR__.'/ServiceUtils.php');
require_once(__DIR__.'/../BizData/DBUser.class.php');

class ServiceUser {
    private $db;
    private $mainLobby;

    /**
	 * Constructor for service
	 */
	function __construct() {
        $this->db = new DBUser();
        $this->mainLobby = 0;
    }

    /**
     * Attempts to login user
     * @return String - if login was successful
     */
    function login($username, $password) {
        $errors = validateCreateUser($username, $password);
        if ($errors != "") {
            return $errors;
        }
        if ($this->db->login($username, $password)) {
            // Move to main lobby
            $this->db->moveUserToLobby($username, $this->mainLobby);
            $_SESSION["gameLoggedIn"] = true; 
            $_SESSION["username"] = $username;
            return "Success";
        } else {
            return "Inncorrect combination.";
        }
    }

    /**
     * Logouts out user
     */
    function logout() {
        $timestamp = date("Y-m-d H:i:s", mktime(0,0,0,10,3,1975));
        $this->db->userLastOnline(getUsername(), $timestamp);
        if ( isset( $_COOKIE[session_name()] ) )
            setcookie(session_name(), "", time()-3600, "/");
        $_SESSION = array();
        session_destroy();
    }

    /**
     * Attempts to create user
     * @return String - if login was successful
     */
    function createUser($username, $password, $confirm) {    
        $errors = validateCreateUser($username, $password);
        if ($errors != "") {
            return $errors;
        } else if ($password != $confirm) {
            return "Confirm password and password do not match";
        }
        if ($this->db->checkUserExists($username)) {
            return "User already exists";
        }

        if ($this->db->createUser($username, $password)) {
            // Move to main lobby
            $this->db->moveUserToLobby($username, $this->mainLobby);
            // Login with new user
            $_SESSION["gameLoggedIn"] = true; 
            $_SESSION["username"] = $username;
            return "Success";
        } else {
            return "DB error";
        }
    }

    /**
     * Sends update to DB to set user online
     */
    function userOnline() {
        $timestamp = date("Y-m-d H:i:s");
        $this->db->userLastOnline(getUsername(), $timestamp);
    }

    // Gets current lobby users
    function getLobbyUsers() {
        $this->db->setUsersOffline();
        return $this->db->getLobbyUsers(getUsername());
    }

    // Gets current lobby id
    function getCurrentLobby() {
        // Remove empty lobbies
        $this->db->removeEmptyLobby();
        $this->db->moveUsersFromEmptyLobby();
        return $this->db->getUserLobby(getUsername());
    }

    // Exit to main lobby
    function exitToMainLobby() {
        $user = getUsername();
        if ($this->db->getUserLobby($user) != 0) {
            $this->db->moveUserToLobby($user, $this->mainLobby);
        }
    }

    // Challenges user
    function challengeUser($userToChallenge) {
        $currentUser = getUsername();
        $response = array();

        // check if in game first
        if ($this->db->getUserLobby(getUsername()) != 0) {
            return "Already in game";
        }

        // Cannot challenge self
        if ($userToChallenge == $currentUser) {
            $this->db->clearAllChallenges($currentUser);
            $newLobbyId = $this->db->newLobby();
            $this->db->moveUserToLobby($currentUser, $newLobbyId);
            // Create game
            require_once("Service/ServiceGame.class.php");
            $serviceGame = new ServiceGame();
            $serviceGame->newGame();
            return "Created game with bot";
        }

        // Check that lobby is mainlobby
        if ($this->db->getUserLobby($currentUser) == $this->mainLobby && $this->db->getUserLobby($userToChallenge) == $this->mainLobby) {
            if (!$this->db->checkUserChallenged($currentUser, $userToChallenge)) {
                // Create challenge
                $this->db->createChallenge($currentUser, $userToChallenge);
                return "Challenged $userToChallenge";
            } else {
                return "Cannot challenge user who has already been challenged";
            }
        } else {
            return "Both users must be in main lobby to challenge";
        }
    }

    // Get current challenges
    function getChallenges() {
        // Clear old challenges
        $this->db->clearOldChallenges();

        $challenges = array();
        $currentUser = getUsername();
        $this->db->clearOldChallenges($currentUser);
        $tempChallenges = $this->db->getChallengeStatus($currentUser);
        foreach ($tempChallenges as $index => $tempChallenge) {
            // Check both accept
            $challengedUser;
            $youAccepted;
            $otherAccepted;
            if ($tempChallenge["gameUser1Id"] == $currentUser) {
                $challengedUser = $tempChallenge["gameUser2Id"];
                $youAccepted = $tempChallenge["gameUser1Accepted"];
                $otherAccepted = $tempChallenge["gameUser2Accepted"];
            } else if ($tempChallenge["gameUser2Id"] == $currentUser) {
                $challengedUser = $tempChallenge["gameUser1Id"];
                $youAccepted = $tempChallenge["gameUser2Accepted"];
                $otherAccepted = $tempChallenge["gameUser1Accepted"];
            }
            $challenges[] = array(
                "challengedUser"=>$challengedUser,
                "youAccepted"=>convertIntToBoolean($youAccepted),
                "otherAccepted"=>convertIntToBoolean($otherAccepted)
            );
        }
        return $challenges;
    }

    // Respond to challenge
    function respondToChallenge($challengedUser, $acceptDecline) {
        $currentUser = getUsername();
        if (!$this->db->checkUserChallenged(getUsername(), $challengedUser)) {
            return;
        }
        $currentUserStatus;
        $challengedUserStatus;
        $challenges = $this->db->getChallengeStatus($currentUser);
        foreach($challenges as $index => $challenge) {
            if ($challenge["gameUser1Id"] == $currentUser && $challenge["gameUser2Id"] == $challengedUser) {
                $currentUserStatus = convertIntToBoolean($challenge["gameUser1Accepted"]);
                $challengedUserStatus = convertIntToBoolean($challenge["gameUser2Accepted"]);
                break;
            } else if ($challenge["gameUser2Id"] == $currentUser && $challenge["gameUser1Id"] == $challengedUser) {
                $currentUserStatus = convertIntToBoolean($challenge["gameUser2Accepted"]);
                $challengedUserStatus = convertIntToBoolean($challenge["gameUser1Accepted"]);
                break;
            }
        }
        // Check if user can respond to challenge
        if ($challengedUserStatus) {
            if ($acceptDecline == "accept") {
                $this->db->clearAllChallenges($currentUser);
                $this->db->clearAllChallenges($challengedUser);
                // Create lobby and move users to lobby
                $newLobbyId = $this->db->newLobby();
                $this->db->moveUserToLobby($currentUser, $newLobbyId);
                $this->db->moveUserToLobby($challengedUser, $newLobbyId);
                // Create game
                require_once("Service/ServiceGame.class.php");
                $serviceGame = new ServiceGame();
                $serviceGame->newGame();
                return "Accepted challenge and joined game";
            } else if ($acceptDecline == "decline") {
                $this->db->clearOneChallenge($currentUser, $challengedUser);
                return "Declined challenge from $challengedUser";
            }
        } else {
            return "$challengedUser has declined";
        }
    }
}