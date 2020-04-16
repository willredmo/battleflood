<?php

if (session_status() == PHP_SESSION_NONE) {
    return;
}

require_once(__DIR__.'/ServiceUtils.php');
require_once(__DIR__.'/../BizData/DBGame.class.php');

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

class ServiceGame {
    private $db;

    /**
	 * Constructor for service
	 */
	function __construct() {
        // Create connection here
        $this->db = new DBGame();
        $this->currentBoard = [];
        $this->blocksToChange = [];
    }

    // Check if user is in game
    private function isInGame($username) {
        return $this->db->isInGame($username);
    }

    function getBoard() {
        $currentUser = getUsername();
        if (!$this->isInGame($currentUser)) {
            return "";
        }
        $data = $this->db->getGameData($currentUser);
        return $data;
    }
    
    // Creates new game in db
    function newGame() {
        $currentUser = getUsername(); 
        $lobbyId = $this->db->getUserLobby($currentUser);
        if ($lobbyId == 0) {
            return "Cannot create game in main lobby";
        }

        $isBot;
        $gameUserTurn;
        $otherUser;
        $lobbyUsers = $this->db->getLobbyUsers($currentUser);
        // Sometimes does not return correctly
        if (is_array($lobbyUsers[0])) {
            $lobbyUsers[0] = $lobbyUsers[0]["username"];
        }
        if (isset($lobbyUsers[1]) && is_array($lobbyUsers[1])) {
            $lobbyUsers[1] = $lobbyUsers[1]["username"];
        }
        if (count($lobbyUsers) == 2) {
            if ($lobbyUsers[0] != $currentUser) {
                $otherUser = $lobbyUsers[0];
            } else if ($lobbyUsers[1] != $currentUser) {
                $otherUser = $lobbyUsers[1];
            }
            $isBot = 0;
            // Determine who goes first
            $gameUserTurn = (rand(0, 1) == 0 ? $currentUser : $otherUser);
        } else if (count($lobbyUsers) == 1) {
            // Bot game
            $otherUser = "bot";
            $isBot = 1;
            $gameUserTurn = $currentUser;
        } else {
            return "Can only create a game with 2 lobby users";
        }
        

        $width = 20;
        $height = 25;


        // $width = 5;
        // $height = 5;

        $gameId;
        // Delete any old games
        $this->db->deleteOldGames($currentUser);
        $gameId = $this->db->newGame($lobbyId, $width, $height, $currentUser, $otherUser, $gameUserTurn, $isBot);
        $newBoard = $this->generateBoard($gameId, $width, $height, $currentUser, $otherUser);
        $this->db->addBlocks($newBoard, $gameId);

        return "";
    }

    // Generates board
    private function generateBoard($gameId, $width, $height, $user1, $user2) {
        $board = [];
        $colors = $this->getCurrentColors();
        $colorCount = count($colors);

        for ($y = 0; $y < $height; $y++) {
            $board[$y] = [];
            for ($x = 0; $x < $width; $x++) {
                $board[$y][$x] = [];
                $board[$y][$x]["colorId"] = $colors[rand(0, ($colorCount - 1))]["id"];
                $board[$y][$x]["gameUserId"] = NULL;
            }
        }

        // Make sure that starting blocks arent the same color
        while ($board[0][$width - 1]["colorId"] == $board[$height - 1][0]["colorId"]) {
            $board[0][$width - 1]["colorId"] = $colors[rand(0, ($colorCount - 1))]["id"];
        }

        // Make sure adjacent blocks arent same color to starts
        // User1 Top right
        while ($board[0][$width - 1]["colorId"] == $board[0][$width - 2]["colorId"]) {
            $board[0][$width - 2]["colorId"] = $colors[rand(0, ($colorCount - 1))]["id"];
        }
        while ($board[0][$width - 1]["colorId"] == $board[1][$width - 1]["colorId"]) {
            $board[1][$width - 1]["colorId"] = $colors[rand(0, ($colorCount - 1))]["id"];
        }
        // User2 Bottom left
        while ($board[$height - 1][0]["colorId"] == $board[$height - 2][0]["colorId"]) {
            $board[$height - 2][0]["colorId"] = $colors[rand(0, ($colorCount - 1))]["id"];
        }
        while ($board[$height - 1][0]["colorId"] == $board[$height - 1][1]["colorId"]) {
            $board[$height - 1][1]["colorId"] = $colors[rand(0, ($colorCount - 1))]["id"];
        }

        // Set up first blocks owned by users
        $board[0][$width - 1]["gameUserId"] = $user1;
        $board[$height - 1][0]["gameUserId"] = $user2;
        return $board;
    }

