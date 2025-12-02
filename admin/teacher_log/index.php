<?php
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/db_handler.php';
require_once __DIR__ . '/../inc/view_helper.php';

$db = new DatabaseHandler($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ZPPSU SMS Analytics Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { 
            background: #f8f9fa; 
            font-size: 14px;
        }
        .tab-custom { margin-top: 30px; }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
        .dropdown-menu form {
            margin: 0;
            padding: 0;
        }
        .status-badge {
            font-size: 0.9em;
            padding: 0.5em 1em;
        }
        .action-dropdown .dropdown-menu {
            min-width: 200px;
            z-index: 1050;
        }
        .action-dropdown .dropdown-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        .action-dropdown .btn-block {
            margin: 2px 0;
        }
        .action-menu {
            padding: 8px 0;
        }
        .action-menu .btn {
            text-align: left;
            white-space: nowrap;
        }
        .action-menu .btn-sm {
            width: 100%;
            padding: .25rem .5rem;
        }
        .action-menu .dropdown-divider {
            margin: 0.5rem 0;
        }
        .update-status:hover {
            opacity: 0.9;
        }
        .action-dropdown {
            position: static;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            body {
                font-size: 12px;
            }
            
            .container-fluid {
                padding: 10px;
            }
            
            .card-header {
                padding: 10px 15px;
            }
            
            .card-header h1 {
                font-size: 1.2rem;
                margin-bottom: 10px;
            }
            
            .form-inline {
                flex-direction: column;
                width: 100%;
            }
            
            .form-inline .form-control {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .form-inline .btn {
                width: 100%;
            }
            
            .table-responsive {
                font-size: 11px;
            }
            
            .table th,
            .table td {
                padding: 5px 3px;
                vertical-align: middle;
            }
            
            .avatar {
                width: 25px;
                height: 25px;
            }
            
            .status-badge {
                font-size: 0.7em;
                padding: 0.3em 0.6em;
            }
            
            .btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.7rem;
            }
            
            .action-dropdown .dropdown-menu {
                min-width: 150px;
                font-size: 0.8rem;
            }
            
            .action-dropdown .dropdown-item {
                padding: 0.3rem 0.8rem;
                font-size: 0.8rem;
            }
            
            /* Hide some columns on mobile */
            .table th:nth-child(6),
            .table td:nth-child(6),
            .table th:nth-child(7),
            .table td:nth-child(7),
            .table th:nth-child(8),
            .table td:nth-child(8),
            .table th:nth-child(9),
            .table td:nth-child(9),
            .table th:nth-child(10),
            .table td:nth-child(10),
            .table th:nth-child(11),
            .table td:nth-child(11),
            .table th:nth-child(12),
            .table td:nth-child(12),
            .table th:nth-child(13),
            .table td:nth-child(13),
            .table th:nth-child(14),
            .table td:nth-child(14),
            .table th:nth-child(15),
            .table td:nth-child(15),
            .table th:nth-child(16),
            .table td:nth-child(16),
            .table th:nth-child(17),
            .table td:nth-child(17),
            .table th:nth-child(18),
            .table td:nth-child(18) {
                display: none;
            }
            
            /* Make name column wider on mobile */
            .table th:nth-child(2),
            .table td:nth-child(2) {
                min-width: 120px;
            }
            
            /* Make status column wider on mobile */
            .table th:nth-child(19),
            .table td:nth-child(19) {
                min-width: 80px;
            }
            
            /* Make actions column wider on mobile */
            .table th:nth-child(20),
            .table td:nth-child(20) {
                min-width: 100px;
            }
        }
        
        @media (max-width: 480px) {
            .card-header h1 {
                font-size: 1rem;
            }
            
            .table-responsive {
                font-size: 10px;
            }
            
            .table th,
            .table td {
                padding: 3px 2px;
            }
            
            .avatar {
                width: 20px;
                height: 20px;
            }
            
            .btn-sm {
                padding: 0.1rem 0.3rem;
                font-size: 0.6rem;
            }
            
            .action-dropdown .dropdown-menu {
                min-width: 120px;
                font-size: 0.7rem;
            }
        }
        
        /* Modal responsive */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 10px;
                max-width: calc(100% - 20px);
            }
            
            .modal-body {
                padding: 15px;
            }
            
            .modal-footer {
                padding: 10px 15px;
            }
        }
        
        /* Loading states */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        .table-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        
        .status-cell {
            transition: all 0.3s ease;
        }
        
        /* Enhanced notifications */
        .alert {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 8px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
        }
        
        /* Action buttons hover effects */
        .action-dropdown .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(2px);
            transition: all 0.2s ease;
        }
        
        .update-status:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
        
        .delete-record:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }
    </style>
