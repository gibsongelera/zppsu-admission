<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../inc/db_connect.php';

// Logged-in student context (if any)
$currentUserId = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : null;
$currentUserRole = isset($_SESSION['userdata']['role']) ? (int)$_SESSION['userdata']['role'] : null;
$sessEmail = isset($_SESSION['userdata']['email']) ? trim($_SESSION['userdata']['email']) : '';
$sessPhone = isset($_SESSION['userdata']['phone']) ? trim($_SESSION['userdata']['phone']) : '';
// Normalize phone for display as 9XXXXXXXXX (no +63)
$displayPhone = '';
if ($sessPhone !== '') {
    $digits = preg_replace('/[^0-9]/', '', $sessPhone);
    // Expected valid shapes:
    // 1) 9XXXXXXXXX (10 digits)
    // 2) 09XXXXXXXXX (11 digits) -> drop leading 0
    // 3) 639XXXXXXXXX (12 digits) -> drop leading 63
    if (strlen($digits) === 12 && strpos($digits, '63') === 0 && preg_match('/^639\d{9}$/', $digits)) {
        $displayPhone = substr($digits, 2); // now 10 digits starting with 9
    } elseif (strlen($digits) === 11 && strpos($digits, '09') === 0 && preg_match('/^09\d{9}$/', $digits)) {
        $displayPhone = substr($digits, 1); // drop the 0 -> 10 digits
    } elseif (strlen($digits) === 10 && preg_match('/^9\d{9}$/', $digits)) {
        $displayPhone = $digits; // already correct
    } else {
        // Unknown shape: do not strip, just show as-is to avoid losing digits
        $displayPhone = $digits;
    }
}
$sessFirstName = isset($_SESSION['userdata']['firstname']) ? trim($_SESSION['userdata']['firstname']) : '';
$sessLastName = isset($_SESSION['userdata']['lastname']) ? trim($_SESSION['userdata']['lastname']) : '';
$sessMiddleName = isset($_SESSION['userdata']['middlename']) ? trim($_SESSION['userdata']['middlename']) : '';
// Prefill reference number and LRN for students
$sessRef = '';
$sessLrn = '';
if ($currentUserId) {
    $prefill = $conn->query("SELECT reference_number, lrn FROM users WHERE id = ".(int)$currentUserId." LIMIT 1");
    if ($prefill && $prefill->num_rows) {
        $prow = $prefill->fetch_assoc();
        $sessRef = isset($prow['reference_number']) ? trim((string)$prow['reference_number']) : '';
        $sessLrn = isset($prow['lrn']) ? trim((string)$prow['lrn']) : '';
    }
}

