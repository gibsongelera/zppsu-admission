<?php
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/reschedule_handler.php';
require_once __DIR__ . '/../inc/room_handler.php';

session_start();

$scheduleId = (int)($_GET['id'] ?? 0);
$rescheduleHandler = new RescheduleHandler($conn);
$roomHandler = new RoomHandler($conn);

// Check if user has permission (admin/staff only)
if (!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['role'], [1, 2])) {
    die('Unauthorized access');
}

// Get schedule details
$stmt = $conn->prepare("SELECT * FROM schedule_admission WHERE id = ?");
$stmt->bind_param("i", $scheduleId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Schedule not found');
}

$schedule = $result->fetch_assoc();
$stmt->close();

// Check if can reschedule
$canReschedule = $rescheduleHandler->canReschedule($scheduleId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDate = $_POST['new_date'] ?? '';
    $newTimeSlot = $_POST['new_time_slot'] ?? '';
    $newRoom = $_POST['new_room'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $rescheduledBy = $_SESSION['userdata']['id'] ?? null;
    
    $result = $rescheduleHandler->rescheduleApplication($scheduleId, $newDate, $newTimeSlot, $newRoom, $reason, $rescheduledBy);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
        // Refresh schedule data
        $stmt = $conn->prepare("SELECT * FROM schedule_admission WHERE id = ?");
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $schedule = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $message = $result['message'];
        $messageType = 'danger';
    }
}

// Get reschedule history
$history = $rescheduleHandler->getRescheduleHistory($scheduleId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reschedule Application</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $messageType ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message) ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header" style="background-color:#5E0A14; color:white;">
                <h3 class="mb-0">
                    <i class="fas fa-calendar-alt"></i> Reschedule Application
                </h3>
            </div>
            <div class="card-body">
                <!-- Current Schedule Info -->
                <div class="alert alert-info">
                    <h5><strong>Current Schedule</strong></h5>
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($schedule['surname'] . ', ' . $schedule['given_name']) ?></p>
                    <p class="mb-1"><strong>Ref #:</strong> <?php echo htmlspecialchars($schedule['reference_number']) ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?php echo date('F d, Y', strtotime($schedule['date_scheduled'])) ?></p>
                    <p class="mb-1"><strong>Time:</strong> <?php echo htmlspecialchars($schedule['time_slot'] ?? 'TBA') ?></p>
                    <p class="mb-1"><strong>Room:</strong> <?php echo htmlspecialchars($schedule['room_number'] ?? 'TBA') ?></p>
                    <p class="mb-0"><strong>Campus:</strong> <?php echo htmlspecialchars($schedule['school_campus']) ?></p>
                </div>
                
                <?php if (!$canReschedule['can_reschedule']): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($canReschedule['reason']) ?>
                </div>
                <?php else: ?>
                <!-- Reschedule Form -->
                <form method="POST" id="rescheduleForm">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>New Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="new_date" id="new_date" required>
                            <small class="form-text text-muted">Must be a future weekend (Saturday/Sunday)</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label>New Time Slot <span class="text-danger">*</span></label>
                            <select class="form-control" name="new_time_slot" id="new_time_slot" required>
                                <option value="">Select Time</option>
                                <option value="Morning (8AM-12PM)">Morning (8AM-12PM)</option>
                                <option value="Afternoon (1PM-5PM)">Afternoon (1PM-5PM)</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>New Room <span class="text-danger">*</span></label>
                            <select class="form-control" name="new_room" id="new_room" required>
                                <option value="">Select room first</option>
                            </select>
                            <small id="room_info" class="form-text text-muted"></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Reason for Rescheduling <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="Enter reason for rescheduling"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Confirm Reschedule
                    </button>
                    <a href="<?php echo base_url ?>admin/?page=sms_log" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
                <?php endif; ?>
                
                <!-- Reschedule History -->
                <?php if (!empty($history)): ?>
                <hr class="my-4">
                <h5><strong>Reschedule History</strong></h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead style="background-color:#5E0A14; color:white;">
                            <tr>
                                <th>Date</th>
                                <th>Old Schedule</th>
                                <th>New Schedule</th>
                                <th>Reason</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($h['rescheduled_at'])) ?></td>
                                <td>
                                    <small>
                                        <?php echo date('M d, Y', strtotime($h['old_date'])) ?><br>
                                        <?php echo htmlspecialchars($h['old_time_slot'] ?? 'TBA') ?><br>
                                        <?php echo htmlspecialchars($h['old_room'] ?? 'TBA') ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('M d, Y', strtotime($h['new_date'])) ?><br>
                                        <?php echo htmlspecialchars($h['new_time_slot'] ?? 'TBA') ?><br>
                                        <?php echo htmlspecialchars($h['new_room'] ?? 'TBA') ?>
                                    </small>
                                </td>
                                <td><small><?php echo htmlspecialchars($h['reason'] ?? 'N/A') ?></small></td>
                                <td><small><?php echo htmlspecialchars($h['firstname'] . ' ' . $h['lastname']) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    var campus = '<?php echo addslashes($schedule['school_campus']) ?>';
    var baseUrl = '<?php echo base_url ?>';
    
    // Set minimum date to tomorrow
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    $('#new_date').attr('min', tomorrow.toISOString().split('T')[0]);
    
    // Load available rooms when date and time are selected
    function loadAvailableRooms() {
        var date = $('#new_date').val();
        var timeSlot = $('#new_time_slot').val();
        
        if (!date || !timeSlot) {
            $('#new_room').html('<option value="">Select date and time first</option>');
            return;
        }
        
        // Validate weekend
        var selectedDate = new Date(date + 'T00:00:00');
        var dayOfWeek = selectedDate.getDay();
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            alert('Please select a weekend date (Saturday or Sunday)');
            $('#new_date').val('');
            return;
        }
        
        $.get(baseUrl + 'admin/inc/get_available_rooms.php', {
            date: date,
            time_slot: timeSlot,
            campus: campus
        }, function(data) {
            if (data.success && data.rooms.length > 0) {
                var html = '<option value="">Select Room</option>';
                data.rooms.forEach(function(room) {
                    html += '<option value="' + room.room_number + '">' + room.room_number + ' (Available: ' + room.available_slots + '/' + room.capacity + ')</option>';
                });
                $('#new_room').html(html);
                $('#room_info').text(data.rooms.length + ' room(s) available');
            } else {
                $('#new_room').html('<option value="">No rooms available</option>');
                $('#room_info').text('No available rooms for selected date/time');
            }
        }, 'json').fail(function() {
            $('#new_room').html('<option value="">Error loading rooms</option>');
        });
    }
    
    $('#new_date, #new_time_slot').on('change', loadAvailableRooms);
    </script>
</body>
</html>