</head>
<body>
    <main class="content">
        <div class="container-fluid p-0">
            <!-- Header section -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#5E0A14; color:white;">
                    <h1 class="h3 mb-3"><strong>ZPPSU</strong> SMS Log Tracker</h1>
                    <form class="form-inline ml-auto">
                        <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search" id="tableSearch">
                        <button class="btn btn-outline-light my-2 my-sm-0" type="button" id="searchBtn">Search</button>
                    </form>
                </div>

                <!-- Table section -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle text-center">
                        <!-- Table headers -->
                        <thead class="thead-maroon" style="background-color:#5E0A14; color:white;">
                            <tr>
                                <th>Photo Uploaded</th>
                                <th>Name Student</th>
                                <th>Gender</th>
                                <th>Age</th>
                                <th>Phone Number</th>
                                <th>Date of Birth</th>
                                <th>Address</th>
                                <th>Email Address</th>
                                <th>Application Type</th>
                                <th>Classification</th>
                                <th>Grade / Level</th>
                                <th>School Campus</th>
                                <th>Reference Number</th>
                                <th>LRN</th>
                                <th>Previous School</th>
                                <th>Date Scheduled</th>
                                <th>Time Slot</th>
                                <th>Room Number</th>
                                <th>Upload Document</th>
                                <th>Date Log</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $result = $db->getAllRecords();
                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $id = (int)$row['id'];
                                        echo '<tr data-id="' . $id . '">';
                                        echo '<td><img src="../uploads/' . htmlspecialchars($row['photo']) . '" class="avatar" alt="Profile"></td>';
                                        echo '<td>' . htmlspecialchars($row['surname']) . ', ' . htmlspecialchars($row['given_name']) . ' ' . htmlspecialchars($row['middle_name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['gender']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['age']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['phone']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['dob']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['address']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['application_type']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['classification']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['grade_level']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['school_campus']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['reference_number']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['lrn'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($row['previous_school'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($row['date_scheduled']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['time_slot'] ?? 'Not set') . '</td>';
                                        echo '<td>' . htmlspecialchars($row['room_number'] ?? 'Not assigned') . '</td>';
                                        
                                        // Upload Document - Show all documents from document_uploads table
                                        echo '<td>';
                                        $docStmt = $conn->prepare("SELECT * FROM document_uploads WHERE schedule_id = ? ORDER BY document_type");
                                        $docStmt->bind_param("i", $id);
                                        $docStmt->execute();
                                        $docResult = $docStmt->get_result();
                                        
                                        if ($docResult->num_rows > 0) {
                                            echo '<button type="button" class="btn btn-primary btn-sm" onclick="showAllDocuments(' . $id . ')">View All (' . $docResult->num_rows . ')</button>';
                                        } else {
                                            // Fallback to legacy document column
                                            if (!empty($row['document'])) {
                                                $docUrl = '../uploads/' . rawurlencode($row['document']);
                                                $ext = strtolower(pathinfo($row['document'], PATHINFO_EXTENSION));
                                                if (in_array($ext, ['pdf'])) {
                                                    echo '<button type="button" class="btn btn-info btn-sm" onclick="showPreview(\'' . $docUrl . '\', \'pdf\')">Preview</button>';
                                                } else if (in_array($ext, ['jpg','jpeg','png'])) {
                                                    echo '<button type="button" class="btn btn-info btn-sm" onclick="showPreview(\'' . $docUrl . '\', \'img\')">Preview</button>';
                                                } else {
                                                    echo '<a href="' . $docUrl . '" class="btn btn-secondary btn-sm" download>Download</a>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">No documents</span>';
                                            }
                                        }
                                        $docStmt->close();
                                        echo '</td>';
                                    echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                                    // Status column with improved badges
                                    echo '<td class="status-cell">';
                                    $status = htmlspecialchars($row['status']);
                                    switch($status) {
                                        case 'Approved':
                                            // Check if this was auto-approved (created and updated at same time)
                                            $isAutoApproved = ($row['created_at'] == $row['updated_at'] || empty($row['updated_at']));
                                            if($isAutoApproved) {
                                                echo '<span class="badge badge-success status-badge"><i class="fas fa-check mr-1"></i>Approved</span>';
                                            } else {
                                                echo '<span class="badge badge-success status-badge"><i class="fas fa-check mr-1"></i>Approved</span>';
                                            }
                                            break;
                                        case 'Pending':
                                            echo '<span class="badge badge-warning status-badge">Pending</span>';
                                            break;
                                        case 'Rejected':
                                            echo '<span class="badge badge-danger status-badge">Rejected</span>';
                                            break;
                                        default:
                                            echo '<span class="badge badge-secondary status-badge">Unknown</span>';
                                    }
                                    echo '</td>';
                                // Actions dropdown (fixed for Bootstrap)
                                    echo '<td>
                                        <div class="dropdown action-dropdown">
                                            <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="actionMenu'.$id.'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Actions
                                            </button>
                                            <div class="dropdown-menu action-menu" aria-labelledby="actionMenu'.$id.'">
                                                <button type="button" class="dropdown-item text-success font-weight-bold update-status" data-id="'.$id.'" data-status="Approved">
                                                    <i class="fas fa-check mr-2"></i> Approve
                                                </button>
                                                <button type="button" class="dropdown-item text-warning font-weight-bold update-status" data-id="'.$id.'" data-status="Pending">
                                                    <i class="fas fa-clock mr-2"></i> Pending
                                                </button>
                                                <button type="button" class="dropdown-item text-danger font-weight-bold update-status" data-id="'.$id.'" data-status="Rejected">
                                                    <i class="fas fa-times mr-2"></i> Reject
                                                </button>
                                                <div class="dropdown-divider"></div>
                                                <button type="button" class="dropdown-item text-primary font-weight-bold reschedule-appointment" data-id="'.$id.'" data-phone="'.htmlspecialchars($row['phone']).'" data-name="'.htmlspecialchars($row['surname'] . ', ' . $row['given_name'] . ' ' . $row['middle_name']).'" data-current-date="'.htmlspecialchars($row['date_scheduled']).'">
                                                    <i class="fas fa-calendar-alt mr-2"></i> Reschedule
                                                </button>
                                                <div class="dropdown-divider"></div>

                                                <a href="#" class="dropdown-item text-danger font-weight-bold delete-record" data-id="'.$id.'">
                                                    <i class="fas fa-trash mr-2"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="22" class="text-center">No records found.</td></tr>';
                            }
                        } catch (Exception $e) {
                            echo '<tr><td colspan="22" class="text-center text-danger">Error: Unable to fetch records. Please try again later.</td></tr>';
                            error_log($e->getMessage());
                        }
                    ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="/zppsu_admission/admin/teacher_log/scripts.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        <?php
            $res = $db->getAllRecords();
            $counts = [];
            if ($res) {
                while($row = $res->fetch_assoc()){
                    $k = date('Y-m-d', strtotime($row['date_scheduled']));
                    if (!isset($counts[$k])) $counts[$k] = 0;
                    $counts[$k]++;
                }
            }
            $countsJson = json_encode($counts);
        ?>
        var counts = <?php echo $countsJson ?> || {};
        var today = new Date();
        var y = today.getFullYear();
        var m = today.getMonth();
        var first = new Date(y, m, 1);
        var last = new Date(y, m+1, 0);
        var container = document.getElementById('teacher-calendar');
        if (!container) return;
        var html = '<div class="table-responsive"><table class="table table-bordered text-center"><thead><tr>'+
                   '<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>'+
                   '</tr></thead><tbody>';
        var d = 1, started = false;
        for (var r=0; r<6; r++){
            html += '<tr>';
            for (var c=0; c<7; c++){
                var cell = '';
                if (!started && c === first.getDay()) started = true;
                if (started && d <= last.getDate()){
                    var mm = (m+1).toString().padStart(2,'0');
                    var dd = d.toString().padStart(2,'0');
                    var key = y+'-'+mm+'-'+dd;
                    var cnt = counts[key] || 0;
                    var badgeClass = cnt >= 100 ? 'badge badge-danger' : (cnt > 0 ? 'badge badge-info' : 'badge badge-secondary');
                    cell = d + '<div><span class="'+badgeClass+'">'+cnt+' booked</span></div>';
                    d++;
                }
                html += '<td style="vertical-align:top;min-width:120px;min-height:80px">'+cell+'</td>';
            }
            html += '</tr>';
            if (d > last.getDate()) break;
        }
        html += '</tbody></table></div>';
        container.innerHTML = html;
    });
    </script>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Document Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="previewBody" style="min-height:400px;max-height:70vh;overflow:auto;"></div>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" role="dialog" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="rescheduleModalLabel">
                    <i class="fas fa-calendar-alt mr-2"></i>Reschedule Appointment
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Student:</strong> <span id="reschedule_student_name"></span><br>
                    <strong>Current Date:</strong> <span id="reschedule_current_date"></span>
                </div>
                <form id="rescheduleForm">
                    <input type="hidden" id="reschedule_id" name="id">
                    <input type="hidden" id="reschedule_phone" name="phone">
                    <input type="hidden" id="reschedule_name" name="name">
                    
                    <div class="form-group">
                        <label for="new_date_scheduled"><strong>New Schedule Date</strong> <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="new_date_scheduled" name="new_date_scheduled" required>
                        <small class="form-text text-muted">Only Saturdays and Sundays available</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="reschedule_reason">Reason for Rescheduling</label>
                        <textarea class="form-control" id="reschedule_reason" name="reason" rows="3" placeholder="Optional: Explain why this appointment is being rescheduled"></textarea>
                    </div>
                    
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input" id="send_sms_notification" name="send_sms" checked>
                        <label class="custom-control-label" for="send_sms_notification">
                            Send SMS notification to student
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmReschedule">
                    <i class="fas fa-check mr-1"></i>Confirm Reschedule
                </button>
            </div>
        </div>
    </div>
