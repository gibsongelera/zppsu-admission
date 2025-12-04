<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/bulk_reschedule_handler.php';

// Check if user has permission (admin/staff only)
if (!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['role'], [1, 2])) {
    header('Location: ' . base_url . 'admin/login.php');
    exit;
}

$handler = new BulkRescheduleHandler($conn);
$scheduledDates = $handler->getScheduledDates();
$campuses = $handler->getCampuses();
$history = $handler->getBulkRescheduleHistory();

$message = null;
$messageType = null;
$previewData = null;

// Handle preview request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preview') {
    $oldDate = $_POST['old_date'] ?? '';
    $campus = !empty($_POST['campus']) ? $_POST['campus'] : null;
    $timeSlot = !empty($_POST['time_slot']) ? $_POST['time_slot'] : null;
    
    if (empty($oldDate)) {
        $message = 'Please select a date to reschedule';
        $messageType = 'danger';
    } else {
        $previewData = $handler->previewBulkReschedule($oldDate, $campus, $timeSlot);
        $previewData['old_date'] = $oldDate;
        $previewData['campus'] = $campus;
        $previewData['time_slot'] = $timeSlot;
    }
}

// Handle bulk reschedule request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reschedule') {
    $oldDate = $_POST['old_date'] ?? '';
    $newDate = $_POST['new_date'] ?? '';
    $campus = !empty($_POST['campus']) ? $_POST['campus'] : null;
    $timeSlot = !empty($_POST['time_slot']) ? $_POST['time_slot'] : null;
    $reason = $_POST['reason'] ?? 'Administrative Reschedule';
    $sendSms = isset($_POST['send_sms']);
    $userId = $_SESSION['userdata']['id'] ?? null;
    
    if (empty($oldDate) || empty($newDate)) {
        $message = 'Please select both original and new dates';
        $messageType = 'danger';
    } else {
        $result = $handler->bulkReschedule($oldDate, $newDate, $campus, $timeSlot, $reason, $userId, $sendSms);
        
        if ($result['success']) {
            $message = "Successfully rescheduled {$result['success_count']} of {$result['total_students']} students. ";
            if ($sendSms) {
                $message .= "SMS sent: {$result['sms_sent']}, Failed: {$result['sms_failed']}";
            }
            $messageType = 'success';
            
            // Refresh data
            $scheduledDates = $handler->getScheduledDates();
            $history = $handler->getBulkRescheduleHistory();
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bulk Reschedule - ZPPSU Admission</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --brand: #5E0A14;
            --brand-light: #8B1A2B;
        }
        body { background: #f4f6f9; }
        .card-header-brand { background: var(--brand); color: white; }
        .date-card {
            border-left: 4px solid var(--brand);
            transition: all 0.2s;
            cursor: pointer;
        }
        .date-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .date-card.selected {
            border-left-color: #28a745;
            background: #f0fff4;
        }
        .stats-card {
            border-radius: 10px;
            overflow: hidden;
        }
        .stats-card .icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .preview-table {
            max-height: 300px;
            overflow-y: auto;
        }
        .reason-preset {
            cursor: pointer;
            margin: 2px;
        }
        .reason-preset:hover {
            background: var(--brand);
            color: white;
        }
        .weekend-only {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-body card-header-brand">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><i class="fas fa-calendar-alt"></i> Bulk Reschedule</h3>
                        <small>Mass reschedule for calamity/emergency situations</small>
                    </div>
                    <a href="<?php echo base_url ?>admin/?page=<?php echo $_SESSION['userdata']['role'] == 1 ? 'admin/' : 'staff/'; ?>" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Left: Schedule Selection -->
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header card-header-brand">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Upcoming Schedules</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($scheduledDates)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> No upcoming scheduled exams found.
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <small class="text-muted">Click on a date to select it for rescheduling</small>
                        </div>
                        <?php 
                        $groupedDates = [];
                        foreach ($scheduledDates as $d) {
                            $date = $d['date_scheduled'];
                            if (!isset($groupedDates[$date])) {
                                $groupedDates[$date] = ['count' => 0, 'campuses' => []];
                            }
                            $groupedDates[$date]['count'] += $d['student_count'];
                            $groupedDates[$date]['campuses'][] = $d['school_campus'] . ' (' . $d['student_count'] . ')';
                        }
                        
                        foreach ($groupedDates as $date => $info): 
                            $dayName = date('l', strtotime($date));
                            $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);
                        ?>
                        <div class="card date-card mb-2" data-date="<?php echo $date; ?>" onclick="selectDate('<?php echo $date; ?>')">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-day text-<?php echo $isWeekend ? 'success' : 'warning'; ?>"></i>
                                            <?php echo date('F d, Y', strtotime($date)); ?>
                                            <small class="text-muted">(<?php echo $dayName; ?>)</small>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo implode(', ', $info['campuses']); ?>
                                        </small>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-primary badge-lg" style="font-size:1.1rem;">
                                            <?php echo $info['count']; ?>
                                        </span>
                                        <br><small class="text-muted">students</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- History -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Bulk Reschedules</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($history)): ?>
                        <div class="p-3 text-muted text-center">No bulk reschedules yet</div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($history, 0, 5) as $h): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo date('M d', strtotime($h['old_date'])); ?></strong>
                                        → <strong><?php echo date('M d, Y', strtotime($h['new_date'])); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $h['reason'] ?: 'No reason specified'; ?>
                                        </small>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-success"><?php echo $h['success_count']; ?>/<?php echo $h['total_affected']; ?></span>
                                        <br>
                                        <small class="text-muted">
                                            by <?php echo htmlspecialchars($h['firstname'] . ' ' . $h['lastname']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right: Reschedule Form -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header card-header-brand">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Reschedule Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="rescheduleForm">
                            <input type="hidden" name="action" id="formAction" value="preview">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Original Date</strong> <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="old_date" id="old_date" required>
                                        <small class="text-muted">Select from the list or enter manually</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>New Date</strong> <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="new_date" id="new_date">
                                        <small class="text-muted weekend-only">
                                            <i class="fas fa-info-circle"></i> Weekends (Sat/Sun) recommended
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Filter by Campus</strong></label>
                                        <select class="form-control" name="campus" id="campus">
                                            <option value="">All Campuses</option>
                                            <?php foreach ($campuses as $c): ?>
                                            <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Filter by Time Slot</strong></label>
                                        <select class="form-control" name="time_slot" id="time_slot">
                                            <option value="">All Time Slots</option>
                                            <option value="Morning (8AM-12PM)">Morning (8AM-12PM)</option>
                                            <option value="Afternoon (1PM-5PM)">Afternoon (1PM-5PM)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Reason for Rescheduling</strong> <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="reason" id="reason" rows="2" required placeholder="Enter reason..."></textarea>
                                <div class="mt-2">
                                    <small class="text-muted">Quick select:</small>
                                    <button type="button" class="btn btn-sm btn-outline-secondary reason-preset" onclick="setReason('Typhoon/Weather Disturbance')">🌀 Typhoon</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary reason-preset" onclick="setReason('Earthquake')">🌍 Earthquake</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary reason-preset" onclick="setReason('Flooding')">🌊 Flooding</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary reason-preset" onclick="setReason('Power Outage')">💡 Power Outage</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary reason-preset" onclick="setReason('Facility Maintenance')">🔧 Maintenance</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary reason-preset" onclick="setReason('Public Holiday')">📅 Holiday</button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="send_sms" name="send_sms" checked>
                                    <label class="custom-control-label" for="send_sms">
                                        <i class="fas fa-sms text-primary"></i> Send SMS notifications to all affected students
                                    </label>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-info mr-2" onclick="previewReschedule()">
                                    <i class="fas fa-eye"></i> Preview Affected Students
                                </button>
                                <button type="button" class="btn btn-danger" onclick="confirmReschedule()" id="btnReschedule" disabled>
                                    <i class="fas fa-calendar-alt"></i> Execute Bulk Reschedule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Preview Results -->
                <?php if ($previewData): ?>
                <div class="card mt-4" id="previewCard">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-users"></i> Preview: <?php echo $previewData['total_students']; ?> Students Affected
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Time Slot Summary -->
                        <div class="row mb-3">
                            <?php foreach ($previewData['time_slot_summary'] as $slot => $count): ?>
                            <div class="col-md-6">
                                <div class="alert alert-secondary mb-2">
                                    <strong><?php echo htmlspecialchars($slot ?: 'Not Specified'); ?>:</strong> 
                                    <?php echo $count; ?> students
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Student List -->
                        <div class="preview-table">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Reference #</th>
                                        <th>Campus</th>
                                        <th>Time Slot</th>
                                        <th>Phone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($previewData['students'], 0, 50) as $s): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['surname'] . ', ' . $s['given_name']); ?></td>
                                        <td><small><?php echo htmlspecialchars($s['reference_number']); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($s['school_campus']); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($s['time_slot'] ?? 'TBA'); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($s['phone']); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if ($previewData['total_students'] > 50): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            ... and <?php echo $previewData['total_students'] - 50; ?> more students
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> This action will reschedule all <?php echo $previewData['total_students']; ?> students 
                            and cannot be undone. Make sure the new date and settings are correct.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    // Set minimum date for new date input
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('new_date').min = tomorrow.toISOString().split('T')[0];
    
    function selectDate(date) {
        // Update visual selection
        document.querySelectorAll('.date-card').forEach(c => c.classList.remove('selected'));
        document.querySelector('.date-card[data-date="'+date+'"]').classList.add('selected');
        
        // Set form value
        document.getElementById('old_date').value = date;
        document.getElementById('btnReschedule').disabled = true;
    }
    
    function setReason(reason) {
        document.getElementById('reason').value = reason;
    }
    
    function previewReschedule() {
        var oldDate = document.getElementById('old_date').value;
        if (!oldDate) {
            alert('Please select a date to preview');
            return;
        }
        document.getElementById('formAction').value = 'preview';
        document.getElementById('rescheduleForm').submit();
    }
    
    function confirmReschedule() {
        var oldDate = document.getElementById('old_date').value;
        var newDate = document.getElementById('new_date').value;
        var reason = document.getElementById('reason').value;
        
        if (!oldDate || !newDate) {
            alert('Please select both original and new dates');
            return;
        }
        
        if (!reason) {
            alert('Please enter a reason for rescheduling');
            return;
        }
        
        // Validate weekend
        var newDateObj = new Date(newDate + 'T00:00:00');
        var dayOfWeek = newDateObj.getDay();
        var isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
        
        var confirmMsg = 'Are you sure you want to bulk reschedule ALL students from ' + oldDate + ' to ' + newDate + '?';
        if (!isWeekend) {
            confirmMsg += '\n\nWARNING: The new date is NOT a weekend. Are you sure?';
        }
        confirmMsg += '\n\nThis action cannot be undone.';
        
        if (confirm(confirmMsg)) {
            document.getElementById('formAction').value = 'reschedule';
            document.getElementById('rescheduleForm').submit();
        }
    }
    
    // Enable reschedule button when preview is shown
    <?php if ($previewData): ?>
    document.getElementById('btnReschedule').disabled = false;
    document.getElementById('old_date').value = '<?php echo $previewData['old_date']; ?>';
    <?php if ($previewData['campus']): ?>
    document.getElementById('campus').value = '<?php echo addslashes($previewData['campus']); ?>';
    <?php endif; ?>
    <?php if ($previewData['time_slot']): ?>
    document.getElementById('time_slot').value = '<?php echo addslashes($previewData['time_slot']); ?>';
    <?php endif; ?>
    <?php endif; ?>
    </script>
</body>
</html>

