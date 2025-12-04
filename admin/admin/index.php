<?php 
// Include database connection first
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/db_handler.php';
require_once __DIR__ . '/../inc/view_helper.php';

// Ensure $conn is available and valid
if (!isset($conn) || $conn === null) {
    die('Database connection failed. Please check your configuration.');
}

// Check if connection is actually established
if (!$conn->isConnected()) {
    die('Database connection is not established. Please check your database settings.');
}

$db = new DatabaseHandler($conn);

if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Administrator Dashboard</h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-3 col-6">
					<div class="small-box bg-info">
						<div class="inner">
							<h3><?php 
							// Handle both 'role' and 'type' columns for compatibility
							$qry = $conn->query("SELECT * FROM users WHERE (role = 2 OR type = 2)");
							echo $qry ? $qry->num_rows : 0;
							?></h3>
							<p>Total Staff</p>
						</div>
						<div class="icon">
							<i class="fas fa-users"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=user/list" class="small-box-footer">
							More info <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-3 col-6">
					<div class="small-box bg-success">
						<div class="inner">
							<h3><?php 
							// Handle both 'role' and 'type' columns for compatibility
							$qry = $conn->query("SELECT * FROM users WHERE (role = 3 OR type = 3)");
							echo $qry ? $qry->num_rows : 0;
							?></h3>
							<p>Total Students</p>
						</div>
						<div class="icon">
							<i class="fas fa-graduation-cap"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=user/list" class="small-box-footer">
							More info <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-3 col-6">
					<div class="small-box bg-warning">
						<div class="inner">
							<h3><?php 
							$qry = $conn->query("SELECT * FROM schedule_admission WHERE status = 'Pending'");
							echo $qry ? $qry->num_rows : 0;
							?></h3>
							<p>Pending Applications</p>
						</div>
						<div class="icon">
							<i class="fas fa-clock"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=sms_log" class="small-box-footer">
							More info <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-3 col-6">
					<div class="small-box bg-info">
						<div class="inner">
							<h3><?php 
							// Compatible with both MySQL and PostgreSQL
							$qry = $conn->query("SELECT * FROM schedule_admission WHERE status = 'Approved'");
							echo $qry ? $qry->num_rows : 0;
							?></h3>
							<p>Approved</p>
						</div>
						<div class="icon">
							<i class="fas fa-check"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=sms_log" class="small-box-footer">
							More info <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-3 col-6">
					<div class="small-box bg-danger">
						<div class="inner">
							<h3><?php 
							$qry = $conn->query("SELECT * FROM schedule_admission WHERE status IN ('Approved', 'Pending')");
							echo $qry ? $qry->num_rows : 0;
							?></h3>
							<p>Teacher Logs</p>
						</div>
						<div class="icon">
							<i class="fas fa-book"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=teacher_log" class="small-box-footer">
							More info <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
			</div>
			
			<div class="row">
				<div class="col-md-6">
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">Quick Actions</h3>
						</div>
						<div class="card-body">
							<div class="list-group">
								<a href="<?php echo base_url ?>admin/?page=user/list" class="list-group-item list-group-item-action">
									<i class="fas fa-users mr-2"></i> Manage Users
								</a>
								<a href="<?php echo base_url ?>admin/?page=system_info" class="list-group-item list-group-item-action">
									<i class="fas fa-cogs mr-2"></i> System Settings
								</a>
								<a href="<?php echo base_url ?>admin/?page=teacher_log" class="list-group-item list-group-item-action">
									<i class="fas fa-book mr-2"></i> Teacher Logs
								</a>
								
							</div>
						</div>
					</div>
					<div class="card mt-3">
                    <div class="card-header">
                    <h3 class="card-title">Admission Calendar</h3>
                    </div>
						<div class="card-header d-flex align-items-center">
							<button class="btn btn-sm btn-outline-secondary mr-2" id="cal-prev">&laquo;</button>
							<h3 class="card-title mb-0" id="cal-title">Admission Calendar</h3>
							<button class="btn btn-sm btn-outline-secondary ml-2" id="cal-next">&raquo;</button>
							<div class="ml-auto small">
								<span class="badge badge-danger">full</span>
								<span class="badge badge-info">has bookings</span>
								<span class="badge badge-secondary">empty</span>
							</div>
						</div>
						<div class="card-body">
							<div id="admin-calendar"></div>
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">Recent Activities</h3>
						</div>
						<div class="card-body">
							<div class="timeline">
								<?php 
								$recent_users = $conn->query("SELECT * FROM users ORDER BY date_added DESC LIMIT 5");
								while($row = $recent_users->fetch_assoc()):
								?>
								<div class="time-label">
									<span class="bg-red"><?php echo date('M d, Y', strtotime($row['date_added'])) ?></span>
								</div>
								<div>
									<i class="fas fa-user bg-blue"></i>
									<div class="timeline-item">
										<span class="time"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($row['date_added'])) ?></span>
										<h3 class="timeline-header">New User Added</h3>
										<div class="timeline-body">
											<?php echo ucwords($row['firstname'] . ' ' . $row['lastname']) ?> - <?php echo $row['username'] ?>
										</div>
									</div>
								</div>
								<?php endwhile; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
