<?php
// Minimal OTP verification + registration handler
error_reporting(0);
ini_set('display_errors', 0);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';
header('Content-Type: application/json');

// Validate OTP
$otp = $_POST['otp'] ?? '';
if (!$otp || !isset($_SESSION['register_otp']) || $otp != $_SESSION['register_otp']) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid OTP.']);
    exit;
}

// Collect and validate inputs
$fullname = trim($_POST['fullname'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$lrn = trim($_POST['lrn'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($fullname === '' || $username === '' || $email === '' || $phone === '' || $password === '' || $confirm === '' || $lrn === '') {
    echo json_encode(['status' => 'error', 'msg' => 'All fields are required.']);
    exit;
}
if ($password !== $confirm) {
    echo json_encode(['status' => 'error', 'msg' => 'Passwords do not match.']);
    exit;
}
if (!preg_match('/^\+\d{10,15}$/', $phone)) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid phone number format. Use +639XXXXXXXXX.']);
    exit;
}
if (!preg_match('/^\d{12}$/', $lrn)) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid LRN. It must be 12 digits.']);
    exit;
}

// Match phone to the one that received the OTP
if (!isset($_SESSION['register_phone']) || $_SESSION['register_phone'] !== $phone) {
    echo json_encode(['status' => 'error', 'msg' => 'Phone mismatch. Please use the same number that received the OTP.']);
    exit;
}

// Ensure users.lrn column exists
try {
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS lrn VARCHAR(20) NULL AFTER phone");
} catch (Exception $e) {}

// Check username uniqueness
$uname = $conn->real_escape_string($username);
$dup = $conn->query("SELECT id FROM users WHERE username = '{$uname}' LIMIT 1");
if ($dup && $dup->num_rows > 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Username already exists.']);
    exit;
}

// Split fullname into firstname/lastname (simple heuristic)
$parts = preg_split('/\s+/', $fullname);
$lastname = array_pop($parts);
$firstname = implode(' ', $parts);
if ($firstname === '') { $firstname = $lastname; $lastname = ''; }

// Insert user (role 3 = Student)
$stmt = $conn->prepare("INSERT INTO users (firstname, lastname, username, password, phone, email, lrn, role, date_added) VALUES (?, ?, ?, ?, ?, ?, ?, 3, NOW())");
$hashed = md5($password);
$stmt->bind_param('sssssss', $firstname, $lastname, $username, $hashed, $phone, $email, $lrn);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // Clean OTP from session
    unset($_SESSION['register_otp']);
    unset($_SESSION['register_phone']);
    echo json_encode(['status' => 'success', 'msg' => 'Registration successful. You can now log in.']);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Registration failed. ' . $conn->error]);
}


