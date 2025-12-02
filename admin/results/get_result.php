<?php
require_once __DIR__ . '/../inc/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
session_start();
if (!isset($_SESSION['userdata'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$scheduleId = (int)($_GET['id'] ?? 0);

if ($scheduleId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT exam_result, exam_score, exam_remarks FROM schedule_admission WHERE id = ?");
    $stmt->bind_param("i", $scheduleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Schedule not found']);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'exam_result' => $row['exam_result'],
        'exam_score' => $row['exam_score'],
        'exam_remarks' => $row['exam_remarks']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

