<?php

if (session_status() == PHP_SESSION_NONE) {
    return;
}

require_once(__DIR__.'/ServiceUtils.php');
require_once(__DIR__.'/../BizData/DBChat.class.php');

class ServiceChat {
    private $db;

    /**
	 * Constructor for service
	 */
	function __construct() {
		$this->db = new DBChat();
    }

    // Sends message
    function sendMessage($text) {
        if (strlen($text) == 0) {
            return "Empty message";
        } else if (strlen($text) > 200) {
            return "Message is longer than 200 characters";
        }   
        // Profanity filter
        $filteredMessage = file_get_contents("https://www.purgomalum.com/service/plain?text=".rawurlencode($text));
        echo "\n\nMessage: $filteredMessage";
        $this->db->sendMessage(getUsername(), $filteredMessage);
        return "";
    }

    // Gets current chat
    function getChat() {
        return $this->db->getChat(getUsername());
    }
}