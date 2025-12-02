<?php
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/db_handler.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Check if user is logged in and is admin or teacher
$currentRole = isset($_SESSION['userdata']['role']) ? (int)$_SESSION['userdata']['role'] : null;
if ($currentRole === 3) {
    die('Access denied. Students cannot export reports.');
}

$format = isset($_GET['format']) ? $_GET['format'] : 'excel';

try {
    $db = new DatabaseHandler($conn);
    $result = $db->getAllRecords();
    
    if (!$result) {
        die('No records found to export.');
    }
    
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    if (empty($data)) {
        die('No records found to export.');
    }
    
    if ($format === 'excel') {
        exportToExcel($data);
    } elseif ($format === 'pdf') {
        exportToPDF($data);
    } else {
        die('Invalid export format.');
    }
    
} catch (Exception $e) {
    die('Error exporting data: ' . $e->getMessage());
}

function exportToExcel($data) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator("ZPPSU Admission System")
        ->setTitle("SMS Log Report")
        ->setSubject("Student Admission Records")
        ->setDescription("Export of student admission records from ZPPSU")
        ->setKeywords("zppsu admission students")
        ->setCategory("Report");
    
    // Header style
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '5E0A14']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ]
    ];
    
    // Set column headers
    $headers = [
        'A1' => 'ID',
        'B1' => 'Full Name',
        'C1' => 'Gender',
        'D1' => 'Age',
        'E1' => 'Phone',
        'F1' => 'Date of Birth',
        'G1' => 'Address',
        'H1' => 'Email',
        'I1' => 'Application Type',
        'J1' => 'Classification',
        'K1' => 'Grade/Level',
        'L1' => 'Campus',
        'M1' => 'Reference Number',
        'N1' => 'LRN',
        'O1' => 'Previous School',
        'P1' => 'Date Scheduled',
        'Q1' => 'Time Slot',
        'R1' => 'Room Number',
        'S1' => 'Date Created',
        'T1' => 'Status'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    $sheet->getStyle('A1:T1')->applyFromArray($headerStyle);
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(12);
    $sheet->getColumnDimension('D')->setWidth(8);
    $sheet->getColumnDimension('E')->setWidth(18);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(30);
    $sheet->getColumnDimension('H')->setWidth(25);
    $sheet->getColumnDimension('I')->setWidth(18);
    $sheet->getColumnDimension('J')->setWidth(35);
    $sheet->getColumnDimension('K')->setWidth(15);
    $sheet->getColumnDimension('L')->setWidth(25);
    $sheet->getColumnDimension('M')->setWidth(20);
    $sheet->getColumnDimension('N')->setWidth(18);
    $sheet->getColumnDimension('O')->setWidth(30);
    $sheet->getColumnDimension('P')->setWidth(18);
    $sheet->getColumnDimension('Q')->setWidth(22);
    $sheet->getColumnDimension('R')->setWidth(15);
    $sheet->getColumnDimension('S')->setWidth(20);
    $sheet->getColumnDimension('T')->setWidth(12);
    
    // Fill data
    $row = 2;
    foreach ($data as $record) {
        $fullName = $record['surname'] . ', ' . $record['given_name'] . ' ' . $record['middle_name'];
        
        $sheet->setCellValue('A' . $row, $record['id']);
        $sheet->setCellValue('B' . $row, $fullName);
        $sheet->setCellValue('C' . $row, $record['gender']);
        $sheet->setCellValue('D' . $row, $record['age']);
        $sheet->setCellValue('E' . $row, $record['phone']);
        $sheet->setCellValue('F' . $row, $record['dob']);
        $sheet->setCellValue('G' . $row, $record['address']);
        $sheet->setCellValue('H' . $row, $record['email']);
        $sheet->setCellValue('I' . $row, $record['application_type']);
        $sheet->setCellValue('J' . $row, $record['classification']);
        $sheet->setCellValue('K' . $row, $record['grade_level']);
        $sheet->setCellValue('L' . $row, $record['school_campus']);
        $sheet->setCellValue('M' . $row, $record['reference_number']);
        $sheet->setCellValue('N' . $row, $record['lrn'] ?? '');
        $sheet->setCellValue('O' . $row, $record['previous_school'] ?? '');
        $sheet->setCellValue('P' . $row, $record['date_scheduled']);
        $sheet->setCellValue('Q' . $row, $record['time_slot'] ?? 'Not set');
        $sheet->setCellValue('R' . $row, $record['room_number'] ?? 'Not assigned');
        $sheet->setCellValue('S' . $row, $record['created_at']);
        $sheet->setCellValue('T' . $row, $record['status']);
        
        // Apply border to data rows
        $sheet->getStyle('A' . $row . ':T' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);
        
        // Color code status
        switch($record['status']) {
            case 'Approved':
                $sheet->getStyle('T' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('28A745');
                $sheet->getStyle('T' . $row)->getFont()->getColor()->setRGB('FFFFFF');
                break;
            case 'Pending':
                $sheet->getStyle('T' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFC107');
                break;
            case 'Rejected':
                $sheet->getStyle('T' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('DC3545');
                $sheet->getStyle('T' . $row)->getFont()->getColor()->setRGB('FFFFFF');
                break;
        }
        
        $row++;
    }
    
    // Set sheet name
    $sheet->setTitle('SMS Log Report');
    
    // Output file
    $filename = 'ZPPSU_SMS_Log_' . date('Y-m-d_His') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function exportToPDF($data) {
    // Generate HTML for PDF
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ZPPSU SMS Log Report</title>
    <style>
        @page {
            size: landscape;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        h1 {
            text-align: center;
            color: #5E0A14;
            margin-bottom: 5px;
        }
        h3 {
            text-align: center;
            margin: 0 0 20px 0;
            font-weight: normal;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #5E0A14;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            border: 1px solid #ddd;
            padding: 6px;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .status-approved {
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-rejected {
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Zamboanga Peninsula Polytechnic State University</h1>
    <h3>Student Admission Records - SMS Log Report</h3>
    <p style="text-align: center; margin: 0 0 10px 0;"><strong>Generated:</strong> ' . date('F d, Y h:i A') . '</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Gender</th>
                <th>Age</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Application Type</th>
                <th>Classification</th>
                <th>Grade/Level</th>
                <th>Campus</th>
                <th>Ref Number</th>
                <th>Date Scheduled</th>
                <th>Time Slot</th>
                <th>Room Number</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($data as $record) {
        $fullName = htmlspecialchars($record['surname'] . ', ' . $record['given_name'] . ' ' . $record['middle_name']);
        $statusClass = 'status-' . strtolower($record['status']);
        
        $html .= '<tr>
            <td>' . htmlspecialchars($record['id']) . '</td>
            <td>' . $fullName . '</td>
            <td>' . htmlspecialchars($record['gender']) . '</td>
            <td>' . htmlspecialchars($record['age']) . '</td>
            <td>' . htmlspecialchars($record['phone']) . '</td>
            <td>' . htmlspecialchars($record['email']) . '</td>
            <td>' . htmlspecialchars($record['application_type']) . '</td>
            <td>' . htmlspecialchars($record['classification']) . '</td>
            <td>' . htmlspecialchars($record['grade_level']) . '</td>
            <td>' . htmlspecialchars($record['school_campus']) . '</td>
            <td>' . htmlspecialchars($record['reference_number']) . '</td>
            <td>' . htmlspecialchars($record['date_scheduled']) . '</td>
            <td>' . htmlspecialchars($record['time_slot'] ?? 'Not set') . '</td>
            <td>' . htmlspecialchars($record['room_number'] ?? 'Not assigned') . '</td>
            <td><span class="' . $statusClass . '">' . htmlspecialchars($record['status']) . '</span></td>
        </tr>';
    }
    
    $html .= '</tbody>
    </table>
    
    <div class="footer">
        <p>ZPPSU Admission System - Confidential Document</p>
        <p>Total Records: ' . count($data) . '</p>
    </div>
</body>
</html>';
    
    // Output HTML that can be printed to PDF
    $filename = 'ZPPSU_SMS_Log_' . date('Y-m-d_His') . '.html';
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo $html;
    echo '<script>window.print();</script>';
    exit;
}
?>

