<?php

if (session_status() == PHP_SESSION_NONE) {
    return;
}

require_once(__DIR__.'/meekrodb.2.3.class.php');


class DBUser {
	private $mdb;

    /**
	 * Constructor for database
	 */
	function __construct() {
		$this->mdb = new MeekroDB($_SERVER['DB_SERVER'], $_SERVER['DB_USER'], $_SERVER['DB_PASSWORD'], $_SERVER['DB_NAME'], $_SERVER['DB_PORT']);
    }
    
    /**
	 * Login as user and returns if successful
	 */
	function login($username, $password) {
		$results = $this->mdb->query("SELECT * FROM gameUser WHERE username = %s AND password = SHA2(%s, 224)", $username, $password);
		if (count($results) > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Will attempt to create new user and returns if successful
	 */
	function createUser($username, $password) {
		$results = $this->mdb->query("INSERT INTO gameUser (username, password) VALUES (%s, SHA2(%s, 224))", $username, $password);
		return "created user";
	}

	/**
	 * Returns if user exists
	 */
	function checkUserExists($username) {
		$users = $this->mdb->query("SELECT * FROM gameUser WHERE username = %s", $username);
		if (count($users) > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Updates user or users to 
	 */
	function userLastOnline($username, $timestamp) {
		$this->mdb->query("UPDATE gameUser SET lastOnline =  %t, isOnline = 1 WHERE username = %s", $timestamp, $username);
	}

	// Gets users in lobby
	function getLobbyUsers($username) {
		$usernames = $this->mdb->queryOneColumn("username", "SELECT g.username FROM lobby as l LEFT OUTER JOIN gameUser as g ON l.id = g.lobbyId
			WHERE g.isOnline = 1 AND l.id IN (SELECT l.id FROM lobby as l LEFT OUTER JOIN gameUser as g ON l.id = g.lobbyId WHERE g.username = %s)", $username);
		
		// error_log( print_r($usernames, true)."\n", 3, __DIR__."/test.log");
		return $usernames;
	}

	// Set to offline where users didn't send a request for more than 10 min offline
	function setUsersOffline() {
		$this->mdb->query("UPDATE gameUser SET isOnline =  0, lobbyId = 0 WHERE lastOnline < (NOW() - INTERVAL 10 MINUTE) AND isOnline !=  0");
	}

	// Move user from lobby with just 1 user
	function moveUsersFromEmptyLobby() { 
		$this->mdb->query("UPDATE gameUser as g RIGHT OUTER JOIN 
			(SELECT l.id as id FROM lobby as l LEFT OUTER JOIN gameUser as g ON g.lobbyId = l.id 
			WHERE l.id != 0 GROUP BY l.id HAVING COUNT(l.id) < 2) lobbyCount ON g.lobbyId = lobbyCount.id
			LEFT OUTER JOIN game ON game.lobbyId = g.lobbyId SET g.lobbyId = 0 WHERE game.botGame = FALSE");
	}

	// Creates new lobby
	function newLobby() {
		$this->mdb->query("INSERT INTO lobby (id) VALUES (null)");
		return $this->mdb->insertId();
	}

	/**
	 * Moves user to different lobby
	 */
	function moveUserToLobby($username, $lobbyId) {
		$this->mdb->query("UPDATE gameUser SET lobbyId = %i WHERE username = %s", $lobbyId, $username);
	}
	
	/**
	 * Deletes empty lobbies after 1 minute (Fixed issue where deleting lobby before users can enter)
	 */
	function removeEmptyLobby() {
		$lobbyIds = $this->mdb->queryOneColumn("id", "SELECT l.id FROM lobby AS l 
			LEFT OUTER JOIN gameUser as g ON g.lobbyId = l.id 
			WHERE l.id != 0 AND l.created < (NOW() - INTERVAL 2 MINUTE) GROUP BY l.id HAVING COUNT(g.username) = 0");
		
		if (count($lobbyIds) > 0) {
			$this->mdb->query("DELETE lobby FROM lobby WHERE id IN %li", $lobbyIds);
		}
	}

	// Gets user's lobby
	function getUserLobby($username) {
		return $this->mdb->queryOneField("lobbyId", "SELECT lobbyId FROM gameUser WHERE username = %s", $username);
	}

	// Check if user has been challenged
	function checkUserChallenged($username1, $username2) {
		$results = $this->mdb->query("SELECT * FROM challenge WHERE (gameUser1Id = %s AND gameUser2Id = %s) 
			OR (gameUser1Id = %s AND gameUser2Id = %s)", $username1, $username2, $username2, $username1);
		// Check if already challenged
		if (count($results) > 0) {
			return true;
		} else {
			return false;
		}
	}

	// Creates challenge with 2 users with the user1 already accepted
	function createChallenge($username1, $username2) {
		$timestamp = date("Y-m-d H:i:s");
		$this->mdb->query("INSERT INTO challenge (gameUser1Id, gameUser2Id, gameUser1Accepted, timestamp)
			VALUES (%s, %s, 1, %t)", $username1, $username2, $timestamp);
	}

	// Check challenge status
	function getChallengeStatus($username) {
		return $this->mdb->query("SELECT gameUser1Id, gameUser2Id, gameUser1Accepted, gameUser2Accepted, timestamp 
			FROM challenge WHERE gameUser1Id = %s OR gameUser2Id = %s", $username, $username);

	}

	// Clear challenges with offline users
	function clearChallengesWithOfflineUser() {
		$this->mdb->query("DELETE c FROM challenge as c LEFT OUTER JOIN gameUser as g 
			ON (g.username = c.gameUser1Id OR g.username = c.gameUser2Id) WHERE g.isOnline = 0");
	}

	// Clears challenges over 2 minutes long
	function clearOldChallenges() {
		$this->mdb->query("DELETE challenge FROM challenge WHERE timestamp < (NOW() - INTERVAL 2 MINUTE)");
	}

	// Clears challenge that user declined
	function clearOneChallenge($username1, $username2) {
		$this->mdb->query("DELETE challenge FROM challenge WHERE (gameUser1Id = %s AND gameUser2Id = %s) 
			OR (gameUser2Id = %s AND gameUser1Id = %s)", $username1, $username2, $username1, $username2);
	}

	// Clear all challenges with user
	function clearAllChallenges($username) {
		$this->mdb->query("DELETE challenge FROM challenge WHERE gameUser1Id = %s OR gameUser2Id = %s", $username, $username);
	}
}