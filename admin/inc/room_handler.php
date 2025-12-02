<?php
/**
 * ZPPSU Admission System - Room Handler
 * Manages room assignments and availability
 */

class RoomHandler {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get all rooms by campus
     */
    public function getRoomsByCampus($campus) {
        $stmt = $this->conn->prepare("SELECT * FROM room_assignments WHERE campus = ? AND is_active = 1 ORDER BY room_number");
        $stmt->bind_param("s", $campus);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rooms = [];
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        
        $stmt->close();
        return $rooms;
    }
    
    /**
     * Get available rooms for a specific date, time slot, and campus
     */
    public function getAvailableRooms($date, $timeSlot, $campus) {
        // Get all active rooms for the campus
        $rooms = $this->getRoomsByCampus($campus);
        
        if (empty($rooms)) {
            return [];
        }
        
        $availableRooms = [];
        
        foreach ($rooms as $room) {
            // Check how many students are already scheduled in this room at this time
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM schedule_admission 
                WHERE DATE(date_scheduled) = DATE(?) 
                AND time_slot = ? 
                AND room_number = ? 
                AND status != 'Rejected'
            ");
            $stmt->bind_param("sss", $date, $timeSlot, $room['room_number']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $currentCount = (int)$row['count'];
            $stmt->close();
            
            // Check if room has available capacity
            if ($currentCount < $room['capacity']) {
                $room['available_slots'] = $room['capacity'] - $currentCount;
                $room['occupied_slots'] = $currentCount;
                $availableRooms[] = $room;
            }
        }
        
        return $availableRooms;
    }
    
    /**
     * Assign room to a schedule
     */
    public function assignRoom($scheduleId, $roomNumber) {
        $stmt = $this->conn->prepare("UPDATE schedule_admission SET room_number = ? WHERE id = ?");
        $stmt->bind_param("si", $roomNumber, $scheduleId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Check if room exists and is active
     */
    public function validateRoom($roomNumber, $campus) {
        $stmt = $this->conn->prepare("SELECT id FROM room_assignments WHERE room_number = ? AND campus = ? AND is_active = 1");
        $stmt->bind_param("ss", $roomNumber, $campus);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Get all rooms (for admin management)
     */
    public function getAllRooms() {
        $result = $this->conn->query("SELECT * FROM room_assignments ORDER BY campus, room_number");
        
        $rooms = [];
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        
        return $rooms;
    }
    
    /**
     * Add new room
     */
    public function addRoom($roomNumber, $campus, $capacity) {
        $stmt = $this->conn->prepare("INSERT INTO room_assignments (room_number, campus, capacity) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $roomNumber, $campus, $capacity);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Update room
     */
    public function updateRoom($id, $roomNumber, $campus, $capacity, $isActive) {
        $stmt = $this->conn->prepare("UPDATE room_assignments SET room_number = ?, campus = ?, capacity = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssiii", $roomNumber, $campus, $capacity, $isActive, $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Delete room
     */
    public function deleteRoom($id) {
        $stmt = $this->conn->prepare("DELETE FROM room_assignments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get room occupancy statistics
     */
    public function getRoomOccupancy($date, $timeSlot) {
        $stmt = $this->conn->prepare("
            SELECT 
                r.room_number,
                r.campus,
                r.capacity,
                COUNT(s.id) as occupied
            FROM room_assignments r
            LEFT JOIN schedule_admission s ON r.room_number = s.room_number 
                AND DATE(s.date_scheduled) = DATE(?) 
                AND s.time_slot = ?
                AND s.status != 'Rejected'
            WHERE r.is_active = 1
            GROUP BY r.id, r.room_number, r.campus, r.capacity
            ORDER BY r.campus, r.room_number
        ");
        $stmt->bind_param("ss", $date, $timeSlot);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $occupancy = [];
        while ($row = $result->fetch_assoc()) {
            $occupancy[] = $row;
        }
        
        $stmt->close();
        return $occupancy;
    }
}
?>

