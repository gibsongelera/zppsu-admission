<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    $link = "https"; 
else
    $link = "http"; 
$link .= "://"; 
$link .= $_SERVER['HTTP_HOST']; 
$link .= $_SERVER['REQUEST_URI'];
// Allow access to login, forgot password, and reset password pages without authentication
if(!isset($_SESSION['userdata']) && strpos($link, 'login.php') === false && strpos($link, 'forgot_password.php') === false && strpos($link, 'reset_password.php') === false){
	redirect('admin/login.php');
}
if(isset($_SESSION['userdata']) && strpos($link, 'login.php') !== false){
	redirect('admin/index.php');
}
// Get user role (handle both 'role' and 'type' columns, and 'login_type')
$userRole = isset($_SESSION['userdata']['role']) ? (int)$_SESSION['userdata']['role'] : 
            (isset($_SESSION['userdata']['type']) ? (int)$_SESSION['userdata']['type'] : 
            (isset($_SESSION['userdata']['login_type']) ? (int)$_SESSION['userdata']['login_type'] : null));

// Role-based module mapping
$module = array('', 'admin', 'staff', 'student'); // Index 0 unused, 1=admin, 2=staff, 3=student

// Exclude certain pages from strict admin-only access control
$excludedPages = array('schedule/print.php', 'sms_log', 'schedule/index.php', 'register.php', 'verify_otp.php', 'send_otp.php');
$isExcluded = false;
foreach ($excludedPages as $excluded) {
    if (strpos($link, $excluded) !== false) {
        $isExcluded = true;
        break;
    }
}

// Allow access based on role - don't block student/teacher from accessing admin/index.php
// The routing in admin/index.php will handle redirecting them to the correct page
if(isset($_SESSION['userdata']) && !$isExcluded && $userRole !== null) {
    // Only block if trying to access admin-specific pages and not admin
    // But let the routing system in admin/index.php handle the redirect
    // This allows all authenticated users to access admin/index.php, which will route them correctly
}
?>
<!-- rest of your header code -->
