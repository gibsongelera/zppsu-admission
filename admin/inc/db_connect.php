<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the absolute path to initialize.php
$initializePath = realpath(__DIR__ . '/../../initialize.php');

if (!$initializePath || !file_exists($initializePath)) {
    die('Error: initialize.php not found at ' . __DIR__ . '/../../initialize.php');
}

require_once $initializePath;

try {
    $host = DB_SERVER;
    $user = DB_USERNAME;
    $pass = DB_PASSWORD;
    $db = DB_NAME;

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Set the character set to utf8mb4
    if (!$conn->set_charset('utf8mb4')) {
        throw new Exception('Error setting character set: ' . $conn->error);
    }
} catch (Exception $e) {
    die('Database connection error: ' . $e->getMessage());
}
?>
