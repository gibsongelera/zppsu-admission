<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<style>
/* Mobile Responsive Styles for Staff Dashboard */
@media (max-width: 768px) {
    .card {
        margin-bottom: 15px;
    }
    
    .card-body {
        padding: 15px;
    }
    
    .small-box {
        margin-bottom: 15px;
    }
    
    .small-box .inner h3 {
        font-size: 1.5rem;
    }
    
    .small-box .inner p {
        font-size: 0.9rem;
    }
    
    .table-responsive {
        font-size: 12px;
    }
    
    .table th,
    .table td {
        padding: 8px 4px;
        vertical-align: middle;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .badge {
        font-size: 0.7rem;
    }
    
    .list-group-item {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .alert {
        font-size: 0.85rem;
        padding: 0.75rem;
    }
    
    /* Hide some columns on mobile */
    .table th:nth-child(2),
    .table td:nth-child(2) {
        display: none;
    }
    
    /* Make name column wider */
    .table th:nth-child(1),
    .table td:nth-child(1) {
        min-width: 120px;
    }
    
    /* Make status column wider */
    .table th:nth-child(3),
    .table td:nth-child(3) {
        min-width: 80px;
    }
    
    /* Make date column wider */
    .table th:nth-child(4),
    .table td:nth-child(4) {
        min-width: 100px;
    }
}

@media (max-width: 480px) {
    .small-box .inner h3 {
        font-size: 1.2rem;
    }
    
    .small-box .inner p {
        font-size: 0.8rem;
    }
    
    .table-responsive {
        font-size: 11px;
    }
    
    .table th,
    .table td {
        padding: 6px 3px;
    }
    
    .btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
    
    .list-group-item {
        padding: 0.6rem 0.8rem;
        font-size: 0.8rem;
    }
    
    .alert {
        font-size: 0.8rem;
        padding: 0.6rem;
    }
}
</style>

<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Staff/Teacher Dashboard</h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-3 col-6">
					<div class="small-box bg-warning">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `schedule_admission` where status = 'Pending'")->num_rows ?></h3>
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
					<div class="small-box bg-success">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `schedule_admission` where status = 'Approved'")->num_rows ?></h3>
							<p>Approved Applications</p>
						</div>
						<div class="icon">
							<i class="fas fa-check-circle"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=sms_log" class="small-box-footer">
							More info <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-3 col-6">
					<div class="small-box bg-danger">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `schedule_admission` where status = 'Rejected'")->num_rows ?></h3>
							<p>Rejected Applications</p>
						</div>
						<div class="icon">
							<i class="fas fa-times-circle"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=sms_log" class="small-box-footer">
							More info <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-3 col-6">
					<div class="small-box bg-info">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `teacher_log`")->num_rows ?></h3>
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
				
			<div class="row mt-3">
				<div class="col-lg-4 col-6">
					<div class="small-box bg-primary">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `schedule_admission` where exam_result = 'Pass'")->num_rows ?></h3>
							<p>Passed Exams</p>
						</div>
						<div class="icon">
							<i class="fas fa-user-graduate"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=results" class="small-box-footer">
							More info <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-4 col-6">
					<div class="small-box bg-secondary">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `schedule_admission` where exam_result = 'Pending'")->num_rows ?></h3>
							<p>Pending Results</p>
						</div>
						<div class="icon">
							<i class="fas fa-hourglass-half"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=results" class="small-box-footer">
							More info <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-4 col-6">
					<div class="small-box bg-dark">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `schedule_admission` where status = 'Approved' AND DATE(date_scheduled) = CURDATE()")->num_rows ?></h3>
							<p>Today's Exams</p>
						</div>
						<div class="icon">
							<i class="fas fa-calendar-day"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=results" class="small-box-footer">
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
								<a href="<?php echo base_url ?>admin/?page=results" class="list-group-item list-group-item-action">
									<i class="fas fa-clipboard-check mr-2"></i> Exam Results
								</a>
								<a href="<?php echo base_url ?>admin/?page=teacher_log" class="list-group-item list-group-item-action">
									<i class="fas fa-book mr-2"></i> Teacher Logs
								</a>
								<a href="<?php echo base_url ?>admin/?page=sms_log" class="list-group-item list-group-item-action">
									<i class="fas fa-list mr-2"></i> SMS Logs
								</a>
								<a href="<?php echo base_url ?>admin/?page=staff/manage_user" class="list-group-item list-group-item-action">
									<i class="fas fa-user mr-2"></i> My Profile
								</a>
							</div>
						</div>
					</div>
					<div class="card mt-3">
						<div class="card-header d-flex align-items-center">
							<button class="btn btn-sm btn-outline-secondary mr-2" id="cal-prev">&laquo;</button>
							<h3 class="card-title mb-0 flex-grow-1" id="cal-title">Admission Calendar</h3>
							<button class="btn btn-sm btn-outline-secondary ml-2" id="cal-next">&raquo;</button>
						</div>
						<div class="card-body">
							<div class="mb-2 text-center small">
								<span class="badge badge-danger mr-1">Full (100+)</span>
								<span class="badge badge-info mr-1">Has bookings</span>
								<span class="badge badge-secondary">Empty</span>
							</div>
							<div id="admin-calendar"></div>
						</div>
					</div>
				</div>	
				<div class="col-md-6">
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">Recent Applications</h3>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-striped">
									<thead>
										<tr>
											<th>Name</th>
											<th>Status</th>
											<th>Date</th>
										</tr>
									</thead>
									<tbody>
										<?php 
										$recent_applications = $conn->query("SELECT * FROM schedule_admission ORDER BY created_at DESC LIMIT 5");
										while($row = $recent_applications->fetch_assoc()):
										?>
										<tr>
											<td><?php echo ucwords($row['surname'] . ', ' . $row['given_name']) ?></td>
											<td>
												<?php if($row['status'] == 'Pending'): ?>
													<span class="badge badge-warning">Pending</span>
												<?php elseif($row['status'] == 'Approved'): ?>
													<span class="badge badge-success">Approved</span>
												<?php else: ?>
													<span class="badge badge-danger">Rejected</span>
												<?php endif; ?>
											</td>
											<td><?php echo date('M d, Y', strtotime($row['created_at'])) ?></td>
										</tr>
										<?php endwhile; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					
					<div class="card mt-3">
						<div class="card-header bg-primary text-white">
							<h3 class="card-title mb-0">
								<i class="fas fa-calendar-alt mr-2"></i>Upcoming Exams (Next 7 Days)
							</h3>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-sm table-striped">
									<thead>
										<tr>
											<th>Date</th>
											<th>Time</th>
											<th>Room</th>
											<th>Students</th>
										</tr>
									</thead>
									<tbody>
										<?php 
										$upcoming_exams = $conn->query("
											SELECT DATE(date_scheduled) as exam_date, time_slot, room_number, COUNT(*) as student_count
											FROM schedule_admission 
											WHERE status = 'Approved' 
											AND DATE(date_scheduled) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
											GROUP BY exam_date, time_slot, room_number
											ORDER BY exam_date ASC, time_slot ASC
											LIMIT 10
										");
										if($upcoming_exams->num_rows > 0):
											while($exam = $upcoming_exams->fetch_assoc()):
										?>
										<tr>
											<td><strong><?php echo date('M d', strtotime($exam['exam_date'])) ?></strong></td>
											<td>
												<span class="badge badge-info">
													<?php echo $exam['time_slot'] ?? 'TBA' ?>
												</span>
											</td>
											<td><?php echo $exam['room_number'] ?? 'TBA' ?></td>
											<td>
												<span class="badge badge-success">
													<?php echo $exam['student_count'] ?> students
												</span>
											</td>
										</tr>
										<?php 
											endwhile;
										else:
										?>
										<tr>
											<td colspan="4" class="text-center text-muted">
												<i class="fas fa-info-circle mr-2"></i>No upcoming exams in the next 7 days
											</td>
										</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
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
                            cell = d + '<div><span class="'+badgeClass+'">'+cnt+' scheduled</span></div>';
                            d++;
                        }
                        var isToday = (key === todayStr);
                        var tdStyle = 'vertical-align:top;min-width:120px;min-height:80px' + (isToday ? ';background-color:#EFCED1' : '');
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
