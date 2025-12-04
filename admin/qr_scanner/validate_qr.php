<?php
/**
 * Validate QR Code API Endpoint
 */
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/qr_handler.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Check permission
if (!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['role'], [1, 2])) {
    echo json_encode(['valid' => false, 'message' => 'Unauthorized']);
    exit;
}

$qrData = $_POST['qr_data'] ?? $_GET['qr_data'] ?? '';

if (empty($qrData)) {
    echo json_encode(['valid' => false, 'message' => 'No QR data provided']);
    exit;
}

$qrHandler = new QRCodeHandler($conn);
$result = $qrHandler->validateQRCode($qrData);

echo json_encode($result);
?>

