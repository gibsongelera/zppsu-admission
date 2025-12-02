
<?php 
// Get current user info
$currentUserId = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : null;
$currentUserRole = isset($_SESSION['userdata']['role']) ? (int)$_SESSION['userdata']['role'] : null;
$isStudent = ($currentUserRole === 3);

// For students, only allow editing their own profile
if ($isStudent) {
    $userId = $currentUserId;
} else {
    // Check if we're editing an existing user or creating a new one
    if (isset($_GET['id']) && $_GET['id'] > 0) {
        $userId = (int)$_GET['id'];
    } else {
        $userId = null; // Creating new user
    }
}

// Only fetch user data if we have a valid userId (editing existing user)
if($userId && $userId > 0){
    $user = $conn->query("SELECT * FROM users where id ='{$userId}'");
    if($user->num_rows > 0) {
        foreach($user->fetch_array() as $k =>$v){
            $meta[$k] = $v;
        }
    }
}
?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>


<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title"><?php echo $isStudent ? 'My Profile' : (isset($meta['id']) ? 'Edit User' : 'Create New User') ?></h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div id="msg"></div>
			<form action="" id="manage-user">	
				<input type="hidden" name="id" value="<?php echo isset($meta['id']) ? $meta['id']: '' ?>">
				
				<?php if (!$isStudent): ?>
				<!-- Name fields for non-students (admin/staff) -->
				<div class="row">
					<div class="form-group col-md-6">
						<label for="firstname">First Name <?php echo ($currentUserRole === 1) ? '' : '<span class=\"text-danger\">*</span>' ?></label>
						<input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($meta['firstname']) ? $meta['firstname']: '' ?>" <?php echo ($currentUserRole === 1) ? '' : 'required' ?> data-original="<?php echo isset($meta['firstname']) ? htmlspecialchars($meta['firstname']) : '' ?>">
					</div>
					<div class="form-group col-md-6">
						<label for="lastname">Last Name <?php echo ($currentUserRole === 1) ? '' : '<span class=\"text-danger\">*</span>' ?></label>
						<input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($meta['lastname']) ? $meta['lastname']: '' ?>" <?php echo ($currentUserRole === 1) ? '' : 'required' ?> data-original="<?php echo isset($meta['lastname']) ? htmlspecialchars($meta['lastname']) : '' ?>">
					</div>
				</div>
				<?php endif; ?>

				<?php if (!$isStudent): ?>
				<!-- Only show these fields for non-students -->
				<div class="row">
					<div class="form-group col-md-6">
						<label for="middlename">Middle Name</label>
						<input type="text" name="middlename" id="middlename" class="form-control" value="<?php echo isset($meta['middlename']) ? $meta['middlename']: '' ?>" data-original="<?php echo isset($meta['middlename']) ? htmlspecialchars($meta['middlename']) : '' ?>">
					</div>
					<div class="form-group col-md-6">
						<label for="username">Username <?php echo ($currentUserRole === 1) ? '' : '<span class=\"text-danger\">*</span>' ?></label>
						<input type="text" name="username" id="username" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" <?php echo ($currentUserRole === 1) ? '' : 'required' ?> autocomplete="off" data-original="<?php echo isset($meta['username']) ? htmlspecialchars($meta['username']) : '' ?>">
					</div>
				</div>

				<div class="row">
					<div class="form-group col-md-6">
						<label for="password">Password <?php echo ($currentUserRole === 1 || isset($meta['id'])) ? '' : '<span class="text-danger">*</span>' ?></label>
						<input type="password" name="password" id="password" class="form-control" value="" autocomplete="off" <?php echo ($currentUserRole === 1 || isset($meta['id'])) ? '' : 'required' ?> data-original="">
						<?php if(isset($meta['id'])): ?>
						<small class="text-info"><i>Leave this blank if you don't want to change the password.</i></small>
						<?php endif; ?>
					</div>
					<div class="form-group col-md-6">
						<label for="email">Email <?php echo ($currentUserRole === 1) ? '' : '<span class="text-danger">*</span>' ?></label>
						<input type="email" name="email" id="email" class="form-control" value="<?php echo isset($meta['email']) ? $meta['email']: '' ?>" <?php echo ($currentUserRole === 1) ? '' : 'required' ?> data-original="<?php echo isset($meta['email']) ? htmlspecialchars($meta['email']) : '' ?>">
					</div>
				</div>

				<div class="row">
					<div class="form-group col-md-6">
						<label for="phone">Phone <?php echo ($currentUserRole === 1) ? '' : '<span class="text-danger">*</span>' ?></label>
						<input type="text" name="phone" id="phone" class="form-control" value="<?php echo isset($meta['phone']) ? $meta['phone']: '' ?>" <?php echo ($currentUserRole === 1) ? '' : 'required' ?> data-original="<?php echo isset($meta['phone']) ? htmlspecialchars($meta['phone']) : '' ?>">
					</div>
					<?php if(isset($meta['id']) && isset($meta['role']) && (int)$meta['role'] === 3): // Only show for existing STUDENT users ?>
					<div class="form-group col-md-6">
						<label>Reference Number</label>
						<input type="text" class="form-control" value="<?php echo isset($meta['reference_number']) ? htmlspecialchars($meta['reference_number']) : '' ?>" readonly>
						<small class="text-muted">Keep this for scheduling verification.</small>
					</div>
					<div class="form-group col-md-6">
						<label for="lrn">LRN</label>
						<input type="text" name="lrn" id="lrn" class="form-control" value="<?php echo isset($meta['lrn']) ? $meta['lrn']: '' ?>" pattern="\d{12}" maxlength="12" placeholder="012345678901" data-original="<?php echo isset($meta['lrn']) ? htmlspecialchars($meta['lrn']) : '' ?>">
					</div>
					<?php endif; ?>
					<div class="form-group col-md-6">
						<label for="role">User Type <span class="text-danger">*</span></label>
						<select name="role" id="role" class="custom-select" required data-original="<?php echo isset($meta['role']) ? $meta['role'] : '' ?>">
							<option value="">Select User Type</option>
							<?php if(isset($meta['id'])): // Editing existing user ?>
								<option value="1" <?php echo isset($meta['role']) && $meta['role'] == 1 ? 'selected': '' ?>>Administrator</option>
								<option value="2" <?php echo isset($meta['role']) && $meta['role'] == 2 ? 'selected': '' ?>>Staff</option>
								<?php if(isset($meta['role']) && $meta['role'] == 3): // Only show student option if editing existing student ?>
								<option value="3" <?php echo $meta['role'] == 3 ? 'selected': '' ?>>Student</option>
								<?php endif; ?>
							<?php else: // Creating new user - only allow Admin and Staff ?>
								<option value="1">Administrator</option>
								<option value="2">Staff</option>
							<?php endif; ?>
						</select>
					</div>
				</div>

				<!-- Student-specific fields (hide for admins) -->
				<?php if ($currentUserRole !== 1): ?>
				<div class="row" id="student-fields" style="display: none;">
					<div class="form-group col-md-6">
						<label for="course">Course</label>
						<input type="text" name="course" id="course" class="form-control" value="<?php echo isset($meta['course']) ? $meta['course']: '' ?>" data-original="<?php echo isset($meta['course']) ? htmlspecialchars($meta['course']) : '' ?>">
					</div>
					<div class="form-group col-md-6">
						<label for="year_level">Year Level</label>
						<select name="year_level" id="year_level" class="custom-select" data-original="<?php echo isset($meta['year_level']) ? $meta['year_level'] : '' ?>">
							<option value="">Select Year Level</option>
							<option value="1st Year" <?php echo isset($meta['year_level']) && $meta['year_level'] == '1st Year' ? 'selected': '' ?>>1st Year</option>
							<option value="2nd Year" <?php echo isset($meta['year_level']) && $meta['year_level'] == '2nd Year' ? 'selected': '' ?>>2nd Year</option>
							<option value="3rd Year" <?php echo isset($meta['year_level']) && $meta['year_level'] == '3rd Year' ? 'selected': '' ?>>3rd Year</option>
							<option value="4th Year" <?php echo isset($meta['year_level']) && $meta['year_level'] == '4th Year' ? 'selected': '' ?>>4th Year</option>
						</select>
					</div>
				</div>
				<?php endif; ?>

				<?php else: ?>
				<!-- Hidden fields for students to preserve existing data -->
				<input type="hidden" name="username" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" data-original="<?php echo isset($meta['username']) ? htmlspecialchars($meta['username']) : '' ?>">
				<input type="hidden" name="email" value="<?php echo isset($meta['email']) ? $meta['email']: '' ?>" data-original="<?php echo isset($meta['email']) ? htmlspecialchars($meta['email']) : '' ?>">
				<input type="hidden" name="phone" value="<?php echo isset($meta['phone']) ? $meta['phone']: '' ?>" data-original="<?php echo isset($meta['phone']) ? htmlspecialchars($meta['phone']) : '' ?>">
				<input type="hidden" name="role" value="<?php echo isset($meta['role']) ? $meta['role']: '3' ?>" data-original="<?php echo isset($meta['role']) ? $meta['role'] : '3' ?>">
				<input type="hidden" name="course" value="<?php echo isset($meta['course']) ? $meta['course']: '' ?>" data-original="<?php echo isset($meta['course']) ? htmlspecialchars($meta['course']) : '' ?>">
				<input type="hidden" name="year_level" value="<?php echo isset($meta['year_level']) ? $meta['year_level']: '' ?>" data-original="<?php echo isset($meta['year_level']) ? $meta['year_level'] : '' ?>">
				
				<!-- Editable name fields for students -->
				<div class="row">
					<div class="form-group col-md-4">
						<label for="lastname">Surname: <span class="text-danger">*</span></label>
						<input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($meta['lastname']) ? $meta['lastname']: '' ?>" required data-original="<?php echo isset($meta['lastname']) ? htmlspecialchars($meta['lastname']) : '' ?>">
					</div>
					<div class="form-group col-md-4">
						<label for="firstname">Given Name: <span class="text-danger">*</span></label>
						<input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($meta['firstname']) ? $meta['firstname']: '' ?>" required data-original="<?php echo isset($meta['firstname']) ? htmlspecialchars($meta['firstname']) : '' ?>">
					</div>
					<div class="form-group col-md-4">
						<label for="middlename">Middle Name</label>
						<input type="text" name="middlename" id="middlename" class="form-control" value="<?php echo isset($meta['middlename']) ? $meta['middlename']: '' ?>" data-original="<?php echo isset($meta['middlename']) ? htmlspecialchars($meta['middlename']) : '' ?>">
						<small class="text-muted">Optional</small>
					</div>
				</div>
				
				<!-- Show read-only info for students -->
				<div class="row">
					<div class="form-group col-md-6">
						<label>Username</label>
						<input type="text" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" readonly>
						<small class="text-muted">Contact administration to change username</small>
					</div>
					<div class="form-group col-md-6">
						<label>Email</label>
						<input type="text" class="form-control" value="<?php echo isset($meta['email']) ? $meta['email']: '' ?>" readonly>
						<small class="text-muted">Contact administration to change email</small>
					</div>
					<div class="form-group col-md-6">
						<label>Reference Number</label>
						<input type="text" class="form-control" value="<?php echo isset($meta['reference_number']) && $meta['reference_number'] !== '' ? htmlspecialchars($meta['reference_number']) : 'Not assigned'; ?>" readonly>
						<small class="text-muted">Use this for scheduling verification.</small>
					</div>
					<div class="form-group col-md-6">
						<label>LRN</label>
						<input type="text" class="form-control" value="<?php echo isset($meta['lrn']) && $meta['lrn'] !== '' ? htmlspecialchars($meta['lrn']) : 'Not set'; ?>" readonly>
						<small class="text-muted">Your 12-digit Learner Reference Number.</small>
					</div>
				</div>
				<?php endif; ?>

				<!-- Avatar upload -->
				<div class="row">
					<div class="form-group col-md-6">
						<label for="" class="control-label">Profile Picture</label>
						<div class="custom-file">
							<input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
							<label class="custom-file-label" for="customFile">Choose file</label>
						</div>
						<?php if(isset($meta['id'])): ?>
						<div class="mt-2">
							<input type="hidden" name="remove_avatar" id="remove_avatar" value="0">
							<button type="button" class="btn btn-sm btn-outline-danger" id="btn-remove-avatar"><span class="fas fa-trash mr-1"></span> Remove photo</button>
						</div>
						<?php endif; ?>
					</div>
					<div class="form-group col-md-6 d-flex justify-content-center">
						<img src="<?php echo validate_image(isset($meta['avatar']) ? $meta['avatar'] :'') ?>" alt="" id="cimg" class="img-fluid img-thumbnail">
					</div>
				</div>
			</form>
		</div>
	</div>
	<div class="card-footer">
			<div class="col-md-12">
				<div class="row">
					<button class="btn btn-sm btn-primary mr-2" form="manage-user"><?php echo isset($meta['id']) ? 'Update' : 'Create' ?></button>
					<?php if ($isStudent): ?>
					<a class="btn btn-sm btn-secondary" href="./?page=schedule">Back to Schedule</a>
					<?php else: ?>
					<a class="btn btn-sm btn-secondary" href="./?page=user/list">Cancel</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
