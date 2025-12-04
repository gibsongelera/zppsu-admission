<?php
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/db_handler.php';

// Ensure connection is available
if (!isset($conn) || $conn === null) {
    die('Database connection failed. Please check your configuration.');
}

$db = new DatabaseHandler($conn);

// Get filter parameters
$filterDate = $_GET['filter_date'] ?? '';
$filterCampus = $_GET['filter_campus'] ?? '';
$filterTimeSlot = $_GET['filter_time_slot'] ?? '';
$filterRoom = $_GET['filter_room'] ?? '';
$filterStatus = $_GET['filter_status'] ?? 'Approved'; // Default to Approved

// Build query with filters (compatible with both MySQL and PostgreSQL)
$sql = "SELECT * FROM schedule_admission WHERE status != 'Rejected'";
$params = [];
$types = '';

if (!empty($filterDate)) {
    $sql .= " AND DATE(date_scheduled) = ?";
    $params[] = $filterDate;
    $types .= 's';
}

if (!empty($filterCampus)) {
    $sql .= " AND school_campus = ?";
    $params[] = $filterCampus;
    $types .= 's';
}

if (!empty($filterTimeSlot)) {
    $sql .= " AND time_slot = ?";
    $params[] = $filterTimeSlot;
    $types .= 's';
}

if (!empty($filterRoom)) {
    $sql .= " AND room_number = ?";
    $params[] = $filterRoom;
    $types .= 's';
}

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

$sql .= " ORDER BY date_scheduled, time_slot, room_number";

