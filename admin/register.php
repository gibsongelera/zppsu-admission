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
<body class="hold-transition login-page dark-mode">
  <script>
    start_loader()
  </script>
  <style>
    body{
      background-image: url("<?php echo validate_image($_settings->info('cover')) ?>");
      background-size:cover;
      background-repeat:no-repeat;
      background-attachment: fixed;
    }
    .login-title{
      text-shadow: 2px 2px black;
      color: white;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
      .login-box {
        width: 90% !important;
        margin: 10px auto;
      }
      .login-title {
        font-size: 1.5rem;
        padding: 20px 10px;
      }
      .card-body {
        padding: 15px;
      }
      .form-group {
        margin-bottom: 15px;
      }
      .btn {
        padding: 12px;
        font-size: 16px;
      }
    }
    
    /* OTP Modal Styles */
    .otp-modal .modal-content {
      border-radius: 15px;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .otp-modal .modal-header {
      background: linear-gradient(135deg, #5E0A14, #8B1538);
      color: white;
      border-radius: 15px 15px 0 0;
      border: none;
    }
    .otp-modal .modal-title {
      font-weight: 600;
      font-size: 1.3rem;
    }
    .otp-modal .close {
      color: white;
      opacity: 0.8;
    }
    .otp-modal .close:hover {
      opacity: 1;
    }
    .otp-input {
      text-align: center;
      font-size: 1.5rem;
      font-weight: bold;
      letter-spacing: 0.5rem;
      border: 2px solid #ddd;
      border-radius: 10px;
      padding: 15px;
      margin: 10px 0;
    }
    .otp-input:focus {
      border-color: #5E0A14;
      box-shadow: 0 0 0 0.2rem rgba(94, 10, 20, 0.25);
    }
    .otp-display {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 10px;
      padding: 15px;
      margin: 15px 0;
      text-align: center;
      font-weight: 500;
    }
    .countdown-timer {
      font-size: 1.1rem;
      font-weight: 600;
      color: #5E0A14;
    }
    .resend-btn {
      background: linear-gradient(135deg, #28a745, #20c997);
      border: none;
      border-radius: 25px;
      padding: 10px 25px;
      color: white;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    .resend-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    }
    .resend-btn:disabled {
      background: #6c757d;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    .modal-footer .btn {
      border-radius: 25px;
      padding: 10px 30px;
      font-weight: 500;
      margin: 0 5px;
    }
    .btn-primary {
      background: linear-gradient(135deg, #5E0A14, #8B1538);
      border: none;
    }
    .btn-primary:hover {
      background: linear-gradient(135deg, #4A0810, #7A1230);
    }
    .btn-secondary {
      background: #6c757d;
      border: none;
    }
    .btn-secondary:hover {
      background: #5a6268;
    }
    
    /* Success/Error Messages */
    .alert-custom {
      border-radius: 10px;
      border: none;
      padding: 15px 20px;
      margin: 15px 0;
      font-weight: 500;
    }
    .alert-success-custom {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      color: #155724;
    }
    .alert-danger-custom {
      background: linear-gradient(135deg, #f8d7da, #f5c6cb);
      color: #721c24;
    }
    
    /* Success Modal Styles */
    .modal-success .modal-content {
      border-radius: 15px;
      border: none;
      box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    }
    .modal-success .modal-header {
      background: linear-gradient(135deg, #28a745, #20c997);
      border-radius: 15px 15px 0 0;
      border: none;
    }
    .modal-success .modal-body {
      padding: 30px;
    }
    .modal-success .alert {
      border-radius: 10px;
      border: none;
    }
    .modal-success .input-group {
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      border-radius: 8px;
      overflow: hidden;
    }
    .modal-success .form-control {
      border: 2px solid #e9ecef;
      font-size: 1.2rem;
      font-weight: 600;
      letter-spacing: 1px;
    }
    .modal-success .form-control:focus {
      border-color: #007bff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .modal-success .btn-lg {
      padding: 12px 30px;
      font-size: 1.1rem;
      border-radius: 25px;
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
        <div class="row">
          <div class="form-group col-md-12">
            <label>Surname: <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="lastname" id="reg_lastname" placeholder="ex. DELA CRUZ" required>
          </div>
          <div class="form-group col-md-6">
            <label>Given Name: <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="firstname" id="reg_firstname" placeholder="ex. JUAN" required>
          </div>
          <div class="form-group col-md-6">
            <label>Middle Name</label>
            <input type="text" class="form-control" name="middlename" id="reg_middlename" placeholder="ex. PEREZ" value="">
            <small class="text-muted">Optional</small>
          </div>
        </div>
        <div class="form-group">
          <label>Enter Username:</label>
          <input type="text" class="form-control" name="username" placeholder="ex. DELACRUZJP" required>
        </div>
        <div class="form-group">
          <label>Enter Email:</label>
          <input type="email" class="form-control" name="email" placeholder="ex. JUANDELACRUZ@GMAIL.COM" required>
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
          <div class="input-group">
            <input type="password" class="form-control" name="password" id="reg_password" required>
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" type="button" id="toggleRegPassword">
                <i class="fas fa-eye" id="regPasswordIcon"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Confirm Password:</label>
          <div class="input-group">
            <input type="password" class="form-control" name="confirm_password" id="reg_confirm_password" required>
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" type="button" id="toggleRegConfirmPassword">
                <i class="fas fa-eye" id="regConfirmPasswordIcon"></i>
              </button>
            </div>
          </div>
        </div>
<!-- OTP Modal -->
<div class="modal fade otp-modal" id="otpModal" tabindex="-1" role="dialog" aria-labelledby="otpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="otpModalLabel">
          <i class="fas fa-mobile-alt mr-2"></i>Verify Phone Number
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <div class="mb-3">
          <i class="fas fa-sms fa-3x text-primary mb-3"></i>
          <h6 class="text-muted">We've sent a verification code to</h6>
          <h5 class="text-primary" id="phone_display"></h5>
        </div>
        
        <div class="form-group">
          <label class="font-weight-bold">Enter 6-digit verification code:</label>
          <input type="text" class="form-control otp-input" id="otp_code" maxlength="6" autocomplete="one-time-code" placeholder="000000">
        </div>
        
        <div class="otp-display">
          <div id="otp_display" class="text-info mb-2">
            <i class="fas fa-spinner fa-spin mr-2"></i>Sending OTP...
          </div>
          <div id="countdown_display" class="countdown-timer" style="display: none;">
            <i class="fas fa-clock mr-2"></i>Resend code in <span id="countdown_timer">30</span>s
          </div>
        </div>
        
        <div class="text-center mt-3">
          <button type="button" class="btn resend-btn" id="resend_otp" disabled>
            <i class="fas fa-redo mr-2"></i>Resend Code
          </button>
        </div>
        
        <div id="otp_message" class="mt-3" style="display: none;"></div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-primary" id="verify_otp">
          <i class="fas fa-check mr-2"></i>Verify & Register
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fas fa-times mr-2"></i>Cancel
        </button>
      </div>
    </div>
  </div>
</div>
<script>
// Password toggle functionality for register form
document.addEventListener('DOMContentLoaded', function(){
  // Toggle password visibility for password field
  var toggleRegPassword = document.getElementById('toggleRegPassword');
  var regPassword = document.getElementById('reg_password');
  var regPasswordIcon = document.getElementById('regPasswordIcon');
  
  if (toggleRegPassword && regPassword && regPasswordIcon) {
    toggleRegPassword.addEventListener('click', function(){
      var type = regPassword.getAttribute('type') === 'password' ? 'text' : 'password';
      regPassword.setAttribute('type', type);
      
      // Toggle icon
      if (type === 'text') {
        regPasswordIcon.classList.remove('fa-eye');
        regPasswordIcon.classList.add('fa-eye-slash');
      } else {
        regPasswordIcon.classList.remove('fa-eye-slash');
        regPasswordIcon.classList.add('fa-eye');
      }
    });
  }
  
  // Toggle password visibility for confirm password field
  var toggleRegConfirmPassword = document.getElementById('toggleRegConfirmPassword');
  var regConfirmPassword = document.getElementById('reg_confirm_password');
  var regConfirmPasswordIcon = document.getElementById('regConfirmPasswordIcon');
  
  if (toggleRegConfirmPassword && regConfirmPassword && regConfirmPasswordIcon) {
    toggleRegConfirmPassword.addEventListener('click', function(){
      var type = regConfirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
      regConfirmPassword.setAttribute('type', type);
      
      // Toggle icon
      if (type === 'text') {
        regConfirmPasswordIcon.classList.remove('fa-eye');
        regConfirmPasswordIcon.classList.add('fa-eye-slash');
      } else {
        regConfirmPasswordIcon.classList.remove('fa-eye-slash');
        regConfirmPasswordIcon.classList.add('fa-eye');
      }
    });
  }
});
</script>

<!-- Reference Number Success Modal -->
<div class="modal fade modal-success" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="successModalLabel">
          <i class="fas fa-check-circle mr-2"></i>Registration Successful!
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <div class="mb-4">
          <i class="fas fa-user-check fa-4x text-success mb-3"></i>
          <h4 class="text-success">Welcome to ZPPSU!</h4>
          <p class="text-muted">Your account has been created successfully.</p>
        </div>
        
        <div class="alert alert-info">
          <h6 class="font-weight-bold mb-2">Your Reference Number:</h6>
          <div class="input-group">
            <input type="text" class="form-control form-control-lg text-center font-weight-bold" id="reference_number_display" readonly>
            <div class="input-group-append">
              <button class="btn btn-outline-primary" type="button" id="copy_reference_btn">
                <i class="fas fa-copy"></i> Copy
              </button>
            </div>
          </div>
          <small class="text-muted mt-2 d-block">
            <i class="fas fa-info-circle mr-1"></i>
            Please save this reference number. You'll need it for scheduling and inquiries.
          </small>
        </div>
        
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          <strong>Important:</strong> You can now log in using your username and password.
        </div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-success btn-lg" id="go_to_login">
          <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
        </button>
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
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  $(document).ready(function(){
    var SKIP_OTP = false; // Enable OTP verification
    var countdownTimer;
    var countdownSeconds = 30;
    var currentPhone = '';
    
    // End the preloader so the form is clickable
    if (typeof end_loader === 'function') end_loader();

    // Ensure Bootstrap modal is initialized
    if (typeof $.fn.modal === 'undefined') {
      alert('Bootstrap modal is not loaded. Please check your Bootstrap JS path and version.');
    }

    // Function to start countdown timer
    function startCountdown() {
      countdownSeconds = 30;
      $('#resend_otp').prop('disabled', true);
      $('#countdown_display').show();
      $('#otp_display').hide();
      
      countdownTimer = setInterval(function() {
        countdownSeconds--;
        $('#countdown_timer').text(countdownSeconds);
        
        if (countdownSeconds <= 0) {
          clearInterval(countdownTimer);
          $('#resend_otp').prop('disabled', false);
          $('#countdown_display').hide();
          $('#otp_display').show().html('<i class="fas fa-info-circle mr-2"></i>Code expired. Click resend to get a new code.');
        }
      }, 1000);
    }

    // Function to send OTP
    function sendOTP(phone) {
      $('#otp_display').html('<i class="fas fa-spinner fa-spin mr-2"></i>Sending OTP...');
      $('#phone_display').text(phone);
      $('#otp_code').val('');
      $('#otp_message').hide();
      
      $.post('send_otp.php', {phone: phone}, function(resp){
        if(resp.status === 'success'){
          $('#otp_display').html('<i class="fas fa-check-circle mr-2 text-success"></i>OTP sent successfully! Check your SMS.');
          startCountdown();
        } else {
          $('#otp_display').html('<i class="fas fa-exclamation-triangle mr-2 text-danger"></i>Failed to send OTP. ' + (resp.msg || 'Please try again.'));
          $('#resend_otp').prop('disabled', false);
        }
      }, 'json').fail(function(jqXHR, textStatus, errorThrown){
        $('#otp_display').html('<i class="fas fa-exclamation-triangle mr-2 text-danger"></i>Network error. Please check your connection and try again.');
        $('#resend_otp').prop('disabled', false);
        console.error('AJAX error:', textStatus, errorThrown);
      });
    }

    // Function to show message
    function showMessage(message, type) {
      var alertClass = type === 'success' ? 'alert-success-custom' : 'alert-danger-custom';
      $('#otp_message').html('<div class="alert-custom ' + alertClass + '">' + message + '</div>').show();
    }

    // Register submit handler
    $('#register-frm').submit(function(e){
      e.preventDefault();
      var form = $(this);
      
      // Validate form first
      var requiredFields = ['lastname', 'firstname', 'username', 'email', 'phone', 'lrn', 'password', 'confirm_password'];
      var isValid = true;
      var missingFields = [];
      
      requiredFields.forEach(function(field) {
        var value = form.find('[name="' + field + '"]').val().trim();
        if (!value) {
          isValid = false;
          missingFields.push(field.replace('_', ' '));
        }
      });
      
      if (!isValid) {
        showMessage('Please fill in all required fields: ' + missingFields.join(', '), 'error');
        return;
      }
      
      // Check password match
      var password = form.find('[name="password"]').val();
      var confirmPassword = form.find('[name="confirm_password"]').val();
      if (password !== confirmPassword) {
        showMessage('Passwords do not match. Please try again.', 'error');
        return;
      }
      
      // Check LRN format
      var lrn = form.find('[name="lrn"]').val();
      if (!/^\d{12}$/.test(lrn)) {
        showMessage('LRN must be exactly 12 digits.', 'error');
        return;
      }
      
      // Check phone format
      var phone = form.find('[name="phone"]').val();
      if (!/^09\d{9}$/.test(phone)) {
        showMessage('Phone number must be in format 09XXXXXXXXX.', 'error');
        return;
      }
      
      if (SKIP_OTP) {
        // Skip OTP for testing
        var data = form.serializeArray();
        var postData = {};
        $.each(data, function(i, field){ postData[field.name] = field.value; });
        postData['phone'] = '+63' + phone.substring(1);
        postData['skip_otp'] = 1;
        
        $.post('verify_otp.php', postData, function(resp){
          if(resp.status === 'success'){
            showMessage(resp.msg, 'success');
            setTimeout(function() {
              $('#register-frm')[0].reset();
              window.location.href = 'login.php';
            }, 2000);
          } else {
            showMessage(resp.msg || 'Registration failed.', 'error');
          }
        }, 'json').fail(function(){
          showMessage('Network error during registration.', 'error');
        });
        return;
      }
      
      // Normalize phone number
      currentPhone = '+63' + phone.substring(1);
      form.find('[name="phone"]').val(currentPhone);
      
      // Show OTP modal
      $('#otpModal').modal({backdrop: 'static', keyboard: false});
      $('#otpModal').modal('show');
      
      // Send OTP
      sendOTP(currentPhone);
    });

    // OTP verification
    $('#verify_otp').on('click', function(){
      var otp = $('#otp_code').val().trim();
      
      if (!otp || otp.length !== 6) {
        showMessage('Please enter a valid 6-digit OTP code.', 'error');
        return;
      }
      
      if (!/^\d{6}$/.test(otp)) {
        showMessage('OTP code must contain only numbers.', 'error');
        return;
      }
      
      var form = $('#register-frm');
      var data = form.serializeArray();
      var postData = {};
      
      $.each(data, function(i, field){
        postData[field.name] = field.value;
      });
      postData['otp'] = otp;
      
      $('#verify_otp').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Verifying...');
      
      $.post('verify_otp.php', postData, function(resp){
        $('#verify_otp').prop('disabled', false).html('<i class="fas fa-check mr-2"></i>Verify & Register');
        
        if(resp.status === 'success'){
          clearInterval(countdownTimer);
          $('#otpModal').modal('hide');
          
          // Show success modal with reference number
          if(resp.reference_number) {
            $('#reference_number_display').val(resp.reference_number);
            $('#successModal').modal({backdrop: 'static', keyboard: false});
            $('#successModal').modal('show');
          } else {
            showMessage(resp.msg, 'success');
            setTimeout(function() {
              $('#register-frm')[0].reset();
              window.location.href = resp.redirect_url || 'login.php';
            }, 2000);
          }
        } else {
          showMessage(resp.msg || 'OTP verification failed.', 'error');
        }
      }, 'json').fail(function(){
        $('#verify_otp').prop('disabled', false).html('<i class="fas fa-check mr-2"></i>Verify & Register');
        showMessage('Network error during verification.', 'error');
      });
    });

    // Resend OTP
    $('#resend_otp').on('click', function(){
      if (currentPhone) {
        sendOTP(currentPhone);
      }
    });

    // Auto-format OTP input
    $('#otp_code').on('input', function(){
      var value = $(this).val().replace(/\D/g, ''); // Remove non-digits
      $(this).val(value);
      
      // Auto-submit when 6 digits are entered
      if (value.length === 6) {
        setTimeout(function() {
          $('#verify_otp').click();
        }, 500);
      }
    });

    // Clear message when modal is closed
    $('#otpModal').on('hidden.bs.modal', function(){
      clearInterval(countdownTimer);
      $('#otp_message').hide();
      $('#otp_code').val('');
      $('#resend_otp').prop('disabled', false);
      $('#countdown_display').hide();
      $('#otp_display').show().html('<i class="fas fa-spinner fa-spin mr-2"></i>Sending OTP...');
    });

    // Copy reference number functionality
    $('#copy_reference_btn').on('click', function(){
      var referenceNumber = $('#reference_number_display').val();
      if (referenceNumber) {
        // Create temporary input element
        var tempInput = document.createElement('input');
        tempInput.value = referenceNumber;
        document.body.appendChild(tempInput);
        tempInput.select();
        tempInput.setSelectionRange(0, 99999); // For mobile devices
        
        try {
          var successful = document.execCommand('copy');
          if (successful) {
            $(this).html('<i class="fas fa-check"></i> Copied!');
            $(this).removeClass('btn-outline-primary').addClass('btn-success');
            setTimeout(function() {
              $('#copy_reference_btn').html('<i class="fas fa-copy"></i> Copy');
              $('#copy_reference_btn').removeClass('btn-success').addClass('btn-outline-primary');
            }, 2000);
          } else {
            // Fallback for browsers that don't support execCommand
            navigator.clipboard.writeText(referenceNumber).then(function() {
              $('#copy_reference_btn').html('<i class="fas fa-check"></i> Copied!');
              $('#copy_reference_btn').removeClass('btn-outline-primary').addClass('btn-success');
              setTimeout(function() {
                $('#copy_reference_btn').html('<i class="fas fa-copy"></i> Copy');
                $('#copy_reference_btn').removeClass('btn-success').addClass('btn-outline-primary');
              }, 2000);
            }).catch(function() {
              alert('Reference number: ' + referenceNumber);
            });
          }
        } catch (err) {
          // Fallback - show the reference number
          alert('Reference number: ' + referenceNumber);
        }
        
        document.body.removeChild(tempInput);
      }
    });

    // Go to login button
    $('#go_to_login').on('click', function(){
      $('#register-frm')[0].reset();
      window.location.href = 'login.php';
    });

    // Auto-select reference number when modal opens
    $('#successModal').on('shown.bs.modal', function(){
      $('#reference_number_display').select();
    });
  });
</script>
<!-- Only load AdminLTE after Bootstrap -->
<script src="/zppsu_admission/admin/dist/js/adminlte.min.js"></script>
</body>
</html>