</div>
<style>
	img#cimg{
		height: 15vh;
		width: 15vh;
		object-fit: cover;
		border-radius: 100% 100%;
	}
	
	/* Mobile Responsive Styles */
	@media (max-width: 768px) {
		.card-body {
			padding: 15px;
		}
		
		.form-group {
			margin-bottom: 15px;
		}
		
		.form-group label {
			font-size: 0.9rem;
			font-weight: 600;
		}
		
		.form-control {
			font-size: 0.9rem;
			padding: 0.5rem 0.75rem;
		}
		
		.custom-select {
			font-size: 0.9rem;
			padding: 0.5rem 0.75rem;
		}
		
		.btn {
			font-size: 0.9rem;
			padding: 0.5rem 1rem;
		}
		
		.btn-sm {
			font-size: 0.8rem;
			padding: 0.4rem 0.8rem;
		}
		
		img#cimg {
			height: 12vh;
			width: 12vh;
		}
		
		.custom-file-label {
			font-size: 0.9rem;
		}
		
		.text-info,
		.text-muted {
			font-size: 0.8rem;
		}
		
		.alert {
			font-size: 0.85rem;
			padding: 0.75rem;
		}
	}
	
	@media (max-width: 480px) {
		.card-body {
			padding: 10px;
		}
		
		.form-group {
			margin-bottom: 12px;
		}
		
		.form-group label {
			font-size: 0.8rem;
		}
		
		.form-control {
			font-size: 0.8rem;
			padding: 0.4rem 0.6rem;
		}
		
		.custom-select {
			font-size: 0.8rem;
			padding: 0.4rem 0.6rem;
		}
		
		.btn {
			font-size: 0.8rem;
			padding: 0.4rem 0.8rem;
		}
		
		.btn-sm {
			font-size: 0.7rem;
			padding: 0.3rem 0.6rem;
		}
		
		img#cimg {
			height: 10vh;
			width: 10vh;
		}
		
		.custom-file-label {
			font-size: 0.8rem;
		}
		
		.text-info,
		.text-muted {
			font-size: 0.7rem;
		}
		
		.alert {
			font-size: 0.8rem;
			padding: 0.6rem;
		}
	}
