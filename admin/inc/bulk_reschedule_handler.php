<?php
/**
 * Bulk Reschedule Handler for ZPPSU Admission System
 * Handles mass rescheduling for calamity/emergency situations
 */

require_once __DIR__ . '/sms_service.php';

class BulkRescheduleHandler {
    private $conn;
    private $smsService;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->smsService = new SMSService($conn);
    }
    
    /**
     * Get all scheduled dates with student counts
     */
    public function getScheduledDates($status = 'Approved') {
        $sql = "SELECT date_scheduled, COUNT(*) as student_count, school_campus 
                FROM schedule_admission 
                WHERE status = ? AND date_scheduled >= CURDATE()
                GROUP BY date_scheduled, school_campus 
                ORDER BY date_scheduled ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $dates = [];
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row;
        }
        $stmt->close();
        
        return $dates;
    }
    
    /**
     * Get students scheduled on a specific date
     */
    public function getStudentsByDate($date, $campus = null, $timeSlot = null) {
        $sql = "SELECT * FROM schedule_admission WHERE date_scheduled = ? AND status = 'Approved'";
        $params = [$date];
        $types = "s";
        
        if ($campus) {
            $sql .= " AND school_campus = ?";
            $params[] = $campus;
            $types .= "s";
        }
        
        if ($timeSlot) {
            $sql .= " AND time_slot = ?";
            $params[] = $timeSlot;
            $types .= "s";
        }
        
        $sql .= " ORDER BY surname, given_name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
        
        return $students;
    }
    
    /**
     * Bulk reschedule all students from one date to another
     * @param string $oldDate Original scheduled date
     * @param string $newDate New scheduled date
     * @param string|null $campus Filter by campus (null = all campuses)
     * @param string|null $timeSlot Filter by time slot (null = all time slots)
     * @param string $reason Reason for rescheduling (e.g., "Calamity", "Emergency")
     * @param int $rescheduledBy User ID who performed the action
     * @param bool $sendSms Whether to send SMS notifications
     * @return array Result with success status and details
     */
    public function bulkReschedule($oldDate, $newDate, $campus = null, $timeSlot = null, $reason = '', $rescheduledBy = null, $sendSms = true) {
        try {
            // Validate dates
            if (strtotime($newDate) < strtotime('today')) {
                return ['success' => false, 'message' => 'New date cannot be in the past'];
            }
            
            if ($oldDate === $newDate) {
                return ['success' => false, 'message' => 'New date must be different from the original date'];
            }
            
            // Get affected students
            $students = $this->getStudentsByDate($oldDate, $campus, $timeSlot);
            
            if (empty($students)) {
                return ['success' => false, 'message' => 'No students found for the selected date'];
            }
            
            $totalStudents = count($students);
            $successCount = 0;
            $failCount = 0;
            $smsSuccess = 0;
            $smsFailed = 0;
            $errors = [];
            
            // Start transaction
            $this->conn->begin_transaction();
            
            foreach ($students as $student) {
                try {
                    // Log the reschedule history
                    $historyStmt = $this->conn->prepare("INSERT INTO reschedule_history 
                        (schedule_id, old_date, old_time_slot, old_room, new_date, new_time_slot, new_room, reason, rescheduled_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $oldTimeSlot = $student['time_slot'];
                    $oldRoom = $student['room_number'];
                    $newTimeSlotValue = $timeSlot ?? $oldTimeSlot; // Keep same time slot if not specified
                    $newRoom = null; // Room will need to be reassigned
                    
                    $historyStmt->bind_param("isssssssi", 
                        $student['id'], $oldDate, $oldTimeSlot, $oldRoom, 
                        $newDate, $newTimeSlotValue, $newRoom, $reason, $rescheduledBy);
                    $historyStmt->execute();
                    $historyStmt->close();
                    
                    // Update the schedule
                    $updateStmt = $this->conn->prepare("UPDATE schedule_admission 
                        SET date_scheduled = ?, room_number = NULL, reschedule_count = reschedule_count + 1 
                        WHERE id = ?");
                    $updateStmt->bind_param("si", $newDate, $student['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    $successCount++;
                    
                    // Send SMS notification if enabled
                    if ($sendSms && !empty($student['phone'])) {
                        $smsResult = $this->sendRescheduleNotification($student, $oldDate, $newDate, $reason);
                        if ($smsResult['success']) {
                            $smsSuccess++;
                        } else {
                            $smsFailed++;
                        }
                    }
                    
                } catch (Exception $e) {
                    $failCount++;
                    $errors[] = "Failed to reschedule {$student['surname']}, {$student['given_name']}: " . $e->getMessage();
                }
            }
            
            // Commit transaction
            $this->conn->commit();
            
            // Log the bulk operation
            $this->logBulkOperation($oldDate, $newDate, $campus, $timeSlot, $reason, $rescheduledBy, $totalStudents, $successCount);
            
            return [
                'success' => true,
                'message' => "Bulk reschedule completed",
                'total_students' => $totalStudents,
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'sms_sent' => $smsSuccess,
                'sms_failed' => $smsFailed,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Bulk Reschedule Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error during bulk reschedule: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send SMS notification for rescheduled exam
     */
    private function sendRescheduleNotification($student, $oldDate, $newDate, $reason) {
        $name = $student['given_name'];
        $oldDateFormatted = date('F d, Y', strtotime($oldDate));
        $newDateFormatted = date('F d, Y', strtotime($newDate));
        
        $message = "IMPORTANT NOTICE!\n\n";
        $message .= "Dear {$name},\n\n";
        $message .= "Your ZPPSU entrance exam has been RESCHEDULED.\n\n";
        $message .= "❌ Original Date: {$oldDateFormatted}\n";
        $message .= "✅ New Date: {$newDateFormatted}\n";
        $message .= "📍 Campus: {$student['school_campus']}\n";
        $message .= "📋 Reason: {$reason}\n\n";
        $message .= "Please check your email for room assignment. Thank you for your understanding.\n\n";
        $message .= "- ZPPSU Admissions Office";
        
        return $this->smsService->sendSms($student['phone'], $message);
    }
    
    /**
     * Log bulk operation for audit
     */
    private function logBulkOperation($oldDate, $newDate, $campus, $timeSlot, $reason, $userId, $total, $success) {
        $sql = "INSERT INTO bulk_reschedule_log 
                (old_date, new_date, campus, time_slot, reason, performed_by, total_affected, success_count, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssiii", $oldDate, $newDate, $campus, $timeSlot, $reason, $userId, $total, $success);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Get available time slots summary for a date
     */
    public function getTimeSlotSummary($date, $campus = null) {
        $sql = "SELECT time_slot, COUNT(*) as count 
                FROM schedule_admission 
                WHERE date_scheduled = ? AND status = 'Approved'";
        $params = [$date];
        $types = "s";
        
        if ($campus) {
            $sql .= " AND school_campus = ?";
            $params[] = $campus;
            $types .= "s";
        }
        
        $sql .= " GROUP BY time_slot";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $summary = [];
        while ($row = $result->fetch_assoc()) {
            $summary[$row['time_slot']] = $row['count'];
        }
        $stmt->close();
        
        return $summary;
    }
    
    /**
     * Get bulk reschedule history/log
     */
    public function getBulkRescheduleHistory($limit = 20) {
        $sql = "SELECT brl.*, u.firstname, u.lastname 
                FROM bulk_reschedule_log brl
                LEFT JOIN users u ON brl.performed_by = u.id
                ORDER BY brl.created_at DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
        
        return $history;
    }
    
    /**
     * Get list of all campuses
     */
    public function getCampuses() {
        $sql = "SELECT DISTINCT school_campus FROM schedule_admission WHERE school_campus IS NOT NULL AND school_campus != '' ORDER BY school_campus";
        $result = $this->conn->query($sql);
        
        $campuses = [];
        while ($row = $result->fetch_assoc()) {
            $campuses[] = $row['school_campus'];
        }
        
        return $campuses;
    }
    
    /**
     * Preview bulk reschedule (dry run without making changes)
     */
    public function previewBulkReschedule($oldDate, $campus = null, $timeSlot = null) {
        $students = $this->getStudentsByDate($oldDate, $campus, $timeSlot);
        $timeSummary = $this->getTimeSlotSummary($oldDate, $campus);
        
        return [
            'total_students' => count($students),
            'students' => $students,
            'time_slot_summary' => $timeSummary
        ];
    }
}
?>

