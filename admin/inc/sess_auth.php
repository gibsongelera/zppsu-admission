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
$module = array('','admin','faculty','student');
// Exclude certain pages from strict admin-only access control (like print.php which has its own access control)
$excludedPages = array('schedule/print.php', 'sms_log', 'schedule/index.php');
$isExcluded = false;
foreach ($excludedPages as $excluded) {
    if (strpos($link, $excluded) !== false) {
        $isExcluded = true;
        break;
    }
}
if(isset($_SESSION['userdata']) && !$isExcluded && (strpos($link, 'index.php') || strpos($link, 'admin/')) && $_SESSION['userdata']['login_type'] !=  1){
	echo "<script>alert('Access Denied!');location.replace('".base_url.$module[$_SESSION['userdata']['login_type']]."');</script>";
    exit;
}
?>
<!-- rest of your header code -->