</style>
<script>
	$(function(){
		$('.select2').select2({
			width:'resolve'
		})
		
		// Show/hide student fields based on role selection
		$('#role').change(function() {
			if ($(this).val() == '3') {
				$('#student-fields').show();
			} else {
				$('#student-fields').hide();
			}
		});
		
		// Trigger change event on page load to set initial state
		$('#role').trigger('change');
		// Remove avatar button
		$('#btn-remove-avatar').on('click', function(){
			$('#remove_avatar').val('1');
			$('#customFile').val('');
			$('#cimg').attr('src', '<?php echo validate_image("") ?>');
			alert_toast('Photo marked for removal. Click Update to save.', 'info');
		});
	})
	
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	
	$('#manage-user').submit(function(e){
		e.preventDefault();
		var _this = $(this)
		
		// Check if this is just an avatar update
		var hasFile = $('#customFile')[0].files.length > 0;
		var hasOtherChanges = false;

		// Always validate required fields for new users or when there are form changes
		var requiredFields = ['firstname', 'lastname'];
		if (!$('#role').val() || $('#role').val() != '3') {
			requiredFields = requiredFields.concat(['username']);
			// For Admin editing, email/phone are optional
			if (<?php echo (int)$currentUserRole; ?> !== 1) {
				requiredFields = requiredFields.concat(['email', 'phone']);
			}
		} else {
			// For students, email and phone are required but they're hidden fields
			// So we don't need to validate them in the form
		}
		
		var isValid = true;
		requiredFields.forEach(function(field) {
			var fieldEl = $('#' + field);
			if (fieldEl.length > 0) {
				var fieldVal = fieldEl.val();
				if (!fieldVal || !fieldVal.trim()) {
					fieldEl.addClass('is-invalid');
					isValid = false;
				} else {
					fieldEl.removeClass('is-invalid');
				}
			}
		});
		
		if (!isValid) {
			alert_toast('Please fill in all required fields.', 'error');
			return false;
		}
		
		// Check if other fields have changed
		$('#manage-user input[type="text"], #manage-user input[type="email"], #manage-user input[type="password"], #manage-user select').each(function() {
			var currentVal = $(this).val() || '';
			var originalVal = $(this).attr('data-original') || '';
			if (currentVal !== originalVal) {
				hasOtherChanges = true;
				return false;
			}
		});
		
		// For students, also check hidden fields for changes
		<?php if ($isStudent): ?>
		$('#manage-user input[type="hidden"]').each(function() {
			var currentVal = $(this).val() || '';
			var originalVal = $(this).attr('data-original') || '';
			if (currentVal !== originalVal) {
				hasOtherChanges = true;
				return false;
			}
		});
		<?php endif; ?>
		
		// Add hidden field for avatar-only updates (only for existing users)
		if (hasFile && !hasOtherChanges && $('input[name="id"]').val()) {
			$('#manage-user').append('<input type="hidden" name="avatar_only" value="1">');
		}
		
		start_loader()
		$.ajax({
			url:_base_url_+'classes/Users.php?f=save',
			data: new FormData($(this)[0]),
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
			success:function(resp){
				console.log('Response:', resp); // Debug log
				// Normalize response (could be JSON or plain number)
				var ok = false;
				var respCode = null;
				try{
					if (typeof resp === 'string') {
						resp = resp.trim();
						respCode = resp;
					}
					if (resp === '1' || resp === 1) ok = true;
					else if (resp && typeof resp === 'object' && (resp.status === 'success' || resp.code === 1)) ok = true;
				} catch(e){
					console.error('Error parsing response:', e);
				}
				
				if(ok){
					<?php if ($isStudent): ?>
					alert_toast('User Details successfully updated.', 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
					<?php else: ?>
					location.href = './?page=user/list';
					<?php endif; ?>
				}else if(respCode == '3' || respCode === 3 || (resp && resp.code === 3)){
					$('#msg').html('<div class="alert alert-danger">Username already exists</div>')
					$("html, body").animate({ scrollTop: 0 }, "fast");
					end_loader();
				}else if(respCode == '5' || respCode === 5 || (resp && resp.code === 5)){
					$('#msg').html('<div class="alert alert-danger">Please fill in all required fields</div>')
					$("html, body").animate({ scrollTop: 0 }, "fast");
					end_loader();
				}else{
					console.error('Unexpected response:', resp);
					$('#msg').html('<div class="alert alert-danger">An error occurred while saving. Response: ' + resp + '</div>')
					$("html, body").animate({ scrollTop: 0 }, "fast");
					end_loader();
				}
			},
			error:function(xhr, status, error){
				console.error('AJAX Error:', status, error);
				$('#msg').html('<div class="alert alert-danger">Server error: ' + error + '</div>')
				$("html, body").animate({ scrollTop: 0 }, "fast");
				end_loader();
			}
		})
	})

</script>

