<?php

/**
 * Validates form data
 * @param  array $form - form data to validate 
 * @return string      - errors during validation or "" if no errors
 */
function validateCreateUser($username, $password) {
    // Valitdate
    $errors = "";
    if (strlen($username) < 6) {
        $errors .= "<p>Username must be more than 6 characters.</p>"; 
    } else if (strlen($username) > 30) {
        $errors .= "<p>Username must be less than 30 characters.</p>";
    } else if (strpos($username, ' ') != null) {
        $errors .= "<p>No spaces allowed in username.</p>";
    }
    
    if (strlen($password) < 6) {
        $errors .= "<p>Password must be more than 6 characters.</p>"; 
    } else if (strlen($password) > 30) {
        $errors .= "<p>Password must be less than 30 characters.</p>";
    }

    return $errors;	
}

/**
 * Sanatizes array data
 * @param  array $array - array data to sanitize
 * @return array        - sanitized array data
 */
function sanitize($array) {
    // Sanatize
    foreach ($array as $key => $value) {
        $value = strip_tags($value);
        // $value = htmlentities($value);
        $array[$key] = $value;
    }
    return $array;
}

/**
 * @return Boolean - If currently logged in
 */
function loggedIn() {
    if (isset($_SESSION["gameLoggedIn"]) && $_SESSION["gameLoggedIn"]) {
        return true;
    } else {
        return false;
    }
}

/**
 * @return String - Username of currently logged in user
 */
function getUsername() {
    if (loggedIn()) {
        return $_SESSION['username'];
    } else {
        return null;
    }
}

// Converts true to false of mysql database
function convertIntToBoolean($value) {
    // 0 == false
    // 1 == true
    if ($value == 1) {
        return true;
    } else {
        return false;
    }
}