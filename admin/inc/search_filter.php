<?php
/**
 * Advanced Search and Filter Component for ZPPSU Admission System
 * Provides comprehensive search and filtering across all student records
 */

class SearchFilter {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Build and execute a filtered query
     * @param array $filters Associative array of filter parameters
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return array Results with pagination info
     */
    public function search($filters = [], $page = 1, $perPage = 20) {
        $where = [];
        $params = [];
        $types = "";
        
        // Text search (name, reference, LRN, email, phone)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $where[] = "(surname LIKE ? OR given_name LIKE ? OR middle_name LIKE ? OR reference_number LIKE ? OR lrn LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $params = array_merge($params, array_fill(0, 7, $searchTerm));
            $types .= str_repeat('s', 7);
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        // Campus filter
        if (!empty($filters['campus'])) {
            $where[] = "school_campus = ?";
            $params[] = $filters['campus'];
            $types .= 's';
        }
        
        // Exam result filter
        if (!empty($filters['exam_result'])) {
            $where[] = "exam_result = ?";
            $params[] = $filters['exam_result'];
            $types .= 's';
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $where[] = "date_scheduled >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "date_scheduled <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        // Single date filter
        if (!empty($filters['date'])) {
            $where[] = "date_scheduled = ?";
            $params[] = $filters['date'];
            $types .= 's';
        }
        
        // Time slot filter
        if (!empty($filters['time_slot'])) {
            $where[] = "time_slot = ?";
            $params[] = $filters['time_slot'];
            $types .= 's';
        }
        
        // Room filter
        if (!empty($filters['room'])) {
            $where[] = "room_number = ?";
            $params[] = $filters['room'];
            $types .= 's';
        }
        
        // Classification filter
        if (!empty($filters['classification'])) {
            $where[] = "classification = ?";
            $params[] = $filters['classification'];
            $types .= 's';
        }
        
        // Application type filter
        if (!empty($filters['application_type'])) {
            $where[] = "application_type = ?";
            $params[] = $filters['application_type'];
            $types .= 's';
        }
        
        // Gender filter
        if (!empty($filters['gender'])) {
            $where[] = "gender = ?";
            $params[] = $filters['gender'];
            $types .= 's';
        }
        
        // Has QR code filter
        if (isset($filters['has_qr']) && $filters['has_qr'] !== '') {
            if ($filters['has_qr']) {
                $where[] = "qr_code_path IS NOT NULL AND qr_code_path != ''";
            } else {
                $where[] = "(qr_code_path IS NULL OR qr_code_path = '')";
            }
        }
        
        // Build WHERE clause
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Sorting
        $orderBy = "ORDER BY ";
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        
        $allowedSortFields = ['created_at', 'date_scheduled', 'surname', 'reference_number', 'status', 'exam_result'];
        if (in_array($sortField, $allowedSortFields)) {
            $orderBy .= "{$sortField} {$sortDir}";
        } else {
            $orderBy .= "created_at DESC";
        }
        
        // Count total records
        $countSql = "SELECT COUNT(*) as total FROM schedule_admission {$whereClause}";
        $countStmt = $this->conn->prepare($countSql);
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();
        
        // Calculate pagination
        $totalPages = ceil($totalRecords / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        
        // Fetch records
        $sql = "SELECT * FROM schedule_admission {$whereClause} {$orderBy} LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        $stmt->close();
        
        return [
            'records' => $records,
            'total' => $totalRecords,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }
    
    /**
     * Get filter options (for dropdown menus)
     */
    public function getFilterOptions() {
        $options = [];
        
        // Campuses
        $result = $this->conn->query("SELECT DISTINCT school_campus FROM schedule_admission WHERE school_campus IS NOT NULL AND school_campus != '' ORDER BY school_campus");
        $options['campuses'] = [];
        while ($row = $result->fetch_assoc()) {
            $options['campuses'][] = $row['school_campus'];
        }
        
        // Classifications
        $result = $this->conn->query("SELECT DISTINCT classification FROM schedule_admission WHERE classification IS NOT NULL AND classification != '' ORDER BY classification");
        $options['classifications'] = [];
        while ($row = $result->fetch_assoc()) {
            $options['classifications'][] = $row['classification'];
        }
        
        // Rooms
        $result = $this->conn->query("SELECT DISTINCT room_number FROM schedule_admission WHERE room_number IS NOT NULL AND room_number != '' ORDER BY room_number");
        $options['rooms'] = [];
        while ($row = $result->fetch_assoc()) {
            $options['rooms'][] = $row['room_number'];
        }
        
        // Scheduled dates (upcoming)
        $result = $this->conn->query("SELECT DISTINCT date_scheduled FROM schedule_admission WHERE date_scheduled >= CURDATE() ORDER BY date_scheduled");
        $options['dates'] = [];
        while ($row = $result->fetch_assoc()) {
            $options['dates'][] = $row['date_scheduled'];
        }
        
        // Application types
        $options['application_types'] = ['New Student', 'Transferee', 'Returning', 'Cross-Enrollee'];
        
        // Statuses
        $options['statuses'] = ['Pending', 'Approved', 'Rejected'];
        
        // Exam results
        $options['exam_results'] = ['Pending', 'Pass', 'Fail'];
        
        // Time slots
        $options['time_slots'] = ['Morning (8AM-12PM)', 'Afternoon (1PM-5PM)'];
        
        // Genders
        $options['genders'] = ['Male', 'Female'];
        
        return $options;
    }
    
    /**
     * Get statistics summary
     */
    public function getStatistics($filters = []) {
        $baseWhere = [];
        $params = [];
        $types = "";
        
        // Apply same filters as search
        if (!empty($filters['date_from'])) {
            $baseWhere[] = "date_scheduled >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $baseWhere[] = "date_scheduled <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        if (!empty($filters['campus'])) {
            $baseWhere[] = "school_campus = ?";
            $params[] = $filters['campus'];
            $types .= 's';
        }
        
        $whereClause = !empty($baseWhere) ? "WHERE " . implode(" AND ", $baseWhere) : "";
        
        $stats = [];
        
        // Total by status
        $sql = "SELECT status, COUNT(*) as count FROM schedule_admission {$whereClause} GROUP BY status";
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['by_status'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        $stmt->close();
        
        // Total by exam result
        $sql = "SELECT IFNULL(exam_result, 'Pending') as exam_result, COUNT(*) as count FROM schedule_admission {$whereClause} GROUP BY exam_result";
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['by_exam_result'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['by_exam_result'][$row['exam_result']] = $row['count'];
        }
        $stmt->close();
        
        // Total with QR codes
        $sql = "SELECT 
                    SUM(CASE WHEN qr_code_path IS NOT NULL AND qr_code_path != '' THEN 1 ELSE 0 END) as with_qr,
                    SUM(CASE WHEN qr_code_path IS NULL OR qr_code_path = '' THEN 1 ELSE 0 END) as without_qr
                FROM schedule_admission {$whereClause}";
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $qrStats = $stmt->get_result()->fetch_assoc();
        $stats['qr_codes'] = [
            'with_qr' => (int)$qrStats['with_qr'],
            'without_qr' => (int)$qrStats['without_qr']
        ];
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Export filtered results to array (for Excel/PDF export)
     */
    public function exportFiltered($filters = [], $limit = 5000) {
        $result = $this->search($filters, 1, $limit);
        return $result['records'];
    }
}

/**
 * Render the search/filter form HTML
 */
function renderSearchFilterForm($filters = [], $options = [], $showAdvanced = true) {
    $html = '<form method="GET" id="searchFilterForm" class="mb-4">';
    
    // Basic search
    $html .= '<div class="row">';
    $html .= '<div class="col-md-6 mb-2">';
    $html .= '<div class="input-group">';
    $html .= '<input type="text" class="form-control" name="search" placeholder="Search name, reference #, LRN, phone, email..." value="' . htmlspecialchars($filters['search'] ?? '') . '">';
    $html .= '<div class="input-group-append">';
    $html .= '<button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>';
    $html .= '</div></div></div>';
    
    // Quick filters
    $html .= '<div class="col-md-6 mb-2">';
    $html .= '<div class="btn-group" role="group">';
    $html .= '<button type="button" class="btn btn-outline-secondary quick-filter" data-status="">All</button>';
    $html .= '<button type="button" class="btn btn-outline-warning quick-filter" data-status="Pending">Pending</button>';
    $html .= '<button type="button" class="btn btn-outline-success quick-filter" data-status="Approved">Approved</button>';
    $html .= '<button type="button" class="btn btn-outline-danger quick-filter" data-status="Rejected">Rejected</button>';
    $html .= '</div></div></div>';
    
    if ($showAdvanced) {
        $html .= '<div id="advancedFilters" class="collapse ' . (!empty(array_filter($filters)) ? 'show' : '') . '">';
        $html .= '<div class="card card-body bg-light">';
        $html .= '<div class="row">';
        
        // Status
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="small">Status</label>';
        $html .= '<select name="status" class="form-control form-control-sm">';
        $html .= '<option value="">All Status</option>';
        foreach ($options['statuses'] ?? [] as $s) {
            $selected = ($filters['status'] ?? '') === $s ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($s) . '" ' . $selected . '>' . htmlspecialchars($s) . '</option>';
        }
        $html .= '</select></div>';
        
        // Campus
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="small">Campus</label>';
        $html .= '<select name="campus" class="form-control form-control-sm">';
        $html .= '<option value="">All Campus</option>';
        foreach ($options['campuses'] ?? [] as $c) {
            $selected = ($filters['campus'] ?? '') === $c ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($c) . '" ' . $selected . '>' . htmlspecialchars($c) . '</option>';
        }
        $html .= '</select></div>';
        
        // Exam Result
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="small">Exam Result</label>';
        $html .= '<select name="exam_result" class="form-control form-control-sm">';
        $html .= '<option value="">All Results</option>';
        foreach ($options['exam_results'] ?? [] as $r) {
            $selected = ($filters['exam_result'] ?? '') === $r ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($r) . '" ' . $selected . '>' . htmlspecialchars($r) . '</option>';
        }
        $html .= '</select></div>';
        
        // Time Slot
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="small">Time Slot</label>';
        $html .= '<select name="time_slot" class="form-control form-control-sm">';
        $html .= '<option value="">All Slots</option>';
        foreach ($options['time_slots'] ?? [] as $t) {
            $selected = ($filters['time_slot'] ?? '') === $t ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($t) . '" ' . $selected . '>' . htmlspecialchars($t) . '</option>';
        }
        $html .= '</select></div>';
        
        // Date From
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="small">Date From</label>';
        $html .= '<input type="date" name="date_from" class="form-control form-control-sm" value="' . htmlspecialchars($filters['date_from'] ?? '') . '">';
        $html .= '</div>';
        
        // Date To
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="small">Date To</label>';
        $html .= '<input type="date" name="date_to" class="form-control form-control-sm" value="' . htmlspecialchars($filters['date_to'] ?? '') . '">';
        $html .= '</div>';
        
        $html .= '</div>'; // row
        
        // Second row
        $html .= '<div class="row">';
        
        // Classification
        $html .= '<div class="col-md-3 mb-2">';
        $html .= '<label class="small">Classification</label>';
        $html .= '<select name="classification" class="form-control form-control-sm">';
        $html .= '<option value="">All Classifications</option>';
        foreach ($options['classifications'] ?? [] as $c) {
            $selected = ($filters['classification'] ?? '') === $c ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($c) . '" ' . $selected . '>' . htmlspecialchars($c) . '</option>';
        }
        $html .= '</select></div>';
        
        // Room
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="small">Room</label>';
        $html .= '<select name="room" class="form-control form-control-sm">';
        $html .= '<option value="">All Rooms</option>';
        foreach ($options['rooms'] ?? [] as $r) {
            $selected = ($filters['room'] ?? '') === $r ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($r) . '" ' . $selected . '>' . htmlspecialchars($r) . '</option>';
        }
        $html .= '</select></div>';
        
        // Gender
        $html .= '<div class="col-md-2 mb-2">';
        $html .= '<label class="small">Gender</label>';
        $html .= '<select name="gender" class="form-control form-control-sm">';
        $html .= '<option value="">All</option>';
        foreach ($options['genders'] ?? [] as $g) {
            $selected = ($filters['gender'] ?? '') === $g ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($g) . '" ' . $selected . '>' . htmlspecialchars($g) . '</option>';
        }
        $html .= '</select></div>';
        
        // Buttons
        $html .= '<div class="col-md-5 mb-2 d-flex align-items-end">';
        $html .= '<button type="submit" class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter"></i> Apply Filters</button>';
        $html .= '<a href="?" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Clear</a>';
        $html .= '</div>';
        
        $html .= '</div>'; // row
        $html .= '</div></div>'; // card, collapse
        
        // Toggle button
        $html .= '<button type="button" class="btn btn-link btn-sm mt-1" data-toggle="collapse" data-target="#advancedFilters">';
        $html .= '<i class="fas fa-sliders-h"></i> Advanced Filters';
        $html .= '</button>';
    }
    
    // Hidden inputs for pagination
    $html .= '<input type="hidden" name="page" value="1" id="pageInput">';
    
    $html .= '</form>';
    
    // JavaScript for quick filters
    $html .= '<script>
    document.querySelectorAll(".quick-filter").forEach(function(btn) {
        btn.addEventListener("click", function() {
            var status = this.dataset.status;
            var form = document.getElementById("searchFilterForm");
            var statusSelect = form.querySelector("[name=status]");
            if (statusSelect) {
                statusSelect.value = status;
            } else {
                var input = document.createElement("input");
                input.type = "hidden";
                input.name = "status";
                input.value = status;
                form.appendChild(input);
            }
            form.submit();
        });
    });
    </script>';
    
    return $html;
}

/**
 * Render pagination HTML
 */
function renderPagination($currentPage, $totalPages, $baseUrl = '?') {
    if ($totalPages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous
    $html .= '<li class="page-item ' . ($currentPage <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '">&laquo; Previous</a></li>';
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $html .= '<li class="page-item ' . ($i === $currentPage ? 'active' : '') . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next
    $html .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '">Next &raquo;</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}
?>

