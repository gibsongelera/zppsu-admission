<?php
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/db_handler.php';
require_once __DIR__ . '/../inc/view_helper.php';

// Ensure connection is available and valid
if (!isset($conn) || $conn === null) {
    die('Database connection failed. Please check your configuration.');
}

// Check if connection is actually established
if (!$conn->isConnected()) {
    die('Database connection is not established. Please check your database settings.');
}

$db = new DatabaseHandler($conn);
// Filter by role: if student, restrict to own schedule records
$isStudent = isset($_SESSION['userdata']['role']) && (int)$_SESSION['userdata']['role'] === 3;
$studentEmail = $isStudent && !empty($_SESSION['userdata']['email']) ? $_SESSION['userdata']['email'] : '';
$studentPhone = $isStudent && !empty($_SESSION['userdata']['phone']) ? $_SESSION['userdata']['phone'] : '';
$studentFirst = $isStudent && !empty($_SESSION['userdata']['firstname']) ? $_SESSION['userdata']['firstname'] : '';
$studentMiddle = $isStudent && isset($_SESSION['userdata']['middlename']) ? $_SESSION['userdata']['middlename'] : '';
$studentLast = $isStudent && !empty($_SESSION['userdata']['lastname']) ? $_SESSION['userdata']['lastname'] : '';
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
        body { background: #f8f9fa; }
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
    </style>
</head>
<body>
    <main class="content">
        <div class="container-fluid p-0">
            <!-- Header section -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#5E0A14; color:white;">
                    <h1 class="h3 mb-3"><strong>ZPPSU</strong> SMS Log Tracker</h1>
                    <div class="ml-auto d-flex align-items-center">
                        <?php if (!$isStudent): // Only show export for Admins and Teachers ?>
                        <div class="btn-group mr-3" role="group">
                            <button type="button" class="btn btn-success btn-sm" onclick="exportToExcel()">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                        <?php endif; ?>
                        <form class="form-inline">
                            <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search" id="tableSearch">
                            <button class="btn btn-outline-light my-2 my-sm-0" type="button" id="searchBtn">Search</button>
                        </form>
                    </div>
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
                                <th>Exam Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                if ($isStudent) {
                                    $result = $db->getStudentRecords($studentEmail, $studentPhone, $studentFirst, $studentLast, $studentMiddle);
                                } else {
                                    $result = $db->getAllRecords();
                                }
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
                                        echo '<td>' . htmlspecialchars($row['document']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                                        // Status column with improved badges
                                        echo '<td class="status-cell">';
                                        $status = htmlspecialchars($row['status']);
                                        switch($status) {
                                            case 'Approved':
                                                echo '<span class="badge badge-success status-badge">Approved</span>';
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
                                        
                                        // Exam Result column
                                        echo '<td class="result-cell">';
                                        $examResult = htmlspecialchars($row['exam_result'] ?? 'Pending');
                                        switch($examResult) {
                                            case 'Pass':
                                                echo '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Pass</span>';
                                                if (!empty($row['exam_score'])) {
                                                    echo '<br><small class="text-muted">Score: ' . htmlspecialchars($row['exam_score']) . '</small>';
                                                }
                                                break;
                                            case 'Fail':
                                                echo '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Fail</span>';
                                                if (!empty($row['exam_score'])) {
                                                    echo '<br><small class="text-muted">Score: ' . htmlspecialchars($row['exam_score']) . '</small>';
                                                }
                                                break;
                                            case 'Pending':
                                            default:
                                                echo '<span class="badge badge-secondary"><i class="fas fa-hourglass-half"></i> Pending</span>';
                                        }
                                        if (!empty($row['exam_remarks'])) {
                                            echo '<br><small class="text-muted" title="' . htmlspecialchars($row['exam_remarks']) . '"><i class="fas fa-comment"></i> Remarks</small>';
                                        }
                                        echo '</td>';
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
    </main>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="/zppsu_admission/admin/teacher_log/scripts.js"></script>
    
    <script>
    var baseUrl = '<?php echo isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http'; ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/zppsu_admission/';
    
    // Export to Excel
    function exportToExcel() {
        window.location.href = baseUrl + 'admin/sms_log/export.php?format=excel';
    }
    
    // Export to PDF
    function exportToPDF() {
        window.location.href = baseUrl + 'admin/sms_log/export.php?format=pdf';
    }
    </script>
</body>
</html>