<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
 <?php require_once('inc/header.php') ?>
<body class="hold-transition login-page dark-mode" style="height: auto !important;">
  <script>
    start_loader()
  </script>
  <style>
    body{
      background-image: url("<?php echo validate_image($_settings->info('cover')) ?>");
      background-size:cover;
      background-repeat:no-repeat;
      min-height: 100vh;
    }
    .login-title{
      text-shadow: 2px 2px black
    }
    .login-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: flex-start;
      gap: 30px;
      padding: 20px;
      max-width: 1400px;
      margin: 0 auto;
    }
    .login-box {
      margin: 0;
      width: 360px;
    }
    .calendar-box {
      width: 100%;
      max-width: 700px;
    }
    .calendar-card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    .calendar-header {
      background: linear-gradient(135deg, #5E0A14, #8B1A2B);
      color: white;
      border-radius: 10px 10px 0 0;
      padding: 15px;
    }
    .calendar-header h4 {
      margin: 0;
      font-weight: 600;
    }
    .calendar-body {
      padding: 15px;
    }
    .calendar-legend {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 15px;
      justify-content: center;
    }
    .calendar-legend span {
      font-size: 0.85rem;
    }
    #login-calendar table {
      width: 100%;
      border-collapse: collapse;
    }
    #login-calendar th {
      background: #5E0A14;
      color: white;
      padding: 8px 4px;
      text-align: center;
      font-size: 0.8rem;
    }
    #login-calendar td {
      border: 1px solid #dee2e6;
      padding: 5px;
      text-align: center;
      vertical-align: top;
      min-height: 60px;
      font-size: 0.9rem;
    }
    #login-calendar td.weekend {
      background: #f8f9fa;
    }
    #login-calendar td.today {
      background: #fff3cd;
    }
    #login-calendar .day-number {
      font-weight: 600;
      display: block;
      margin-bottom: 3px;
    }
    .nav-btn {
      background: transparent;
      border: 1px solid rgba(255,255,255,0.5);
      color: white;
      padding: 5px 12px;
      border-radius: 5px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .nav-btn:hover {
      background: rgba(255,255,255,0.2);
    }
    
    @media (max-width: 992px) {
      .login-container {
        flex-direction: column;
        align-items: center;
      }
      .calendar-box {
        order: 2;
        max-width: 100%;
      }
      .login-box {
        order: 1;
      }
    }
    @media (max-width: 576px) {
      .login-box {
        width: 100%;
        max-width: 360px;
      }
      #login-calendar th,
      #login-calendar td {
        padding: 3px 2px;
        font-size: 0.75rem;
      }
      .calendar-legend span {
        font-size: 0.75rem;
      }
    }
  </style>
  <h1 class="text-center py-4 login-title"><b><?php echo $_settings->info('name') ?></b></h1>

