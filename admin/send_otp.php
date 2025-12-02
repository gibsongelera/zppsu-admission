
<?php
// Suppress all errors/warnings/notices
error_reporting(0);
ini_set('display_errors', 0);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';
require_once 'inc/sms_service.php';
header('Content-Type: application/json');

$phone = $_POST['phone'] ?? '';
if (!$phone) {
    echo json_encode(['status' => 'error', 'msg' => 'Phone number required.']);
    exit;
}

// Validate phone number format
$validatedPhone = ApiConfig::validatePhoneNumber($phone);
if (!$validatedPhone) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid phone number format. Use +639XXXXXXXXX.']);
    exit;
}

$otp = rand(100000, 999999);
$_SESSION['register_otp'] = $otp;
$_SESSION['register_phone'] = $validatedPhone;

// Send OTP using SMS service
$smsService = new SmsService();
$result = $smsService->sendOtp($validatedPhone, $otp);

if ($result['success']) {
    echo json_encode([
        'status' => 'success', 
        'msg' => 'succesfully send the otp', 
        'otp' => $otp,
        'formatted_phone' => $result['formatted_phone']
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'msg' => 'Failed to send OTP. ' . $result['error']
    ]);
}
