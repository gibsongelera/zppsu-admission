<?php
/**
 * ZPPSU Admission System - Admission Slip Generator
 * Generates PDF admission slips with QR codes
 */

require_once __DIR__ . '/../../libs/phpqrcode/qrlib.php';

class SlipGenerator {
    private $conn;
    private $uploadDir;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->uploadDir = realpath(__DIR__ . '/../../uploads');
        
        if ($this->uploadDir === false || !is_dir($this->uploadDir)) {
            $this->uploadDir = __DIR__ . '/../../uploads';
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0777, true);
            }
        }
    }
    
    /**
     * Generate admission slip for a schedule
     */
    public function generateAdmissionSlip($scheduleId) {
        // Get schedule details
        $stmt = $this->conn->prepare("SELECT * FROM schedule_admission WHERE id = ?");
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Schedule not found'];
        }
        
        $schedule = $result->fetch_assoc();
        $stmt->close();
        
        // Check if already approved
        if ($schedule['status'] !== 'Approved') {
            return ['success' => false, 'message' => 'Schedule must be approved before generating slip'];
        }
        
        // Generate QR code
        $qrData = "ZPPSU-ADMISSION\n"
                . "Ref: " . $schedule['reference_number'] . "\n"
                . "Name: " . $schedule['surname'] . ", " . $schedule['given_name'] . "\n"
                . "Date: " . $schedule['date_scheduled'] . "\n"
                . "Time: " . ($schedule['time_slot'] ?? 'TBA') . "\n"
                . "Room: " . ($schedule['room_number'] ?? 'TBA') . "\n"
                . "Campus: " . $schedule['school_campus'];
        
        $qrFilename = 'qr-admission-' . $scheduleId . '-' . time() . '.png';
        $qrPath = $this->uploadDir . '/' . $qrFilename;
        
        QRcode::png($qrData, $qrPath, QR_ECLEVEL_L, 4);
        
        // Generate HTML slip (will be converted to PDF or printed)
        $slipFilename = 'slip-' . $scheduleId . '-' . time() . '.html';
        $slipPath = $this->uploadDir . '/' . $slipFilename;
        
        $html = $this->generateSlipHTML($schedule, $qrFilename);
        file_put_contents($slipPath, $html);
        
        // Update database
        $stmt = $this->conn->prepare("UPDATE schedule_admission SET admission_slip_generated = 1, admission_slip_path = ? WHERE id = ?");
        $stmt->bind_param("si", $slipFilename, $scheduleId);
        $stmt->execute();
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Admission slip generated successfully',
            'slip_path' => $slipFilename,
            'qr_path' => $qrFilename
        ];
    }
    
    /**
     * Generate HTML for admission slip
     */
    private function generateSlipHTML($schedule, $qrFilename) {
        $name = strtoupper($schedule['surname'] . ', ' . $schedule['given_name'] . ' ' . $schedule['middle_name']);
        $date = date('F d, Y', strtotime($schedule['date_scheduled']));
        $timeSlot = $schedule['time_slot'] ?? 'To Be Announced';
        $room = $schedule['room_number'] ?? 'To Be Announced';
        $campus = $schedule['school_campus'];
        $refNum = $schedule['reference_number'];
        $classification = $schedule['classification'];
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ZPPSU Admission Slip</title>
    <style>
        @media print {
            body { margin: 0; padding: 20mm; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #5E0A14;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #5E0A14;
            margin: 5px 0;
            font-size: 24px;
        }
        .header h2 {
            color: #333;
            margin: 5px 0;
            font-size: 18px;
        }
        .slip-body {
            padding: 20px;
            border: 2px solid #5E0A14;
            border-radius: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #5E0A14;
            width: 200px;
        }
        .info-value {
            flex: 1;
            text-align: right;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
        }
        .qr-section img {
            width: 200px;
            height: 200px;
            border: 2px solid #5E0A14;
            padding: 10px;
            background: white;
        }
        .instructions {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .instructions h3 {
            color: #856404;
            margin-top: 0;
        }
        .instructions ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 5px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #5E0A14;
            font-size: 12px;
            color: #666;
        }
        .no-print {
            text-align: center;
            margin: 20px 0;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ZAMBOANGA PENINSULA POLYTECHNIC STATE UNIVERSITY</h1>
        <h2>ENTRANCE EXAMINATION ADMISSION SLIP</h2>
    </div>
    
    <div class="slip-body">
        <div class="info-row">
            <span class="info-label">Reference Number:</span>
            <span class="info-value"><strong>' . htmlspecialchars($refNum) . '</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Name of Examinee:</span>
            <span class="info-value"><strong>' . htmlspecialchars($name) . '</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Program/Course:</span>
            <span class="info-value">' . htmlspecialchars($classification) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Examination Date:</span>
            <span class="info-value"><strong>' . htmlspecialchars($date) . '</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Examination Time:</span>
            <span class="info-value"><strong>' . htmlspecialchars($timeSlot) . '</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Room Number:</span>
            <span class="info-value"><strong>' . htmlspecialchars($room) . '</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Campus:</span>
            <span class="info-value">' . htmlspecialchars($campus) . '</span>
        </div>
        
        <div class="qr-section">
            <img src="../uploads/' . htmlspecialchars($qrFilename) . '" alt="QR Code">
            <p><strong>Present this QR code on examination day</strong></p>
        </div>
        
        <div class="instructions">
            <h3>IMPORTANT REMINDERS:</h3>
            <ul>
                <li>Please arrive at least <strong>30 minutes before</strong> your scheduled examination time.</li>
                <li>Bring this admission slip (printed copy) and a <strong>valid ID</strong>.</li>
                <li>Bring <strong>pencils, eraser, and sharpener</strong>. No calculators or electronic devices allowed.</li>
                <li>Late examinees will NOT be accommodated.</li>
                <li>Observe proper dress code - no sleeveless shirts, shorts, or slippers.</li>
                <li>Mobile phones must be turned off during the examination.</li>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>This is a computer-generated admission slip. No signature required.</p>
        <p>For inquiries, please contact the Admissions Office.</p>
        <p>&copy; ' . date('Y') . ' Zamboanga Peninsula Polytechnic State University</p>
    </div>
    
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; background: #5E0A14; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Print Admission Slip
        </button>
    </div>
</body>
</html>';
        
        return $html;
    }
}
?>

