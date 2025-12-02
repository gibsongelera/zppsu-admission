<?php
/**
 * ZPPSU Admission System - Detailed SMS Test & Diagnostics
 * Use this to troubleshoot SMS delivery issues
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/api_config.php';
require_once __DIR__ . '/inc/sms_service.php';

// Test phone number - CHANGE THIS TO YOUR PHONE NUMBER
$testPhone = '+639971545203'; // Your phone number

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Diagnostics - ZPPSU</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .card { margin-bottom: 20px; }
        .config-item { padding: 10px; border-bottom: 1px solid #eee; }
        .config-label { font-weight: bold; color: #555; }
        .config-value { color: #000; font-family: monospace; }
        .test-result { padding: 15px; border-radius: 5px; margin-top: 10px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4"><i class="fas fa-mobile-alt"></i> SMS Gateway Diagnostics</h1>
        
        <!-- API Configuration -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cog"></i> API Configuration</h5>
            </div>
            <div class="card-body">
                <div class="config-item">
                    <span class="config-label">SMS Gateway:</span>
                    <span class="config-value">TextBee API</span>
                </div>
                <div class="config-item">
                    <span class="config-label">Base URL:</span>
                    <span class="config-value"><?php echo ApiConfig::SMS_BASE_URL; ?></span>
                </div>
                <div class="config-item">
                    <span class="config-label">Device ID:</span>
                    <span class="config-value"><?php echo ApiConfig::SMS_DEVICE_ID; ?></span>
                </div>
                <div class="config-item">
                    <span class="config-label">API Key:</span>
                    <span class="config-value"><?php echo substr(ApiConfig::SMS_API_KEY, 0, 10) . '...' . substr(ApiConfig::SMS_API_KEY, -10); ?></span>
                </div>
                <div class="config-item">
                    <span class="config-label">Full Endpoint:</span>
                    <span class="config-value"><?php echo ApiConfig::getSmsUrl(); ?></span>
                </div>
                <div class="config-item">
                    <span class="config-label">Sender Name:</span>
                    <span class="config-value"><?php echo ApiConfig::SMS_SENDER_NAME; ?></span>
                </div>
            </div>
        </div>

        <!-- Device Status Check -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-heartbeat"></i> Device Status Check</h5>
            </div>
            <div class="card-body">
                <?php
                // Check device status
                $deviceCheckUrl = ApiConfig::SMS_BASE_URL . ApiConfig::SMS_DEVICE_ID;
                
                $ch = curl_init($deviceCheckUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: ' . ApiConfig::SMS_API_KEY]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $deviceResponse = curl_exec($ch);
                $deviceHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $deviceData = json_decode($deviceResponse, true);
                ?>
                
                <div class="config-item">
                    <span class="config-label">HTTP Status:</span>
                    <span class="config-value <?php echo $deviceHttpCode == 200 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $deviceHttpCode; ?> <?php echo $deviceHttpCode == 200 ? '✓' : '✗'; ?>
                    </span>
                </div>
                
                <?php if ($deviceData): ?>
                    <div class="config-item">
                        <span class="config-label">Device Status:</span>
                        <span class="config-value">
                            <?php 
                            $isOnline = isset($deviceData['data']['isOnline']) ? $deviceData['data']['isOnline'] : false;
                            echo $isOnline ? '<span class="badge badge-success">ONLINE</span>' : '<span class="badge badge-danger">OFFLINE</span>';
                            ?>
                        </span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Device Name:</span>
                        <span class="config-value"><?php echo $deviceData['data']['name'] ?? 'N/A'; ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Phone Number:</span>
                        <span class="config-value"><?php echo $deviceData['data']['phoneNumber'] ?? 'N/A'; ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <button class="btn btn-info btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync"></i> Refresh Status
                    </button>
                </div>
                
                <div class="mt-3">
                    <strong>⚠️ Common Issues:</strong>
                    <ul class="mt-2">
                        <li><strong>Device OFFLINE</strong> - Your phone/gateway device is not connected to the internet</li>
                        <li><strong>Not receiving SMS</strong> - Device might be in airplane mode or has no signal</li>
                        <li><strong>Queue processing delay</strong> - SMS might take 1-2 minutes to send</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Phone Number Validation -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-phone"></i> Phone Number Validation</h5>
            </div>
            <div class="card-body">
                <div class="config-item">
                    <span class="config-label">Test Phone:</span>
                    <span class="config-value"><?php echo $testPhone; ?></span>
                </div>
                <div class="config-item">
                    <span class="config-label">Validated Format:</span>
                    <span class="config-value">
                        <?php 
                        $validated = ApiConfig::validatePhoneNumber($testPhone);
                        echo $validated ? '<span class="text-success">' . $validated . ' ✓</span>' : '<span class="text-danger">Invalid format ✗</span>';
                        ?>
                    </span>
                </div>
                <div class="config-item">
                    <span class="config-label">Display Format:</span>
                    <span class="config-value"><?php echo ApiConfig::formatPhoneForDisplay($testPhone); ?></span>
                </div>
            </div>
        </div>

        <!-- Send Test SMS -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Send Test SMS</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Phone Number:</label>
                        <input type="text" class="form-control" name="test_phone" value="<?php echo $testPhone; ?>" required>
                        <small class="form-text text-muted">Format: +639XXXXXXXXX</small>
                    </div>
                    <div class="form-group">
                        <label>Test Message:</label>
                        <textarea class="form-control" name="test_message" rows="3" required>Test SMS from ZPPSU Admission System - <?php echo date('Y-m-d H:i:s'); ?></textarea>
                    </div>
                    <button type="submit" name="send_test" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Send Test SMS
                    </button>
                </form>
                
                <?php
                if (isset($_POST['send_test'])) {
                    $phone = $_POST['test_phone'];
                    $message = $_POST['test_message'];
                    
                    $smsService = new SmsService($conn);
                    $result = $smsService->sendSms($phone, $message);
                    
                    if ($result['success']) {
                        echo '<div class="test-result success mt-3">';
                        echo '<strong><i class="fas fa-check-circle"></i> SMS Request Sent Successfully!</strong><br>';
                        echo 'To: ' . htmlspecialchars($result['phone']) . '<br>';
                        echo 'Formatted: ' . htmlspecialchars($result['formatted_phone']) . '<br>';
                        
                        if (isset($result['batch_id'])) {
                            echo 'Batch ID: ' . htmlspecialchars($result['batch_id']) . '<br>';
                        }
                        
                        echo '<hr>';
                        echo '<strong>⏱️ Wait Time:</strong> SMS should arrive within 1-2 minutes<br>';
                        echo '<strong>📱 Check Your Phone:</strong> If you don\'t receive the SMS, check:<br>';
                        echo '<ul>';
                        echo '<li>Device is online (check status above)</li>';
                        echo '<li>Phone has mobile signal</li>';
                        echo '<li>Number is correct: ' . htmlspecialchars($phone) . '</li>';
                        echo '<li>Not blocked by mobile carrier</li>';
                        echo '</ul>';
                        
                        echo '<details class="mt-2">';
                        echo '<summary>View Raw Response</summary>';
                        echo '<pre>' . htmlspecialchars(print_r($result, true)) . '</pre>';
                        echo '</details>';
                        echo '</div>';
                    } else {
                        echo '<div class="test-result error mt-3">';
                        echo '<strong><i class="fas fa-times-circle"></i> SMS Failed to Send</strong><br>';
                        echo 'Error: ' . htmlspecialchars($result['error']) . '<br>';
                        echo '<details class="mt-2">';
                        echo '<summary>View Error Details</summary>';
                        echo '<pre>' . htmlspecialchars(print_r($result, true)) . '</pre>';
                        echo '</details>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Troubleshooting Guide -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-tools"></i> Troubleshooting Guide</h5>
            </div>
            <div class="card-body">
                <h6><strong>If SMS shows "Success" but you don't receive it:</strong></h6>
                <ol>
                    <li><strong>Check Device Status Above</strong> - Must show "ONLINE"</li>
                    <li><strong>Check Your Phone:</strong>
                        <ul>
                            <li>Has mobile signal (not in airplane mode)</li>
                            <li>Not blocking unknown numbers</li>
                            <li>SMS inbox not full</li>
                        </ul>
                    </li>
                    <li><strong>Check Gateway Device:</strong>
                        <ul>
                            <li>Phone/device connected to internet</li>
                            <li>TextBee app is running and logged in</li>
                            <li>Device has battery/power</li>
                            <li>No Android battery optimization blocking the app</li>
                        </ul>
                    </li>
                    <li><strong>Wait 1-2 Minutes</strong> - SMS goes to queue first</li>
                    <li><strong>Try Manual Send</strong> - Send SMS directly from gateway device to verify it works</li>
                </ol>
                
                <hr>
                
                <h6><strong>TextBee Gateway Setup:</strong></h6>
                <ol>
                    <li>Download TextBee app on Android phone</li>
                    <li>Login with your account</li>
                    <li>Make sure phone is always connected to internet</li>
                    <li>Disable battery optimization for TextBee app</li>
                    <li>Keep the app running in background</li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <strong><i class="fas fa-info-circle"></i> Note:</strong> 
                    The API returning "success" means your request was accepted and added to the queue.
                    Actual delivery depends on the gateway device being online and connected.
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>

