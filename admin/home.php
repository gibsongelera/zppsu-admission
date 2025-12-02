<?php
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/db_handler.php';
require_once __DIR__ . '/../inc/view_helper.php';

$db = new DatabaseHandler($conn);
// --- Metrics computation ---
// Dates
$today = new DateTime();
$weekStart = (new DateTime())->modify('monday this week');
$weekEnd = (new DateTime())->modify('sunday this week');
$lastWeekStart = (clone $weekStart)->modify('-7 days');
$lastWeekEnd = (clone $weekEnd)->modify('-7 days');

// Totals
$totalSend = $db->getScheduleCount();
$thisWeekSend = $db->getScheduleCountInRange($weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
$lastWeekSend = $db->getScheduleCountInRange($lastWeekStart->format('Y-m-d'), $lastWeekEnd->format('Y-m-d'));
$thisWeekPctOfTotal = $totalSend > 0 ? round(($thisWeekSend / $totalSend) * 100) : 0;
$wowSend = $lastWeekSend > 0 ? round((($thisWeekSend - $lastWeekSend) / $lastWeekSend) * 100) : ($thisWeekSend > 0 ? 100 : 0);

// Status-based
$totalSuccess = $db->getScheduleCount('Approved');
$thisWeekSuccess = $db->getScheduleCountInRange($weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'), 'Approved');
$lastWeekSuccess = $db->getScheduleCountInRange($lastWeekStart->format('Y-m-d'), $lastWeekEnd->format('Y-m-d'), 'Approved');
$wowSuccess = $lastWeekSuccess > 0 ? round((($thisWeekSuccess - $lastWeekSuccess) / $lastWeekSuccess) * 100) : ($thisWeekSuccess > 0 ? 100 : 0);

$totalFailed = $db->getScheduleCount('Rejected');
$thisWeekFailed = $db->getScheduleCountInRange($weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'), 'Rejected');
$lastWeekFailed = $db->getScheduleCountInRange($lastWeekStart->format('Y-m-d'), $lastWeekEnd->format('Y-m-d'), 'Rejected');
$wowFailed = $lastWeekFailed > 0 ? round((($thisWeekFailed - $lastWeekFailed) / $lastWeekFailed) * 100) : ($thisWeekFailed > 0 ? 100 : 0);

// Users
$totalStudents = $db->getUsersCountByRole(3);
$totalStaff = $db->getUsersCountByRole(2);
$studentsThisWeek = $db->getUsersCountByRole(3, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
$studentsLastWeek = $db->getUsersCountByRole(3, $lastWeekStart->format('Y-m-d'), $lastWeekEnd->format('Y-m-d'));
$wowStudents = $studentsLastWeek > 0 ? round((($studentsThisWeek - $studentsLastWeek) / $studentsLastWeek) * 100) : ($studentsThisWeek > 0 ? 100 : 0);
$staffsThisWeek = $db->getUsersCountByRole(2, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
$staffsLastWeek = $db->getUsersCountByRole(2, $lastWeekStart->format('Y-m-d'), $lastWeekEnd->format('Y-m-d'));
$wowStaff = $staffsLastWeek > 0 ? round((($staffsThisWeek - $staffsLastWeek) / $staffsLastWeek) * 100) : ($staffsThisWeek > 0 ? 100 : 0);

// Charts
$dailyData = $db->getDailyScheduleCounts(14);
$dailyLabels = array_keys($dailyData);
$dailyCounts = array_values($dailyData);
$monthlyCountsAssoc = $db->getMonthlyScheduleCounts((int)$today->format('Y'));
$monthlyCounts = array_values($monthlyCountsAssoc);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ZPPSU SMS Analytics Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { 
            background: #f8f9fa; 
        }
        .tab-custom { 
            margin-top: 30px; 
        }
        .avatar {
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            object-fit: cover; 
        }
        .card { 
            margin-bottom: 20px; 
        }
        @media (max-width: 768px) {
            .avatar {
                width: 30px; 
                height: 30px; 
            }
        }
    </style>
</head>
<body>
<?php
if (isset($_GET['msg']) && $_GET['msg'] !== '') {
    echo '<div class="alert alert-info text-center">' . htmlspecialchars($_GET['msg']) . '</div>';
}
?>
<main class="content">
    <div class="container-fluid p-0">
        <h1 class="h3 mb-3"><strong>ZPPSU Analytics</strong> Dashboard</h1>
        <div class="row">
            <div class="col-xl-6 col-xxl-5 d-flex flex-column">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col mt-0">
                                        <h5 class="card-title">Total Send Sms</h5>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stat text-primary">
                                            <i class="align-middle" data-feather="truck"></i>
                                        </div>
                                    </div>
                                </div>
                                <h1 class="mt-1 mb-3"><?php echo number_format($totalSend) ?></h1>
                                <div class="mb-0">
                                    <span class="text-success"><?php echo $thisWeekPctOfTotal ?>%</span>
                                    <span class="text-muted">Total Send Sms This Week</span>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col mt-0">
                                        <h5 class="card-title">Failed Send Message</h5>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stat text-primary">
                                            <i class="align-middle" data-feather="users"></i>
                                        </div>
                                    </div>
                                </div>
                                <h1 class="mt-1 mb-3"><?php echo number_format($totalFailed) ?></h1>
                                <div class="mb-0">
                                    <span class="text-danger"><?php echo $wowFailed ?>%</span>
                                    <span class="text-muted">Since last week</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col mt-0">
                                        <h5 class="card-title">Successfully Send Message</h5>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stat text-primary">
                                            <i class="align-middle" data-feather="dollar-sign"></i>
                                        </div>
                                    </div>
                                </div>
                                <h1 class="mt-1 mb-3"><?php echo number_format($totalSuccess) ?></h1>
                                <div class="mb-0">
                                    <span class="text-success"><?php echo $wowSuccess ?>%</span>
                                    <span class="text-muted">Since last week</span>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col mt-0">
                                        <h5 class="card-title">Total Student</h5>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stat text-primary">
                                            <i class="align-middle" data-feather="shopping-cart"></i>
                                        </div>
                                    </div>
                                </div>
                                <h1 class="mt-1 mb-3"><?php echo number_format($totalStudents) ?></h1>
                                <div class="mb-0">
                                    <span class="text-<?php echo $wowStudents >= 0 ? 'success' : 'danger' ?>"><?php echo $wowStudents ?>%</span>
                                    <span class="text-muted">Since last week</span>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col mt-0">
                                        <h5 class="card-title">Total Staff</h5>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stat text-primary">
                                            <i class="align-middle fas fa-user-tie"></i>
                                        </div>
                                    </div>
                                </div>
                                <h1 class="mt-1 mb-3"><?php echo number_format($totalStaff) ?></h1>
                                <div class="mb-0">
                                    <span class="text-<?php echo $wowStaff >= 0 ? 'success' : 'danger' ?>"><?php echo $wowStaff ?>%</span>
                                    <span class="text-muted">Since last week</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-xxl-7">
                <div class="card flex-fill w-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Movement Sms Log Sms Log</h5>
                    </div>
                    <div class="card-body py-3">
                        <div class="chart chart-sm">
                            <canvas id="chartjs-dashboard-line"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-10 col-xxl-10 d-flex">
            <div class="card flex-fill w-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Total Send Sms</h5>
                </div>
                <div class="card-body d-flex w-200">
                    <div class="align-self-center col-lg-12 col-xxl-12 chart chart-lg">
                        <canvas id="chartjs-dashboard-bar"></canvas>
                    </div>
                </div>
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
    </div>
</main>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="/zppsu_admission/admin/teacher_log/scripts.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById("chartjs-dashboard-line").getContext("2d");
        var gradient = ctx.createLinearGradient(0, 0, 0, 225);
        gradient.addColorStop(0, "rgba(215, 227, 244, 1)");
        gradient.addColorStop(1, "rgba(215, 227, 244, 0)");
        // Line chart
        new Chart(ctx, {
            type: "line",
            data: {
                labels: <?php echo json_encode($dailyLabels) ?>,
                datasets: [{
                    label: "Recent Movement (daily)",
                    fill: true,
                    backgroundColor: gradient,
                    borderColor: "#007bff",
                    data: <?php echo json_encode($dailyCounts) ?>
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: { intersect: false },
                hover: { intersect: true },
                scales: {
                    xAxes: [{
                        reverse: true,
                        gridLines: { color: "rgba(0,0,0,0.0)" }
                    }],
                    yAxes: [{
                        ticks: { stepSize: 10},
                        gridLines: { color: "rgba(0,0,0,0.0)" }
                    }]
                }
            }
        });
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Bar chart
        new Chart(document.getElementById("chartjs-dashboard-bar"), {
            type: "bar",
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                datasets: [{
                    label: "Monthly Total Send Sms (<?php echo $today->format('Y') ?>)",
                    backgroundColor: "#007bff",
                    data: <?php echo json_encode(array_values($monthlyCounts)) ?>,
                    barPercentage: .75,
                    categoryPercentage: .5
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{
                        gridLines: { display: false },
                        ticks: { stepSize: 20 }
                    }],
                    xAxes: [{
                        gridLines: { color: "transparent" }
                    }]
                }
            }
        });
    });
</script>
</body>
</html>