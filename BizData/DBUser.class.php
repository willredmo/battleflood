<?php

if (session_status() == PHP_SESSION_NONE) {
    return;
}

class DBUser {
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
	 * Login as user and returns if successful
	 */
	function login($username, $password) {
		try { // password_hash($password, PASSWORD_DEFAULT); // password_verify($password, $res);
			if ($stmt = $this->connection->prepare("SELECT * FROM gameUser WHERE username = ? AND password = SHA2(?, 224)")) {
				$stmt->bind_param("ss", $username, $password);
				$stmt->execute();
				$stmt->store_result();
				$num_rows = $stmt->num_rows;
				$stmt->close();
				if ($num_rows > 0) {
					return true;
				} else {
					return false;
				}
			}
		} catch (Exception $e) {
			
		}
	}

	/**
	 * Will attempt to create new user and returns if successful
	 */
	function createUser($username, $password) {
		try {
			if ($stmt = $this->connection->prepare("INSERT INTO gameUser (username, password) VALUES (?, SHA2(?, 224))")) {
				$stmt->bind_param("ss", $username, $password);
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
			print_r($e);
		}
	}

	/**
	 * Returns if user exists
	 */
	function checkUserExists($username) {
		try {
			if ($stmt = $this->connection->prepare("SELECT * from gameUser WHERE username = ?")) {
				$stmt->bind_param("s", $username);
				$stmt->execute();
				$stmt->store_result();
				$num_rows = $stmt->num_rows;
				$stmt->close();
				if ($num_rows > 0) {
					return true;
				} else {
					return false;
				}
			}
		} catch (Exception $e) {
			
		}
	}

	/**
	 * Updates user or users to 
	 */
	function userLastOnline($username, $timestamp) {
		try {
			if ($stmt = $this->connection->prepare("UPDATE gameUser SET lastOnline =  ?, isOnline = 1 WHERE username = ?")) {
				$stmt->bind_param("ss", $timestamp, $username);
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

	// Gets users in lobby
	function getLobbyUsers($username) {
		$data = array();
		try {
			if ($stmt = $this->connection->prepare("SELECT g2.username FROM gameUser as g1 
					LEFT OUTER JOIN gameUser as g2 ON g1.lobbyId = g2.lobbyId WHERE g1.username = ? AND g2.isOnline = 1")) {
				$stmt->bind_param("s", $username);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($username);
				$affected_rows = $stmt->affected_rows;
				if ($stmt->num_rows > 0) {
					while ($stmt->fetch()) {
						$data[] = $username;
					}
				}
				$stmt->close();
			} 
			return $data;
		} catch (Exception $e) {
			
		}
	}

	// Set to offline where users didn't send a request for more than 10 min offline
	function setUsersOffline() {
		try {
			if ($stmt = $this->connection->prepare("UPDATE gameUser SET isOnline =  0, lobbyId = 0 WHERE lastOnline < (NOW() - INTERVAL 10 MINUTE) AND isOnline !=  0")) {
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

	// Move user from lobby with just 1 user
	function moveUsersFromEmptyLobby() { 
		try {
			if ($stmt = $this->connection->prepare("UPDATE gameUser as g RIGHT OUTER JOIN 
					(SELECT l.id as id FROM lobby as l LEFT OUTER JOIN gameUser as g ON g.lobbyId = l.id 
					WHERE l.id != 0 GROUP BY l.id HAVING COUNT(l.id) < 2) lobbyCount ON g.lobbyId = lobbyCount.id
					LEFT OUTER JOIN game ON game.lobbyId = g.lobbyId SET g.lobbyId = 0 WHERE game.botGame = FALSE")) {
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

	// Creates new lobby
	function newLobby() {
		$lobbyId;
		try {
			if ($stmt = $this->connection->prepare("INSERT INTO lobby VALUES ()")) {
				$stmt->execute();
				$stmt->store_result();
				if ($stmt->affected_rows > 0) {
					$lobbyId = $stmt->insert_id;
				}
				$stmt->close();
				return $lobbyId;
			} 
		} catch (Exception $e) {
			
		}
	}

	/**
	 * Moves user to different lobby
	 */
	function moveUserToLobby($username, $lobbyId) {
		try {
			if ($stmt = $this->connection->prepare("UPDATE gameUser SET lobbyId = ? WHERE username = ?")) {
				$stmt->bind_param("is", $lobbyId, $username);
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
	
	// Deletes empty lobbies
	function removeEmptyLobby() {
		$lobbyIds;
		try {
			if ($stmt = $this->connection->prepare("SELECT l.id FROM lobby AS l LEFT OUTER JOIN gameUser as g 
					ON g.lobbyId = l.id WHERE l.id != 0 GROUP BY l.id HAVING COUNT(g.username) = 0")) {
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($result);
				if ($stmt->num_rows > 0) {
					while ($stmt->fetch()) {
						$lobbyIds[] = $result;
					}
				} else {
					return;
				}
				$stmt->close();
				$totalIds = count($lobbyIds); 
				if ($totalIds > 0) {
					$lobbyIdsString = "";
					foreach($lobbyIds as $index => $lobbyId) {
						if ($index + 1 == $totalIds) {
							$lobbyIdsString .= $lobbyId;
						} else {
							$lobbyIdsString .= $lobbyId . ",";
						}
						
					}
					if ($lobbyIdsString == "") {
						return;
					}
					if ($stmt = $this->connection->prepare("DELETE lobby FROM lobby WHERE id IN (?)")) {
						$stmt->bind_param("s", $lobbyIdsString);
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
				}
			} 
		} catch (Exception $e) {

		}
	}

	// Gets user's lobby
	function getUserLobby($username) {
		$lobbyId;
		try {
			if ($stmt = $this->connection->prepare("SELECT lobbyId FROM gameUser WHERE username = ?")) {
				$stmt->bind_param("s", $username);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($result);
				if ($stmt->num_rows > 0) {
					while ($stmt->fetch()) {
						$lobbyId = $result;
					}
				}
				$stmt->close();
				return $lobbyId;
			} 
		} catch (Exception $e) {
			
		}
	}

	// Check if user has been challenged
	function checkUserChallenged($username1, $username2) {
		try {
			if ($stmt = $this->connection->prepare("SELECT * FROM challenge WHERE (gameUser1Id = ? AND gameUser2Id = ?) OR (gameUser1Id = ? AND gameUser2Id = ?)")) {
				$stmt->bind_param("ssss", $username1, $username2, $username2, $username1);
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

	// Creates challenge with 2 users with the user1 already accepted
	function createChallenge($username1, $username2) {
		$timestamp = date("Y-m-d H:i:s");
		try {
			if ($stmt = $this->connection->prepare("INSERT INTO challenge (gameUser1Id, gameUser2Id, gameUser1Accepted, timestamp)
					VALUES (?, ?, 1, ?)")) {
				$stmt->bind_param("sss", $username1, $username2, $timestamp);
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

	// Check challenge status
	function getChallengeStatus($username) {
		$data = array();
		try {
			if ($stmt = $this->connection->prepare("SELECT * FROM challenge WHERE gameUser1Id = ? OR gameUser2Id = ?")) {
				$stmt->bind_param("ss", $username, $username);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($id, $gameUser1Id, $gameUser2Id, $gameUser1Accepted, $gameUser2Accepted, $timestamp);
				if ($stmt->num_rows > 0) {
					while ($stmt->fetch()) {
						$data[] = array(
							"gameUser1Id"=>$gameUser1Id,
							"gameUser2Id"=>$gameUser2Id,
							"gameUser1Accepted"=>$gameUser1Accepted,
							"gameUser2Accepted"=>$gameUser2Accepted,
							"timestamp"=>$timestamp
						);
					}
				}
				$stmt->close();
			}
			return $data;
		} catch (Exception $e) {
			
		}
	}

	// Clear challenges with offline users
	function clearChallengesWithOfflineUser() {
		try {
			if ($stmt = $this->connection->prepare("DELETE c FROM challenge as c LEFT OUTER JOIN gameUser as g 
					ON (g.username = c.gameUser1Id OR g.username = c.gameUser2Id) WHERE g.isOnline = 0")) {
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

	// Clears challenges over 2 minutes long
	function clearOldChallenges() {
		// Clears challenges over 1 min
		try {
			if ($stmt = $this->connection->prepare("DELETE challenge FROM challenge WHERE timestamp < (NOW() - INTERVAL 2 MINUTE)")) {
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

	// Remove one challenge with 2 users
	function clearOneChallenge($username1, $username2) {
		// Clears challenge that user declined
		try {
			if ($stmt = $this->connection->prepare("DELETE challenge FROM challenge WHERE (gameUser1Id = ? AND gameUser2Id = ?) OR (gameUser2Id = ? AND gameUser1Id = ?)")) {
				$stmt->bind_param("ssss", $username1, $username2, $username1, $username2);
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

	// Clear all challenges with user
	function clearAllChallenges($username) {
		try {
			if ($stmt = $this->connection->prepare("DELETE challenge FROM challenge WHERE gameUser1Id = ? OR gameUser2Id = ?")) {
				$stmt->bind_param("ss", $username, $username);
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
}