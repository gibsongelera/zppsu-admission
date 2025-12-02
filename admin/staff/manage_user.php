
<?php 
// Get current user info
$currentUserId = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : null;
$currentUserRole = isset($_SESSION['userdata']['role']) ? (int)$_SESSION['userdata']['role'] : null;
$isStaff = ($currentUserRole === 2);

// For Staffs, only allow editing their own profile
if ($isStaff) {
    $userId = $currentUserId;
} else {
    $userId = isset($_GET['id']) && $_GET['id'] > 0 ? (int)$_GET['id'] : $currentUserId;
}

if($userId){
	$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
	$stmt->bind_param("i", $userId);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0) {
		foreach($result->fetch_array() as $k => $v){
			$meta[$k] = $v;
		}
	}
	$stmt->close();
}
?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title"><?php echo $isStaff ? 'My Profile' : 'Manage User' ?></h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div id="msg"></div>
			<form action="" id="manage-user">	
				<input type="hidden" name="id" value="<?php echo isset($meta['id']) ? $meta['id']: '' ?>">
				
				<!-- Always show First Name and Last Name -->
				<div class="form-group col-6">
					<label for="firstname">First Name</label>
					<input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($meta['firstname']) ? $meta['firstname']: '' ?>" required data-original="<?php echo isset($meta['firstname']) ? htmlspecialchars($meta['firstname']) : '' ?>">
				</div>
				<div class="form-group col-6">
					<label for="lastname">Last Name</label>
					<input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($meta['lastname']) ? $meta['lastname']: '' ?>" required data-original="<?php echo isset($meta['lastname']) ? htmlspecialchars($meta['lastname']) : '' ?>">
				</div>

				<?php if (!$isStaff): ?>
				<!-- Only show these fields for non-Staffs -->
				<div class="form-group col-6">
					<label for="middlename">Middle Name</label>
					<input type="text" name="middlename" id="middlename" class="form-control" value="<?php echo isset($meta['middlename']) ? $meta['middlename']: '' ?>" data-original="<?php echo isset($meta['middlename']) ? htmlspecialchars($meta['middlename']) : '' ?>">
				</div>
				<div class="form-group col-6">
					<label for="username">Username</label>
					<input type="text" name="username" id="username" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" required autocomplete="off" data-original="<?php echo isset($meta['username']) ? htmlspecialchars($meta['username']) : '' ?>">
				</div>
				<div class="form-group col-6">
					<label for="password">Password</label>
					<input type="password" name="password" id="password" class="form-control" value="" autocomplete="off" <?php echo isset($meta['id']) ? "": 'required' ?> data-original="">
					<?php if(isset($meta['id'])): ?>
					<small class="text-info"><i>Leave this blank if you dont want to change the password.</i></small>
					<?php endif; ?>
				</div>
				<div class="form-group col-6">
					<label for="email">Email</label>
					<input type="email" name="email" id="email" class="form-control" value="<?php echo isset($meta['email']) ? $meta['email']: '' ?>" data-original="<?php echo isset($meta['email']) ? htmlspecialchars($meta['email']) : '' ?>">
				</div>
				<div class="form-group col-6">
					<label for="phone">Phone</label>
					<input type="text" name="phone" id="phone" class="form-control" value="<?php echo isset($meta['phone']) ? $meta['phone']: '' ?>" data-original="<?php echo isset($meta['phone']) ? htmlspecialchars($meta['phone']) : '' ?>">
				</div>
				<div class="form-group col-6">
					<label for="role">User Type</label>
					<select name="role" id="role" class="custom-select" required data-original="<?php echo isset($meta['role']) ? $meta['role'] : '' ?>">
						<option value="1" <?php echo isset($meta['role']) && $meta['role'] == 1 ? 'selected': '' ?>>Administrator</option>
						<option value="2" <?php echo isset($meta['role']) && $meta['role'] == 2 ? 'selected': '' ?>>Staff</option>
						<option value="3" <?php echo isset($meta['role']) && $meta['role'] == 3 ? 'selected': '' ?>>Staff</option>
					</select>
				</div>
				<?php else: ?>
				<!-- Hidden fields for Staffs to preserve existing data -->
				<input type="hidden" name="middlename" value="<?php echo isset($meta['middlename']) ? $meta['middlename']: '' ?>">
				<input type="hidden" name="username" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>">
				<input type="hidden" name="email" value="<?php echo isset($meta['email']) ? $meta['email']: '' ?>">
				<input type="hidden" name="phone" value="<?php echo isset($meta['phone']) ? $meta['phone']: '' ?>">
				<input type="hidden" name="role" value="<?php echo isset($meta['role']) ? $meta['role']: '3' ?>">
				
				<!-- Show read-only info for Staffs -->
				<div class="form-group col-6">
					<label>Username</label>
					<input type="text" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" readonly>
					<small class="text-muted">Contact administration to change username</small>
				</div>
				<div class="form-group col-6">
					<label>Email</label>
					<input type="text" class="form-control" value="<?php echo isset($meta['email']) ? $meta['email']: '' ?>" readonly>
					<small class="text-muted">Contact administration to change email</small>
				</div>
				<?php endif; ?>

				<!-- Always show avatar upload -->
				<div class="form-group col-6">
					<label for="" class="control-label">Profile Picture</label>
					<div class="custom-file">
		              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
		              <label class="custom-file-label" for="customFile">Choose file</label>
		            </div>
				</div>
				<div class="form-group col-6 d-flex justify-content-center">
					<img src="<?php echo validate_image(isset($meta['avatar']) ? $meta['avatar'] :'') ?>" alt="" id="cimg" class="img-fluid img-thumbnail">
				</div>
			</form>
		</div>
	</div>
	<div class="card-footer">
			<div class="col-md-12">
				<div class="row">
					<button class="btn btn-sm btn-primary mr-2" form="manage-user">Save</button>
					<?php if ($isStaff): ?>
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
</style>
<script>
	$(function(){
		$('.select2').select2({
			width:'resolve'
		})
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
		
		// Check if other fields have changed
		$('#manage-user input[type="text"], #manage-user input[type="email"], #manage-user input[type="password"], #manage-user select').each(function() {
			if ($(this).val() !== $(this).attr('data-original')) {
				hasOtherChanges = true;
				return false;
			}
		});
		
		// Add hidden field for avatar-only updates
		if (hasFile && !hasOtherChanges) {
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
				if(resp ==1){
					<?php if ($isStaff): ?>
					alert_toast('Profile updated successfully!', 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
					<?php else: ?>
					location.href = './?page=user/list';
					<?php endif; ?>
				}else if(resp ==3){
					$('#msg').html('<div class="alert alert-danger">Username already exists</div>')
					$("html, body").animate({ scrollTop: 0 }, "fast");
				}else{
					$('#msg').html('<div class="alert alert-danger">An error occurred while saving</div>')
					$("html, body").animate({ scrollTop: 0 }, "fast");
				}
                end_loader()
			}
		})
	})

</script>

