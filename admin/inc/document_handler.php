<?php
/**
 * ZPPSU Admission System - Document Handler
 * Handles multiple document uploads for schedule applications
 */

class DocumentHandler {
    private $conn;
    private $uploadDir;
    
    // Allowed file types for each document category
    private $allowedTypes = [
        'Photo' => ['image/jpeg', 'image/jpg', 'image/png'],
        'Birth Certificate' => ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'],
        'Report Card' => ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'],
        'Good Moral' => ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'],
        'Other' => ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    ];
    
    // Max file size in bytes (5MB)
    private $maxFileSize = 5242880;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->uploadDir = realpath(__DIR__ . '/../../uploads');
        
        // Create uploads directory if it doesn't exist
        if ($this->uploadDir === false || !is_dir($this->uploadDir)) {
            $this->uploadDir = __DIR__ . '/../../uploads';
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0777, true);
            }
        }
    }
    
    /**
     * Handle multiple document uploads
     * @param int $scheduleId - The schedule admission ID
     * @param array $files - $_FILES array containing uploaded documents
     * @return array - Array with success status and messages
     */
    public function handleMultipleUploads($scheduleId, $files) {
        $results = [];
        $errors = [];
        $uploadedCount = 0;
        
        // Define document types mapping
        $documentTypes = [
            'photo' => 'Photo',
            'birth_certificate' => 'Birth Certificate',
            'report_card' => 'Report Card',
            'good_moral' => 'Good Moral',
            'other_document' => 'Other'
        ];
        
        foreach ($documentTypes as $fieldName => $documentType) {
            if (isset($files[$fieldName]) && $files[$fieldName]['error'] != UPLOAD_ERR_NO_FILE) {
                $result = $this->uploadSingleDocument($scheduleId, $files[$fieldName], $documentType);
                
                if ($result['success']) {
                    $uploadedCount++;
                    $results[] = $result['message'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        return [
            'success' => $uploadedCount > 0,
            'uploaded_count' => $uploadedCount,
            'messages' => $results,
            'errors' => $errors
        ];
    }
    
    /**
     * Upload a single document
     */
    private function uploadSingleDocument($scheduleId, $file, $documentType) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => "Upload error for {$documentType}: " . $this->getUploadErrorMessage($file['error'])];
        }
        
        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'message' => "{$documentType}: File size exceeds 5MB limit"];
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes[$documentType])) {
            return ['success' => false, 'message' => "{$documentType}: Invalid file type"];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = $documentType . '_' . $scheduleId . '_' . uniqid() . '.' . $extension;
        $filePath = $this->uploadDir . '/' . $uniqueName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => "{$documentType}: Failed to save file"];
        }
        
        // Save to database
        $stmt = $this->conn->prepare("INSERT INTO document_uploads (schedule_id, document_type, file_name, file_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $scheduleId, $documentType, $file['name'], $uniqueName);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => "{$documentType} uploaded successfully"];
        } else {
            // Delete uploaded file if database insert fails
            unlink($filePath);
            $stmt->close();
            return ['success' => false, 'message' => "{$documentType}: Database error"];
        }
    }
    
    /**
     * Get all documents for a schedule
     */
    public function getDocumentsBySchedule($scheduleId) {
        $stmt = $this->conn->prepare("SELECT * FROM document_uploads WHERE schedule_id = ? ORDER BY document_type, uploaded_at DESC");
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $documents = [];
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        
        $stmt->close();
        return $documents;
    }
    
    /**
     * Delete a document
     */
    public function deleteDocument($documentId) {
        // Get document info first
        $stmt = $this->conn->prepare("SELECT file_path FROM document_uploads WHERE id = ?");
        $stmt->bind_param("i", $documentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Document not found'];
        }
        
        $doc = $result->fetch_assoc();
        $stmt->close();
        
        // Delete from database
        $stmt = $this->conn->prepare("DELETE FROM document_uploads WHERE id = ?");
        $stmt->bind_param("i", $documentId);
        
        if ($stmt->execute()) {
            // Delete physical file
            $filePath = $this->uploadDir . '/' . $doc['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $stmt->close();
            return ['success' => true, 'message' => 'Document deleted successfully'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Failed to delete document'];
        }
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        return isset($errors[$errorCode]) ? $errors[$errorCode] : 'Unknown upload error';
    }
}
?>

