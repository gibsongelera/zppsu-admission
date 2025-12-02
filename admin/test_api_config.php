<?php
// Test API Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once 'inc/api_config.php';
require_once 'inc/sms_service.php';

$tests = [];

// Test 1: API Configuration
$tests['api_config'] = [
    'test' => 'API Configuration',
    'result' => ApiConfig::getSmsConfig(),
    'status' => 'success'
];

// Test 2: Phone Number Validation
$testPhones = [
    '09123456789',
    '9123456789',
    '+639123456789',
    '0912345678', // Invalid (too short)
    '123456789'   // Invalid (wrong format)
];

$tests['phone_validation'] = [
    'test' => 'Phone Number Validation',
    'results' => []
];

foreach ($testPhones as $phone) {
    $validated = ApiConfig::validatePhoneNumber($phone);
    $formatted = $validated ? ApiConfig::formatPhoneForDisplay($validated) : 'Invalid';
    
    $tests['phone_validation']['results'][] = [
        'input' => $phone,
        'validated' => $validated,
        'formatted' => $formatted,
        'is_valid' => $validated !== false
    ];
}

// Test 3: SMS Service Initialization
try {
    $smsService = new SmsService();
    $tests['sms_service'] = [
        'test' => 'SMS Service Initialization',
        'result' => 'SMS Service initialized successfully',
        'status' => 'success'
    ];
} catch (Exception $e) {
    $tests['sms_service'] = [
        'test' => 'SMS Service Initialization',
        'result' => 'Error: ' . $e->getMessage(),
        'status' => 'error'
    ];
}

// Test 4: API URLs and Headers
$tests['api_endpoints'] = [
    'test' => 'API Endpoints',
    'sms_url' => ApiConfig::getSmsUrl(),
    'headers' => ApiConfig::getSmsHeaders(),
    'status' => 'success'
];

// Summary
$totalTests = count($tests);
$successfulTests = 0;
foreach ($tests as $test) {
    if (isset($test['status']) && $test['status'] === 'success') {
        $successfulTests++;
    }
}

$result = [
    'summary' => [
        'total_tests' => $totalTests,
        'successful_tests' => $successfulTests,
        'failed_tests' => $totalTests - $successfulTests,
        'success_rate' => round(($successfulTests / $totalTests) * 100, 2) . '%'
    ],
    'tests' => $tests,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($result, JSON_PRETTY_PRINT);
?>
