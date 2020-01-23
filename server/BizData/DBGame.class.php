<?php

if (session_status() == PHP_SESSION_NONE) {
    // return;
}

require_once(__DIR__.'/meekrodb.2.3.class.php');

class DBGame {
	private $connection;
	private $mdb;

    /**
	 * Constructor for database
	 */
	function __construct() {
		$this->connection = new mysqli($_SERVER['DB_SERVER'], $_SERVER['DB_USER'], $_SERVER['DB_PASSWORD'], $_SERVER['DB'], $_SERVER['DB_PORT']);

		if ($this->connection->connect_error) {
			echo "Connection failed: ".mysqli_connect_error();
			die();
		}

		$this->mdb = new MeekroDB($_SERVER['DB_SERVER'], $_SERVER['DB_USER'], $_SERVER['DB_PASSWORD'], $_SERVER['DB'], $_SERVER['DB_PORT']);
    }

	// Check if user is in game
	function isInGame($username) {
		$results = $this->mdb->query("SELECT * FROM game WHERE gameUser1 = %s OR gameUser2 = %s", $username, $username);
		if (count($results) > 0) {
			return true;
		} else {
			return false;
		}
	}

	// Get current colors
	function getCurrentColors() {
		return $this->mdb->query("SELECT id, colorValue FROM color");
	}

	// Add blocks to game
	function addBlocks($blocks, $gameId) {
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

		$this->mdb->query($sql);
	}

	// Get game data
	function getGameData($username) {
		$gameData = $this->mdb->queryFirstRow("SELECT g.id, g.gameUserWon, g.gameUserTurn, g.gameUser1, g.gameUser2, g.width, g.height, g.botGame as isBot, COUNT(b.id) as totalBlocks  
			FROM game as g LEFT OUTER JOIN gameBlock as b ON b.gameId = g.id
			WHERE g.gameUser1 = %s OR g.gameUser2 = %s", $username, $username);

		// Get user 1 color and score
		$user1Results = $this->mdb->queryFirstRow("SELECT COUNT(b.id) as count, b.colorId FROM gameBlock as b 
			WHERE b.gameId = %i AND b.gameUserId = %s", $gameData["id"], $gameData["gameUser1"]);
		$gameData["gameUser1Score"] = $user1Results["count"];
		$gameData["gameUser1Color"] = $user1Results["colorId"];

		// Get user 2 color and score
		$user2Results = $this->mdb->queryFirstRow("SELECT COUNT(b.id) as count, b.colorId FROM gameBlock as b 
			WHERE b.gameId = %i AND b.gameUserId = %s", $gameData["id"], $gameData["gameUser2"]);
		$gameData["gameUser2Score"] = $user2Results["count"];
		$gameData["gameUser2Color"] = $user2Results["colorId"];

		// Get blocks
		$gameData["blocks"] = $this->mdb->query("SELECT b.x, b.y, b.id, b.gameUserId as gameUser, b.colorId, c.colorValue FROM gameBlock as b 
			LEFT OUTER JOIN color as c ON c.id = b.colorId WHERE b.gameId = %i", $gameData["id"]);

		return $gameData;
	}

	// Change turn
	function changeTurn($gameId, $username) {
		$this->mdb->query("UPDATE game SET gameUserTurn = %s WHERE id = %i", $username, $gameId);
	}

	// Updates owner of block
	function updateBlockOwner($blockId, $username) {
		$this->mdb->query("UPDATE gameBlock SET gameUserId = %s WHERE id = %i", $username, $blockId);
	}

	function updateBlocksOwner($blockIds, $username) {
        if (count($blockIds) > 0) {
            $this->mdb->query("UPDATE gameBlock SET gameUserId = %s WHERE id IN %li", $username, $blockIds);
        }
	}

	// Change color of blocks
	function changeColor($colorId, $username, $gameId) {
		$this->mdb->query("UPDATE gameBlock SET colorId = %i WHERE gameUserId = %s AND gameId = %i", $colorId, $username, $gameId);
	}

	// Insert new game data
	function newGame($lobbyId, $width, $height, $gameUser1, $gameUser2, $gameUserTurn, $isBot) {
		$this->mdb->query("INSERT INTO game (lobbyId, width, height, gameUser1, gameUser2, gameUserTurn, botGame) 
			VALUES (%i,%i,%i,%s,%s,%s,%i)", $lobbyId, $width, $height, $gameUser1, $gameUser2, $gameUserTurn, $isBot);
		return $this->mdb->insertId();
	}

	// Get users in lobby
	function getLobbyUsers($username) {
		return $this->mdb->query("SELECT g2.username FROM gameUser as g1 LEFT OUTER JOIN gameUser as g2 
			ON g1.lobbyId = g2.lobbyId WHERE g1.username = %s", $username);
	}

	// Get lobby id of user
	function getUserLobby($username) {
		return $this->mdb->queryOneField("lobbyId", "SELECT lobbyId FROM gameUser WHERE username = %s", $username);
	}

	// Set winner of game
	function setWinner($gameId, $winner) {
		$this->mdb->query("UPDATE game SET gameUserWon = %s WHERE id = %i", $winner, $gameId);
	}

	// Deletes games with user
	function deleteOldGames($username) {
		$this->mdb->query("DELETE FROM game WHERE gameUser1 = %s OR gameUser2 = %s", $username, $username);
	}
}