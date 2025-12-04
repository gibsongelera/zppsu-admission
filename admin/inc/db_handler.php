<?php
class DatabaseHandler {
    public $conn;
    public $lastAffectedRows = null;

    private $dbType = 'mysql';
    
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
        // Get database type from connection if available
        if (property_exists($this->conn, 'dbType')) {
            $this->dbType = $this->conn->dbType;
        } elseif (defined('DB_TYPE')) {
            $this->dbType = DB_TYPE;
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
            if (!$stmt) {
                error_log("Prepare failed in getRecordById");
                return false;
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result ? $result->fetch_assoc() : false;
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
                if (!$stmt) {
                    error_log("Prepare failed in getScheduleCount with status");
                    return 0;
                }
                $stmt->bind_param("s", $status);
                $stmt->execute();
                $result = $stmt->get_result();
                $res = $result ? $result->fetch_assoc() : ['cnt' => 0];
            } else {
                $qry = $this->conn->query("SELECT COUNT(*) AS cnt FROM schedule_admission");
                $res = $qry ? $qry->fetch_assoc() : ['cnt' => 0];
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
            // Use date_log instead of created_at for compatibility (works with both MySQL and PostgreSQL)
            if ($status !== null) {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM schedule_admission WHERE DATE(date_log) BETWEEN ? AND ? AND status = ?");
                if (!$stmt) {
                    error_log("Prepare failed in getScheduleCountInRange with status");
                    return 0;
                }
                $stmt->bind_param("sss", $startDate, $endDate, $status);
            } else {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM schedule_admission WHERE DATE(date_log) BETWEEN ? AND ?");
                if (!$stmt) {
                    error_log("Prepare failed in getScheduleCountInRange without status");
                    return 0;
                }
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
            // Use date_log - the convertToPostgres will handle MySQL->PostgreSQL conversion
            // Use MySQL syntax, it will be converted automatically if needed
            $sql = "SELECT MONTH(date_log) AS m, COUNT(*) AS cnt FROM schedule_admission WHERE YEAR(date_log)=? GROUP BY m";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed in getMonthlyScheduleCounts");
                return $counts;
            }
            $stmt->bind_param("i", $year);
            $stmt->execute();
            $result = $stmt->get_result();
            if (!$result) {
                return $counts;
            }
            $res = $result;
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
            // Handle both 'role' and 'type' columns
            if ($startDate && $endDate) {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE (role = ? OR type = ?) AND DATE(date_added) BETWEEN ? AND ?");
                if (!$stmt) {
                    error_log("Prepare failed in getUsersCountByRole with date range");
                    return 0;
                }
                $stmt->bind_param("iiss", $role, $role, $startDate, $endDate);
            } else {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE (role = ? OR type = ?)");
                if (!$stmt) {
                    error_log("Prepare failed in getUsersCountByRole");
                    return 0;
                }
                $stmt->bind_param("ii", $role, $role);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $res = $result ? $result->fetch_assoc() : ['cnt' => 0];
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

            // Handle both column name conventions:
            // Old: surname, given_name, middle_name
            // New: first_name, last_name, middle_name
            // Also handle both phone_number and phone columns
            // Use COALESCE to check both column name sets
            $sql = "SELECT * FROM schedule_admission 
                    WHERE (
                        (email = ? AND email <> '')
                        OR (COALESCE(phone_number, phone) = ? AND COALESCE(phone_number, phone) <> '')
                        OR (COALESCE(last_name, surname) = ? AND COALESCE(first_name, given_name) = ? AND (middle_name = ? OR ? = ''))
                    )
                    ORDER BY date_log DESC, id DESC";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed in getStudentRecords");
                return new DatabaseResult(null, $this->dbType);
            }
            // Bind parameters: email, phone, last_name/surname, first_name/given_name, middle_name (x2)
            $stmt->bind_param("ssssss", $email, $phone, $lastName, $firstName, $middleName, $middleName);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result ? $result : new DatabaseResult(null, $this->dbType);
        } catch (Exception $e) {
            error_log("Error fetching student records: " . $e->getMessage());
            return new DatabaseResult(null, $this->dbType);
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