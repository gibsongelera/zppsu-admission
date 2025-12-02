<?php
/**
 * ZPPSU Admission System - Reschedule Handler
 * Handles rescheduling of admission applications
 */

require_once __DIR__ . '/room_handler.php';
require_once __DIR__ . '/sms_service.php';

class RescheduleHandler {
    private $conn;
    private $roomHandler;
    private $smsService;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->roomHandler = new RoomHandler($connection);
        $this->smsService = new SmsService();
    }
    
    /**
     * Reschedule an application
     */
    public function rescheduleApplication($scheduleId, $newDate, $newTimeSlot, $newRoom, $reason = '', $rescheduledBy = null) {
        // Get current schedule details
        $stmt = $this->conn->prepare("SELECT * FROM schedule_admission WHERE id = ?");
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Schedule not found'];
        }
        
        $oldSchedule = $result->fetch_assoc();
        $stmt->close();
        
        // Validate new date (must be in the future)
        if (strtotime($newDate) < strtotime(date('Y-m-d'))) {
            return ['success' => false, 'message' => 'New date must be in the future'];
        }
        
        // Validate room availability
        $availableRooms = $this->roomHandler->getAvailableRooms($newDate, $newTimeSlot, $oldSchedule['school_campus']);
        $roomAvailable = false;
        
        foreach ($availableRooms as $room) {
            if ($room['room_number'] === $newRoom) {
                $roomAvailable = true;
                break;
            }
        }
        
        if (!$roomAvailable) {
            return ['success' => false, 'message' => 'Selected room is not available for the chosen date and time'];
        }
        
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Log the reschedule in history
            $stmt = $this->conn->prepare("
                INSERT INTO reschedule_history 
                (schedule_id, old_date, new_date, old_time_slot, new_time_slot, old_room, new_room, reason, rescheduled_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "isssssssi",
                $scheduleId,
                $oldSchedule['date_scheduled'],
                $newDate,
                $oldSchedule['time_slot'],
                $newTimeSlot,
                $oldSchedule['room_number'],
                $newRoom,
                $reason,
                $rescheduledBy
            );
            $stmt->execute();
            $stmt->close();
            
            // Update the schedule
            $stmt = $this->conn->prepare("
                UPDATE schedule_admission 
                SET date_scheduled = ?, time_slot = ?, room_number = ?, reminder_sent = 0 
                WHERE id = ?
            ");
            $stmt->bind_param("sssi", $newDate, $newTimeSlot, $newRoom, $scheduleId);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $this->conn->commit();
            
            // Send SMS notification
            if (!empty($oldSchedule['phone'])) {
                $name = $oldSchedule['surname'] . ', ' . $oldSchedule['given_name'];
                $formattedDate = date('F d, Y', strtotime($newDate));
                
                $this->smsService->sendRescheduleNotification(
                    $oldSchedule['phone'],
                    $name,
                    $oldSchedule['reference_number'],
                    $formattedDate,
                    $newTimeSlot,
                    $newRoom,
                    $oldSchedule['school_campus']
                );
            }
            
            return [
                'success' => true,
                'message' => 'Schedule rescheduled successfully. SMS notification sent.'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Failed to reschedule: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get reschedule history for a schedule
     */
    public function getRescheduleHistory($scheduleId) {
        $stmt = $this->conn->prepare("
            SELECT rh.*, u.firstname, u.lastname 
            FROM reschedule_history rh
            LEFT JOIN users u ON rh.rescheduled_by = u.id
            WHERE rh.schedule_id = ?
            ORDER BY rh.rescheduled_at DESC
        ");
        $stmt->bind_param("i", $scheduleId);
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
     * Check if schedule can be rescheduled
     */
    public function canReschedule($scheduleId) {
        $stmt = $this->conn->prepare("SELECT status, date_scheduled FROM schedule_admission WHERE id = ?");
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['can_reschedule' => false, 'reason' => 'Schedule not found'];
        }
        
        $schedule = $result->fetch_assoc();
        $stmt->close();
        
        // Can only reschedule Approved or Pending schedules
        if (!in_array($schedule['status'], ['Approved', 'Pending'])) {
            return ['can_reschedule' => false, 'reason' => 'Only approved or pending schedules can be rescheduled'];
        }
        
        // Cannot reschedule past dates
        if (strtotime($schedule['date_scheduled']) < strtotime(date('Y-m-d'))) {
            return ['can_reschedule' => false, 'reason' => 'Cannot reschedule past examinations'];
        }
        
        return ['can_reschedule' => true, 'reason' => ''];
    }
}
?>

