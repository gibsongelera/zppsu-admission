<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
 <?php require_once('inc/header.php') ?>
<body class="hold-transition login-page dark-mode">
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
  <!-- /.login-logo -->
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="./" class="h1"><b>Login</b></a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Sign in to start your session</p>

      <form id="login-frm" action="" method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control" autofocus name="username" placeholder="Username">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" name="password" id="login_password" placeholder="Password">
          <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword" style="border-color: #ced4da;">
              <span class="fas fa-eye"></span>
            </button>
          </div>
        </div>
        <div class="row">
          <div class="col-8"></div>
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
          </div>
        </div>
      </form>
      <hr>
      <div class="text-center">
        <a class="btn btn-link" href="<?php echo base_url ?>admin/register.php">Don't have an account? Register</a>
        <br>
        <a class="btn btn-link" href="forgot_password.php">Forgot Password?</a>
      </div>
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo base_url ?>dist/js/adminlte.min.js"></script>
<!-- SweetAlert2 -->
<script src="<?php echo base_url ?>plugins/sweetalert2/sweetalert2.min.js"></script>
<script>
  var _base_url_ = '<?php echo base_url ?>';
</script>
<!-- Custom Script -->
<script src="<?php echo base_url ?>dist/js/script.js"></script>

<script>
  $(document).ready(function(){
    end_loader();
    
    // Password toggle functionality
    $('#toggleLoginPassword').on('click', function(){
      var passwordField = $('#login_password');
      var icon = $(this).find('span');
      
      if (passwordField.attr('type') === 'password') {
        passwordField.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
      } else {
        passwordField.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
      }
    });
    // Show register form
    $('#show-register').click(function(){
      $('#login-frm').hide();
      $('#show-register').hide();
      $('#register-frm').show();
    });
    // Registration AJAX with OTP
    $('#register-frm').submit(function(e){
      e.preventDefault();
      var form = $(this);
      var phone = form.find('[name="phone"]').val();
      // Send OTP
      $.post('send_otp.php', {phone: phone}, function(resp){
        if(resp.status === 'success'){
          $('#otpModal').modal('show');
        } else {
          alert(resp.msg || 'Failed to send OTP');
        }
      }, 'json');
    });
    // OTP verification and registration completion
    $('#verify_otp').click(function(){
      var otp = $('#otp_code').val();
      var form = $('#register-frm');
      var data = form.serializeArray();
      var postData = {};
      $.each(data, function(i, field){
        postData[field.name] = field.value;
      });
      postData['otp'] = otp;
      $.post('verify_otp.php', postData, function(resp){
        if(resp.status === 'success'){
          alert(resp.msg);
          $('#otpModal').modal('hide');
          $('#register-frm')[0].reset();
          $('#register-frm').hide();
          $('#login-frm').show();
          $('#show-register').show();
        } else {
          alert(resp.msg || 'OTP verification failed.');
        }
      }, 'json');
    });
  })
</script>
</body>
</html>
