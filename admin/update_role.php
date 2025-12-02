<?php
// update_role.php
require_once '../config.php'; // Adjust path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $role = isset($_POST['role']) ? intval($_POST['role']) : 0;
    if ($user_id > 0 && in_array($role, [1,2])) {
        $stmt = $conn->prepare("UPDATE users SET type = ? WHERE id = ?");
        $stmt->bind_param("ii", $role, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','msg'=>'User role updated.']);
        } else {
            echo json_encode(['status'=>'error','msg'=>'Update failed: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['status'=>'error','msg'=>'Invalid user ID or role.']);
    }
    exit;
}
?>
<!-- Simple HTML form for testing -->
<!DOCTYPE html>
<html>
<head><title>Update User Role</title></head>
<body>
    <form method="post">
        <label>User ID: <input type="number" name="user_id" required></label><br>
        <label>Role:
            <select name="role">
                <option value="1">Admin</option>
                <option value="2">Staff</option>
            </select>
        </label><br>
        <button type="submit">Update Role</button>
    </form>
</body>
</html>
