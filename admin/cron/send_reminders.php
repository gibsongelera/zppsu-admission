<?php
/**
 * ZPPSU Admission System - 3-Day Reminder Cron Job
 * 
 * This script should be run daily to send SMS reminders to applicants
 * whose exam is scheduled 3 days from now.
 * 
 * Setup cron job (run daily at 9:00 AM):
 * 0 9 * * * /usr/bin/php /path/to/admin/cron/send_reminders.php
 * 
 * For Windows Task Scheduler:
 * Program: C:\xampp\php\php.exe
 * Arguments: C:\xampp\htdocs\zppsu_admission\admin\cron\send_reminders.php
 * Schedule: Daily at 9:00 AM
 */

// Set script execution time limit
set_time_limit(300); // 5 minutes max

// Get the path to project root
$projectRoot = realpath(__DIR__ . '/../..');

require_once $projectRoot . '/config.php';
require_once $projectRoot . '/admin/inc/db_connect.php';
require_once $projectRoot . '/admin/inc/sms_service.php';

// Log file
$logFile = $projectRoot . '/admin/cron/reminder_log.txt';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("=== Reminder Cron Job Started ===");

try {
    // Calculate target date (3 days from now)
    $targetDate = date('Y-m-d', strtotime('+3 days'));
    logMessage("Target date: $targetDate");
    
    // Get all approved applications scheduled for the target date that haven't received reminders
    $stmt = $conn->prepare("
        SELECT * FROM schedule_admission 
        WHERE DATE(date_scheduled) = ? 
        AND status = 'Approved' 
        AND reminder_sent = 0
        AND phone IS NOT NULL 
        AND phone != ''
    ");
    $stmt->bind_param("s", $targetDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $totalFound = $result->num_rows;
    logMessage("Found $totalFound applications to send reminders");
    
    if ($totalFound === 0) {
        logMessage("No reminders to send today");
        $stmt->close();
        exit(0);
    }
    
    $smsService = new SmsService();
    $successCount = 0;
    $failCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $scheduleId = $row['id'];
        $name = trim($row['surname'] . ', ' . $row['given_name']);
        $phone = $row['phone'];
        $refNum = $row['reference_number'];
        $date = date('F d, Y (l)', strtotime($row['date_scheduled']));
        $timeSlot = $row['time_slot'] ?? 'To Be Announced';
        $room = $row['room_number'] ?? 'To Be Announced';
        $campus = $row['school_campus'];
        
        logMessage("Sending reminder to: $name ($refNum) - $phone");
        
        // Send SMS reminder
        $smsResult = $smsService->sendExamReminder($phone, $name, $date, $timeSlot, $room, $campus);
        
        if ($smsResult['success']) {
            // Update reminder_sent flag
            $updateStmt = $conn->prepare("UPDATE schedule_admission SET reminder_sent = 1, last_sms_sent = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $scheduleId);
            $updateStmt->execute();
            $updateStmt->close();
            
            $successCount++;
            logMessage("  ✓ Reminder sent successfully");
        } else {
            $failCount++;
            $error = $smsResult['error'] ?? 'Unknown error';
            logMessage("  ✗ Failed to send: $error");
        }
        
        // Small delay to avoid overwhelming the SMS service
        usleep(500000); // 0.5 seconds
    }
    
    $stmt->close();
    
    logMessage("=== Reminder Cron Job Completed ===");
    logMessage("Success: $successCount | Failed: $failCount");
    logMessage("");
    
    // Return success exit code
    exit(0);
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("=== Reminder Cron Job Failed ===");
    logMessage("");
    
    // Return error exit code
    exit(1);
}
?>

