<?php require_once('../config.php'); ?>
 <!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once('inc/header.php') ?>
  <body class="sidebar-mini layout-fixed control-sidebar-slide-open layout-navbar-fixed sidebar-mini-md sidebar-mini-xs" data-new-gr-c-s-check-loaded="14.991.0" data-gr-ext-installed="" style="height: auto;">
    <div class="wrapper">
     <?php require_once('inc/topBarNav.php') ?>
     <?php require_once('inc/navigation.php') ?>
     <?php if($_settings->chk_flashdata('success')): ?>
      <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
      </script>
      <?php endif;?>    
     <?php 
        // Role-based routing system
        $role = isset($_SESSION['userdata']['role']) ? (int)$_SESSION['userdata']['role'] : null;
        $page = isset($_GET['page']) ? $_GET['page'] : 'admin';
        
        // Define role-based page mappings
        $rolePages = [
            1 => 'admin',    // Admin role -> /admin/ pages
            2 => 'staff',    // Staff/Teacher role -> /staff/ pages  
            3 => 'student'   // Student role -> /student/ pages
        ];
        
        // Get the role directory
        $roleDir = $rolePages[$role] ?? 'admin';
        
        // Check if user is trying to access a different role's pages
        $requestedRole = null;
        if (strpos($page, 'admin/') === 0) $requestedRole = 'admin';
        elseif (strpos($page, 'staff/') === 0) $requestedRole = 'staff';
        elseif (strpos($page, 'student/') === 0) $requestedRole = 'student';
        
        // Redirect to appropriate role directory if accessing wrong role
        if ($requestedRole && $requestedRole !== $roleDir) {
            // Redirect to user's role directory
            $redirectPage = str_replace($requestedRole . '/', $roleDir . '/', $page);
            header("Location: " . base_url . "admin/?page=" . $redirectPage);
            exit;
        }
        
        // For students, restrict access to only student pages
        if ($role === 3) {
            $studentAllowed = array('student/index','schedule', 'sms_log', 'user/manage_user');
            if (!in_array($page, $studentAllowed)) {
                $page = 'student/index';
            }
        }
        
        // For staff, restrict access to staff and admin pages
        if ($role === 2) {
            $staffAllowed = array('staff/index','home', 'teacher_log', 'sms_log', 'results', 'qr_scanner', 'bulk_reschedule', 'admin/user/list', 'admin/user/manage_user', 'staff/manage_user');
            if (!in_array($page, $staffAllowed)) {
                $page = 'staff/index';
            }
        }
     ?>
      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper pt-3" style="min-height: 567.854px;">
     
        <!-- Main content -->
        <section class="content ">
          <div class="container-fluid">
            <?php 
              if(!file_exists($page.".php") && !is_dir($page)){
                  include '404.html';
              }else{
                if(is_dir($page))
                  include $page.'/index.php';
                else
                  include $page.'.php';

              }
            ?>
          </div>
        </section>
        <!-- /.content -->
  <div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Confirmation</h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal_right" role='dialog'>
    <div class="modal-dialog modal-full-height  modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span class="fa fa-arrow-right"></span>
        </button>
      </div>
      <div class="modal-body">
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="viewer_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
              <button type="button" class="btn-close" data-dismiss="modal"><span class="fa fa-times"></span></button>
              <img src="" alt="">
      </div>
    </div>
  </div>
      </div>
      <!-- /.content-wrapper -->
      <?php require_once('inc/footer.php') ?>
  </body>
</html>
