<?php
class DatabaseHandler {
    private $conn;
    public $lastAffectedRows = null;

    /**
     * Initialize database handler
     */
    public function __construct($connection) {
        $this->conn = $connection;
        $this->conn->set_charset("utf8mb4");
    }

    /**
     * Get all admission records ordered by creation date
     */
    public function getAllRecords() {
        try {
            return $this->conn->query("SELECT * FROM schedule_admission ORDER BY created_at DESC");
        } catch (Exception $e) {
            error_log("Error fetching records: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update admission status
     */
    public function updateStatus($id, $status) {
        try {
            $this->conn->begin_transaction();

            $stmt = $this->conn->prepare("UPDATE schedule_admission SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();

            $this->lastAffectedRows = $stmt->affected_rows;

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
     * Delete admission record
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
    public function getStudentRecords($email, $phone) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM schedule_admission WHERE (email = ? AND email <> '') OR (phone = ? AND phone <> '') ORDER BY created_at DESC");
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            return $stmt->get_result();
        } catch (Exception $e) {
            error_log("Error fetching student records: " . $e->getMessage());
            return false;
        }
    }
}
?>
