<?php require_once('../config.php'); ?>
<?php
// Do not include sess_auth.php for register.php to allow guest access
// Remove or skip any redirect to login for guests
?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php
// Custom header include without sess_auth.php
$header_path = __DIR__ . '/inc/header.php';
if (file_exists($header_path)) {
  // Temporarily override sess_auth.php include for this page
  $header_code = file_get_contents($header_path);
  $header_code = preg_replace('/require_once\([\'\"]sess_auth.php[\'\"]\);?/', '// sess_auth.php skipped for register', $header_code);
  eval('?>' . $header_code);
}
?>
<body class="hold-transition login-page  dark-mode">
  <script>
    start_loader()
  </script>
  <style>
    body{
      background-image: url("<?php echo validate_image($_settings->info('cover')) ?>");
      background-size:cover;
      background-repeat:no-repeat;
    }
    .login-title{
      text-shadow: 2px 2px black
    }
  </style>
  <h1 class="text-center py-5 login-title"><b><?php echo $_settings->info('name') ?></b></h1>
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="./" class="h1"><b>Register</b></a>
    </div>
    <div class="card-body">
      <form id="register-frm" action="" method="post">
        <h5 class="text-center">Register</h5>
        <div class="form-group">
          <label>Enter Fullname:</label>
          <input type="text" class="form-control" name="fullname" required>
        </div>
        <div class="form-group">
          <label>Enter Username:</label>
          <input type="text" class="form-control" name="username" required>
        </div>
        <div class="form-group">
          <label>Enter Email:</label>
          <input type="email" class="form-control" name="email" required>
        </div>
        <div class="form-group">
          <label>Enter LRN (12 digits):</label>
          <input type="text" class="form-control" name="lrn" pattern="\d{12}" maxlength="12" placeholder="012345678901" required>
        </div>
        <div class="form-group">
          <label>Enter Phone Number:</label>
          <input type="text" class="form-control" name="phone" required pattern="09[0-9]{9}" placeholder="09XXXXXXXXX">
        </div>
        <div class="form-group">
          <label>Enter Password:</label>
          <input type="password" class="form-control" name="password" required>
        </div>
        <div class="form-group">
          <label>Confirm Password:</label>
          <input type="password" class="form-control" name="confirm_password" required>
        </div>
<!-- OTP Modal -->
<div class="modal fade" id="otpModal" tabindex="-1" role="dialog" aria-labelledby="otpModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="otpModalLabel">Verify OTP</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Enter the OTP sent to your phone:</label>
          <input type="text" class="form-control" id="otp_code" maxlength="6" autocomplete="one-time-code">
        </div>
        <div class="form-group text-center">
          <span id="otp_display" class="text-info"></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="verify_otp">Verify OTP</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>
        <div class="form-group text-center">
          <button type="submit" class="btn btn-success btn-block" id="register-btn">Register</button>
        </div>
      </form>
      <div class="text-center mt-3">
        <a href="login.php">Back to Login</a>
      </div>
    </div>
  </div>
</div>
<!-- Load jQuery and Bootstrap 4.6.2 JS in correct order -->
<script src="/zppsu_admission/admin/plugins/jquery/jquery.min.js"></script>
<script src="/zppsu_admission/plugins/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
  $(document).ready(function(){
    // End the preloader so the form is clickable
    if (typeof end_loader === 'function') end_loader();

    // Ensure Bootstrap modal is initialized
    if (typeof $.fn.modal === 'undefined') {
      alert('Bootstrap modal is not loaded. Please check your Bootstrap JS path and version.');
    }

    // Always show OTP modal when Register is clicked
    $('#register-frm').submit(function(e){
      e.preventDefault();
      var form = $(this);
      var phone = form.find('[name="phone"]').val();
      // Convert 09XXXXXXXXX to +639XXXXXXXXX
      if(phone.match(/^09\d{9}$/)) {
        phone = '+63' + phone.substring(1);
        form.find('[name="phone"]').val(phone); // update the field for later use
      }
      // Show OTP modal immediately and enable verify button
      $('#otp_display').text('Check your SMS for the code');
      $('#verify_otp').prop('disabled', false);
      $('#otpModal').modal({backdrop: 'static', keyboard: false});
      $('#otpModal').modal('show');
      // Send OTP via AJAX
      $.post('send_otp.php', {phone: phone}, function(resp){
        if(resp.status === 'success'){
          $('#otp_display').text(resp.otp ? resp.otp : 'Check your SMS');
        } else {
          $('#otp_display').text('Check your SMS');
        }
      }, 'json').fail(function(jqXHR, textStatus, errorThrown){
        $('#otp_display').text('Check your SMS');
        alert('AJAX error: ' + textStatus + ' ' + errorThrown);
      });
    });

    // OTP verification and registration completion
    $('#verify_otp').off('click').on('click', function(){
      var otp = $('#otp_code').val();
      var form = $('#register-frm');
      var data = form.serializeArray();
      var postData = {};
      $.each(data, function(i, field){
        // Convert phone to +639XXXXXXXXX if needed
        if(field.name === 'phone' && field.value.match(/^09\d{9}$/)) {
          postData[field.name] = '+63' + field.value.substring(1);
        } else {
          postData[field.name] = field.value;
        }
      });
      postData['otp'] = otp;
      $('#verify_otp').prop('disabled', true); // Prevent double submit
      $.post('verify_otp.php', postData, function(resp){
        $('#verify_otp').prop('disabled', false);
        if(resp.status === 'success'){
          alert(resp.msg);
          $('#otpModal').modal('hide');
          $('#register-frm')[0].reset();
        } else {
          alert(resp.msg || 'OTP verification failed.');
        }
      }, 'json').fail(function(){
        $('#verify_otp').prop('disabled', false);
        alert('AJAX error during verification.');
      });
    });
  });
</script>
<!-- Only load AdminLTE after Bootstrap -->
<script src="/zppsu_admission/admin/dist/js/adminlte.min.js"></script>
</body>
</html>
