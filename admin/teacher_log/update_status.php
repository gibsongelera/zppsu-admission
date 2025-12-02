<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../inc/db_connect.php';
require_once '../inc/db_handler.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $db = new DatabaseHandler($conn);
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_DEFAULT);
    $status = preg_replace('/[^A-Za-z]/', '', $status);
    
    if ($id === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID provided']);
        exit();
    }

    $validStatuses = ['Approved', 'Pending', 'Rejected'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status provided', 'debug' => $status]);
        exit();
    }

    $result = $db->updateStatus($id, $status);
    // Get extra debug info from db_handler.php
    $affectedRows = property_exists($db, 'lastAffectedRows') ? $db->lastAffectedRows : null;
    $debugInfo = [
        'id' => $id,
        'status' => $status,
        'sql_error' => $conn->error,
        'affected_rows' => $affectedRows
    ];

    // Send SMS notification for status changes
    $smsSent = false;
    $smsError = '';
    if ($result && in_array($status, ['Approved', 'Rejected'])) {
        // Get applicant info
        $record = $db->getRecordById($id);
        if ($record && !empty($record['phone'])) {
            require_once '../inc/sms_service.php';
            $smsService = new SmsService();
            
            $campus = isset($record['school_campus']) ? $record['school_campus'] : '';
            $schedule = isset($record['date_scheduled']) ? $record['date_scheduled'] : '';
            $surname = isset($record['surname']) ? $record['surname'] : '';
            $given_name = isset($record['given_name']) ? $record['given_name'] : '';
            $middle_name = isset($record['middle_name']) ? $record['middle_name'] : '';
            $phone = $record['phone'];
            $reference_number = isset($record['reference_number']) ? $record['reference_number'] : '';
            
            $applicantName = trim($surname . ', ' . $given_name . ' ' . $middle_name);
            
            // Send appropriate notification based on status
            if ($status === 'Approved') {
                $smsResult = $smsService->sendApprovalNotification($phone, $applicantName, $reference_number, $campus, $schedule);
            } else if ($status === 'Rejected') {
                $smsResult = $smsService->sendRejectionNotification($phone, $applicantName, $reference_number, $campus);
            }
            
            if ($smsResult['success']) {
                $smsSent = true;
            } else {
                $smsError = $smsResult['error'];
            }
        } else {
            $smsError = 'No phone number found for applicant.';
        }
    }

    if ($result) {
        // On success, return only a concise success message
        echo json_encode([
            'success' => true,
            'message' => 'succesfully send the message'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update status',
            'debug' => $debugInfo,
            'sms_sent' => $smsSent,
            'sms_error' => $smsError
        ]);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}