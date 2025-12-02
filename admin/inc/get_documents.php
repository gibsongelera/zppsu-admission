<?php
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

$scheduleId = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;

if ($scheduleId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM document_uploads WHERE schedule_id = ? ORDER BY document_type");
    $stmt->bind_param("i", $scheduleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = [
            'id' => $row['id'],
            'document_type' => $row['document_type'],
            'file_name' => $row['file_name'],
            'file_path' => $row['file_path'],
            'uploaded_at' => $row['uploaded_at']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'documents' => $documents
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching documents: ' . $e->getMessage()
    ]);
}
?>

