<style>
  .user-img {
      position: absolute;
      height: 27px;
      width: 27px;
      object-fit: cover;
      left: -7%;
      top: -12%;
  }
  .btn-rounded {
      border-radius: 50px;
  }
  .navbar-custom {
      background-color: #5e0a14ff; /* Change navbar background color */
      border: none; /* Remove borders */
      position: relative; /* Ensure proper stacking context */
      z-index: 10060; /* Keep above page overlays/alerts */
  }
  .navbar-custom .nav-link {
      color: white; /* Change text color for links */
  }
  .navbar-custom .dropdown-menu {
      background-color: #770815ff; /* Keep dropdown background consistent */
      z-index: 10070; /* Ensure dropdown appears above other UI */
  }
  .navbar-custom .dropdown-item {
      color: white; /* Change dropdown item text color */
  }
  .navbar-custom .dropdown-item:hover {
      background-color: #f1eff0ff; /* Optional: Add hover effect for dropdown items */
  }
  /* Ensure Bootstrap modals appear above navbar/backdrops */
  .modal { z-index: 11050; }
  .modal-backdrop { z-index: 11040; }
</style>
<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark navbar-custom text-sm">
    <style>
    /* Ensure dropdown stays above content */
    .main-header .dropdown-menu { z-index: 10080; }
    /* Do not reset modal z-index here to avoid blocking clicks */
    .content-wrapper, .content, .card { z-index: auto; }
    </style>
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo base_url ?>" class="nav-link"><?php echo (!isMobileDevice()) ? $_settings->info('name') : $_settings->info('short_name'); ?></a>
        </li>
    </ul>
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Messages Dropdown Menu -->
        <li class="nav-item">
            <div class="btn-group nav-link">
                <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    <span><img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="img-circle elevation-2 user-img" alt="User Image"></span>
                    <span class="ml-3"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu" role="menu">
                    <?php 
                    $user_role = $_settings->userdata('role') ?? 3;
                    if ($user_role == 3) {
                        // Student role: link to student profile page
                        $account_url = base_url.'admin/?page=user/manage_user';
                    } elseif ($user_role == 2) {
                        // Staff/Teacher role: link to staff profile page
                        $account_url = base_url.'admin/?page=staff/manage_user';
                    } else {
                        // Admin or default
                        $account_url = base_url.'admin/?page=user/manage_user&id='.$_settings->userdata('id');
                    }
                    ?>
                    <a class="dropdown-item" href="<?php echo $account_url; ?>"><span class="fa fa-user"></span> My Account</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo base_url.'/classes/Login.php?f=logout' ?>"><span class="fas fa-sign-out-alt"></span> Logout</a>
                </div>
            </div>
        </li>
        <li class="nav-item">
        </li>
    </ul>
</nav>
<!-- /.navbar -->