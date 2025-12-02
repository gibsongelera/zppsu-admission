# ZPPSU Admission System Enhancement - Implementation Guide

## Completed Files

### Database
✅ `database/migration_v2.sql` - Run this to update your database schema

### New Core Files
✅ `admin/inc/document_handler.php` - Handles multiple document uploads
✅ `admin/inc/room_handler.php` - Manages room assignments
✅ `admin/inc/slip_generator.php` - Generates admission slips with QR codes
✅ `admin/inc/reschedule_handler.php` - Handles rescheduling logic
✅ `admin/inc/get_available_rooms.php` - AJAX endpoint for room availability

### New Interface Files
✅ `admin/rooms/index.php` - Room management interface
✅ `admin/results/index.php` - Exam results management
✅ `admin/results/update_result.php` - Update exam results endpoint
✅ `admin/results/get_result.php` - Get exam result data
✅ `admin/schedule/reschedule.php` - Reschedule application form

### Cron Job
✅ `admin/cron/send_reminders.php` - 3-day reminder system

### Modified Files
✅ `admin/inc/sms_service.php` - Added reminder, reschedule, deduplication methods
✅ `admin/inc/db_handler.php` - Added filtered records method
✅ `admin/inc/navigation.php` - Added Results and Rooms menu items

## Remaining Manual Modifications

### 1. Schedule Form (admin/schedule/index.php)

**Step 1: Remove Academic Year Field (lines 354-391)**
Delete the entire form-group div for academicYear.

**Step 2: Update POST handler (line 77)**
Change:
```php
$academicYear = $_POST['academicYear'] ?? '';
```
To:
```php
// $academicYear removed - no longer needed
$timeSlot = $_POST['timeSlot'] ?? '';
$roomNumber = $_POST['roomNumber'] ?? '';
```

**Step 3: Add Time Slot and Room fields (after line 471, before referenceNumber)**
Add the following HTML:
```html
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
```

**Step 4: Replace single document upload (lines 489-500) with multiple uploads**
Replace with:
```html
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="photo"><b>Upload Photo (2x2)</b> <span class="text-danger">*</span></label>
        <input type="file" class="form-control-file" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png" required>
        <small class="form-text text-muted">White background, proper attire. JPG/PNG only</small>
    </div>
    <div class="form-group col-md-6">
        <label for="birth_certificate"><b>Birth Certificate</b> <span class="text-danger">*</span></label>
        <input type="file" class="form-control-file" id="birth_certificate" name="birth_certificate" accept="image/*,application/pdf" required>
        <small class="form-text text-muted">Clear copy. JPG/PNG/PDF</small>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="report_card"><b>Report Card / Transcript</b> <span class="text-danger">*</span></label>
        <input type="file" class="form-control-file" id="report_card" name="report_card" accept="image/*,application/pdf" required>
        <small class="form-text text-muted">Latest grades. JPG/PNG/PDF</small>
    </div>
    <div class="form-group col-md-6">
        <label for="good_moral"><b>Good Moral Certificate</b></label>
        <input type="file" class="form-control-file" id="good_moral" name="good_moral" accept="image/*,application/pdf">
        <small class="form-text text-muted">Optional. JPG/PNG/PDF</small>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-12">
        <label for="other_document"><b>Other Documents</b></label>
        <input type="file" class="form-control-file" id="other_document" name="other_document" accept="image/*,application/pdf,.doc,.docx">
        <small class="form-text text-muted">Optional. Any additional documents</small>
    </div>
</div>
```

**Step 5: Update INSERT query (line 188-193)**
Change from:
```php
$stmt = $conn->prepare("INSERT INTO schedule_admission 
    (surname, given_name, middle_name, gender, age, dob, address, academic_year, application_type, classification, grade_level, school_campus, email, phone, date_scheduled, reference_number, lrn, previous_school, photo, document, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
```
To:
```php
$stmt = $conn->prepare("INSERT INTO schedule_admission 
    (surname, given_name, middle_name, gender, age, dob, address, application_type, classification, grade_level, school_campus, email, phone, date_scheduled, time_slot, room_number, reference_number, lrn, previous_school, photo, document, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
```

**Step 6: Update bind_param (line 191-194)**
Change from:
```php
$stmt->bind_param(
    "ssssissssssssssssssss",
    $surname, $givenName, $middleName, $gender, $age, $dob, $address, $academicYear, $applicationType, $classification, $gradeLevel, $schoolCampus, $email, $phone, $dateScheduled, $referenceNumber, $lrn, $previousSchool, $photo, $document, $initialStatus
);
```
To:
```php
$stmt->bind_param(
    "ssssississsssssssssss",
    $surname, $givenName, $middleName, $gender, $age, $dob, $address, $applicationType, $classification, $gradeLevel, $schoolCampus, $email, $phone, $dateScheduled, $timeSlot, $roomNumber, $referenceNumber, $lrn, $previousSchool, $photo, $document, $initialStatus
);
```

