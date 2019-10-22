<?php

if (session_status() == PHP_SESSION_NONE) {
    return;
}

class DBChat {
    private $connection;

    /**
	 * Constructor for database
	 */
	function __construct() {
		$this->connection = new mysqli($_SERVER['DB_SERVER'], $_SERVER['DB_USER'], $_SERVER['DB_PASSWORD'], $_SERVER['DB'], $_SERVER['DB_PORT']);

		if ($this->connection->connect_error) {
			echo "Connection failed: ".mysqli_connect_error();
			die();
		}
    }
    
    /**
	 * Closes connect 
	 */
	function closeConnection() {
		$this->connection->close();
    }
    
    /**
	 * Sends message
	 */
	function sendMessage($username, $text) {
		$timestamp = date("Y-m-d H:i:s");
		try {
			if ($stmt = $this->connection->prepare("INSERT INTO message (lobbyId, gameUserId, text, timestamp)
					SELECT lobbyId, username, ?, ? FROM gameUser WHERE username = ?")) {
				$stmt->bind_param("sss", $text, $timestamp, $username);
				$stmt->execute();
				$stmt->store_result();
				$affected_rows = $stmt->affected_rows;
				$stmt->close();
				if ($affected_rows > 0) {
					return true;
				} else {
					return false;
				}
			} 
		} catch (Exception $e) {
			
		}
	}

	/**
	 * Gets chat from lobby according to username
	 */
	function getChat($username) {
		$data = array();
		try {
			if ($stmt = $this->connection->prepare("SELECT m.gameUserId, m.text, m.timestamp, m.id
					FROM gameUser AS g JOIN message AS m ON m.lobbyId = g.lobbyId WHERE g.username = ?")) {
				$stmt->bind_param("s", $username);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($messageUsername, $text, $timestamp, $id);
				if ($stmt->num_rows > 0) {
					while ($stmt->fetch()) {
						$data[] = array(
							"username"=>$messageUsername,
							"text"=>$text,
							"timestamp"=>$timestamp,
							"id"=>$id
						);
					}
				}
				$stmt->close();
			}
			return $data;
		} catch (Exception $e) {
			
		}
	}
}
