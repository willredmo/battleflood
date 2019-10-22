<?php

if (session_status() == PHP_SESSION_NONE) {
    return;
}

class DBGame {
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

	// Check if user is in game
	function isInGame($username) {
		try {
			if ($stmt = $this->connection->prepare("SELECT * FROM game WHERE gameUser1 = ? OR gameUser2 = ?")) {
				$stmt->bind_param("ss", $username, $username);
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

	// Get current colors
	function getCurrentColors() {
		$data = array();
		try {
			if ($stmt = $this->connection->prepare("SELECT id, colorValue FROM color")) {
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($id, $colorValue);
				$affected_rows = $stmt->affected_rows;
				if ($stmt->num_rows > 0) {
					while ($stmt->fetch()) {
						$color = [];
						$color["id"] = $id;
						$color["colorValue"] = $colorValue;
						$data[] = $color;
					}
				}
				$stmt->close();
			} 
			return $data;
		} catch (Exception $e) {
			
		}
	}

	// Add blocks to game
	function addBlocks($blocks, $gameId) {
		$gameId;
		try {
			$sql = "INSERT INTO gameBlock (x, y, colorId, gameId, gameUserId) VALUES ";

			$query_parts = array();
			$height = count($blocks);
			for ($y = 0; $y < $height; $y++) {
				for ($x = 0; $x < 	count($blocks[$y]); $x++) {
					$block = $blocks[$y][$x];
					$color = $block["colorId"];
					$user = $block["gameUserId"];
					if ($user == NULL) {
						$query_parts[] = "($x,$y,$color,$gameId,'')";
					} else {
						$query_parts[] = "($x,$y,$color,$gameId,'$user')";
					}
				}
			}

			$sql .= implode(",", $query_parts);
			$sql .= ";";
			
			$this->connection->query($sql);
		} catch (Exception $e) {
			
		}
	}

	// Get game data
	function getGameData($username) {
		$data = [];
		try {
			if ($stmt = $this->connection->prepare("SELECT g.id, g.gameUserWon, g.gameUserTurn, g.gameUser1, g.gameUser2, g.width, g.height, g.botGame, b.x, b.y, b.colorId, c.colorValue, b.id, b.gameUserId FROM game as g 
					LEFT OUTER JOIN gameBlock as b ON g.id = b.gameId
					LEFT OUTER JOIN color as c ON c.id = b.colorId
					WHERE g.gameUser1 = ? OR g.gameUser2 = ?")) {
				$stmt->bind_param("ss", $username, $username);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($gameId, $gameUserWon, $gameUserTurn, $gameUser1, $gameUser2, $width, $height, $isBot, $x, $y, $colorId, $colorValue, $blockId, $gameUserId);
				if ($stmt->num_rows > 0) {
					$data["blocks"] = [];
					while ($stmt->fetch()) {
						if (!isset($data["id"])) {
							$data["id"] = $gameId;
						}
						if (!isset($data["gameUserWon"])) {
							$data["gameUserWon"] = $gameUserWon;
						}
						if (!isset($data["gameUserTurn"])) {
							$data["gameUserTurn"] = $gameUserTurn;
						}
						if (!isset($data["gameUser1"])) {
							$data["gameUser1"] = $gameUser1;
						}
						if (!isset($data["gameUser2"])) {
							$data["gameUser2"] = $gameUser2;
						}
						if (!isset($data["width"])) {
							$data["width"] = $width;
						}
						if (!isset($data["height"])) {
							$data["height"] = $height;
						}
						if (!isset($data["isBot"])) {
							if ($isBot == 0) {
								$data["isBot"] = false;
							} else if ($isBot == 1) {
								$data["isBot"] = true;
							}
						}

						$block = [];
						$block["x"] = $x;
						$block["y"] = $y;
						$block["colorId"] = $colorId;
						$block["colorValue"] = $colorValue;
						$block["id"] = $blockId;
						$block["gameUser"] = $gameUserId;
						$data["blocks"][] = $block;
					}
				}
				$stmt->close();
				return $data;
			} 
		} catch (Exception $e) {

		}
	}

	// Change turn
	function changeTurn($gameId, $username) {
		try {
			if ($stmt = $this->connection->prepare("UPDATE game SET gameUserTurn = ? WHERE id = ?")) {
				$stmt->bind_param("si", $username, $gameId);
				$stmt->execute();
				$stmt->store_result();
				$stmt->close();
			} 
		} catch (Exception $e) {
			print_r($e);
		}
	}

	// Updates owner of block
	function updateBlockOwner($blockId, $username) {
		try {
			if ($stmt = $this->connection->prepare("UPDATE gameBlock SET gameUserId = ? WHERE id = ?")) {
				$stmt->bind_param("si", $username, $blockId);
				$stmt->execute();
				$stmt->store_result();
				$stmt->close();
			} 
		} catch (Exception $e) {
			print_r($e);
		}
	}

	// Change color of blocks
	function changeColor($colorId, $username, $gameId) {
		try {
			if ($stmt = $this->connection->prepare("UPDATE gameBlock SET colorId = ? WHERE gameUserId = ? AND gameId = ?")) {
				$stmt->bind_param("isi", $colorId, $username, $gameId);
				$stmt->execute();
				$stmt->store_result();
				$stmt->close();
			} 
		} catch (Exception $e) {
			 
		}
	}

	// Insert new game data
	function newGame($lobbyId, $width, $height, $gameUser1, $gameUser2, $gameUserTurn, $isBot) {
		$gameId;
		try {
			if ($stmt = $this->connection->prepare("INSERT INTO game (lobbyId, width, height, gameUser1, gameUser2, gameUserTurn, botGame) 
					VALUES (?,?,?,?,?,?,?)")) {
				$stmt->bind_param("iiisssi", $lobbyId, $width, $height, $gameUser1, $gameUser2, $gameUserTurn, $isBot);
				$stmt->execute();
				$stmt->store_result();
				if ($stmt->affected_rows > 0) {
					$gameId = $stmt->insert_id;
				}
				$stmt->close();
				return $gameId;
			} 
		} catch (Exception $e) {
			
		}
	}

	// Get users in lobby
	function getLobbyUsers($username) {
		$data = array();
		try {
			if ($stmt = $this->connection->prepare("SELECT g2.username FROM gameUser as g1 
					LEFT OUTER JOIN gameUser as g2 ON g1.lobbyId = g2.lobbyId WHERE g1.username = ?")) {
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

	// Get lobby id of user
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

	// Set winner of game
	function setWinner($gameId, $winner) {
		try {
			if ($stmt = $this->connection->prepare("UPDATE game SET gameUserWon = ? WHERE id = ?")) {
				$stmt->bind_param("si", $winner, $gameId);
				$stmt->execute();
				$stmt->store_result();
				$stmt->close();
			} 
		} catch (Exception $e) {
			
		}
	}

	// Deletes games with user
	function deleteOldGames($username) {
		try {
			if ($stmt = $this->connection->prepare("DELETE FROM game WHERE gameUser1 = ? OR gameUser2 = ?")) {
				$stmt->bind_param("ss", $username, $username);
				$stmt->execute();
				$stmt->store_result();
				$stmt->close();
			} 
		} catch (Exception $e) {

		}
	}
}