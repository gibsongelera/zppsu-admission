<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../inc/db_connect.php';

// Validate and get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Fallbacks: allow printing by reference number or latest record for current student
if ($id < 0) {
    // Try reference number
    $ref = isset($_GET['reference']) ? trim($_GET['reference']) : (isset($_GET['reference_number']) ? trim($_GET['reference_number']) : '');
    if (!empty($ref)) {
        $stmt = $conn->prepare("SELECT id FROM schedule_admission WHERE reference_number = ? LIMIT 1");
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        $rs = $stmt->get_result();
        if ($rs && $rs->num_rows) {
            $id = (int)$rs->fetch_assoc()['id'];
        }
        $stmt->close();
    }
}
if ($id < 0) {
    // If student is logged in, find their schedule by reference_number and LRN
    $currentRole = isset($_SESSION['userdata']['role']) ? (int)$_SESSION['userdata']['role'] : null;
    if ($currentRole === 3) {
        $sessRefNumber = isset($_SESSION['userdata']['reference_number']) ? trim($_SESSION['userdata']['reference_number']) : '';
        $sessLrn = isset($_SESSION['userdata']['lrn']) ? trim($_SESSION['userdata']['lrn']) : '';
        
        // Find the latest matching record by reference_number AND lrn
        if (!empty($sessRefNumber) && !empty($sessLrn)) {
            $sql = "SELECT id FROM schedule_admission WHERE 
                    reference_number = ? AND lrn = ?
                    ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $sessRefNumber, $sessLrn);
            $stmt->execute();
            $rs = $stmt->get_result();
            if ($rs && $rs->num_rows) {
                $id = (int)$rs->fetch_assoc()['id'];
            }
            $stmt->close();
        }
    }
}
if ($id < 0) {
    // As a final fallback, use the latest schedule record
    $rs = $conn->query("SELECT id FROM schedule_admission ORDER BY created_at DESC LIMIT 1");
    if ($rs && $rs->num_rows) {
        $id = (int)$rs->fetch_assoc()['id'];
    }
}
if ($id < 0) {
    echo '<div style="padding:20px;font-family:Arial;">Invalid request.</div>';
    exit;
}

