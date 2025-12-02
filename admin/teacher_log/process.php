
<?php
require_once '../inc/db_connect.php';
require_once '../inc/db_handler.php';

$db = new DatabaseHandler($conn);

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $success = false;

    switch($action) {
        case 'approve':
            $success = $db->updateStatus($id, 'Approved');
            break;
        case 'pending':
            $success = $db->updateStatus($id, 'Pending');
            break;
        case 'reject':
            $success = $db->updateStatus($id, 'Rejected');
            break;
        case 'delete':
            $success = $db->deleteRecord($id);
            break;
    }

    $_SESSION['message'] = $success ? 'Operation successful' : 'Operation failed';
    $_SESSION['message_type'] = $success ? 'success' : 'error';
}

header('Location: index.php');
exit;