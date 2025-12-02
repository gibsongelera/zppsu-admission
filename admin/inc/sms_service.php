<?php
/**
 * ZPPSU Admission System - SMS Service
 * Centralized SMS sending functionality
 */

require_once __DIR__ . '/api_config.php';

class SmsService {
    private $config;
    private $conn;
    
    public function __construct($connection = null) {
        $this->config = ApiConfig::getSmsConfig();
        $this->conn = $connection;
        
        // If no connection provided, try to get it from db_connect.php
        if ($this->conn === null) {
            $dbPath = __DIR__ . '/db_connect.php';
            if (file_exists($dbPath)) {
                require_once $dbPath;
                global $conn;
                $this->conn = $conn;
            }
        }
    }
    
    /**
     * Send SMS message
     */
    public function sendSms($phone, $message, $senderName = null) {
        try {
            // Validate and format phone number
            $validatedPhone = ApiConfig::validatePhoneNumber($phone);
            if (!$validatedPhone) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number format: ' . $phone
                ];
            }
            
            // Prepare payload
            $payload = [
                'recipients' => [$validatedPhone],
                'message' => $message,
                'sender_name' => $senderName ?: $this->config['sender_name']
            ];
            
            // Send SMS
            $result = $this->makeApiCall($payload);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'phone' => $validatedPhone,
                    'formatted_phone' => ApiConfig::formatPhoneForDisplay($validatedPhone)
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error'],
                    'phone' => $validatedPhone
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'SMS service error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send OTP SMS
     */
    public function sendOtp($phone, $otp) {
        $message = "Your ZPPSU Admission OTP code is: {$otp}. This code will expire in 5 minutes. Do not share this code with anyone.";
        return $this->sendSms($phone, $message);
    }
    
    /**
     * Send approval notification
     */
    public function sendApprovalNotification($phone, $applicantName, $referenceNumber, $campus, $scheduleDate) {
        $message = "🎉 CONGRATULATIONS! 🎉\n\nDear {$applicantName},\n\nYour application to Zamboanga Peninsula Polytechnic State University - {$campus} has been APPROVED!\n\n📋 Reference Number: {$referenceNumber}\n📅 Scheduled Date: {$scheduleDate}\n\n✅ Please bring the following requirements:\n• Printed admission slip\n• Valid ID or Birth Certificate\n• Recent 2x2 photo\n• Previous school records\n\n📍 Report to the admissions office 30 minutes before your scheduled time.\n\nFor inquiries, contact the admissions office.\n\nThank you and welcome to ZPPSU!";
        
        return $this->sendSms($phone, $message);
    }
    
    /**
     * Send rejection notification
     */
    public function sendRejectionNotification($phone, $applicantName, $referenceNumber, $campus) {
        $message = "Dear {$applicantName},\n\nWe regret to inform you that your application to Zamboanga Peninsula Polytechnic State University - {$campus} has been REJECTED.\n\n📋 Reference Number: {$referenceNumber}\n\n❌ Reason: Incomplete requirements or does not meet admission criteria.\n\n📞 For more information about the rejection and possible reapplication, please contact the admissions office.\n\nWe encourage you to reapply in the next admission period with complete requirements.\n\nThank you for your interest in ZPPSU.";
        
        return $this->sendSms($phone, $message);
    }
    
