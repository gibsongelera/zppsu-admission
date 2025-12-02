<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../inc/db_connect.php';
require_once '../inc/db_handler.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $db = new DatabaseHandler($conn);
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($id === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid ID provided'
        ]);
        exit();
    }

    $result = $db->deleteRecord($id);
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Record deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete record'
        ]);
    }
    exit();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit();
}
?>