// Database handler already initialized at top of file
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
            font-size: 14px;
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
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            body {
                font-size: 12px;
            }
            
            .container-fluid {
                padding: 10px;
            }
            
            .h3 {
                font-size: 1.2rem;
            }
            
            .card {
                margin-bottom: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .row {
                margin: 0 -5px;
            }
            
            .col-xl-6,
            .col-xxl-5,
            .col-xxl-7,
            .col-lg-10,
            .col-xxl-10 {
                padding: 0 5px;
                margin-bottom: 15px;
            }
            
            .col-sm-6 {
                margin-bottom: 15px;
            }
            
            .card-title {
                font-size: 0.9rem;
                margin-bottom: 10px;
            }
            
            .card-body h1 {
                font-size: 1.5rem;
            }
            
            .card-body h5 {
                font-size: 0.8rem;
            }
            
            .avatar {
                width: 30px; 
                height: 30px; 
            }
            
            .chart {
                height: 200px !important;
            }
            
            .timeline {
                margin: 0 0 20px 0;
            }
            
            .timeline-item {
                margin-left: 50px;
            }
            
            .timeline > li > .fa,
            .timeline > li > .fas,
            .timeline > li > .far,
            .timeline > li > .fab {
                width: 25px;
                height: 25px;
                font-size: 12px;
                line-height: 25px;
                left: 12px;
            }
            
            .timeline:before {
                left: 24px;
                width: 2px;
            }
        }
        
        @media (max-width: 480px) {
            .h3 {
                font-size: 1rem;
            }
            
            .card-title {
                font-size: 0.8rem;
            }
            
            .card-body h1 {
                font-size: 1.2rem;
            }
            
            .card-body h5 {
                font-size: 0.7rem;
            }
            
            .chart {
                height: 150px !important;
            }
            
            .timeline-item {
                margin-left: 40px;
            }
            
            .timeline > li > .fa,
            .timeline > li > .fas,
            .timeline > li > .far,
            .timeline > li > .fab {
                width: 20px;
                height: 20px;
                font-size: 10px;
                line-height: 20px;
                left: 10px;
            }
            
            .timeline:before {
                left: 20px;
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
        
                    <div class="card-body">
                        <div id="admin-calendar"></div>
                    </div>
                </div>
            </div>
        </div>
        
  
            </div>
        </div>
    </div>
</main>
<script src="/zppsu_admission/admin/teacher_log/scripts.js"></script>
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
<script>
    document.addEventListener('DOMContentLoaded', function(){
        var container = document.getElementById('admin-calendar');
        var titleEl = document.getElementById('cal-title');
        var y = (new Date()).getFullYear();
        var m = (new Date()).getMonth();

        function fmtMonth(year, monthIdx){
            return year + '-' + String(monthIdx+1).padStart(2,'0');
        }

        function renderCalendar(year, monthIdx){
            var monthStr = fmtMonth(year, monthIdx);
            if (titleEl) titleEl.textContent = new Date(year, monthIdx, 1).toLocaleString('default', { month:'long', year:'numeric' });
            fetch('<?php echo base_url ?>admin/inc/calendar_counts.php?month=' + encodeURIComponent(monthStr))
              .then(function(r){ return r.json(); })
              .then(function(data){
                var counts = (data && data.counts) ? data.counts : {};
                var todayStr = new Date().toISOString().slice(0,10);
                var first = new Date(year, monthIdx, 1);
                var last = new Date(year, monthIdx+1, 0);
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
                            var mm = String(monthIdx+1).padStart(2,'0');
                            var dd = String(d).padStart(2,'0');
                            var key = year+'-'+mm+'-'+dd;
                            var cnt = counts[key] || 0;
                            var badgeClass = cnt >= 100 ? 'badge badge-danger' : (cnt > 0 ? 'badge badge-info' : 'badge badge-secondary');
                            cell = d + '<div><span class="'+badgeClass+'">'+cnt+' /100 scheduled</span></div>';
                            d++;
                        }
                        var isToday = (key === todayStr);
                        var tdStyle = 'vertical-align:top;min-width:120px;min-height:80px' + (isToday ? ';background-color:#F2F2F2' : '');
                        html += '<td style="'+tdStyle+'">'+cell+'</td>';
                    }
                    html += '</tr>';
                    if (d > last.getDate()) break;
                }
                html += '</tbody></table></div>';
                if (container) container.innerHTML = html;
              });
        }

        var prevBtn = document.getElementById('cal-prev');
        var nextBtn = document.getElementById('cal-next');
        if (prevBtn) prevBtn.addEventListener('click', function(){
            m -= 1; if (m < 0){ m = 11; y -= 1; }
            renderCalendar(y, m);
        });
        if (nextBtn) nextBtn.addEventListener('click', function(){
            m += 1; if (m > 11){ m = 0; y += 1; }
            renderCalendar(y, m);
        });

        renderCalendar(y, m);
    });