    /**
     * Make API call to SMS service
     */
    private function makeApiCall($payload) {
        $ch = curl_init(ApiConfig::getSmsUrl());
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ApiConfig::getSmsHeaders());
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);
        
        if ($response === false) {
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $curlError
            ];
        }
        
        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => 'HTTP Error ' . $httpCode . ': ' . $response
            ];
        }
        
        // Try to parse JSON response first (new API format)
        $jsonResponse = json_decode($response, true);
        if ($jsonResponse !== null && isset($jsonResponse['data']['success'])) {
            if ($jsonResponse['data']['success'] === true) {
                return [
                    'success' => true,
                    'response' => $response,
                    'batch_id' => $jsonResponse['data']['smsBatchId'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $jsonResponse['data']['message'] ?? 'SMS failed'
                ];
            }
        }
        
        // Fallback to old string-based check (legacy API format)
        if (strpos($response, 'PENDING') !== false || strpos($response, 'SUCCESS') !== false) {
            return [
                'success' => true,
                'response' => $response
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Unexpected response: ' . $response
        ];
    }
    
    /**
     * Send exam reminder (3 days before)
     */
    public function sendExamReminder($phone, $name, $date, $time, $room, $campus) {
        $message = "⏰ REMINDER: Dear {$name},\n\n"
                 . "Your ZPPSU entrance exam is in 3 DAYS!\n\n"
                 . "📅 Date: {$date}\n"
                 . "🕐 Time: {$time}\n"
                 . "🚪 Room: {$room}\n"
                 . "📍 Campus: {$campus}\n\n"
                 . "⚠️ IMPORTANT REMINDERS:\n"
                 . "• Arrive 30 minutes early\n"
                 . "• Bring printed admission slip\n"
                 . "• Bring valid ID\n"
                 . "• Bring pencils & eraser\n"
                 . "• No mobile phones allowed\n\n"
                 . "Good luck!\n- ZPPSU Admissions";
        
        return $this->sendSms($phone, $message);
    }
    
    /**
     * Send reschedule notification
     */
    public function sendRescheduleNotification($phone, $name, $refNumber, $newDate, $newTime, $newRoom, $campus) {
        $message = "📅 SCHEDULE CHANGE NOTICE\n\n"
                 . "Dear {$name},\n\n"
                 . "Your exam schedule has been changed:\n\n"
                 . "📋 Ref: {$refNumber}\n"
                 . "📅 NEW Date: {$newDate}\n"
                 . "🕐 NEW Time: {$newTime}\n"
                 . "🚪 NEW Room: {$newRoom}\n"
                 . "📍 Campus: {$campus}\n\n"
                 . "Please take note of this change. For concerns, contact the admissions office.\n\n"
                 . "- ZPPSU Admissions";
        
        return $this->sendSms($phone, $message);
    }
    
    /**
     * Send exam result notification
     */
    public function sendExamResultNotification($phone, $name, $refNumber, $result, $score = null, $remarks = null) {
        $resultEmoji = $result === 'Pass' ? '✅' : ($result === 'Fail' ? '❌' : '⏳');
        $resultText = strtoupper($result);
        
        $message = "📊 EXAM RESULT NOTIFICATION\n\n"
                 . "Dear {$name},\n\n"
                 . "Your ZPPSU entrance exam result is now available:\n\n"
                 . "📋 Reference: {$refNumber}\n"
                 . "📝 Result: {$resultEmoji} {$resultText}\n";
        
        if ($score !== null && $score > 0) {
            $message .= "📈 Score: {$score}\n";
        }
        
        if (!empty($remarks)) {
            $message .= "💬 Remarks: {$remarks}\n";
        }
        
        if ($result === 'Pass') {
            $message .= "\n🎉 Congratulations! You have passed the entrance exam.\n"
                     . "Please wait for further instructions regarding enrollment.\n\n";
        } else if ($result === 'Fail') {
            $message .= "\nWe encourage you to reapply in the next admission period.\n"
                     . "For inquiries, contact the admissions office.\n\n";
        } else {
            $message .= "\nYour exam result is being processed.\n\n";
        }
        
        $message .= "For more details, login to your admission portal.\n\n"
                 . "- ZPPSU Admissions";
        
        return $this->sendSms($phone, $message);
    }
    
    /**
     * Check SMS deduplication by classification and phone
     * Returns true if duplicate (already sent), false if can send
     */
    public function checkSmsDeduplication($classification, $phone, $messageType = 'Approval') {
        if ($this->conn === null) {
            return false; // If no DB connection, allow sending
        }
        
        try {
            // Check if same classification + phone already received this message type
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM sms_log 
                WHERE classification = ? 
                AND phone = ? 
                AND message_type = ?
                AND sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->bind_param("sss", $classification, $phone, $messageType);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return (int)$row['count'] > 0; // true if duplicate exists
            
        } catch (Exception $e) {
            error_log("SMS Deduplication check error: " . $e->getMessage());
            return false; // On error, allow sending
        }
    }
    
    /**
     * Log SMS to database
     */
    public function logSms($classification, $phone, $messageType, $messageContent = '') {
        if ($this->conn === null) {
            return false;
        }
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO sms_log (classification, phone, message_type, message_content) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $classification, $phone, $messageType, $messageContent);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("SMS logging error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test SMS functionality
     */
    public function testSms($testPhone = '+639123456789') {
        $testMessage = "Test SMS from ZPPSU Admission System - " . date('Y-m-d H:i:s');
        return $this->sendSms($testPhone, $testMessage, 'ZPPSU TEST');
    }
}
?>
