<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<style>
/* Mobile Responsive Styles for Student Dashboard */
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
    .table th:nth-child(3),
    .table td:nth-child(3),
    .table th:nth-child(4),
    .table td:nth-child(4),
    .table th:nth-child(5),
    .table td:nth-child(5) {
        display: none;
    }
    
    /* Make name column wider */
    .table th:nth-child(1),
    .table td:nth-child(1) {
        min-width: 100px;
    }
    
    /* Make status column wider */
    .table th:nth-child(2),
    .table td:nth-child(2) {
        min-width: 80px;
    }
    
    /* Make action column wider */
    .table th:nth-child(6),
    .table td:nth-child(6) {
        min-width: 120px;
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
		<h3 class="card-title">Student Dashboard</h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-4 col-6">
					<div class="small-box bg-info">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `schedule_admission` where email = '{$_SESSION['userdata']['email']}'")->num_rows ?></h3>
							<p>My Applications</p>
						</div>
						<div class="icon">
							<i class="fas fa-file-alt"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=schedule" class="small-box-footer">
							View All <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-4 col-6">
					<div class="small-box bg-success">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `schedule_admission` where email = '{$_SESSION['userdata']['email']}' and status = 'Approved'")->num_rows ?></h3>
							<p>Approved Applications</p>
						</div>
						<div class="icon">
							<i class="fas fa-check-circle"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=sms_log" class="small-box-footer">
							View Details <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-4 col-6">
					<div class="small-box bg-warning">
						<div class="inner">
							<h3><?php echo $conn->query("SELECT * FROM `schedule_admission` where email = '{$_SESSION['userdata']['email']}' and status = 'Pending'")->num_rows ?></h3>
							<p>Pending Applications</p>
						</div>
						<div class="icon">
							<i class="fas fa-clock"></i>
						</div>
						<a href="<?php echo base_url ?>admin/?page=sms_log" class="small-box-footer">
							Check Status <i class="fas fa-arrow-circle-right"></i>
						</a>
					</div>
				</div>
			</div>
			
			<div class="row">
				<div class="col-md-8">
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">My Application Status</h3>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-striped">
									<thead>
										<tr>
											<th>Reference #</th>
											<th>Program</th>
											<th>Date Scheduled</th>
											<th>LRN</th>
											<th>Previous School</th>
											<th>Status</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
										<?php 
										$my_applications = $conn->query("SELECT * FROM schedule_admission WHERE email = '{$_SESSION['userdata']['email']}' ORDER BY created_at DESC");
										if($my_applications->num_rows > 0):
											while($row = $my_applications->fetch_assoc()):
										?>
										<tr>
											<td><?php echo $row['reference_number'] ?></td>
											<td><?php echo $row['classification'] ?></td>
											<td><?php echo date('M d, Y', strtotime($row['date_scheduled'])) ?></td>
											<td><?php echo isset($row['lrn']) ? htmlspecialchars($row['lrn']) : '' ?></td>
											<td><?php echo isset($row['previous_school']) ? htmlspecialchars($row['previous_school']) : '' ?></td>
											<td>
												<?php if($row['status'] == 'Pending'): ?>
													<span class="badge badge-warning">Pending</span>
												<?php elseif($row['status'] == 'Approved'): ?>
													<?php 
													// Check if this was auto-approved (created and updated at same time)
													$isAutoApproved = ($row['created_at'] == $row['updated_at'] || empty($row['updated_at']));
													?>
													<span class="badge badge-success">
														<?php if($isAutoApproved): ?>
															<i class="fas fa-check mr-1"></i>Approved
														<?php else: ?>
															<i class="fas fa-check mr-1"></i>Approved
														<?php endif; ?>
													</span>
												<?php else: ?>
													<span class="badge badge-danger">Rejected</span>
												<?php endif; ?>
											</td>
											<td>
												<div class="d-flex flex-column align-items-start" style="gap: 0.25rem; padding: 0.25rem 0;">
													<a href="<?php echo base_url ?>admin/?page=sms_log" class="btn btn-sm btn-primary mb-1" style="padding: 0.35rem 0.75rem;">
														<i class="fas fa-eye"></i> View
													</a>
													<a href="<?php echo base_url ?>admin/schedule/print.php?id=<?php echo (int)$row['id']; ?>" target="_blank" class="btn btn-sm btn-secondary" style="padding: 0.35rem 0.75rem;">
														<i class="fas fa-print mr-1"></i> <span>Print Admission</span>
													</a>
												</div>
											</td>
										</tr>
										<?php 
											endwhile;
										else:
										?>
										<tr>
											<td colspan="7" class="text-center">No applications found. <a href="<?php echo base_url ?>admin/?page=schedule">Apply now!</a></td>
										</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">Quick Actions</h3>
						</div>
						<div class="card-body">
							<div class="list-group">
								<a href="<?php echo base_url ?>admin/?page=schedule" class="list-group-item list-group-item-action">
									<i class="fas fa-calendar-plus mr-2"></i> New Application
								</a>
								<a href="<?php echo base_url ?>admin/?page=user/manage_user" class="list-group-item list-group-item-action">
									<i class="fas fa-user mr-2"></i> My Profile
								</a>
								<a href="<?php echo base_url ?>admin/?page=sms_log" class="list-group-item list-group-item-action">
									<i class="fas fa-list mr-2"></i> Application History
								</a>
							</div>
						</div>
					</div>
					
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">Important Information</h3>
						</div>
						<div class="card-body">
							<div class="alert alert-info">
								<strong>Application Deadline:</strong><br>
								Please submit your application at least 2 weeks before your preferred schedule date.
							</div>
							<div class="alert alert-warning">
								<strong>Required Documents:</strong><br>
								• Recent 2x2 Photo<br>
								• Valid ID or Birth Certificate<br>
								• Previous School Records
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
