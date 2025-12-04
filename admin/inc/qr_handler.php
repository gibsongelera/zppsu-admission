<?php
/**
 * QR Code Handler for ZPPSU Admission System
 * Handles QR code generation and validation for student registration
 */

// Include the QR library
require_once __DIR__ . '/../../libs/phpqrcode/qrlib.php';

class QRCodeHandler {
    private $conn;
    private $uploadDir;
    private $secretKey = 'ZPPSU_ADMISSION_2025_SECRET';
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->uploadDir = realpath(__DIR__ . '/../../uploads/qrcodes');
        
        // Create QR codes directory if it doesn't exist
        if (!$this->uploadDir) {
            $this->uploadDir = __DIR__ . '/../../uploads/qrcodes';
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0777, true);
            }
        }
    }
    
    /**
     * Generate a unique validation token for QR code
     */
    private function generateToken($scheduleId, $referenceNumber) {
        $data = $scheduleId . '|' . $referenceNumber . '|' . $this->secretKey;
        return hash('sha256', $data);
    }
    
    /**
     * Generate QR code for a registered student
     * @param int $scheduleId The schedule_admission record ID
     * @return array Result with success status and QR code path
     */
    public function generateQRCode($scheduleId) {
        try {
            // Fetch student data
            $stmt = $this->conn->prepare("SELECT id, reference_number, surname, given_name, lrn, status, qr_code_path, qr_token 
                                          FROM schedule_admission WHERE id = ?");
            $stmt->bind_param("i", $scheduleId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Student record not found'];
            }
            
            $student = $result->fetch_assoc();
            $stmt->close();
            
            // Only generate QR for approved students
            if ($student['status'] !== 'Approved') {
                return ['success' => false, 'message' => 'QR code can only be generated for approved applications'];
            }
            
            // Generate unique token
            $token = $this->generateToken($scheduleId, $student['reference_number']);
            
            // Create QR code content (JSON with validation data)
            $qrContent = json_encode([
                'type' => 'ZPPSU_ADMISSION',
                'ref' => $student['reference_number'],
                'id' => $scheduleId,
                'token' => substr($token, 0, 16), // Short token for QR
                'name' => $student['surname'] . ', ' . $student['given_name']
            ]);
            
            // Generate QR code filename
            $filename = 'qr_' . $student['reference_number'] . '_' . time() . '.png';
            $filepath = $this->uploadDir . '/' . $filename;
            
            // Generate QR code image
            QRcode::png($qrContent, $filepath, QR_ECLEVEL_M, 10, 2);
            
            if (file_exists($filepath)) {
                // Update database with QR code info
                $relativePath = 'uploads/qrcodes/' . $filename;
                $updateStmt = $this->conn->prepare("UPDATE schedule_admission SET qr_code_path = ?, qr_token = ? WHERE id = ?");
                $updateStmt->bind_param("ssi", $relativePath, $token, $scheduleId);
                $updateStmt->execute();
                $updateStmt->close();
                
                return [
                    'success' => true,
                    'message' => 'QR code generated successfully',
                    'qr_path' => $relativePath,
                    'qr_url' => base_url . $relativePath
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to generate QR code image'];
            }
            
        } catch (Exception $e) {
            error_log("QR Generation Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error generating QR code: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate a QR code scan
     * @param string $qrData The scanned QR code data (JSON string)
     * @return array Validation result with student details
     */
    public function validateQRCode($qrData) {
        try {
            $data = json_decode($qrData, true);
            
            if (!$data || !isset($data['type']) || $data['type'] !== 'ZPPSU_ADMISSION') {
                return [
                    'valid' => false,
                    'status' => 'invalid',
                    'message' => 'Invalid QR code format',
                    'icon' => 'times-circle',
                    'color' => 'danger'
                ];
            }
            
            // Fetch student record
            $stmt = $this->conn->prepare("SELECT * FROM schedule_admission WHERE id = ? AND reference_number = ?");
            $stmt->bind_param("is", $data['id'], $data['ref']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return [
                    'valid' => false,
                    'status' => 'not_found',
                    'message' => 'Student not found in system. NOT YET REGISTERED.',
                    'icon' => 'exclamation-triangle',
                    'color' => 'warning'
                ];
            }
            
            $student = $result->fetch_assoc();
            $stmt->close();
            
            // Verify token
            $expectedToken = $this->generateToken($data['id'], $data['ref']);
            if (substr($expectedToken, 0, 16) !== $data['token']) {
                return [
                    'valid' => false,
                    'status' => 'invalid_token',
                    'message' => 'QR code validation failed. Token mismatch.',
                    'icon' => 'times-circle',
                    'color' => 'danger'
                ];
            }
            
            // Check status
            switch ($student['status']) {
                case 'Approved':
                    return [
                        'valid' => true,
                        'status' => 'registered',
                        'message' => 'REGISTERED - Student is verified!',
                        'icon' => 'check-circle',
                        'color' => 'success',
                        'student' => [
                            'id' => $student['id'],
                            'name' => $student['surname'] . ', ' . $student['given_name'] . ' ' . $student['middle_name'],
                            'reference_number' => $student['reference_number'],
                            'lrn' => $student['lrn'] ?? 'N/A',
                            'email' => $student['email'],
                            'phone' => $student['phone'],
                            'campus' => $student['school_campus'],
                            'classification' => $student['classification'],
                            'date_scheduled' => $student['date_scheduled'],
                            'time_slot' => $student['time_slot'] ?? 'TBA',
                            'room_number' => $student['room_number'] ?? 'TBA',
                            'exam_result' => $student['exam_result'] ?? 'Pending',
                            'photo' => $student['photo']
                        ]
                    ];
                    
                case 'Pending':
                    return [
                        'valid' => false,
                        'status' => 'pending',
                        'message' => 'PENDING - Application not yet approved.',
                        'icon' => 'hourglass-half',
                        'color' => 'warning',
                        'student' => [
                            'name' => $student['surname'] . ', ' . $student['given_name'],
                            'reference_number' => $student['reference_number']
                        ]
                    ];
                    
                case 'Rejected':
                    return [
                        'valid' => false,
                        'status' => 'rejected',
                        'message' => 'REJECTED - Application was rejected.',
                        'icon' => 'ban',
                        'color' => 'danger',
                        'student' => [
                            'name' => $student['surname'] . ', ' . $student['given_name'],
                            'reference_number' => $student['reference_number']
                        ]
                    ];
                    
                default:
                    return [
                        'valid' => false,
                        'status' => 'unknown',
                        'message' => 'Unknown registration status.',
                        'icon' => 'question-circle',
                        'color' => 'secondary'
                    ];
            }
            
        } catch (Exception $e) {
            error_log("QR Validation Error: " . $e->getMessage());
            return [
                'valid' => false,
                'status' => 'error',
                'message' => 'Error validating QR code.',
                'icon' => 'exclamation-circle',
                'color' => 'danger'
            ];
        }
    }
    
    /**
     * Get QR code for a student
     */
    public function getQRCode($scheduleId) {
        $stmt = $this->conn->prepare("SELECT qr_code_path, reference_number, status FROM schedule_admission WHERE id = ?");
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Record not found'];
        }
        
        $record = $result->fetch_assoc();
        $stmt->close();
        
        if (empty($record['qr_code_path'])) {
            // Auto-generate if approved but no QR exists
            if ($record['status'] === 'Approved') {
                return $this->generateQRCode($scheduleId);
            }
            return ['success' => false, 'message' => 'QR code not generated yet'];
        }
        
        return [
            'success' => true,
            'qr_path' => $record['qr_code_path'],
            'qr_url' => base_url . $record['qr_code_path']
        ];
    }
    
    /**
     * Search student by reference number or LRN
     */
    public function searchStudent($searchTerm) {
        $searchTerm = '%' . $searchTerm . '%';
        $stmt = $this->conn->prepare("SELECT id, reference_number, surname, given_name, middle_name, lrn, status, phone, school_campus, date_scheduled, qr_code_path 
                                      FROM schedule_admission 
                                      WHERE reference_number LIKE ? OR lrn LIKE ? OR surname LIKE ? OR given_name LIKE ?
                                      ORDER BY created_at DESC LIMIT 20");
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
        
        return $students;
    }
}
?>