<div class="login-container">
  <!-- Login Box -->
  <div class="login-box">
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
    </div>
  </div>
  
  <!-- Admission Calendar -->
  <div class="calendar-box">
    <div class="calendar-card">
      <div class="calendar-header d-flex justify-content-between align-items-center">
        <button class="nav-btn" id="cal-prev"><i class="fas fa-chevron-left"></i></button>
        <div class="text-center">
          <h4 id="cal-title"><i class="fas fa-calendar-alt mr-2"></i>Admission Schedule</h4>
          <small>View available exam dates</small>
        </div>
        <button class="nav-btn" id="cal-next"><i class="fas fa-chevron-right"></i></button>
      </div>
      <div class="calendar-body">
        <div class="calendar-legend">
          <span><span class="badge badge-success">&#9679;</span> Available (Weekends)</span>
          <span><span class="badge badge-warning">&#9679;</span> Limited Slots</span>
          <span><span class="badge badge-danger">&#9679;</span> Full (100+)</span>
        </div>
        <div id="login-calendar">
          <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading calendar...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- /.login-container -->

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
    
    // ============================================
    // Admission Calendar
    // ============================================
    var container = document.getElementById('login-calendar');
    var titleEl = document.getElementById('cal-title');
    var y = (new Date()).getFullYear();
    var m = (new Date()).getMonth();
    var baseUrl = _base_url_;

    function fmtMonth(year, monthIdx){
        return year + '-' + String(monthIdx+1).padStart(2,'0');
    }

    function renderCalendar(year, monthIdx){
        var monthStr = fmtMonth(year, monthIdx);
        var monthName = new Date(year, monthIdx, 1).toLocaleString('default', { month:'long', year:'numeric' });
        if (titleEl) titleEl.innerHTML = '<i class="fas fa-calendar-alt mr-2"></i>' + monthName;
        
        fetch(baseUrl + 'admin/inc/calendar_counts.php?month=' + encodeURIComponent(monthStr))
          .then(function(r){ return r.json(); })
          .then(function(data){
            var counts = (data && data.counts) ? data.counts : {};
            var todayStr = new Date().toISOString().slice(0,10);
            var first = new Date(year, monthIdx, 1);
            var last = new Date(year, monthIdx+1, 0);
            
            var html = '<table class="table table-bordered mb-0"><thead><tr>'+
                       '<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>'+
                       '</tr></thead><tbody>';
            var d = 1, started = false;
            
            for (var r=0; r<6; r++){
                html += '<tr>';
                for (var c=0; c<7; c++){
                    var cell = '';
                    var cellClass = '';
                    var isWeekend = (c === 0 || c === 6);
                    
                    if (!started && c === first.getDay()) started = true;
                    
                    if (started && d <= last.getDate()){
                        var mm = String(monthIdx+1).padStart(2,'0');
                        var dd = String(d).padStart(2,'0');
                        var key = year+'-'+mm+'-'+dd;
                        var cnt = counts[key] || 0;
                        var dateObj = new Date(year, monthIdx, d);
                        var isPast = dateObj < new Date(new Date().setHours(0,0,0,0));
                        
                        cell = '<span class="day-number">' + d + '</span>';
                        
                        if (isWeekend && !isPast) {
                            // Weekend - show booking status
                            if (cnt >= 100) {
                                cell += '<span class="badge badge-danger">Full</span>';
                            } else if (cnt > 50) {
                                cell += '<span class="badge badge-warning">' + cnt + '</span>';
                            } else if (cnt > 0) {
                                cell += '<span class="badge badge-info">' + cnt + '</span>';
                            } else {
                                cell += '<span class="badge badge-success">Open</span>';
                            }
                            cellClass = 'weekend';
                        } else if (!isPast && cnt > 0) {
                            cell += '<small class="text-muted">' + cnt + '</small>';
                        }
                        
                        if (key === todayStr) {
                            cellClass += ' today';
                        }
                        
                        d++;
                    }
                    
                    html += '<td class="' + cellClass + '">' + cell + '</td>';
                }
                html += '</tr>';
                if (d > last.getDate()) break;
            }
            html += '</tbody></table>';
            html += '<div class="text-center mt-3 small text-muted">';
            html += '<i class="fas fa-info-circle mr-1"></i> Exams are only held on <strong>Saturdays</strong> and <strong>Sundays</strong>';
            html += '</div>';
            
            if (container) container.innerHTML = html;
          })
          .catch(function(err){
            console.error('Calendar error:', err);
            if (container) container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Could not load calendar</div>';
          });
    }

    var prevBtn = document.getElementById('cal-prev');
    var nextBtn = document.getElementById('cal-next');
    
    if (prevBtn) prevBtn.addEventListener('click', function(){
        m -= 1; 
        if (m < 0){ m = 11; y -= 1; }
        renderCalendar(y, m);
    });
    
    if (nextBtn) nextBtn.addEventListener('click', function(){
        m += 1; 
        if (m > 11){ m = 0; y += 1; }
        renderCalendar(y, m);
    });

    // Initial render
    renderCalendar(y, m);
  })
</script>
</body>
</html>