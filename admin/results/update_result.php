<?php
require_once __DIR__ . '/../inc/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and has permission
session_start();
if (!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['role'], [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$scheduleId = (int)($_POST['schedule_id'] ?? 0);
$examResult = $_POST['exam_result'] ?? 'Pending';
$examScore = !empty($_POST['exam_score']) ? (float)$_POST['exam_score'] : null;
$examRemarks = $_POST['exam_remarks'] ?? '';

if ($scheduleId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
    exit;
}

// Validate exam result
if (!in_array($examResult, ['Pending', 'Pass', 'Fail'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam result']);
    exit;
}

// Validate score if provided
if ($examScore !== null && ($examScore < 0 || $examScore > 100)) {
    echo json_encode(['success' => false, 'message' => 'Score must be between 0 and 100']);
    exit;
}

// Get student information before updating
try {
    $getStmt = $conn->prepare("SELECT surname, given_name, phone, reference_number FROM schedule_admission WHERE id = ?");
    $getStmt->bind_param("i", $scheduleId);
    $getStmt->execute();
    $result = $getStmt->get_result();
    
    if ($result->num_rows === 0) {
        $getStmt->close();
        echo json_encode(['success' => false, 'message' => 'Schedule not found']);
        exit;
    }
    
    $studentData = $result->fetch_assoc();
    $getStmt->close();
    
    // Update database
    $stmt = $conn->prepare("UPDATE schedule_admission SET exam_result = ?, exam_score = ?, exam_remarks = ? WHERE id = ?");
    $stmt->bind_param("sdsi", $examResult, $examScore, $examRemarks, $scheduleId);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Send SMS notification if result is Pass or Fail (not Pending)
        if (in_array($examResult, ['Pass', 'Fail']) && !empty($studentData['phone'])) {
            require_once __DIR__ . '/../inc/sms_service.php';
            $smsService = new SmsService($conn);
            
            $studentName = $studentData['surname'] . ', ' . $studentData['given_name'];
            $refNumber = $studentData['reference_number'];
            
            $smsResult = $smsService->sendExamResultNotification(
                $studentData['phone'],
                $studentName,
                $refNumber,
                $examResult,
                $examScore,
                $examRemarks
            );
            
            // Log SMS send attempt (optional)
            if ($smsResult['success']) {
                error_log("Exam result SMS sent to {$studentData['phone']} for ref: {$refNumber}");
            } else {
                error_log("Failed to send exam result SMS: " . ($smsResult['error'] ?? 'Unknown error'));
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Exam result updated successfully' . (in_array($examResult, ['Pass', 'Fail']) ? ' and SMS notification sent' : '')
        ]);
    } else {
        $stmt->close();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update exam result: ' . $conn->error
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