</div>
<script>
function showPreview(url, type) {
        var body = '';
        if (type === 'pdf') {
                body = '<iframe src="' + url + '" width="100%" height="500px" style="border:none;"></iframe>';
        } else if (type === 'img') {
                body = '<img src="' + url + '" style="max-width:100%;max-height:500px;display:block;margin:auto;" />';
        } else if (type === 'gdoc') {
                body = '<iframe src="' + url + '" width="100%" height="500px" style="border:none;"></iframe>';
        } else {
                body = '<div class="text-danger">Preview not available for this file type.</div>';
        }
        $('#previewBody').html(body);
        $('#previewModal').modal('show');
}

function showAllDocuments(scheduleId) {
        $.ajax({
                url: '/zppsu_admission/admin/inc/get_documents.php',
                method: 'GET',
                data: { schedule_id: scheduleId },
                dataType: 'json',
                success: function(response) {
                        if (response.success && response.documents.length > 0) {
                                var html = '<div class="list-group">';
                                response.documents.forEach(function(doc) {
                                        var docUrl = '../uploads/' + doc.file_path;
                                        var ext = doc.file_path.split('.').pop().toLowerCase();
                                        var icon = 'fa-file';
                                        if (['jpg','jpeg','png','gif'].indexOf(ext) !== -1) icon = 'fa-image';
                                        else if (ext === 'pdf') icon = 'fa-file-pdf';
                                        else if (['doc','docx'].indexOf(ext) !== -1) icon = 'fa-file-word';
                                        
                                        html += '<div class="list-group-item">';
                                        html += '<div class="d-flex justify-content-between align-items-center">';
                                        html += '<div><i class="fas ' + icon + ' mr-2"></i><strong>' + doc.document_type + '</strong><br><small class="text-muted">' + doc.file_name + '</small></div>';
                                        html += '<div>';
                                        if (['jpg','jpeg','png','gif'].indexOf(ext) !== -1) {
                                                html += '<button class="btn btn-sm btn-info mr-1" onclick="showPreview(\'' + docUrl + '\', \'img\')"><i class="fas fa-eye"></i> View</button>';
                                        } else if (ext === 'pdf') {
                                                html += '<button class="btn btn-sm btn-info mr-1" onclick="showPreview(\'' + docUrl + '\', \'pdf\')"><i class="fas fa-eye"></i> View</button>';
                                        }
                                        html += '<a href="' + docUrl + '" class="btn btn-sm btn-secondary" download><i class="fas fa-download"></i> Download</a>';
                                        html += '</div></div></div>';
                                });
                                html += '</div>';
                                $('#previewBody').html(html);
                                $('#previewModal').modal('show');
                        } else {
                                alert('No documents found');
                        }
                },
                error: function() {
                        alert('Error loading documents');
                }
        });
}
$(document).ready(function() {
        $('#tableSearch').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('table tbody tr').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
        });
        $('#searchBtn').on('click', function(e) {
                e.preventDefault();
                var value = $('#tableSearch').val().toLowerCase();
                $('table tbody tr').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
        });
        
        // Reschedule appointment handler
        $(document).on('click', '.reschedule-appointment', function(){
            var id = $(this).data('id');
            var phone = $(this).data('phone');
            var name = $(this).data('name');
            var currentDate = $(this).data('current-date');
            
            $('#reschedule_id').val(id);
            $('#reschedule_phone').val(phone);
            $('#reschedule_name').val(name);
            $('#reschedule_student_name').text(name);
            $('#reschedule_current_date').text(currentDate);
            
            // Set minimum date to tomorrow
            var today = new Date();
            var tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            $('#new_date_scheduled').attr('min', tomorrow.toISOString().split('T')[0]);
            
            // Set maximum date to 3 months from now
            var maxDate = new Date(today);
            maxDate.setMonth(maxDate.getMonth() + 3);
            $('#new_date_scheduled').attr('max', maxDate.toISOString().split('T')[0]);
            
            // Find next weekend
            function getNextWeekend(date) {
                var day = date.getDay();
                var daysUntilSaturday = (6 - day + 7) % 7;
                if (daysUntilSaturday === 0 && day !== 6) {
                    daysUntilSaturday = 7;
                }
                if (daysUntilSaturday === 0) return date;
                var nextWeekend = new Date(date);
                nextWeekend.setDate(date.getDate() + daysUntilSaturday);
                return nextWeekend;
            }
            
            // Set default to next Saturday
            var nextWeekend = getNextWeekend(tomorrow);
            $('#new_date_scheduled').val(nextWeekend.toISOString().split('T')[0]);
            
            $('#rescheduleModal').modal('show');
        });
        
        // Validate weekend selection
        $('#new_date_scheduled').on('change', function(){
            var selectedDate = new Date(this.value + 'T00:00:00');
            var dayOfWeek = selectedDate.getDay();
            
            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                alert('Please select a Saturday or Sunday only. Entrance exams are only available on weekends.');
                
                // Auto-correct to next weekend
                function getNextWeekend(date) {
                    var day = date.getDay();
                    var daysUntilSaturday = (6 - day + 7) % 7;
                    if (daysUntilSaturday === 0 && day !== 6) {
                        daysUntilSaturday = 7;
                    }
                    if (daysUntilSaturday === 0) return date;
                    var nextWeekend = new Date(date);
                    nextWeekend.setDate(date.getDate() + daysUntilSaturday);
                    return nextWeekend;
                }
                
                var correctedDate = getNextWeekend(selectedDate);
                this.value = correctedDate.toISOString().split('T')[0];
            }
        });
        
        // Confirm reschedule
        $('#confirmReschedule').on('click', function(){
            var id = $('#reschedule_id').val();
            var newDate = $('#new_date_scheduled').val();
            var phone = $('#reschedule_phone').val();
            var name = $('#reschedule_name').val();
            var reason = $('#reschedule_reason').val();
            var sendSms = $('#send_sms_notification').is(':checked');
            
            if (!newDate) {
                alert('Please select a new date');
                return;
            }
            
            // Show loading
            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span>Processing...');
            
            $.ajax({
                url: '/zppsu_admission/admin/teacher_log/reschedule.php',
                method: 'POST',
                data: {
                    id: id,
                    new_date_scheduled: newDate,
                    phone: phone,
                    name: name,
                    reason: reason,
                    send_sms: sendSms ? '1' : '0'
                },
                dataType: 'json',
                success: function(response){
                    if (response.success) {
                        showNotification(response.message || 'Appointment rescheduled successfully!', 'success');
                        $('#rescheduleModal').modal('hide');
                        
                        // Reload page after 2 seconds
                        setTimeout(function(){
                            location.reload();
                        }, 2000);
                    } else {
                        showNotification(response.message || 'Failed to reschedule appointment', 'error');
                        $('#confirmReschedule').prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Confirm Reschedule');
                    }
                },
                error: function(){
                    showNotification('Network error. Please try again.', 'error');
                    $('#confirmReschedule').prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Confirm Reschedule');
                }
            });
        });
        
        // Reset form when modal closes
        $('#rescheduleModal').on('hidden.bs.modal', function(){
            $('#rescheduleForm')[0].reset();
            $('#confirmReschedule').prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Confirm Reschedule');
        });
});
</script></body>
</html>
