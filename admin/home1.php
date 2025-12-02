<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

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
						<a href="<?php echo base_url ?>admin/?page=sms_log" class="small-box-footer">
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
											<td>
												<?php if($row['status'] == 'Pending'): ?>
													<span class="badge badge-warning">Pending</span>
												<?php elseif($row['status'] == 'Approved'): ?>
													<span class="badge badge-success">Approved</span>
												<?php else: ?>
													<span class="badge badge-danger">Rejected</span>
												<?php endif; ?>
											</td>
											<td>
												<a href="?page=student/schedule&action=view&id=<?php echo $row['id'] ?>" class="btn btn-sm btn-primary">
													<i class="fas fa-eye"></i> View
												</a>
											</td>
										</tr>
										<?php 
											endwhile;
										else:
										?>
										<tr>
											<td colspan="5" class="text-center">No applications found. <a href="?page=student/schedule">Apply now!</a></td>
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
