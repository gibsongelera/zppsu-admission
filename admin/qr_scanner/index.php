<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/qr_handler.php';

// Check if user has permission (admin/staff only)
if (!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['role'], [1, 2])) {
    header('Location: ' . base_url . 'admin/login.php');
    exit;
}

$qrHandler = new QRCodeHandler($conn);
$validationResult = null;
$searchResults = [];

// Handle QR code validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $validationResult = $qrHandler->validateQRCode($_POST['qr_data']);
}

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchResults = $qrHandler->searchStudent($_GET['search']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Code Scanner - ZPPSU Admission</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        :root {
            --brand: #5E0A14;
            --brand-light: #8B1A2B;
        }
        body { background: #f4f6f9; }
        .scanner-container {
            max-width: 500px;
            margin: 0 auto;
        }
        #qr-reader {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
        }
        #qr-reader video {
            border-radius: 10px;
        }
        .result-card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .result-header {
            padding: 20px;
            color: white;
            text-align: center;
        }
        .result-header.success { background: linear-gradient(135deg, #28a745, #20c997); }
        .result-header.warning { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .result-header.danger { background: linear-gradient(135deg, #dc3545, #c82333); }
        .result-header i { font-size: 4rem; margin-bottom: 15px; }
        .student-info { padding: 20px; }
        .student-info .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .student-info .info-row:last-child { border-bottom: none; }
        .student-info .label { 
            width: 140px; 
            color: #666;
            font-weight: 500;
        }
        .student-info .value { 
            flex: 1; 
            font-weight: 600;
            color: #333;
        }
        .student-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            margin: -50px auto 15px;
            display: block;
            background: white;
        }
        .search-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .search-result-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        .search-result-item:hover {
            background: #f8f9fa;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-dot.approved { background: #28a745; }
        .status-dot.pending { background: #ffc107; }
        .status-dot.rejected { background: #dc3545; }
        .tab-pane { padding-top: 20px; }
        .nav-pills .nav-link.active {
            background: var(--brand);
        }
        .nav-pills .nav-link {
            color: var(--brand);
        }
        #manual-input {
            font-family: monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="card mb-4" style="background: var(--brand); color: white;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><i class="fas fa-qrcode"></i> QR Code Scanner</h3>
                        <small>Verify student registration status</small>
                    </div>
                    <a href="<?php echo base_url ?>admin/?page=<?php echo $_SESSION['userdata']['role'] == 1 ? 'admin/' : 'staff/'; ?>" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Column: Scanner -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-pills card-header-pills" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="pill" href="#camera-tab">
                                    <i class="fas fa-camera"></i> Camera Scan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="pill" href="#manual-tab">
                                    <i class="fas fa-keyboard"></i> Manual Entry
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="pill" href="#search-tab">
                                    <i class="fas fa-search"></i> Search
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Camera Tab -->
                            <div class="tab-pane fade show active" id="camera-tab">
                                <div class="scanner-container">
                                    <div id="qr-reader"></div>
                                    <div class="text-center mt-3">
                                        <button id="start-scanner" class="btn btn-primary">
                                            <i class="fas fa-play"></i> Start Scanner
                                        </button>
                                        <button id="stop-scanner" class="btn btn-secondary" style="display:none;">
                                            <i class="fas fa-stop"></i> Stop Scanner
                                        </button>
                                    </div>
                                    <p class="text-muted text-center mt-3 mb-0">
                                        <i class="fas fa-info-circle"></i> Point camera at student's QR code
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Manual Entry Tab -->
                            <div class="tab-pane fade" id="manual-tab">
                                <form method="POST" id="manual-form">
                                    <div class="form-group">
                                        <label><strong>Enter QR Code Data</strong></label>
                                        <textarea class="form-control" name="qr_data" id="manual-input" rows="5" 
                                            placeholder='Paste QR code JSON data here...'></textarea>
                                        <small class="text-muted">Usually from a QR code reader app</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-check"></i> Validate
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Search Tab -->
                            <div class="tab-pane fade" id="search-tab">
                                <form method="GET" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" 
                                            placeholder="Search by Reference #, LRN, or Name..."
                                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                
                                <?php if (!empty($searchResults)): ?>
                                <div class="search-card">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($searchResults as $student): ?>
                                        <div class="list-group-item search-result-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <span class="status-dot <?php echo strtolower($student['status']); ?>"></span>
                                                        <?php echo htmlspecialchars($student['surname'] . ', ' . $student['given_name'] . ' ' . $student['middle_name']); ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        Ref: <?php echo htmlspecialchars($student['reference_number']); ?> | 
                                                        LRN: <?php echo htmlspecialchars($student['lrn'] ?? 'N/A'); ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <?php if ($student['status'] === 'Approved' && !empty($student['qr_code_path'])): ?>
                                                    <button class="btn btn-sm btn-success" onclick="viewQR(<?php echo $student['id']; ?>)">
                                                        <i class="fas fa-qrcode"></i>
                                                    </button>
                                                    <?php elseif ($student['status'] === 'Approved'): ?>
                                                    <button class="btn btn-sm btn-primary" onclick="generateQR(<?php echo $student['id']; ?>)">
                                                        <i class="fas fa-plus"></i> Generate
                                                    </button>
                                                    <?php else: ?>
                                                    <span class="badge badge-<?php echo $student['status'] === 'Pending' ? 'warning' : 'danger'; ?>">
                                                        <?php echo $student['status']; ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php elseif (isset($_GET['search'])): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No students found matching your search.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Results -->
            <div class="col-lg-6">
                <div id="result-container">
                    <?php if ($validationResult): ?>
                    <div class="result-card card">
                        <div class="result-header <?php echo $validationResult['color']; ?>">
                            <i class="fas fa-<?php echo $validationResult['icon']; ?>"></i>
                            <h4><?php echo htmlspecialchars($validationResult['message']); ?></h4>
                        </div>
                        <?php if (isset($validationResult['student'])): ?>
                        <div class="student-info">
                            <?php if (!empty($validationResult['student']['photo'])): ?>
                            <img src="<?php echo base_url ?>uploads/<?php echo htmlspecialchars($validationResult['student']['photo']); ?>" 
                                class="student-photo" alt="Student Photo">
                            <?php endif; ?>
                            
                            <div class="info-row">
                                <div class="label">Name</div>
                                <div class="value"><?php echo htmlspecialchars($validationResult['student']['name']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="label">Reference #</div>
                                <div class="value"><?php echo htmlspecialchars($validationResult['student']['reference_number']); ?></div>
                            </div>
                            <?php if (isset($validationResult['student']['lrn'])): ?>
                            <div class="info-row">
                                <div class="label">LRN</div>
                                <div class="value"><?php echo htmlspecialchars($validationResult['student']['lrn']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($validationResult['student']['campus'])): ?>
                            <div class="info-row">
                                <div class="label">Campus</div>
                                <div class="value"><?php echo htmlspecialchars($validationResult['student']['campus']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($validationResult['student']['date_scheduled'])): ?>
                            <div class="info-row">
                                <div class="label">Scheduled Date</div>
                                <div class="value"><?php echo date('F d, Y', strtotime($validationResult['student']['date_scheduled'])); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($validationResult['student']['time_slot'])): ?>
                            <div class="info-row">
                                <div class="label">Time Slot</div>
                                <div class="value"><?php echo htmlspecialchars($validationResult['student']['time_slot']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($validationResult['student']['room_number'])): ?>
                            <div class="info-row">
                                <div class="label">Room</div>
                                <div class="value"><?php echo htmlspecialchars($validationResult['student']['room_number']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($validationResult['student']['exam_result'])): ?>
                            <div class="info-row">
                                <div class="label">Exam Result</div>
                                <div class="value">
                                    <?php 
                                    $result = $validationResult['student']['exam_result'];
                                    $badgeClass = $result === 'Pass' ? 'success' : ($result === 'Fail' ? 'danger' : 'secondary');
                                    echo '<span class="badge badge-'.$badgeClass.'">'.htmlspecialchars($result).'</span>';
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="card text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-qrcode text-muted" style="font-size: 5rem;"></i>
                            <h5 class="mt-3 text-muted">Scan a QR Code</h5>
                            <p class="text-muted">Results will appear here after scanning</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- QR View Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--brand); color: white;">
                    <h5 class="modal-title"><i class="fas fa-qrcode"></i> Student QR Code</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <img id="qr-image" src="" alt="QR Code" style="max-width: 300px;">
                    <p id="qr-student-name" class="mt-3 mb-0 font-weight-bold"></p>
                    <small id="qr-ref-number" class="text-muted"></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printQR()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    var baseUrl = '<?php echo base_url ?>';
    var html5QrCode = null;
    var isScanning = false;
    
    // Initialize scanner
    document.getElementById('start-scanner').addEventListener('click', function() {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("qr-reader");
        }
        
        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess,
            onScanError
        ).then(() => {
            isScanning = true;
            document.getElementById('start-scanner').style.display = 'none';
            document.getElementById('stop-scanner').style.display = 'inline-block';
        }).catch(err => {
            console.error('Scanner error:', err);
            alert('Could not start camera. Please ensure camera permissions are granted.');
        });
    });
    
    document.getElementById('stop-scanner').addEventListener('click', function() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().then(() => {
                isScanning = false;
                document.getElementById('start-scanner').style.display = 'inline-block';
                document.getElementById('stop-scanner').style.display = 'none';
            });
        }
    });
    
    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanning
        if (html5QrCode && isScanning) {
            html5QrCode.stop().then(() => {
                isScanning = false;
                document.getElementById('start-scanner').style.display = 'inline-block';
                document.getElementById('stop-scanner').style.display = 'none';
            });
        }
        
        // Submit for validation
        document.getElementById('manual-input').value = decodedText;
        document.getElementById('manual-form').submit();
    }
    
    function onScanError(error) {
        // Ignore scan errors (normal when no QR code in view)
    }
    
    function viewQR(scheduleId) {
        $.get(baseUrl + 'admin/qr_scanner/get_qr.php', { id: scheduleId }, function(data) {
            if (data.success) {
                $('#qr-image').attr('src', baseUrl + data.qr_path);
                $('#qr-student-name').text(data.student_name || '');
                $('#qr-ref-number').text('Ref: ' + (data.reference_number || ''));
                $('#qrModal').modal('show');
            } else {
                alert(data.message || 'Could not load QR code');
            }
        }, 'json');
    }
    
    function generateQR(scheduleId) {
        if (confirm('Generate QR code for this student?')) {
            $.post(baseUrl + 'admin/qr_scanner/generate_qr.php', { id: scheduleId }, function(data) {
                if (data.success) {
                    alert('QR code generated successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Could not generate QR code');
                }
            }, 'json');
        }
    }
    
    function printQR() {
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Print QR Code</title></head><body style="text-align:center;padding:50px;">');
        printWindow.document.write('<img src="' + $('#qr-image').attr('src') + '" style="max-width:300px;">');
        printWindow.document.write('<p style="margin-top:20px;font-weight:bold;">' + $('#qr-student-name').text() + '</p>');
        printWindow.document.write('<p>' + $('#qr-ref-number').text() + '</p>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
    </script>
</body>
</html>