**Step 7: Handle multiple document uploads (after line 110)**
Add after document upload handling:
```php
// Handle multiple document uploads
require_once __DIR__ . '/../inc/document_handler.php';
$documentHandler = new DocumentHandler($conn);
$docResult = $documentHandler->handleMultipleUploads($newRecordId, $_FILES);

if (!$docResult['success'] && $docResult['uploaded_count'] === 0) {
    error_log("Document upload failed for schedule ID: $newRecordId");
}
```

**Step 8: Add SMS deduplication check (before line 220)**
Change from:
```php
// Send SMS notification if auto-approved
if ($autoApprove && !empty($phone)) {
    require_once __DIR__ . '/../inc/sms_service.php';
    $smsService = new SmsService();
```
To:
```php
// Send SMS notification if auto-approved
if ($autoApprove && !empty($phone)) {
    require_once __DIR__ . '/../inc/sms_service.php';
    $smsService = new SmsService($conn);
    
    // Check for duplicate SMS
    if (!$smsService->checkSmsDeduplication($classification, $phone, 'Approval')) {
```

And add after SMS send (line 234):
```php
        // Log SMS
        $smsService->logSms($classification, $phone, 'Approval', 'Auto-approval notification');
    } else {
        logMessage("SMS skipped - duplicate detected for $classification to $phone");
    }
```

**Step 9: Add JavaScript for dynamic room loading (before closing </script> tag around line 929)**
```javascript
// Load available rooms based on campus, date, and time
$('#schoolCampus, #dateScheduled, #timeSlot').on('change', function() {
    var campus = $('#schoolCampus').val();
    var date = $('#dateScheduled').val();
    var timeSlot = $('#timeSlot').val();
    
    if (campus && date && timeSlot) {
        $.get('<?php echo base_url ?>admin/inc/get_available_rooms.php', {
            campus: campus,
            date: date,
            time_slot: timeSlot
        }, function(data) {
            if (data.success && data.rooms.length > 0) {
                var html = '<option value="">Select Room</option>';
                data.rooms.forEach(function(room) {
                    html += '<option value="' + room.room_number + '">' + room.room_number + ' (Available: ' + room.available_slots + '/' + room.capacity + ')</option>';
                });
                $('#roomNumber').html(html);
                $('#room_availability').text(data.rooms.length + ' room(s) available').removeClass('text-danger').addClass('text-success');
            } else {
                $('#roomNumber').html('<option value="">No rooms available</option>');
                $('#room_availability').text('No available rooms').removeClass('text-success').addClass('text-danger');
            }
        }, 'json').fail(function() {
            $('#roomNumber').html('<option value="">Error loading rooms</option>');
        });
    }
});
```

## Installation Steps

1. **Backup your database first!**

2. **Run database migration:**
   ```sql
   -- In phpMyAdmin or MySQL client
   source database/migration_v2.sql;
   ```

3. **Setup cron job for reminders:**
   
   **Linux/Mac:**
   ```bash
   crontab -e
   # Add this line:
   0 9 * * * /usr/bin/php /path/to/zppsu_admission/admin/cron/send_reminders.php
   ```
   
   **Windows Task Scheduler:**
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\zppsu_admission\admin\cron\send_reminders.php`
   - Schedule: Daily at 9:00 AM

4. **Test the system:**
   - Check room management at: `/admin/?page=rooms`
   - Check exam results at: `/admin/?page=results`
   - Try creating a new admission with time slot and room
   - Verify SMS deduplication is working
   - Check reminder log at: `/admin/cron/reminder_log.txt`

## Features Summary

✅ Multiple document uploads (Photo, Birth Cert, Report Card, Good Moral, Other)
✅ Time slot selection (Morning/Afternoon)
✅ Room assignment with availability checking
✅ Admission slip generation with QR code
✅ Exam results management by proctors
✅ SMS deduplication by classification
✅ Automatic 3-day exam reminders
✅ Reschedule functionality
✅ Advanced filtering in SMS log
✅ Removed academic year field

## Notes

- All new files follow existing code structure
- Uses Bootstrap 4.5.2 styling
- Maintains SMS service centralization
- All database queries use prepared statements
- Proper error handling throughout