// Fetch record
$stmt = $conn->prepare("SELECT * FROM schedule_admission WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$record = $res && $res->num_rows ? $res->fetch_assoc() : null;
$stmt->close();

if (!$record) {
    echo '<div style="padding:20px;font-family:Arial;">Record not found.</div>';
    exit;
}

// Access control: students can only print their own slip using reference_number and LRN
$currentRole = isset($_SESSION['userdata']['role']) ? (int)$_SESSION['userdata']['role'] : null;
if ($currentRole === 3) {
    // Get student's reference number and LRN from session
    $sessRefNumber = isset($_SESSION['userdata']['reference_number']) ? trim($_SESSION['userdata']['reference_number']) : '';
    $sessLrn = isset($_SESSION['userdata']['lrn']) ? trim($_SESSION['userdata']['lrn']) : '';
    
    // Verify ownership by matching reference_number AND lrn
    $ownsRecord = false;
    
    // Both reference_number and LRN must match
    if (!empty($sessRefNumber) && !empty($sessLrn)) {
        $recordRefNumber = isset($record['reference_number']) ? trim($record['reference_number']) : '';
        $recordLrn = isset($record['lrn']) ? trim($record['lrn']) : '';
        
        if (strcasecmp($sessRefNumber, $recordRefNumber) === 0 && strcasecmp($sessLrn, $recordLrn) === 0) {
            $ownsRecord = true;
        }
    }
    
    if (!$ownsRecord) {
        echo '<div style="padding:20px;font-family:Arial;text-align:center;">
            <h3>Access Denied</h3>
            <p>You can only print your own admission slip.</p>
            <p>This slip\'s Reference Number or LRN does not match your account.</p>
            <br>
            <p><strong>Your Reference Number:</strong> ' . htmlspecialchars($sessRefNumber) . '</p>
            <p><strong>Your LRN:</strong> ' . htmlspecialchars($sessLrn) . '</p>
            <br>
            <a href="javascript:history.back()" style="color:blue;text-decoration:underline;">Go Back</a>
        </div>';
        exit;
    }
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$fullName = trim($record['surname'] . ', ' . $record['given_name'] . ' ' . $record['middle_name']);
$issuedDate = date('F d, Y');
$logoPath = '../../uploads/zppsu1.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admission Slip - <?php echo h($record['reference_number']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        :root{ --brand:#5E0A14; }
        body{ background:#fff; color:#000; }
        .slip-container{ max-width: 900px; margin: 30px auto; padding: 32px; border:1px solid #dee2e6; }
        .slip-header{ display:flex; align-items:center; border-bottom:3px solid var(--brand); padding-bottom:16px; margin-bottom:20px; }
        .slip-header img{ width:80px; height:80px; object-fit:cover; }
        .slip-header .title{ flex:1; text-align:center; }
        .slip-header .title h4{ margin:0; font-weight:700; letter-spacing:.5px; }
        .slip-header .title small{ display:block; color:#666; }
        .slip-meta{ margin-bottom: 18px; }
        .meta-row{ display:flex; flex-wrap:wrap; margin-bottom:8px; }
        .meta-row .label{ width: 220px; color:#555; }
        .meta-row .value{ flex:1; font-weight:600; }
        .section-title{ font-weight:700; color:var(--brand); margin-top:18px; border-bottom:1px solid #eee; padding-bottom:6px; }
        .note{ font-size:.92rem; color:#333; margin-top:18px; }
        .signatures{ margin-top:40px; display:flex; gap:40px; }
        .sig{ flex:1; text-align:center; }
        .sig .line{ margin-top:60px; border-top:1px solid #000; padding-top:6px; }
        .print-actions{ margin:20px auto; max-width:900px; display:flex; gap:10px; justify-content:flex-end; }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .slip-container{ 
                max-width: 95%; 
                margin: 15px auto; 
                padding: 20px; 
                border: 1px solid #dee2e6; 
            }
            .slip-header{ 
                flex-direction: column; 
                text-align: center; 
                padding-bottom: 12px; 
                margin-bottom: 15px; 
            }
            .slip-header img{ 
                width: 60px; 
                height: 60px; 
                margin-bottom: 10px; 
            }
            .slip-header .title h4{ 
                font-size: 1.1rem; 
                margin-bottom: 5px; 
            }
            .slip-header .title small{ 
                font-size: 0.8rem; 
            }
            .meta-row{ 
                flex-direction: column; 
                margin-bottom: 10px; 
            }
            .meta-row .label{ 
                width: 100%; 
                font-weight: 600; 
                margin-bottom: 2px; 
            }
            .meta-row .value{ 
                width: 100%; 
                margin-bottom: 8px; 
            }
            .section-title{ 
                font-size: 1rem; 
                margin-top: 15px; 
            }
            .note{ 
                font-size: 0.85rem; 
                margin-top: 15px; 
            }
            .signatures{ 
                margin-top: 30px; 
                flex-direction: column; 
                gap: 20px; 
            }
            .sig .line{ 
                margin-top: 40px; 
            }
            .print-actions{ 
                margin: 15px auto; 
                max-width: 95%; 
                flex-direction: column; 
                gap: 10px; 
            }
            .print-actions .btn{ 
                width: 100%; 
                padding: 12px; 
                font-size: 1rem; 
            }
        }
        
        @media (max-width: 480px) {
            .slip-container{ 
                padding: 15px; 
            }
            .slip-header img{ 
                width: 50px; 
                height: 50px; 
            }
            .slip-header .title h4{ 
                font-size: 1rem; 
            }
            .slip-header .title small{ 
                font-size: 0.7rem; 
            }
            .meta-row .label{ 
                font-size: 0.9rem; 
            }
            .meta-row .value{ 
                font-size: 0.9rem; 
            }
            .section-title{ 
                font-size: 0.9rem; 
            }
            .note{ 
                font-size: 0.8rem; 
            }
            .sig .line{ 
                margin-top: 30px; 
                font-size: 0.9rem; 
            }
        }
        
        @media print {
            .print-actions{ display:none !important; }
            .slip-container{ border:none; margin:0; padding:0; }
            .slip-header{ flex-direction: row; }
            .slip-header img{ width:80px; height:80px; }
            .meta-row{ flex-direction: row; }
            .meta-row .label{ width: 220px; }
            .signatures{ flex-direction: row; gap:40px; }
            .sig .line{ margin-top:60px; }
        }
    </style>
    <script>
        function doPrint(){ try { window.print(); } catch(e) {} }
        // Auto-trigger print shortly after load
        document.addEventListener('DOMContentLoaded', function(){
            setTimeout(function(){ doPrint(); }, 300);
        });
    </script>
    </head>
<body>
    <div class="print-actions">
        
        <button class="btn btn-primary" onclick="doPrint()"><span class="fas fa-print mr-1"></span>Print</button>
    </div>
    <div class="slip-container">
        <div class="slip-header">
            <img src="<?php echo h($logoPath); ?>" alt="Logo">
            <div class="title">
                <h4>Zamboanga Peninsula Polytechnic State University</h4>
                <small>Official Admission Test Scheduling Slip</small>
            </div>
            <div style="width:80px;"></div>
        </div>

        <div class="slip-meta">
            <div class="meta-row"><div class="label">Reference Number</div><div class="value"><?php echo h($record['reference_number']); ?></div></div>
            <div class="meta-row"><div class="label">Issued Date</div><div class="value"><?php echo h($issuedDate); ?></div></div>
            <div class="meta-row"><div class="label">Status</div><div class="value"><?php echo h($record['status']); ?></div></div>
        </div>

        <div class="section-title">Applicant Information</div>
        <div class="meta-row"><div class="label">Full Name</div><div class="value"><?php echo h($fullName); ?></div></div>
        <div class="meta-row"><div class="label">Scheduled Date</div><div class="value"><?php echo h($record['date_scheduled']); ?></div></div>
        <div class="meta-row"><div class="label">Gender</div><div class="value"><?php echo h($record['gender']); ?></div></div>
        <div class="meta-row"><div class="label">Date of Birth</div><div class="value"><?php echo h($record['dob']); ?></div></div>
        <div class="meta-row"><div class="label">Age</div><div class="value"><?php echo h($record['age']); ?></div></div>
        <div class="meta-row"><div class="label">LRN</div><div class="value"><?php echo h(isset($record['lrn']) ? $record['lrn'] : ''); ?></div></div>
        <div class="meta-row"><div class="label">Previous School</div><div class="value"><?php echo h(isset($record['previous_school']) ? $record['previous_school'] : ''); ?></div></div>
        <div class="meta-row"><div class="label">Email</div><div class="value"><?php echo h($record['email']); ?></div></div>
        <div class="meta-row"><div class="label">Phone</div><div class="value">+63<?php echo h(ltrim($record['phone'], '+63')); ?></div></div>
        <div class="meta-row"><div class="label">Address</div><div class="value"><?php echo h($record['address']); ?></div></div>

        <div class="section-title">Application Details</div>
        <div class="meta-row"><div class="label">Application Type</div><div class="value"><?php echo h($record['application_type']); ?></div></div>
        <div class="meta-row"><div class="label">Classification</div><div class="value"><?php echo h($record['classification']); ?></div></div>
        <div class="meta-row"><div class="label">Grade / Level</div><div class="value"><?php echo h($record['grade_level']); ?></div></div>
        <div class="meta-row"><div class="label">School Campus</div><div class="value"><?php echo h($record['school_campus']); ?></div></div>
        <div class="meta-row"><div class="label">Exam Time Slot</div><div class="value"><?php echo h(isset($record['time_slot']) ? $record['time_slot'] : 'To Be Announced'); ?></div></div>
        <div class="meta-row"><div class="label">Room Number</div><div class="value"><?php echo h(isset($record['room_number']) ? $record['room_number'] : 'To Be Announced'); ?></div></div>
        

        <div class="note">
            Please bring this printed slip together with a valid ID and your required documents on the scheduled date. Arrive at least 30 minutes before your time slot. For inquiries or changes, contact the admissions office.
        </div>

        <div class="signatures">
            <div class="sig">
                <div class="line">Applicant's Signature</div>
            </div>
            <div class="sig">
                <div class="line">Admissions Officer</div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>