// Check if student already has a submitted schedule
$hasExistingSchedule = false;
$existingScheduleMessage = '';
if ($currentUserRole === 3) { // Student role
    $checkStmt = $conn->prepare("SELECT id, status FROM schedule_admission WHERE 
        (email = ? AND email <> '') OR 
        (phone = ? AND phone <> '') OR 
        (surname = ? AND given_name = ? AND (middle_name = ? OR ? = '')) 
        LIMIT 1");
    $checkStmt->bind_param("ssssss", $sessEmail, $sessPhone, $sessLastName, $sessFirstName, $sessMiddleName, $sessMiddleName);
    $checkStmt->execute();
    $existingResult = $checkStmt->get_result();
    if ($existingResult->num_rows > 0) {
        $existing = $existingResult->fetch_assoc();
        $hasExistingSchedule = true;
        $existingScheduleMessage = "You have already submitted your schedule. Status: " . htmlspecialchars($existing['status']) . ". Multiple submissions are not allowed.";
    }
    $checkStmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prevent duplicate submissions for students
    if ($currentUserRole === 3 && $hasExistingSchedule) {
        // Don't set error message here - let the existing schedule message show instead
    } else {
    // Collect form data
    $surname = $_POST['surname'] ?? '';
    $givenName = $_POST['givenName'] ?? '';
    $middleName = $_POST['middleName'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $address = $_POST['address'] ?? '';
    $applicationType = $_POST['applicationType'] ?? '';
    $classification = $_POST['classification'] ?? '';
    $gradeLevel = $_POST['gradeLevel'] ?? '';
    $schoolCampus = $_POST['schoolCampus'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $dateScheduled = $_POST['dateScheduled'] ?? '';
    $timeSlot = $_POST['timeSlot'] ?? '';
    $roomNumber = $_POST['roomNumber'] ?? '';
    $referenceNumber = $_POST['referenceNumber'] ?? '';
    $lrn = $_POST['lrn'] ?? '';
    $previousSchool = $_POST['previousSchool'] ?? '';
    // Handle file uploads (photo, document)
    $photo = $_FILES['photo']['name'] ?? '';
    $document = $_FILES['document']['name'] ?? '';

    // Move uploaded files to uploads folder (absolute path)
    $uploadDir = realpath(__DIR__ . '/../../uploads');
    if ($uploadDir === false) {
        // Try to create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    }
    $photoPath = '';
    $documentPath = '';
    if ($photo && is_uploaded_file($_FILES['photo']['tmp_name'])) {
        $photoPath = $uploadDir . '/' . basename($photo);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
    }
    if ($document && is_uploaded_file($_FILES['document']['tmp_name'])) {
        $documentPath = $uploadDir . '/' . basename($document);
        move_uploaded_file($_FILES['document']['tmp_name'], $documentPath);
    }

    // If student, enforce own reference number from users table and restrict to one record
    if ($currentUserRole === 3) {
        if (!empty($sessEmail)) $email = $sessEmail;
        if (!empty($sessPhone)) $phone = $sessPhone;
        // Load student's reference number and LRN, verify against submitted
        if ($currentUserId) {
            $rs = $conn->query("SELECT reference_number, lrn FROM users WHERE id = ".(int)$currentUserId." LIMIT 1");
            if ($rs && $rs->num_rows) {
                $u = $rs->fetch_assoc();
                $userRef = isset($u['reference_number']) ? trim($u['reference_number']) : '';
                $userLrn = isset($u['lrn']) ? trim($u['lrn']) : '';

                // Validate reference number if provided does not match
                if (!empty($userRef)) {
                    if (!empty($referenceNumber) && $referenceNumber !== $userRef) {
                        $message = "Error: Reference Number does not match your profile.";
                    }
                    // Always enforce to stored value
                    $referenceNumber = $userRef;
                }

                // Validate LRN: must be 12 digits and match user's LRN when set
                if (!preg_match('/^\\d{12}$/', $lrn)) {
                    $message = "Error: Invalid LRN. It must be 12 digits.";
                } elseif (!empty($userLrn) && $lrn !== $userLrn) {
                    $message = "Error: LRN does not match your profile.";
                }
            }
        }
        // If validation produced an error message, stop processing
        if (isset($message) && strpos($message, 'Error:') === 0) {
            // fall through to show message without attempting to insert
        } else {
            // no error so far
        }
    }

    // Enforce per-day booking limit (max 100 per date)
    if (!isset($message) || strpos($message, 'Error:') !== 0) {
        if (!empty($dateScheduled)) {
            $ds = $conn->real_escape_string($dateScheduled);
            $ctrRes = $conn->query("SELECT COUNT(*) AS cnt FROM schedule_admission WHERE DATE(date_scheduled) = DATE('{$ds}')");
            $ctrRow = $ctrRes ? $ctrRes->fetch_assoc() : ['cnt' => 0];
            $slotsUsed = (int)($ctrRow['cnt'] ?? 0);
            if ($slotsUsed >= 100) {
                $message = "Error: this date fullybooked change the date";
            }
        }
    }

    // Ensure new columns exist
    try {
        $conn->query("ALTER TABLE schedule_admission ADD COLUMN IF NOT EXISTS lrn VARCHAR(20) NULL AFTER reference_number");
        $conn->query("ALTER TABLE schedule_admission ADD COLUMN IF NOT EXISTS previous_school VARCHAR(255) NULL AFTER lrn");
    } catch (Exception $e) {
        // ignore if cannot alter; insert will fail if truly missing
    }

    // If any validation error above, skip insert
    if (!isset($message) || strpos($message, 'Error:') !== 0) {
        // Check if date is not full for auto-approval
        $autoApprove = false;
        $slotsUsed = 0;
        if (!empty($dateScheduled)) {
            $ds = $conn->real_escape_string($dateScheduled);
            $ctrRes = $conn->query("SELECT COUNT(*) AS cnt FROM schedule_admission WHERE DATE(date_scheduled) = DATE('{$ds}')");
            $ctrRow = $ctrRes ? $ctrRes->fetch_assoc() : ['cnt' => 0];
            $slotsUsed = (int)($ctrRow['cnt'] ?? 0);
            // Auto-approve if less than 100 slots are used (not full)
            $autoApprove = ($slotsUsed < 100);
        }

        // Determine initial status
        $initialStatus = $autoApprove ? 'Approved' : 'Pending';
        
        // Prepare and execute insert query including LRN and Previous School with status
        $stmt = $conn->prepare("INSERT INTO schedule_admission 
            (surname, given_name, middle_name, gender, age, dob, address, application_type, classification, grade_level, school_campus, email, phone, date_scheduled, time_slot, room_number, reference_number, lrn, previous_school, photo, document, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssississssssssssssss",
            $surname, $givenName, $middleName, $gender, $age, $dob, $address, $applicationType, $classification, $gradeLevel, $schoolCampus, $email, $phone, $dateScheduled, $timeSlot, $roomNumber, $referenceNumber, $lrn, $previousSchool, $photo, $document, $initialStatus
        );
        $success = $stmt->execute();
        $newRecordId = $conn->insert_id;
        $stmt->close();
        
        // Handle multiple document uploads
        if ($success && $newRecordId > 0) {
            require_once __DIR__ . '/../inc/document_handler.php';
            $documentHandler = new DocumentHandler($conn);
            $docResult = $documentHandler->handleMultipleUploads($newRecordId, $_FILES);
            
            if (!$docResult['success'] && $docResult['uploaded_count'] === 0) {
                error_log("Document upload failed for schedule ID: $newRecordId");
            }
        }

        if ($success) {
            $wasSuccess = true;
            $smsSent = false;
            $smsError = '';
            
            // If a student just submitted and their profile lacks email/phone, update their user profile
            if ($currentUserRole === 3 && $currentUserId) {
                if (empty($sessEmail) || empty($sessPhone)) {
                    $newEmail = empty($sessEmail) ? $email : $sessEmail;
                    $newPhone = empty($sessPhone) ? $phone : $sessPhone;
                    $u2 = $conn->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?");
                    $u2->bind_param("ssi", $newEmail, $newPhone, $currentUserId);
                    $u2->execute();
                    $u2->close();
                    // Reflect immediately in session
                    if (empty($sessEmail)) $_SESSION['userdata']['email'] = $newEmail;
                    if (empty($sessPhone)) $_SESSION['userdata']['phone'] = $newPhone;
                }
            }
            
            // Send SMS notification if auto-approved
            if ($autoApprove && !empty($phone)) {
                require_once __DIR__ . '/../inc/sms_service.php';
                $smsService = new SmsService($conn);
                
                $applicantName = trim($surname . ', ' . $givenName . ' ' . $middleName);
                $campus = $schoolCampus;
                $schedule = $dateScheduled;
                
                // Check for SMS deduplication
                if (!$smsService->checkSmsDeduplication($classification, $phone, 'Approval')) {
                $smsResult = $smsService->sendApprovalNotification($phone, $applicantName, $referenceNumber, $campus, $schedule);
                
                if ($smsResult['success']) {
                    $smsSent = true;
                        // Log SMS
                        $smsService->logSms($classification, $phone, 'Approval', 'Auto-approval notification');
                } else {
                    $smsError = $smsResult['error'];
                    }
                } else {
                    // Duplicate SMS, skip sending
                    error_log("SMS skipped - duplicate detected for $classification to $phone");
                }
            }
            
            // Set appropriate success message
            if ($autoApprove) {
                $message = "🎉 Congratulations! Your application has been automatically approved! ";
                if ($smsSent) {
                    $message .= "A confirmation SMS has been sent to your phone.";
                } elseif (!empty($smsError)) {
                    $message .= "Note: SMS notification failed to send (" . $smsError . ").";
                }
            } else {
                $message = "Student admission record added successfully! Your application is pending review.";
            }
        } else {
            $message = "Error: Could not add record. " . $conn->error;
        }
    } // end insert guard
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Schedule Admission</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .tab-custom { margin-top: 30px; }
    </style>
</head>
<body>
    <?php if (isset($message)): ?>
        <?php 
        $alertClass = 'success';
        if (strpos($message, 'Error') === 0) {
            $alertClass = 'danger';
        } elseif (strpos($message, 'Congratulations') !== false) {
            $alertClass = 'success';
        }
        ?>
        <div class="alert alert-<?php echo $alertClass ?> text-center">
            <?php if (strpos($message, 'Congratulations') !== false): ?>
                <i class="fas fa-check-circle fa-2x mb-2"></i><br>
            <?php endif; ?>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php if (isset($wasSuccess) && $wasSuccess): ?>
        <script>
            // Auto-refresh the Set a Schedule page shortly after a successful booking
            setTimeout(function(){ location.reload(); }, 500);
        </script>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($hasExistingSchedule): ?>
        <div class="alert alert-warning text-center"><?php echo htmlspecialchars($existingScheduleMessage); ?></div>
    <?php endif; ?>
    <main class="content">
        <div class="container-fluid p-0 mb-4">
            <div class="card mt-4">
                <div class="card-header" style="background-color:#5E0A14; color:white;">
                    <h5 class="mb-0">Applicant Form</h5>
                </div>
                <div class="card-body">
                    <?php if ($hasExistingSchedule): ?>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Schedule Already Submitted</h6>
                            <p>You have already submitted your schedule and cannot submit another one. If you need to make changes, please contact the administration.</p>
                        </div>
                        <div class="text-center">
                            <a href="<?php echo base_url ?>admin/?page=sms_log" class="btn btn-primary">
                                <i class="fas fa-list"></i> View Your Schedule Log
                            </a>
                        </div>
                    <?php else: ?>
                    <form enctype="multipart/form-data" method="post" id="smsLogForm" autocomplete="off">
                        <div class="form-row">
                            <!-- Name: Surname, Given Name, Middle Name -->
                            <div class="form-group col-md-4">
                                <label for="surname"><b>Surename</b> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="surname" name="surname" placeholder="Enter surname" value="<?php echo htmlspecialchars($sessLastName); ?>" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="givenName"><b>Given Name</b> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="givenName" name="givenName" placeholder="Enter given name" value="<?php echo htmlspecialchars($sessFirstName); ?>" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="middleName"><b>Middle Name</b> <span class="">(Optional)</span></label>
                                <input type="text" class="form-control" id="middleName" name="middleName" placeholder="Enter middle name" value="<?php echo htmlspecialchars($sessMiddleName); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="gender"><b>Gender</b> <span class="text-danger">*</span></label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="" selected>Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="age"><b>Age</b> <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="age" name="age" placeholder="Enter age" min="1" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="dob"><b>Date of Birth</b> <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="dob" name="dob" placeholder="dd/mm/yyyy" required>
                                <small class="form-text text-muted">Format: dd/mm/yyyy</small>
                            </div>
                            <div class="form-group col-md-5">
                                <label for="address"><b>Location Address</b> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="Address" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="applicationType"><b>Application Type</b> <span class="text-danger">*</span></label>
                                <select name="applicationType" class="form-control" id="applicationType" required>
                                                    <option value="" selected>-Please select Application Type</option>
                                                    <option value="Senior High">Senior High</option>
                                                    <option value="Freshman">Freshman</option>
                                                    <option value="Transferee">Transferee</option>
                                                    <option value="Returnee">Returnee</option>
                                                    <option value="Shifter">Shifter</option>
                                                    <option value="Cross Enrollee">Cross Enrollee</option>
                                                    <option value="Graduate Programs">Graduate Programs</option>
                                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="programType"><b>Program Type</b> <span class="text-danger">*</span></label>
                                <select class="form-control" id="programType" required>
                                    <option value="" selected>Select Program Type</option>
                                    <option value="Senior High School">Senior High School</option>
                                    <option value="2">Certificate (2 Years)</option>
                                    <option value="3">Diploma (3 Years)</option>
                                    <option value="4">Bachelor (4 Years)</option>
                                </select>
                                <small class="form-text text-muted">Selecting a type filters the Classification list.</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="classification"><b>Classification</b> <span class="text-danger">*</span></label>
                               <select name="classification" class="form-control" id="classification" required>
                                    <option value="" selected>- Please select Program Type first</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="gradeLevel"><b>Grade / Level</b> <span class="text-danger">*</span></label>
                               <select name="gradeLevel" class="form-control" id="gradeLevel" required>
                                                    <option value="" selected>-Please select Grade Level</option>
                                                    <option value="Grade 11">Grade 11</option>
                                                    <option value="Grade 12">Grade 12</option>
                                                    <option value="1st Year">1st Year</option>
                                                    <option value="2nd Year">2nd Year</option>
                                                    <option value="3rd Year">3rd Year</option>
                                                    <option value="4th Year">4th Year</option>
                                                    
                                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="campus"><b>School Campus</b> <span class="text-danger">*</span></label>
                                <select name="schoolCampus" class="form-control" id="schoolCampus" required>
                                                    <option value="" selected>Select Campus</option>
                                                    <option value="ZPPSU MAIN">ZPPSU MAIN</option>
                                                    <option value="Gregorio Campus (Vitali)">Gregorio Campus (Vitali)</option>
                                                    <option value="ZPPSU Campus (Kabasalan)">ZPPSU Campus (Kabasalan)</option>
                                                    <option value="Anna Banquial Campus (Malangas)">Anna Banquial Campus (Malangas)</option>
                                                    <option value="Timuay Tubod M. Mandi Campus (Siay)</">Timuay Tubod M. Mandi Campus (Siay)</option>
                                                    <option value="ZPPSU Campus (Bayog)">ZPPSU Campus (Bayog)</option>
                                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="email"><b>Email</b> <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($sessEmail) ?>" required>
                                <small class="form-text text-muted">Format: example@email.com</small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="phone"><b>Phone Number</b> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+63</span>
                                    </div>
                                    <input type="tel" class="form-control" name="phone" id="phone" placeholder="9123456789" pattern="[9][0-9]{9}" maxlength="10" title="Enter a valid Philippine mobile number starting with 9" value="<?php echo htmlspecialchars($displayPhone) ?>" required>
                                </div>
                                <small class="form-text text-muted">Format: 9XXXXXXXXX (10 digits)</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="dateScheduled"><b>Date Scheduled</b> <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="dateScheduled" name="dateScheduled" required>
                                <small class="form-text text-muted">Only Saturdays and Sundays available</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="timeSlot"><b>Exam Time</b> <span class="text-danger">*</span></label>
                                <select class="form-control" id="timeSlot" name="timeSlot" required>
                                    <option value="">Select Time Slot</option>
                                    <option value="Morning (8AM-12PM)">Morning (8AM-12PM)</option>
                                    <option value="Afternoon (1PM-5PM)">Afternoon (1PM-5PM)</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="roomNumber"><b>Room Number</b> <span class="text-danger">*</span></label>
                                <select class="form-control" id="roomNumber" name="roomNumber" required>
                                    <option value="">Select room after choosing campus, date & time</option>
                                </select>
                                <small id="room_availability" class="form-text text-muted"></small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="referenceNumber"><b>Reference Number</b> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="referenceNumber" name="referenceNumber" placeholder="Enter your reference number"  title="Format: 123-456-7890" value="<?php echo htmlspecialchars($sessRef); ?>" <?php echo !empty($sessRef) ? 'readonly' : '' ?> required>
                                <small class="form-text text-muted">Format: 123-456-7890</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="lrn"><b>LRN</b> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="lrn" name="lrn" placeholder="12-digit LRN" pattern="\d{12}" maxlength="12" title="Enter a valid 12-digit LRN" value="<?php echo htmlspecialchars($sessLrn); ?>" <?php echo !empty($sessLrn) ? 'readonly' : '' ?> required>
                                <small class="form-text text-muted">12 digits, numbers only</small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label for="previousSchool"><b>Previous School</b> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="previousSchool" name="previousSchool" placeholder="Enter your previous school" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="photo"><b>Upload Photo (2x2)</b> <span class="text-danger">*</span></label>
                                <input type="file" class="form-control-file" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png" required>
                                <small class="form-text text-muted">White background, proper attire. JPG/PNG only (Max 5MB)</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="birth_certificate"><b>Birth Certificate</b> <span class="text-danger">*</span></label>
                                <input type="file" class="form-control-file" id="birth_certificate" name="birth_certificate" accept="image/*,application/pdf" required>
                                <small class="form-text text-muted">Clear copy. JPG/PNG/PDF (Max 5MB)</small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="report_card"><b>Report Card / Transcript</b> <span class="text-danger">*</span></label>
                                <input type="file" class="form-control-file" id="report_card" name="report_card" accept="image/*,application/pdf" required>
                                <small class="form-text text-muted">Latest grades. JPG/PNG/PDF (Max 5MB)</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="good_moral"><b>Good Moral Certificate</b></label>
                                <input type="file" class="form-control-file" id="good_moral" name="good_moral" accept="image/*,application/pdf">
                                <small class="form-text text-muted">Optional. JPG/PNG/PDF (Max 5MB)</small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="other_document"><b>Other Documents</b></label>
                                <input type="file" class="form-control-file" id="other_document" name="other_document" accept="image/*,application/pdf,.doc,.docx">
                                <small class="form-text text-muted">Optional. Any additional documents (Max 5MB)</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="document"><b>Legacy Document Field</b></label>
                                <input type="file" class="form-control-file" id="document" name="document" accept=".jpg,.png,.pdf,.docx">
                                <small class="form-text text-muted">Optional - kept for compatibility</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
</div>
    </main>
    <script>
        function toggleOtherInput() {
            const genderSelect = document.getElementById('gender');
            const otherGenderDiv = document.getElementById('otherGenderDiv');
            otherGenderDiv.style.display = (genderSelect.value === 'Other') ? 'block' : 'none';
        }
    </script>
<script>
// Dynamic Classification options based on Program Type
document.addEventListener('DOMContentLoaded', function(){
    var typeEl = document.getElementById('programType');
    var clsEl = document.getElementById('classification');
    if (!typeEl || !clsEl) return;

    function setLoading(){
        clsEl.innerHTML = '<option value="">Loading...</option>';
        clsEl.disabled = true;
    }
    function setPrompt(){
        clsEl.innerHTML = '<option value="">- Please select Program Type first</option>';
        clsEl.disabled = true;
    }
    function fillGradeLevelsByProgramType(val){
        var gl = document.getElementById('gradeLevel');
        if (!gl) return;
        
        // Check if Application Type is Freshman - if so, always show only 1st Year
        var appTypeEl = document.getElementById('applicationType');
        if (appTypeEl && appTypeEl.value === 'Freshman') {
            gl.innerHTML = '<option value="">-Please select Grade Level</option>' +
                          '<option value="1st Year" selected>1st Year</option>';
            return;
        }
        
        // Check if Application Type is Senior High - if so, always show Grade 11 and 12
        if (appTypeEl && appTypeEl.value === 'Senior High') {
            gl.innerHTML = '<option value="">-Please select Grade Level</option>' +
                          '<option value="Grade 11">Grade 11</option>' +
                          '<option value="Grade 12">Grade 12</option>';
            return;
        }
        
        // Otherwise, filter by Program Type
        var html = '<option value="">-Please select Grade Level</option>';
        if (val === 'Senior High School'){
            html += '<option value="Grade 11">Grade 11</option>'+
                    '<option value="Grade 12">Grade 12</option>';
        } else if (val === '2') {
            html += '<option value="1st Year">1st Year</option>'+
                    '<option value="2nd Year">2nd Year</option>';
        } else if (val === '3') {
            html += '<option value="1st Year">1st Year</option>'+
                    '<option value="2nd Year">2nd Year</option>'+
                    '<option value="3rd Year">3rd Year</option>';
        } else if (val === '4') {
            html += '<option value="1st Year">1st Year</option>'+
                    '<option value="2nd Year">2nd Year</option>'+
                    '<option value="3rd Year">3rd Year</option>'+
                    '<option value="4th Year">4th Year</option>';
        } else {
            // default keep as-is
        }
        gl.innerHTML = html;
    }
    function fillOptions(list){
        var html = '<option value="">- Please select Program</option>';
        list.forEach(function(item){
            var label = item.program_name + (item.program_code ? (' ('+item.program_code+')') : '');
            html += '<option value="'+label.replace(/\"/g,'&quot;')+'" data-years="'+item.years+'" data-college="'+(item.college||'')+'">'+label+'</option>';
        });
        clsEl.innerHTML = html;
        clsEl.disabled = false;
    }

    typeEl.addEventListener('change', function(){
        var val = this.value;
        if (!val){ setPrompt(); return; }
        // Senior High strands (no API call)
        if (val === 'Senior High School'){
            var html = '<option value="">- Please select Strand</option>'+
                       '<option value="STEM">STEM</option>'+
                       '<option value="HUMMS">HUMMS</option>'+
                       '<option value="TVL">TVL</option>';
            clsEl.innerHTML = html;
            clsEl.disabled = false;
            fillGradeLevelsByProgramType(val);
            return;
        }
        // Diploma/Bachelor via API
        setLoading();
        var url = '<?php echo base_url ?>admin/inc/programs.php?years=' + encodeURIComponent(val);
        fetch(url)
          .then(function(r){ return r.json(); })
          .then(function(data){
            if (data && data.status === 'success' && Array.isArray(data.data)){
                fillOptions(data.data);
                fillGradeLevelsByProgramType(val);
            } else {
                setPrompt();
            }
          })
          .catch(function(){ setPrompt(); });
    });
    
    // Also listen to Classification changes to update Grade Level
    clsEl.addEventListener('change', function(){
        var programTypeVal = typeEl.value;
        if (programTypeVal) {
            fillGradeLevelsByProgramType(programTypeVal);
        }
    });
});
</script>
<script>
// Two-way filtering: Program Type ↔ Application Type
document.addEventListener('DOMContentLoaded', function(){
    var programTypeEl = document.getElementById('programType');
    var applicationTypeEl = document.getElementById('applicationType');
    
    if (!programTypeEl || !applicationTypeEl) return;
    
    // Store original options
    var allProgramTypeOptions = {
        '': 'Select Program Type',
        'Senior High School': 'Senior High School',
        '2': 'Certificate (2 Years)',
        '3': 'Diploma (3 Years)',
        '4': 'Bachelor (4 Years)'
    };
    
    var allApplicationTypeOptions = {
        '': '-Please select Application Type',
        'Senior High': 'Senior High',
        'Freshman': 'Freshman',
        'Transferee': 'Transferee',
        'Returnee': 'Returnee',
        'Shifter': 'Shifter',
        'Cross Enrollee': 'Cross Enrollee',
        'Graduate Programs': 'Graduate Programs'
    };
    
    // Filter Application Type based on Program Type
    function filterApplicationType() {
        var programType = programTypeEl.value;
        var currentAppType = applicationTypeEl.value;
        var html = '';
        
        if (programType === 'Senior High School') {
            // Only show Senior High
            html = '<option value="">-Please select Application Type</option>' +
                   '<option value="Senior High"' + (currentAppType === 'Senior High' ? ' selected' : '') + '>Senior High</option>';
        } else if (programType === '2' || programType === '3' || programType === '4') {
            // Hide Senior High, show all others
            html = '<option value="">-Please select Application Type</option>';
            for (var key in allApplicationTypeOptions) {
                if (key !== '' && key !== 'Senior High') {
                    var selected = (currentAppType === key) ? ' selected' : '';
                    html += '<option value="' + key + '"' + selected + '>' + allApplicationTypeOptions[key] + '</option>';
                }
            }
        } else {
            // Show all options
            html = '<option value="">-Please select Application Type</option>';
            for (var key in allApplicationTypeOptions) {
                if (key !== '') {
                    var selected = (currentAppType === key) ? ' selected' : '';
                    html += '<option value="' + key + '"' + selected + '>' + allApplicationTypeOptions[key] + '</option>';
                }
            }
        }
        
        applicationTypeEl.innerHTML = html;
    }
    
    // Filter Program Type based on Application Type
    function filterProgramType() {
        var appType = applicationTypeEl.value;
        var currentProgType = programTypeEl.value;
        var html = '';
        
        if (appType === 'Freshman') {
            // Only show Certificate, Diploma and Bachelor
            html = '<option value="">Select Program Type</option>' +
                   '<option value="2"' + (currentProgType === '2' ? ' selected' : '') + '>Certificate (2 Years)</option>' +
                   '<option value="3"' + (currentProgType === '3' ? ' selected' : '') + '>Diploma (3 Years)</option>' +
                   '<option value="4"' + (currentProgType === '4' ? ' selected' : '') + '>Bachelor (4 Years)</option>';
        } else if (appType === 'Senior High') {
            // Only show Senior High School
            html = '<option value="">Select Program Type</option>' +
                   '<option value="Senior High School"' + (currentProgType === 'Senior High School' ? ' selected' : '') + '>Senior High School</option>';
        } else if (appType === 'Transferee' || appType === 'Returnee' || appType === 'Shifter' || appType === 'Cross Enrollee' || appType === 'Graduate Programs') {
            // Show Certificate, Diploma and Bachelor only
            html = '<option value="">Select Program Type</option>' +
                   '<option value="2"' + (currentProgType === '2' ? ' selected' : '') + '>Certificate (2 Years)</option>' +
                   '<option value="3"' + (currentProgType === '3' ? ' selected' : '') + '>Diploma (3 Years)</option>' +
                   '<option value="4"' + (currentProgType === '4' ? ' selected' : '') + '>Bachelor (4 Years)</option>';
        } else {
            // Show all options
            html = '<option value="">Select Program Type</option>';
            for (var key in allProgramTypeOptions) {
                if (key !== '') {
                    var selected = (currentProgType === key) ? ' selected' : '';
                    html += '<option value="' + key + '"' + selected + '>' + allProgramTypeOptions[key] + '</option>';
                }
            }
        }
        
        programTypeEl.innerHTML = html;
    }
    
    // Listen to Program Type changes
    programTypeEl.addEventListener('change', function(){
        filterApplicationType();
    });
    
    // Listen to Application Type changes
    applicationTypeEl.addEventListener('change', function(){
        filterProgramType();
    });
});
</script>
<script>
// Sync Age and Date of Birth both ways
document.addEventListener('DOMContentLoaded', function(){
    var ageEl = document.getElementById('age');
    var dobEl = document.getElementById('dob');
    if (!ageEl || !dobEl) return;

    function pad(n){ return String(n).padStart(2,'0'); }
    function toYMD(d){ return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate()); }

    function recomputeDobFromAge(){
        var v = parseInt(ageEl.value, 10);
        if (!isFinite(v) || v < 0 || v > 120) return;
        var today = new Date();
        var y = today.getFullYear() - v;
        var m = today.getMonth();
        var d = today.getDate();
        // Handle leap day gracefully
        var dt = new Date(y, m, d);
        dobEl.value = toYMD(dt);
    }

    function recomputeAgeFromDob(){
        if (!dobEl.value) return;
        var parts = dobEl.value.split('-');
        if (parts.length !== 3) return;
        var y = parseInt(parts[0],10), m = parseInt(parts[1],10)-1, d = parseInt(parts[2],10);
        if (!isFinite(y) || !isFinite(m) || !isFinite(d)) return;
        var today = new Date();
        var birth = new Date(y, m, d);
        var age = today.getFullYear() - birth.getFullYear();
        var beforeBirthday = (today.getMonth() < birth.getMonth()) || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate());
        if (beforeBirthday) age -= 1;
        if (age >= 0 && age <= 120) ageEl.value = age;
    }

    ageEl.addEventListener('change', recomputeDobFromAge);
    ageEl.addEventListener('keyup', recomputeDobFromAge);
    dobEl.addEventListener('change', recomputeAgeFromDob);

    // Initialize on load: if DOB present, set age; else if age present, set DOB
    if (dobEl.value) {
        recomputeAgeFromDob();
    } else if (ageEl.value) {
        recomputeDobFromAge();
    }
});
</script>
<script>
// Restrict date scheduler to weekends only (Saturday and Sunday)
document.addEventListener('DOMContentLoaded', function(){
    var dateScheduledEl = document.getElementById('dateScheduled');
    if (!dateScheduledEl) return;
    
    // Set minimum date to tomorrow
    var today = new Date();
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    var minDate = tomorrow.toISOString().split('T')[0];
    dateScheduledEl.setAttribute('min', minDate);
    
    // Set maximum date to 3 months from now
    var maxDate = new Date(today);
    maxDate.setMonth(maxDate.getMonth() + 3);
    dateScheduledEl.setAttribute('max', maxDate.toISOString().split('T')[0]);
    
    // Find next available Saturday or Sunday
    function getNextWeekend(date) {
        var day = date.getDay();
        var daysUntilSaturday = (6 - day + 7) % 7;
        if (daysUntilSaturday === 0 && day !== 6) {
            daysUntilSaturday = 7;
        }
        if (daysUntilSaturday === 0) {
            // Today is Saturday
            return date;
        }
        var nextWeekend = new Date(date);
        nextWeekend.setDate(date.getDate() + daysUntilSaturday);
        return nextWeekend;
    }
    
    // Set default to next Saturday
    var nextWeekend = getNextWeekend(tomorrow);
    dateScheduledEl.value = nextWeekend.toISOString().split('T')[0];
    
    // Validate on change
    dateScheduledEl.addEventListener('change', function(){
        var selectedDate = new Date(this.value + 'T00:00:00');
        var dayOfWeek = selectedDate.getDay();
        
        // Check if it's Saturday (6) or Sunday (0)
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            alert('Please select a Saturday or Sunday only. Entrance exams are only available on weekends.');
            
            // Auto-correct to next weekend
            var correctedDate = getNextWeekend(selectedDate);
            this.value = correctedDate.toISOString().split('T')[0];
        }
    });
    
    // Block manual input of weekdays
    dateScheduledEl.addEventListener('input', function(){
        if (this.value) {
            var selectedDate = new Date(this.value + 'T00:00:00');
            var dayOfWeek = selectedDate.getDay();
            
            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                var correctedDate = getNextWeekend(selectedDate);
                this.value = correctedDate.toISOString().split('T')[0];
            }
        }
    });
});
</script>
<script>
// Auto-filter and select Grade Level based on Application Type
document.addEventListener('DOMContentLoaded', function(){
    var applicationTypeEl = document.getElementById('applicationType');
    var gradeLevelEl = document.getElementById('gradeLevel');
    var programTypeEl = document.getElementById('programType');
    
    if (!applicationTypeEl || !gradeLevelEl) return;
    
    // Store the original full set of options
    var allGradeLevelOptions = gradeLevelEl.innerHTML;
    
    function updateGradeLevelOptions(){
        var selectedType = applicationTypeEl.value;
        var programType = programTypeEl ? programTypeEl.value : '';
        
        // When Freshman is selected, ONLY show 1st Year
        if (selectedType === 'Freshman') {
            gradeLevelEl.innerHTML = '<option value="" selected>-Please select Grade Level</option>' +
                                     '<option value="1st Year">1st Year</option>';
            gradeLevelEl.value = '1st Year';
        }
        // When Senior High is selected, show only Grade 11 and 12
        else if (selectedType === 'Senior High') {
            gradeLevelEl.innerHTML = '<option value="" selected>-Please select Grade Level</option>' +
                                     '<option value="Grade 11">Grade 11</option>' +
                                     '<option value="Grade 12">Grade 12</option>';
            gradeLevelEl.value = '';
        }
        // For other types, show levels based on Program Type
        else {
            // Check if Program Type is selected to determine available levels
            if (programType === 'Senior High School') {
                gradeLevelEl.innerHTML = '<option value="" selected>-Please select Grade Level</option>' +
                                         '<option value="Grade 11">Grade 11</option>' +
                                         '<option value="Grade 12">Grade 12</option>';
            } else if (programType === '2') {
                gradeLevelEl.innerHTML = '<option value="" selected>-Please select Grade Level</option>' +
                                         '<option value="1st Year">1st Year</option>' +
                                         '<option value="2nd Year">2nd Year</option>';
            } else if (programType === '3') {
                gradeLevelEl.innerHTML = '<option value="" selected>-Please select Grade Level</option>' +
                                         '<option value="1st Year">1st Year</option>' +
                                         '<option value="2nd Year">2nd Year</option>' +
                                         '<option value="3rd Year">3rd Year</option>';
            } else if (programType === '4') {
                gradeLevelEl.innerHTML = '<option value="" selected>-Please select Grade Level</option>' +
                                         '<option value="1st Year">1st Year</option>' +
                                         '<option value="2nd Year">2nd Year</option>' +
                                         '<option value="3rd Year">3rd Year</option>' +
                                         '<option value="4th Year">4th Year</option>';
            } else {
                // No program type selected, show all options
                gradeLevelEl.innerHTML = '<option value="" selected>-Please select Grade Level</option>' +
                                         '<option value="Grade 11">Grade 11</option>' +
                                         '<option value="Grade 12">Grade 12</option>' +
                                         '<option value="1st Year">1st Year</option>' +
                                         '<option value="2nd Year">2nd Year</option>' +
                                         '<option value="3rd Year">3rd Year</option>' +
                                         '<option value="4th Year">4th Year</option>';
            }
            gradeLevelEl.value = '';
        }
    }
    
    // Update when Application Type changes
    applicationTypeEl.addEventListener('change', updateGradeLevelOptions);
    
    // Also update when Program Type changes (to keep in sync)
    if (programTypeEl) {
        programTypeEl.addEventListener('change', function(){
            // Always update - if Freshman is selected, it will show only 1st Year
            updateGradeLevelOptions();
        });
    }
    
    // Also update when Classification changes
    var classificationEl = document.getElementById('classification');
    if (classificationEl) {
        classificationEl.addEventListener('change', function(){
            // Always update - if Freshman is selected, it will show only 1st Year
            updateGradeLevelOptions();
        });
    }
    
    // Load available rooms based on campus, date, and time
    function loadAvailableRooms() {
        var campus = $('#schoolCampus').val();
        var date = $('#dateScheduled').val();
        var timeSlot = $('#timeSlot').val();
        
        if (campus && date && timeSlot) {
            $.ajax({
                url: '<?php echo base_url ?>admin/inc/get_available_rooms.php',
                method: 'GET',
                data: {
                    campus: campus,
                    date: date,
                    time_slot: timeSlot
                },
                dataType: 'json',
                timeout: 10000,
                success: function(data) {
                    if (data && data.success && data.rooms && data.rooms.length > 0) {
                        var html = '<option value="">Select Room</option>';
                        data.rooms.forEach(function(room) {
                            html += '<option value="' + (room.room_number || '') + '">' + (room.room_number || '') + ' (Available: ' + (room.available_slots || 0) + '/' + (room.capacity || 0) + ')</option>';
                        });
                        $('#roomNumber').html(html);
                        $('#room_availability').text(data.rooms.length + ' room(s) available').removeClass('text-danger').addClass('text-success');
                    } else {
                        $('#roomNumber').html('<option value="">No rooms available</option>');
                        $('#room_availability').text('No available rooms').removeClass('text-success').addClass('text-danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Room loading error:', status, error);
                    $('#roomNumber').html('<option value="">Error loading rooms</option>');
                    $('#room_availability').text('Error loading rooms').removeClass('text-success').addClass('text-danger');
                }
            });
        }
    }
    
    // Trigger room loading when campus, date, or time changes
    $('#schoolCampus, #dateScheduled, #timeSlot').on('change', loadAvailableRooms);
});
</script>
</body>
</html>