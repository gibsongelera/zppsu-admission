<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$initPath = realpath(__DIR__ . '/../../initialize.php');
if (!$initPath) {
    die("Error: Could not find initialize.php");
}
require_once $initPath;

try {
    // Create connection without database selection
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }

    // Select the database
    if (!$conn->select_db(DB_NAME)) {
        throw new Exception("Error selecting database: " . $conn->error);
    }

    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/schedule_admission.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found at: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Error reading SQL file: " . $sqlFile);
    }
    
    echo "Successfully read SQL file<br>";

    // Execute each query separately
    if ($conn->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
            // Prepare next result set
        } while ($conn->more_results() && $conn->next_result());
    }

    if ($conn->error) {
        throw new Exception("Error executing SQL: " . $conn->error);
    }

    echo "Database and table setup completed successfully!";

} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
}

// Close the connection
if (isset($conn)) {
    $conn->close();
}
?>
