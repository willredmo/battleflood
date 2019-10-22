<?php

if (session_status() == PHP_SESSION_NONE) {
    return;
}

require_once("BizData/DBChat.class.php");
require_once("Service/ServiceUtils.php");

class ServiceChat {
    private $db;

    /**
	 * Constructor for service
	 */
	function __construct() {
		$this->db = new DBChat();
    }

    /**
     * Closes db connection
     */
    function closeDBConn() {
        $this->db->closeConnection();
    }

    // Sends message
    function sendMessage($text) {
        if ($text == "") {
            return "Empty message";
        } else if (strlen($text) > 200) {
            return "Message longer than 200 characters";
        }   
        $this->db->sendMessage(getUsername(), $text);
        return "";
    }

    // Gets current chat
    function getChat() {
        return $this->db->getChat(getUsername());
    }
}