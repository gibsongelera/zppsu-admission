<?php
/**
 * Get QR Code API Endpoint
 */
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/qr_handler.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Check permission
if (!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['role'], [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$scheduleId = (int)($_GET['id'] ?? 0);

if ($scheduleId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
    exit;
}

$qrHandler = new QRCodeHandler($conn);
$result = $qrHandler->getQRCode($scheduleId);

// Add student name and reference
if ($result['success']) {
    $stmt = $conn->prepare("SELECT surname, given_name, reference_number FROM schedule_admission WHERE id = ?");
    $stmt->bind_param("i", $scheduleId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        $result['student_name'] = $row['surname'] . ', ' . $row['given_name'];
        $result['reference_number'] = $row['reference_number'];
    }
}

echo json_encode($result);
?>

