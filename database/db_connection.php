<?php
// Check if running from the command line (CLI) or web server
if (PHP_SAPI === 'cli') {
    // Running from command line (CLI), assume it's local development
    $servername = "localhost";
    $username = "root";
    $password = "Arslan@321";
    $database = "slimreset";
} else {
    // Running on a web server, check if it's localhost or live server
    if ($_SERVER['SERVER_NAME'] === 'localhost') {
        // Local environment
        $servername = "localhost";
        $username = "root";
        $password = "Arslan@321";
        $database = "slimreset";
    } else {
        // Live environment
        $servername = "localhost";
        $username = "root";
        $password = "Arslan@321";
        $database = "slimreset";
    }
}

// Create a connection
$mysqli = new mysqli($servername, $username, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

global $mysqli;
