<?php
// Minimal OTP verification + registration handler
error_reporting(0);
ini_set('display_errors', 0);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';
header('Content-Type: application/json');

// Allow OTP to be skipped for testing
$skipOtp = isset($_POST['skip_otp']);
// Validate OTP only when not skipping
if (!$skipOtp) {
    $otp = $_POST['otp'] ?? '';
    if (!$otp || !isset($_SESSION['register_otp']) || $otp != $_SESSION['register_otp']) {
        echo json_encode(['status' => 'error', 'msg' => 'Invalid OTP.']);
        exit;
    }
}

// Collect and validate inputs
$fullname = trim($_POST['fullname'] ?? '');
$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$lrn = trim($_POST['lrn'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($username === '' || $email === '' || $phone === '' || $password === '' || $confirm === '' || $lrn === '') {
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

// Match phone to the one that received the OTP (skip when testing)
if (!$skipOtp) {
    if (!isset($_SESSION['register_phone']) || $_SESSION['register_phone'] !== $phone) {
        echo json_encode(['status' => 'error', 'msg' => 'Phone mismatch. Please use the same number that received the OTP.']);
        exit;
    }
}

// Ensure users.lrn and users.reference_number columns exist
try {
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS lrn VARCHAR(20) NULL AFTER phone");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS reference_number VARCHAR(20) NULL AFTER lrn");
} catch (Exception $e) {}

// Check username uniqueness
$uname = $conn->real_escape_string($username);
$dup = $conn->query("SELECT id FROM users WHERE username = '{$uname}' LIMIT 1");
if ($dup && $dup->num_rows > 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Username already exists.']);
    exit;
}

// Parse fullname if firstname/lastname are not provided
if (empty($firstname) || empty($lastname)) {
    // Parse from fullname: "LASTNAME, FIRSTNAME M."
    if (strpos($fullname, ',') !== false) {
        $parts = explode(',', $fullname, 2);
        $lastname = trim($parts[0]);
        $rest = trim($parts[1] ?? '');
        $tokens = preg_split('/\s+/', $rest);
        // Check if last token is middle initial
        $lastToken = end($tokens);
        if (count($tokens) > 1 && preg_match('/^[A-Z]\.?$/i', $lastToken)) {
            array_pop($tokens); // Remove middle initial
        }
        $firstname = implode(' ', $tokens);
    } else {
        // Fallback: simple space split
        $parts = preg_split('/\s+/', $fullname);
        $lastname = array_pop($parts);
        $firstname = implode(' ', $parts);
        if ($firstname === '') { $firstname = $lastname; $lastname = ''; }
    }
}

// Generate unique reference number (format: 3-3-4 digits like 123-456-7890)
function generate_ref($conn){
    do {
        $ref = sprintf('%03d-%03d-%04d', rand(0,999), rand(0,999), rand(0,9999));
        $chk = $conn->query("SELECT id FROM users WHERE reference_number = '".$conn->real_escape_string($ref)."' LIMIT 1");
    } while ($chk && $chk->num_rows > 0);
    return $ref;
}
$reference_number = generate_ref($conn);

// Insert user (role 3 = Student) with reference number
$stmt = $conn->prepare("INSERT INTO users (firstname, lastname, middlename, username, password, phone, email, lrn, reference_number, role, date_added) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 3, NOW())");
$hashed = md5($password);
$stmt->bind_param('sssssssss', $firstname, $lastname, $middlename, $username, $hashed, $phone, $email, $lrn, $reference_number);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // Clean OTP from session
    unset($_SESSION['register_otp']);
    unset($_SESSION['register_phone']);
    echo json_encode([
        'status' => 'success', 
        'msg' => 'Registration successful!', 
        'reference_number' => $reference_number,
        'redirect_url' => 'login.php'
    ]);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Registration failed. ' . $conn->error]);
}


