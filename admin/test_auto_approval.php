<?php
/**
 * Test script for auto-approval functionality
 * This script simulates a schedule submission to test auto-approval
 */

require_once __DIR__ . '/inc/db_connect.php';

echo "<h2>Auto-Approval Test</h2>";

// Test 1: Check current slot count for today
$today = date('Y-m-d');
$countQuery = "SELECT COUNT(*) AS cnt FROM schedule_admission WHERE DATE(date_scheduled) = DATE('$today')";
$result = $conn->query($countQuery);
$currentCount = $result ? $result->fetch_assoc()['cnt'] : 0;

echo "<p><strong>Current slots used for today ($today):</strong> $currentCount / 100</p>";

// Test 2: Simulate auto-approval logic
$autoApprove = ($currentCount < 100);
echo "<p><strong>Would auto-approve:</strong> " . ($autoApprove ? "YES" : "NO") . "</p>";

// Test 3: Show recent submissions
echo "<h3>Recent Submissions (Last 10)</h3>";
$recentQuery = "SELECT id, surname, given_name, date_scheduled, status, created_at, updated_at 
                FROM schedule_admission 
                ORDER BY created_at DESC 
                LIMIT 10";
$recentResult = $conn->query($recentQuery);

if ($recentResult && $recentResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Date Scheduled</th><th>Status</th><th>Created</th><th>Updated</th><th>Approved?</th></tr>";
    
    while ($row = $recentResult->fetch_assoc()) {
        $isAutoApproved = ($row['created_at'] == $row['updated_at'] || empty($row['updated_at']));
        $autoApprovedText = ($row['status'] == 'Approved' && $isAutoApproved) ? "YES" : "NO";
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['surname'] . ', ' . $row['given_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['date_scheduled']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "<td>" . htmlspecialchars($row['updated_at'] ?? 'NULL') . "</td>";
        echo "<td>" . $autoApprovedText . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No recent submissions found.</p>";
}

// Test 4: Show statistics
echo "<h3>Statistics</h3>";
$statsQuery = "SELECT 
    status,
    COUNT(*) as count,
    SUM(CASE WHEN created_at = updated_at OR updated_at IS NULL THEN 1 ELSE 0 END) as auto_approved
    FROM schedule_admission 
    GROUP BY status";
$statsResult = $conn->query($statsQuery);

if ($statsResult && $statsResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Status</th><th>Total Count</th><th>Approved</th><th>Manual</th></tr>";
    
    while ($row = $statsResult->fetch_assoc()) {
        $manual = $row['count'] - $row['auto_approved'];
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . $row['auto_approved'] . "</td>";
        echo "<td>" . $manual . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
