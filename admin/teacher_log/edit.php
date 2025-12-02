<?php
require_once '../inc/db_connect.php';
require_once '../inc/db_handler.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$db = new DatabaseHandler($conn);
$record = $db->getRecordById($id);

if (!$record) {
    echo '<div class="alert alert-danger">Record not found.</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Example: update name, phone, email
    $surname = $_POST['surname'] ?? '';
    $given_name = $_POST['given_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $stmt = $conn->prepare("UPDATE schedule_admission SET surname=?, given_name=?, middle_name=?, phone=?, email=? WHERE id=?");
    $stmt->bind_param("sssssi", $surname, $given_name, $middle_name, $phone, $email, $id);
    $stmt->execute();
    echo '<div class="alert alert-success">Record updated!</div>';
    $record = $db->getRecordById($id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Applicant</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Edit Applicant</h2>
    <form method="post">
        <div class="form-group">
            <label>Surname</label>
            <input type="text" name="surname" class="form-control" value="<?php echo htmlspecialchars($record['surname']); ?>">
        </div>
        <div class="form-group">
            <label>Given Name</label>
            <input type="text" name="given_name" class="form-control" value="<?php echo htmlspecialchars($record['given_name']); ?>">
        </div>
        <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($record['middle_name']); ?>">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($record['phone']); ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($record['email']); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
