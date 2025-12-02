<?php
// Test SMS functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once 'inc/sms_service.php';

// Test phone number (replace with your test number)
$test_phone = '+639123456789'; // Replace with actual test number

// Test SMS service
$smsService = new SmsService();
$result = $smsService->testSms($test_phone);

// Add additional test information
$result['test_phone'] = $test_phone;
$result['test_time'] = date('Y-m-d H:i:s');
$result['api_config'] = ApiConfig::getSmsConfig();

echo json_encode($result, JSON_PRETTY_PRINT);
?>
