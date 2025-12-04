<?php
class DatabaseHandler {
    public $conn;
    public $lastAffectedRows = null;

    /**
     * Initialize database handler
     */
    public function __construct($connection) {
        if ($connection === null) {
            error_log("DatabaseHandler: Connection is null!");
            throw new Exception("Database connection is null");
        }
        $this->conn = $connection;
        if (method_exists($this->conn, 'set_charset')) {
            $this->conn->set_charset("utf8mb4");
        }
    }

    /**
     * Get all admission records ordered by creation date
     */
    public function getAllRecords() {
        try {
            if ($this->conn === null) {
                error_log("getAllRecords: Connection is null!");
                // This should not happen if __construct throws, but handle gracefully
                throw new Exception("Database connection is null");
            }
            // Use date_log instead of created_at for compatibility (works with both MySQL and PostgreSQL)
            $result = $this->conn->query("SELECT * FROM schedule_admission ORDER BY date_log DESC, id DESC");
            return $result; // query() always returns DatabaseResult now
        } catch (Exception $e) {
            error_log("Error fetching records: " . $e->getMessage());
            // Return empty query result - query() will return DatabaseResult with num_rows=0
            if ($this->conn) {
                return $this->conn->query("SELECT * FROM schedule_admission WHERE 1=0");
            }
            // This should not happen, but if it does, we need DatabaseResult
            // Since db_connect.php should be included before this file, DatabaseResult should exist
            if (class_exists('DatabaseResult')) {
                return new DatabaseResult(null, 'mysql');
            }
            // Last resort: return null and let calling code handle it
            return null;
        }
    }