    // Gets current colors
    function getCurrentColors() {
        return $this->db->getCurrentColors();
    }

    // Make move in game
    function makeMove($colorId) {
        // $time_start = microtime_float();

        $currentUser = getUsername();
        if (!$this->isInGame($currentUser)) {
            return "Not currently in a game";
        }
        $board = $this->db->getGameData($currentUser);
        $user1 = $board["gameUser1"];
        $this->blocksToChange[$user1] = [];
        $user2 = $board["gameUser2"];
        $this->blocksToChange[$user2] = [];
        $isUser1;
        $user1ColorId = $board["gameUser1Color"];
        $user2ColorId = $board["gameUser2Color"];
        $userTurn = $board["gameUserTurn"];
        if ($board["gameUserWon"] != null) {
            return "Cannot make a move if game is over";
        }


        // Check if user's turn
        if ($userTurn != $currentUser) {
            return "Not your turn";
        }

        if ($user1 == $currentUser) {
            $isUser1 = true;
        } else {
            $isUser1 = false;
        }

        // Map color values to ids
        $colors = $this->getCurrentColors();
        $foundColorId = false;
        foreach($colors as $index => $color) {
            if ($colorId == $color["id"]) {
                $foundColorId = true;
            } 
        }
        if (!$foundColorId) {
            return "Color id does not exist";
        }

        // Determine if color already taken
        if ($colorId == $user1ColorId || $colorId == $user2ColorId) {
            return "Color is currently taken";
        }


        // Change color in database
        $this->db->changeColor($colorId, $currentUser, $board["id"]);
        // Get new board with color changes
        $board = $this->db->getGameData($currentUser);

        // Prepare board for making move
        $this->currentBoard = [];
        for ($y = 0; $y < $board["height"]; $y++) {
            $this->currentBoard[] = [];
        }

        // Recreate board into 2D array
        $blocks = $board["blocks"];
        for ($i = 0; $i < count($blocks); $i++) {
            $block = $blocks[$i];
            $this->currentBoard[$block["y"]][$block["x"]] = $block;
            $this->currentBoard[$block["y"]][$block["x"]]["checked"] = false;
        }
        
        if ($isUser1) {
            // User 1 top right
            $this->checkBoard($board["height"], $board["width"], 0, $board["width"] - 1, $currentUser, $colorId);
            $this->db->changeTurn($board["id"], $user2);
        } else {
            // User 2 bottom left
            $this->checkBoard($board["height"], $board["width"], $board["height"] - 1, 0, $currentUser, $colorId);
            $this->db->changeTurn($board["id"], $user1);
        }
        
        if ($board["isBot"]) {
            $isBotUser1 = ($user1 == "bot");
            $oldBotColor;
            if ($isBotUser1) {
                $oldBotColor = $user1ColorId;
            } else {
                $oldBotColor = $user2ColorId;
            }
            $newBotColor = $colors[0]["id"];
            $colorCount = count($colors);
            while ($newBotColor == $oldBotColor || $newBotColor == $colorId) {
                $newBotColor = $colors[rand(0, ($colorCount - 1))]["id"];
            } 
            // Change color in database
            $this->db->changeColor($newBotColor, "bot", $board["id"]);
            // Get new board with color changes
            $board = $this->db->getGameData($currentUser);
            
            // Prepare board for making move
            $this->currentBoard = [];
            for ($y = 0; $y < $board["height"]; $y++) {
                $this->currentBoard[] = [];
            }

            // Recreate board into 2D array
            $blocks = $board["blocks"];
            for ($i = 0; $i < count($blocks); $i++) {
                $block = $blocks[$i];
                $this->currentBoard[$block["y"]][$block["x"]] = $block;
                $this->currentBoard[$block["y"]][$block["x"]]["checked"] = false;
            }
            
            if ($isBotUser1) {
                $this->checkBoard($board["height"], $board["width"], 0, $board["width"] - 1, "bot", $newBotColor);
                $this->db->changeTurn($board["id"], $user2);
            } else {
                $this->checkBoard($board["height"], $board["width"], $board["height"] - 1, 0, "bot", $newBotColor);
                $this->db->changeTurn($board["id"], $user1);
            }
            
        }

        $this->db->updateBlocksOwner($this->blocksToChange[$user1], $user1);
        $this->db->updateBlocksOwner($this->blocksToChange[$user2], $user2);

        $this->checkWin(); 

        // $time_end = microtime_float();
        // $time = $time_end - $time_start;
        // $info = [];
        // $info["time"] = "Total time: $time";
        // $info["blocks"] = $this->blocksToChange;

        // return $info;
        return "";
    }  

