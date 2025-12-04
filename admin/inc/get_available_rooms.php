<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/room_handler.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
$timeSlot = $_GET['time_slot'] ?? '';
$campus = $_GET['campus'] ?? '';

if (empty($date) || empty($timeSlot) || empty($campus)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Check if connection is valid
if (!isset($conn) || $conn === null || !$conn->isConnected()) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $roomHandler = new RoomHandler($conn);
    $availableRooms = $roomHandler->getAvailableRooms($date, $timeSlot, $campus);
    
    echo json_encode([
        'success' => true,
        'rooms' => $availableRooms
    ]);
} catch (Exception $e) {
    error_log("Error in get_available_rooms.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading rooms: ' . $e->getMessage()
    ]);
}
?>