</script>
</body>
</html>

<style>
.timeline {
    position: relative;
    margin: 0 0 30px 0;
    padding: 0;
    list-style: none;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #ddd;
    left: 31px;
    margin: 0;
    border-radius: 2px;
}

.timeline > li {
    position: relative;
    margin-right: 10px;
    margin-bottom: 15px;
}

.timeline .time-label {
    font-size: 12px;
    font-weight: 600;
    padding: 5px 10px;
    background: #f4f4f4;
    border-radius: 4px;
    margin-bottom: 10px;
    display: inline-block;
}

.timeline > li > .fa,
.timeline > li > .fas,
.timeline > li > .far,
.timeline > li > .fab {
    width: 30px;
    height: 30px;
    font-size: 15px;
    line-height: 30px;
    position: absolute;
    color: #666;
    background: #d2d6de;
    border-radius: 50%;
    text-align: center;
    left: 15px;
    top: 0;
}

.timeline > li > .bg-blue {
    background-color: #007bff !important;
    color: #fff !important;
}

.timeline-item {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: 0.25rem;
    background-color: #fff;
    color: #495057;
    margin-left: 60px;
    margin-top: 0;
    margin-bottom: 0;
    position: relative;
    border: 1px solid #dee2e6;
}

.timeline-item .time {
    color: #999;
    float: right;
    padding: 10px;
    font-size: 12px;
}

.timeline-item .timeline-header {
    margin: 0;
    color: #495057;
    padding: 10px;
    border-bottom: 1px solid rgba(0,0,0,.125);
    line-height: 1.1;
    font-size: 16px;
    font-weight: 600;
}

.timeline-item .timeline-body {
    padding: 10px;
}
</style>
