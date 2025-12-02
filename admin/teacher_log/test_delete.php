<?php
// Test delete functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../inc/db_connect.php';
require_once '../inc/db_handler.php';

// Test the delete functionality
$db = new DatabaseHandler($conn);

// Get a test record (first record)
$testRecord = $db->getAllRecords();
if ($testRecord && $testRecord->num_rows > 0) {
    $record = $testRecord->fetch_assoc();
    $testId = $record['id'];
    
    echo json_encode([
        'test_id' => $testId,
        'test_record' => $record,
        'message' => 'Test record found. You can test delete with ID: ' . $testId,
        'note' => 'This is just a test - no actual deletion performed'
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'message' => 'No records found to test with',
        'note' => 'Add some records first to test the delete functionality'
    ], JSON_PRETTY_PRINT);
}
?>