    // Check if user won
    private function checkWin() {
        $board = $this->getBoard();
        if ($board["gameUser1Score"] > $board["totalBlocks"]/2) {
            $this->db->setWinner($board["id"], $board["gameUser1"]);
        } else if ($board["gameUser2Score"] > $board["totalBlocks"]/2) {
            $this->db->setWinner($board["id"], $board["gameUser2"]);
        } else if ($board["gameUser1Score"] == $board["totalBlocks"]/2 && $board["gameUser1Score"] == $board["gameUser2Score"]) {
            $this->db->setWinner($board["id"], "tie");
        }
    }  

    // Check board for changes
    // Iterates over owned or changed blocks until nothing left
    private function checkBoard($height, $width, $y, $x, $user, $color) {
        $blocksToCheck = [];
        $block = [];
        $block["y"] = $y;
        $block["x"] = $x;
        array_push($blocksToCheck, $block);
        while(count($blocksToCheck) > 0) {
            $block = array_pop($blocksToCheck);
            $y = $block["y"];
            $x = $block["x"];
            $this->currentBoard[$y][$x]["checked"] = true;

            // Check top
            if ($this->inBounds($x, $y - 1, $width, $height)) {
                if ($this->checkBlock($y - 1, $x, $color, $user)) {
                    $newBlock = [];
                    $newBlock["y"] = $y - 1;
                    $newBlock["x"] = $x;
                    array_push($blocksToCheck, $newBlock);
                }
            }
            // Check bottom
            if ($this->inBounds($x, $y + 1, $width, $height)) {
                if ($this->checkBlock($y + 1, $x, $color, $user)) {
                    $newBlock = [];
                    $newBlock["y"] = $y + 1;
                    $newBlock["x"] = $x;
                    array_push($blocksToCheck, $newBlock);
                }
            }
            // Check left
            if ($this->inBounds($x - 1, $y, $width, $height)) {
                if ($this->checkBlock($y, $x - 1, $color, $user)) {
                    $newBlock = [];
                    $newBlock["y"] = $y;
                    $newBlock["x"] = $x - 1;
                    array_push($blocksToCheck, $newBlock);
                }
            }
            // Check right
            if ($this->inBounds($x + 1, $y, $width, $height)) {
                if ($this->checkBlock($y, $x + 1, $color, $user)) {
                    $newBlock = [];
                    $newBlock["y"] = $y;
                    $newBlock["x"] = $x + 1;
                    array_push($blocksToCheck, $newBlock);
                }
            }
        } 
    }

    // Checks if block is owned or same color
    private function checkBlock($y, $x, $color, $user) {
        $block = $this->currentBoard[$y][$x];
        if ($block["checked"]) {
            return false;
        } else if ($block["gameUser"] == null && $block["colorId"] == $color) {
            $this->currentBoard[$y][$x]["gameUser"] = $user;
            $this->currentBoard[$y][$x]["colorId"] = $color;
            $changeBlock = [];
            $changeBlock["id"] = $block["id"];
            array_push($this->blocksToChange[$user], $block["id"]);
            // $this->db->updateBlockOwner($block["id"], $user);
            return true;
        } else if ($block["gameUser"] == $user) {
            return true;
        }
        return false;
    }

    // Checks if block is in bounds
    private function inBounds($x, $y, $width, $height) {
        if ($x < 0 || $x >= $width) {
            return false;
        }
        if ($y < 0 || $y >= $height) {
            return false;
        }
        return true;
    } 
}