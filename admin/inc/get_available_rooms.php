<?php
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

$roomHandler = new RoomHandler($conn);
$availableRooms = $roomHandler->getAvailableRooms($date, $timeSlot, $campus);

echo json_encode([
    'success' => true,
    'rooms' => $availableRooms
]);
?>

