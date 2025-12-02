<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
 <?php require_once('inc/header.php') ?>
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
      <a href="./" class="h1"><b>Reset Password</b></a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Enter your phone number to receive a reset code via SMS</p>

      <div id="step1" style="display: block;">
        <form id="phone-frm">
          <div class="form-group">
            <label>Phone Number</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">+63</span>
              </div>
              <input type="tel" class="form-control" name="phone" id="reset_phone" placeholder="9123456789" pattern="[9][0-9]{9}" maxlength="10" required>
            </div>
            <small class="form-text text-muted">Format: 9XXXXXXXXX (10 digits)</small>
          </div>
          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-primary btn-block">Send Reset Code</button>
            </div>
          </div>
        </form>
      </div>

      <div id="step2" style="display: none;">
        <form id="verify-frm">
          <div class="alert alert-info">
            Reset code sent to <strong id="phone_display"></strong>
          </div>
          <div class="form-group">
            <label>Enter 6-digit code</label>
            <input type="text" class="form-control text-center" name="otp" id="reset_otp" maxlength="6" placeholder="000000" style="font-size: 1.5rem; letter-spacing: 0.5rem;" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <div class="input-group">
              <input type="password" class="form-control" name="new_password" id="new_password" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <div class="input-group">
              <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div id="countdown_display" class="text-center mb-3" style="display: none;">
            <small>Resend code in <span id="countdown_timer">60</span>s</small>
          </div>
          <div class="row">
            <div class="col-6">
              <button type="button" class="btn btn-secondary btn-block" id="resend_code" disabled>Resend Code</button>
            </div>
            <div class="col-6">
              <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </div>
          </div>
        </form>
      </div>

      <div id="message_area"></div>

      <hr>
      <div class="text-center">
        <a href="login.php">Back to Login</a>
      </div>
    </div>
  </div>
</div>

<script>
  var _base_url_ = '<?php echo base_url ?>';
</script>

<script>
$(document).ready(function(){
    end_loader();
    var currentPhone = '';
    var countdownTimer;
    
    // Password toggle for new password
    $('#toggleNewPassword').on('click', function(){
        var passwordField = $('#new_password');
        var icon = $(this).find('i');
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Password toggle for confirm password
    $('#toggleConfirmPassword').on('click', function(){
        var passwordField = $('#confirm_password');
        var icon = $(this).find('i');
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Start countdown
    function startCountdown() {
        var seconds = 60;
        $('#countdown_display').show();
        $('#resend_code').prop('disabled', true);
        
        countdownTimer = setInterval(function() {
            seconds--;
            $('#countdown_timer').text(seconds);
            
            if (seconds <= 0) {
                clearInterval(countdownTimer);
                $('#countdown_display').hide();
                $('#resend_code').prop('disabled', false);
            }
        }, 1000);
    }
    
    // Send reset code
    $('#phone-frm').submit(function(e){
        e.preventDefault();
        var phone = $('#reset_phone').val();
        currentPhone = '+63' + phone;
        
        $.post('reset_password.php', {action: 'send_code', phone: currentPhone}, function(resp){
            if(resp.status === 'success'){
                $('#step1').hide();
                $('#step2').show();
                $('#phone_display').text(currentPhone);
                startCountdown();
                showMessage(resp.msg, 'success');
            } else {
                showMessage(resp.msg || 'Failed to send reset code', 'error');
            }
        }, 'json').fail(function(){
            showMessage('Network error. Please try again.', 'error');
        });
    });
    
    // Verify code and reset password
    $('#verify-frm').submit(function(e){
        e.preventDefault();
        var otp = $('#reset_otp').val();
        var newPassword = $('#new_password').val();
        var confirmPassword = $('#confirm_password').val();
        
        if(newPassword !== confirmPassword){
            showMessage('Passwords do not match!', 'error');
            return;
        }
        
        if(newPassword.length < 6){
            showMessage('Password must be at least 6 characters!', 'error');
            return;
        }
        
        $.post('reset_password.php', {
            action: 'reset_password',
            phone: currentPhone,
            otp: otp,
            new_password: newPassword
        }, function(resp){
            if(resp.status === 'success'){
                showMessage(resp.msg, 'success');
                setTimeout(function(){
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                showMessage(resp.msg || 'Failed to reset password', 'error');
            }
        }, 'json').fail(function(){
            showMessage('Network error. Please try again.', 'error');
        });
    });
    
    // Resend code
    $('#resend_code').on('click', function(){
        $.post('reset_password.php', {action: 'send_code', phone: currentPhone}, function(resp){
            if(resp.status === 'success'){
                startCountdown();
                showMessage('Reset code resent successfully!', 'success');
            } else {
                showMessage(resp.msg || 'Failed to resend code', 'error');
            }
        }, 'json');
    });
    
    function showMessage(msg, type){
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        $('#message_area').html('<div class="alert ' + alertClass + ' mt-3">' + msg + '</div>');
        setTimeout(function(){
            $('#message_area').html('');
        }, 5000);
    }
});
</script>
</body>
</html>

