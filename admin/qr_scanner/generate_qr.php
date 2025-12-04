<?php
/**
 * Generate QR Code API Endpoint
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

$scheduleId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($scheduleId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
    exit;
}

$qrHandler = new QRCodeHandler($conn);
$result = $qrHandler->generateQRCode($scheduleId);

echo json_encode($result);
?>

