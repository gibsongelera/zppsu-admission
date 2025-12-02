<?php
session_start();
require_once('../inc/db_connect.php');
require_once('../inc/sms_service.php');

header('Content-Type: application/json');

// Check if user is logged in and has admin/teacher permissions
$currentRole = isset($_SESSION['userdata']['role']) ? (int)$_SESSION['userdata']['role'] : null;
if ($currentRole !== 1 && $currentRole !== 2) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Only admins and teachers can reschedule appointments.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$newDate = isset($_POST['new_date_scheduled']) ? trim($_POST['new_date_scheduled']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$sendSms = isset($_POST['send_sms']) && $_POST['send_sms'] === '1';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

if (empty($newDate)) {
    echo json_encode(['success' => false, 'message' => 'New date is required']);
    exit;
}

// Validate date format
$dateObj = DateTime::createFromFormat('Y-m-d', $newDate);
if (!$dateObj || $dateObj->format('Y-m-d') !== $newDate) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// Check if date is a weekend (Saturday or Sunday)
$dayOfWeek = $dateObj->format('N'); // 1 (Monday) to 7 (Sunday)
if ($dayOfWeek < 6) {
    echo json_encode(['success' => false, 'message' => 'Only Saturday and Sunday are allowed for appointments']);
    exit;
}

// Check if date is in the future
$today = new DateTime();
$today->setTime(0, 0, 0);
if ($dateObj <= $today) {
    echo json_encode(['success' => false, 'message' => 'Date must be in the future']);
    exit;
}

try {
    // Get current schedule details before update
    $stmt = $conn->prepare("SELECT date_scheduled, reference_number FROM schedule_admission WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        $stmt->close();
        exit;
    }
    
    $oldSchedule = $result->fetch_assoc();
    $oldDate = $oldSchedule['date_scheduled'];
    $referenceNumber = $oldSchedule['reference_number'];
    $stmt->close();
    
    // Update the schedule
    $updateStmt = $conn->prepare("UPDATE schedule_admission SET date_scheduled = ? WHERE id = ?");
    $updateStmt->bind_param("si", $newDate, $id);
    
    if (!$updateStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update appointment: ' . $conn->error]);
        $updateStmt->close();
        exit;
    }
    
    $updateStmt->close();
    
    // Send SMS notification if requested and phone is available
    $smsStatus = '';
    if ($sendSms && !empty($phone)) {
        try {
            $smsService = new SmsService();
            
            // Format the new date nicely
            $formattedNewDate = date('F d, Y (l)', strtotime($newDate));
            $formattedOldDate = date('F d, Y (l)', strtotime($oldDate));
            
            // Build SMS message
            $message = "ZPPSU ADMISSION RESCHEDULE NOTICE\n\n";
            $message .= "Hello $name,\n\n";
            $message .= "Your entrance exam schedule has been rescheduled.\n\n";
            $message .= "Reference #: $referenceNumber\n";
            $message .= "Previous Date: $formattedOldDate\n";
            $message .= "NEW Date: $formattedNewDate\n\n";
            
            if (!empty($reason)) {
                $message .= "Reason: $reason\n\n";
            }
            
            $message .= "Please arrive 30 minutes early. Bring valid ID and required documents.\n\n";
            $message .= "For inquiries, contact ZPPSU Admission Office.";
            
            $smsResult = $smsService->sendSMS($phone, $message);
            
            if ($smsResult['success']) {
                $smsStatus = ' SMS notification sent successfully.';
            } else {
                $smsStatus = ' However, SMS notification failed: ' . ($smsResult['error'] ?? 'Unknown error');
            }
        } catch (Exception $e) {
            $smsStatus = ' However, SMS notification failed: ' . $e->getMessage();
        }
    }
    
    // Format response message
    $formattedDate = date('F d, Y (l)', strtotime($newDate));
    $responseMessage = "Appointment successfully rescheduled to $formattedDate.$smsStatus";
    
    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'new_date' => $newDate,
        'formatted_date' => $formattedDate
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>

