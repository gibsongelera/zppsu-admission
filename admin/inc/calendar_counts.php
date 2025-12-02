<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

try {
    // Auto-remove past bookings (strictly older than today)
    $conn->query("DELETE FROM schedule_admission WHERE DATE(date_scheduled) < CURDATE()");

    $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m'); // format YYYY-MM
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }

    $firstDay = $conn->real_escape_string($month . '-01');
    // Get last day of the requested month
    $lastDay = date('Y-m-t', strtotime($firstDay));

    $sql = "SELECT DATE(date_scheduled) AS d, COUNT(*) AS c 
            FROM schedule_admission 
            WHERE DATE(date_scheduled) BETWEEN '{$firstDay}' AND '{$lastDay}' 
            GROUP BY d";
    $res = $conn->query($sql);
    $counts = [];
    if ($res) {
        while($row = $res->fetch_assoc()){
            $counts[$row['d']] = (int)$row['c'];
        }
    }
    echo json_encode([
        'status' => 'success',
        'month' => $month,
        'counts' => $counts
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>


