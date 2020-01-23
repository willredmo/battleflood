<?php

if (session_status() == PHP_SESSION_NONE) {
    return;
}

require_once(__DIR__.'/meekrodb.2.3.class.php');

class DBChat {
    private $mdb;

    /**
	 * Constructor for database
	 */
	function __construct() {
		$this->mdb = new MeekroDB($_SERVER['DB_SERVER'], $_SERVER['DB_USER'], $_SERVER['DB_PASSWORD'], $_SERVER['DB'], $_SERVER['DB_PORT']);
    }
    
    /**
	 * Sends message
	 */
	function sendMessage($username, $text) {
		$timestamp = date("Y-m-d H:i:s");
		$this->mdb->query("INSERT INTO message (lobbyId, gameUserId, text, timestamp)
			SELECT lobbyId, username, %s, %t FROM gameUser WHERE username = %s", $text, $timestamp, $username);
	}

	/**
	 * Gets chat from lobby according to username
	 */
	function getChat($username) {
		return $this->mdb->query("SELECT m.gameUserId as username, m.text, m.timestamp, m.id
			FROM gameUser AS g JOIN message AS m ON m.lobbyId = g.lobbyId WHERE g.username = %s", $username);
	}
}