    /**
     * Update admission status with transaction support
     */
    public function updateStatus($id, $status) {
        try {
            $this->conn->begin_transaction();

            // Debug: log input values
            error_log('[DB_HANDLER] updateStatus called with id=' . var_export($id, true) . ', status=' . var_export($status, true));

            // FIX: Remove updated_at from query
            $stmt = $this->conn->prepare("UPDATE schedule_admission SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();

            // Debug: log error if any
            if ($stmt->error) {
                error_log('[DB_HANDLER] updateStatus error: ' . $stmt->error);
            }

            // Store affected_rows for external debug
            $this->lastAffectedRows = $stmt->affected_rows;
            error_log('[DB_HANDLER] updateStatus affected_rows=' . $stmt->affected_rows);

            if ($stmt->affected_rows > 0) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error updating status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete admission record with transaction support
     */
    public function deleteRecord($id) {
        try {
            $this->conn->begin_transaction();

            $stmt = $this->conn->prepare("DELETE FROM schedule_admission WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error deleting record: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get single record by ID
     */
    public function getRecordById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM schedule_admission WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching record: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count schedule records, optionally filtered by status
     */
    public function getScheduleCount($status = null) {
        try {
            if ($status !== null) {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM schedule_admission WHERE status = ?");
                $stmt->bind_param("s", $status);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
            } else {
                $res = $this->conn->query("SELECT COUNT(*) AS cnt FROM schedule_admission")->fetch_assoc();
            }
            return (int)($res['cnt'] ?? 0);
        } catch (Exception $e) {
            error_log("Error counting schedules: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count schedule records between dates (inclusive), optionally by status
     */
    public function getScheduleCountInRange($startDate, $endDate, $status = null) {
        try {
            if ($status !== null) {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM schedule_admission WHERE DATE(created_at) BETWEEN ? AND ? AND status = ?");
                $stmt->bind_param("sss", $startDate, $endDate, $status);
            } else {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM schedule_admission WHERE DATE(created_at) BETWEEN ? AND ?");
                $stmt->bind_param("ss", $startDate, $endDate);
            }
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            return (int)($res['cnt'] ?? 0);
        } catch (Exception $e) {
            error_log("Error counting schedules in range: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get monthly total counts for the given year (index 1..12)
     */
    public function getMonthlyScheduleCounts($year) {
        $counts = array_fill(1, 12, 0);
        try {
            $stmt = $this->conn->prepare("SELECT MONTH(created_at) AS m, COUNT(*) AS cnt FROM schedule_admission WHERE YEAR(created_at)=? GROUP BY m");
            $stmt->bind_param("i", $year);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $m = (int)$row['m'];
                $counts[$m] = (int)$row['cnt'];
            }
        } catch (Exception $e) {
            error_log("Error fetching monthly counts: " . $e->getMessage());
        }
        return $counts;
    }

    /**
     * Get daily counts for recent N days (including today)
     */
    public function getDailyScheduleCounts($days = 14) {
        $data = [];
        try {
            $start = (new DateTime())->modify('-' . ((int)$days - 1) . ' days')->format('Y-m-d');
            $stmt = $this->conn->prepare("SELECT DATE(created_at) AS d, COUNT(*) AS cnt FROM schedule_admission WHERE DATE(created_at) >= ? GROUP BY d ORDER BY d");
            $stmt->bind_param("s", $start);
            $stmt->execute();
            $res = $stmt->get_result();
            // Initialize with zeros
            for ($i = 0; $i < $days; $i++) {
                $key = (new DateTime($start))->modify("+{$i} days")->format('Y-m-d');
                $data[$key] = 0;
            }
            while ($row = $res->fetch_assoc()) {
                $data[$row['d']] = (int)$row['cnt'];
            }
        } catch (Exception $e) {
            error_log("Error fetching daily counts: " . $e->getMessage());
        }
        return $data; // associative date => count
    }

    /**
     * Count users by role (1=Admin,2=Staff,3=Student), with optional date range on date_added
     */
    public function getUsersCountByRole($role, $startDate = null, $endDate = null) {
        try {
            if ($startDate && $endDate) {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE role = ? AND DATE(date_added) BETWEEN ? AND ?");
                $stmt->bind_param("iss", $role, $startDate, $endDate);
            } else {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE role = ?");
                $stmt->bind_param("i", $role);
            }
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            return (int)($res['cnt'] ?? 0);
        } catch (Exception $e) {
            error_log("Error counting users by role: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Fetch student-specific schedule records based on email or phone
     */
    public function getStudentRecords($email, $phone, $firstName = '', $lastName = '', $middleName = '') {
        try {
            $email = trim((string)$email);
            $phone = trim((string)$phone);
            $firstName = trim((string)$firstName);
            $lastName = trim((string)$lastName);
            $middleName = trim((string)$middleName);

            $sql = "SELECT * FROM schedule_admission 
                    WHERE (
                        (email = ? AND email <> '')
                        OR (phone = ? AND phone <> '')
                        OR (surname = ? AND given_name = ? AND (middle_name = ? OR ? = ''))
                    )
                    ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssss", $email, $phone, $lastName, $firstName, $middleName, $middleName);
            $stmt->execute();
            return $stmt->get_result();
        } catch (Exception $e) {
            error_log("Error fetching student records: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get filtered records based on multiple criteria
     */
    public function getFilteredRecords($filters = []) {
        try {
            $sql = "SELECT * FROM schedule_admission WHERE 1=1";
            $params = [];
            $types = '';
            
            // Filter by status
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
                $types .= 's';
            }
            
            // Filter by campus
            if (!empty($filters['campus'])) {
                $sql .= " AND school_campus = ?";
                $params[] = $filters['campus'];
                $types .= 's';
            }
            
            // Filter by exam result
            if (!empty($filters['exam_result'])) {
                $sql .= " AND exam_result = ?";
                $params[] = $filters['exam_result'];
                $types .= 's';
            }
            
            // Filter by date
            if (!empty($filters['date'])) {
                $sql .= " AND DATE(date_scheduled) = ?";
                $params[] = $filters['date'];
                $types .= 's';
            }
            
            // Filter by time slot
            if (!empty($filters['time_slot'])) {
                $sql .= " AND time_slot = ?";
                $params[] = $filters['time_slot'];
                $types .= 's';
            }
            
            // Filter by room
            if (!empty($filters['room'])) {
                $sql .= " AND room_number = ?";
                $params[] = $filters['room'];
                $types .= 's';
            }
            
            // Filter by classification
            if (!empty($filters['classification'])) {
                $sql .= " AND classification LIKE ?";
                $params[] = '%' . $filters['classification'] . '%';
                $types .= 's';
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if (empty($params)) {
                return $this->conn->query($sql);
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result();
            
        } catch (Exception $e) {
            error_log("Error fetching filtered records: " . $e->getMessage());
            return false;
        }
    }
}