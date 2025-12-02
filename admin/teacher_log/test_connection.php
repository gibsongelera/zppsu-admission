<?php
$dbConnectPath = realpath(__DIR__ . '/../inc/db_connect.php');
if (!$dbConnectPath) {
    die("Error: Could not find db_connect.php");
}
require_once $dbConnectPath;

try {
    // Test database connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Test if table exists
    $result = $conn->query("SHOW TABLES LIKE 'schedule_admission'");
    if (!$result || $result->num_rows === 0) {
        throw new Exception("Table 'schedule_admission' does not exist");
    }

    // Test if we can read from the table
    $result = $conn->query("SELECT * FROM schedule_admission LIMIT 1");
    if ($result === false) {
        throw new Exception("Error querying table: " . $conn->error);
    }

    // Get table structure
    $result = $conn->query("DESCRIBE schedule_admission");
    if ($result === false) {
        throw new Exception("Error getting table structure: " . $conn->error);
    }

    echo "Database connection successful!\n";
    echo "Table 'schedule_admission' exists and is accessible.\n";
    echo "\nTable structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }

} catch (Exception $e) {
    die("Test failed: " . $e->getMessage());
}
?>