// Use prepared statement with proper parameter binding
$stmt = $conn->prepare($sql);
if ($stmt && !empty($params)) {
    // Bind parameters one by one for compatibility
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));
    $stmt->execute();
    $result = $stmt->get_result();
} else if ($stmt) {
    // No parameters, just execute
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Fallback to direct query if prepare fails
    error_log("Prepare failed, using direct query");
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exam Results Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .table-scroll { max-height: 600px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="card mt-4">
            <div class="card-header" style="background-color:#5E0A14; color:white;">
                <h3 class="mb-0">
                    <i class="fas fa-clipboard-check"></i> Exam Results Management
                </h3>
            </div>
            <div class="card-body">
                <!-- Filter Section -->
                <form method="GET" class="form-inline mb-4">
                    <div class="form-group mr-2">
                        <label class="mr-2">Date:</label>
                        <input type="date" class="form-control" name="filter_date" value="<?php echo htmlspecialchars($filterDate) ?>">
                    </div>
                    <div class="form-group mr-2">
                        <label class="mr-2">Campus:</label>
                        <select class="form-control" name="filter_campus">
                            <option value="">All Campuses</option>
                            <option value="ZPPSU MAIN" <?php echo $filterCampus === 'ZPPSU MAIN' ? 'selected' : '' ?>>ZPPSU MAIN</option>
                            <option value="Gregorio Campus (Vitali)" <?php echo $filterCampus === 'Gregorio Campus (Vitali)' ? 'selected' : '' ?>>Gregorio Campus (Vitali)</option>
                            <option value="ZPPSU Campus (Kabasalan)" <?php echo $filterCampus === 'ZPPSU Campus (Kabasalan)' ? 'selected' : '' ?>>ZPPSU Campus (Kabasalan)</option>
                            <option value="Anna Banquial Campus (Malangas)" <?php echo $filterCampus === 'Anna Banquial Campus (Malangas)' ? 'selected' : '' ?>>Anna Banquial Campus (Malangas)</option>
                            <option value="Timuay Tubod M. Mandi Campus (Siay)" <?php echo $filterCampus === 'Timuay Tubod M. Mandi Campus (Siay)' ? 'selected' : '' ?>>Timuay Tubod M. Mandi Campus (Siay)</option>
                            <option value="ZPPSU Campus (Bayog)" <?php echo $filterCampus === 'ZPPSU Campus (Bayog)' ? 'selected' : '' ?>>ZPPSU Campus (Bayog)</option>
                        </select>
                    </div>
                    <div class="form-group mr-2">
                        <label class="mr-2">Time Slot:</label>
                        <select class="form-control" name="filter_time_slot">
                            <option value="">All Times</option>
                            <option value="Morning (8AM-12PM)" <?php echo $filterTimeSlot === 'Morning (8AM-12PM)' ? 'selected' : '' ?>>Morning (8AM-12PM)</option>
                            <option value="Afternoon (1PM-5PM)" <?php echo $filterTimeSlot === 'Afternoon (1PM-5PM)' ? 'selected' : '' ?>>Afternoon (1PM-5PM)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="?page=results" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
                
                <!-- Results Table -->
                <div class="table-responsive table-scroll">
                    <table class="table table-bordered table-striped table-hover">
                        <thead style="background-color:#5E0A14; color:white; position: sticky; top: 0;">
                            <tr>
                                <th>Ref #</th>
                                <th>Name</th>
                                <th>Program</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Campus</th>
                                <th>Result</th>
                                <th>Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="10" class="text-center">No records found</td>
                            </tr>
                            <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['reference_number']) ?></td>
                                <td><?php echo htmlspecialchars($row['surname'] . ', ' . $row['given_name']) ?></td>
                                <td><small><?php echo htmlspecialchars($row['classification']) ?></small></td>
                                <td><?php echo date('M d, Y', strtotime($row['date_scheduled'])) ?></td>
                                <td><?php echo htmlspecialchars($row['time_slot'] ?? 'TBA') ?></td>
                                <td><?php echo htmlspecialchars($row['room_number'] ?? 'TBA') ?></td>
                                <td><small><?php echo htmlspecialchars($row['school_campus']) ?></small></td>
                                <td>
                                    <?php
                                    $result_val = $row['exam_result'] ?? 'Pending';
                                    $badge = 'secondary';
                                    if ($result_val === 'Pass') $badge = 'success';
                                    elseif ($result_val === 'Fail') $badge = 'danger';
                                    ?>
                                    <span class="badge badge-<?php echo $badge ?>"><?php echo $result_val ?></span>
                                </td>
                                <td><?php echo $row['exam_score'] ? number_format($row['exam_score'], 2) : '-' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="updateResult(<?php echo $row['id'] ?>, '<?php echo htmlspecialchars($row['surname'] . ', ' . $row['given_name']) ?>')">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Result Modal -->
    <div class="modal fade" id="updateResultModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#5E0A14; color:white;">
                    <h5 class="modal-title">Update Exam Result</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="resultForm">
                        <input type="hidden" id="schedule_id" name="schedule_id">
                        <div class="form-group">
                            <label><strong>Student Name:</strong></label>
                            <p id="student_name"></p>
                        </div>
                        <div class="form-group">
                            <label>Exam Result <span class="text-danger">*</span></label>
                            <select class="form-control" id="exam_result" name="exam_result" required>
                                <option value="Pending">Pending</option>
                                <option value="Pass">Pass</option>
                                <option value="Fail">Fail</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Exam Score (Optional)</label>
                            <input type="number" class="form-control" id="exam_score" name="exam_score" min="0" max="100" step="0.01" placeholder="e.g., 85.50">
                            <small class="form-text text-muted">Enter score out of 100</small>
                        </div>
                        <div class="form-group">
                            <label>Remarks (Optional)</label>
                            <textarea class="form-control" id="exam_remarks" name="exam_remarks" rows="3" placeholder="Enter any remarks or comments"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitResult()">Save Result</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    var baseUrl = '<?php echo base_url ?>';
    
    function updateResult(scheduleId, studentName) {
        $('#schedule_id').val(scheduleId);
        $('#student_name').text(studentName);
        
        // Load current values
        $.get(baseUrl + 'admin/results/get_result.php?id=' + scheduleId, function(data) {
            if (data.success) {
                $('#exam_result').val(data.exam_result || 'Pending');
                $('#exam_score').val(data.exam_score || '');
                $('#exam_remarks').val(data.exam_remarks || '');
            }
        }, 'json');
        
        $('#updateResultModal').modal('show');
    }
    
    function submitResult() {
        var formData = {
            schedule_id: $('#schedule_id').val(),
            exam_result: $('#exam_result').val(),
            exam_score: $('#exam_score').val(),
            exam_remarks: $('#exam_remarks').val()
        };
        
        $.post(baseUrl + 'admin/results/update_result.php', formData, function(response) {
            if (response.success) {
                alert('Result updated successfully!');
                location.reload();
            } else {
                alert('Failed to update result: ' + (response.message || 'Unknown error'));
            }
        }, 'json').fail(function() {
            alert('Network error. Please try again.');
        });
    }
    </script>
</body>
</html>

