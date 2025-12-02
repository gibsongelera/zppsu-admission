<?php
session_start();
require_once('../config.php');
require_once('inc/db_connect.php');
require_once('inc/sms_service.php');

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'send_code') {
    sendResetCode();
} elseif ($action === 'reset_password') {
    resetPassword();
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid action']);
}

function sendResetCode() {
    global $conn;
    
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    if (empty($phone)) {
        echo json_encode(['status' => 'error', 'msg' => 'Phone number is required']);
        return;
    }
    
    // Check if user exists with this phone number
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE phone = ? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'msg' => 'No account found with this phone number']);
        $stmt->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in session with expiry (5 minutes)
    $_SESSION['password_reset'] = [
        'phone' => $phone,
        'otp' => $otp,
        'expiry' => time() + 300, // 5 minutes
        'user_id' => $user['id']
    ];
    
    // Send SMS
    try {
        $smsService = new SmsService();
        $message = "Your ZPPSU password reset code is: $otp. Valid for 5 minutes. Do not share this code with anyone.";
        
        $result = $smsService->sendSMS($phone, $message);
        
        if ($result['success']) {
            echo json_encode([
                'status' => 'success',
                'msg' => 'Reset code sent to your phone via SMS'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'msg' => 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error')
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'SMS service error: ' . $e->getMessage()
        ]);
    }
}

function resetPassword() {
    global $conn;
    
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    
    if (empty($phone) || empty($otp) || empty($newPassword)) {
        echo json_encode(['status' => 'error', 'msg' => 'All fields are required']);
        return;
    }
    
    // Verify OTP from session
    if (!isset($_SESSION['password_reset'])) {
        echo json_encode(['status' => 'error', 'msg' => 'No reset request found. Please request a new code.']);
        return;
    }
    
    $resetData = $_SESSION['password_reset'];
    
    // Check if OTP expired
    if (time() > $resetData['expiry']) {
        unset($_SESSION['password_reset']);
        echo json_encode(['status' => 'error', 'msg' => 'Reset code expired. Please request a new code.']);
        return;
    }
    
    // Verify phone and OTP match
    if ($resetData['phone'] !== $phone || $resetData['otp'] !== $otp) {
        echo json_encode(['status' => 'error', 'msg' => 'Invalid reset code']);
        return;
    }
    
    // Password validation
    if (strlen($newPassword) < 6) {
        echo json_encode(['status' => 'error', 'msg' => 'Password must be at least 6 characters']);
        return;
    }
    
    // Update password
    $hashedPassword = md5($newPassword);
    $userId = $resetData['user_id'];
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    if ($stmt->execute()) {
        // Clear reset session
        unset($_SESSION['password_reset']);
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'msg' => 'Password reset successfully! You can now login with your new password.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Failed to update password. Please try again.'
        ]);
        $stmt->close();
    }
}
?>

