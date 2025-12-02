<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

// Normalize and validate input
$years = null;
if (isset($_GET['years'])) {
    $y = (int)$_GET['years'];
    if ($y === 2 || $y === 3 || $y === 4) $years = $y;
}

try {
    // Create table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS programs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_name VARCHAR(255) NOT NULL,
        program_code VARCHAR(64) NULL,
        college VARCHAR(255) NULL,
        years INT NOT NULL,
        degree_type ENUM('Certificate','Associate','Diploma','Bachelor','Senior High') NOT NULL,
        UNIQUE KEY uniq_name_years (program_name, years)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Seed if empty
    $check = $conn->query("SELECT COUNT(*) AS c FROM programs");
    $count = $check ? (int)$check->fetch_assoc()['c'] : 0;
    if ($count === 0) {
        $seed = [
            // 2 years (Certificate)
            ['program_name' => 'Certificate in Computer Hardware Servicing', 'program_code' => 'CHS', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            ['program_name' => 'Certificate in Electrical Installation and Maintenance', 'program_code' => 'EIM', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            ['program_name' => 'Certificate in Plumbing', 'program_code' => 'PLUMBING', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            ['program_name' => 'Certificate in Automotive Servicing', 'program_code' => 'AUTO-SVC', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            ['program_name' => 'Certificate in Welding and Fabrication', 'program_code' => 'WELD-FAB', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            ['program_name' => 'Certificate in Food and Beverage Services', 'program_code' => 'FBS', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            ['program_name' => 'Certificate in Housekeeping', 'program_code' => 'HSK', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            ['program_name' => 'Certificate in Cookery', 'program_code' => 'COOKERY', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            ['program_name' => 'Certificate in Dressmaking', 'program_code' => 'DRESSMAKING', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            ['program_name' => 'Certificate in Cosmetology', 'program_code' => 'COSMETOLOGY', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Certificate'],
            
            // 2 years (Associate) — Associate in Industrial Technology programs
            ['program_name' => 'Two-Year Associate in Industrial Technology - Automotive Technology', 'program_code' => 'AIT-AUTO', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Associate'],
            ['program_name' => 'Two-Year Associate in Industrial Technology - Food Technology', 'program_code' => 'AIT-FOOD', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Associate'],
            ['program_name' => 'Two-Year Associate in Industrial Technology - Garments Textile and Technology', 'program_code' => 'AIT-GTT', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Associate'],
            ['program_name' => 'Two-Year Associate in Industrial Technology - Electronics Technology', 'program_code' => 'AIT-ELEXT', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Associate'],
            ['program_name' => 'Two-Year Associate in Industrial Technology - Electrical Technology', 'program_code' => 'AIT-ELECT', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Associate'],
            ['program_name' => 'Two-Year Associate in Industrial Technology - Refrigeration and Air Conditioning Technology', 'program_code' => 'AIT-RACT', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Associate'],
            ['program_name' => 'Two-Year Associate in Industrial Technology - Architectural Drafting Technology', 'program_code' => 'TTEC-ADT', 'college' => 'Institute of Technical Education (ITE) - Two-Year', 'years' => 2, 'degree_type' => 'Associate'],
            
            // 3 years (Diploma) — based on provided list
            ['program_name' => 'Bachelor of Science in Hospitality Management', 'program_code' => 'BSHM', 'college' => 'School of Business Administration (SBA)', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Bachelor of Science in Entrepreneurship', 'program_code' => 'BS ENTREP', 'college' => 'School of Business Administration (SBA)', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Automotive Engineering Technology', 'program_code' => 'DT-AET', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Information Technology', 'program_code' => 'DT-IT', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Electrical Engineering Technology', 'program_code' => 'DT-EET', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Electronics and Communication Technology', 'program_code' => 'DT-ECT', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Hospitality Management Technology', 'program_code' => 'DT-HMT', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Civil Engineering Technology', 'program_code' => 'DT-CET', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Food Production and Services Management Technology', 'program_code' => 'DT-FPSMT', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Mechanical Engineering Technology', 'program_code' => 'DT-MET', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Garments, Fashion and Design Technology', 'program_code' => 'DT-GFDT', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],
            ['program_name' => 'Trade Industrial Technical Education - Welding and Fabrication Technology', 'program_code' => 'TITE-WAFT', 'college' => 'Institute of Technical Education (ITE) - Three-Year', 'years' => 3, 'degree_type' => 'Diploma'],

            // Note: Two-year programs were provided but are intentionally excluded from 3-year list

            // 4 years (Bachelor) — based on provided list
            ['program_name' => 'Bachelor of Fine Arts - Industrial Design', 'program_code' => 'BFA-ID', 'college' => 'College of Arts, Humanities and Social Sciences (CAHSS)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Science in Development Communication', 'program_code' => 'BS DEVCOM', 'college' => 'College of Arts, Humanities and Social Sciences (CAHSS)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Batsilyer sa Sining ng Filipino', 'program_code' => 'BA-FIL', 'college' => 'College of Arts, Humanities and Social Sciences (CAHSS)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Science in Information Technology', 'program_code' => 'BS INFOTECH', 'college' => 'College of Information and Computing Sciences (CISC)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Science in Information System', 'program_code' => 'BS INFO SYS', 'college' => 'College of Information and Computing Sciences (CISC)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Computer Technology', 'program_code' => 'BINDTECH-COMPTECH', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Electrical Technology', 'program_code' => 'BINDTECH-ET', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Electronics Technology', 'program_code' => 'BINDTECH-ELEXT', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Mechanical Technology', 'program_code' => 'BINDTECH-MECHANICAL', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Automotive Technology', 'program_code' => 'BINDTECH-AT', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Heating, Ventilating and Air Conditioning Technology', 'program_code' => 'BINDTECH-HVAC', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Culinary Technology', 'program_code' => 'BINDTECH-CULINARY TECH', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Construction Technology', 'program_code' => 'BINDTECH-CONTRUCTION', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Apparel and Fashion Technology', 'program_code' => 'BINDTECH-AFT', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Power Plant Engineering Technology', 'program_code' => 'BINDTECH-PPET', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Architectural Drafting Technology', 'program_code' => 'BINDTECH-ADT', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Industrial Technology major in Mechatronics Technology', 'program_code' => 'BINDTECH-MECHATRONICS', 'college' => 'College of Engineering Technology (CET)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Elementary Education', 'program_code' => 'BEED', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Secondary Education - English', 'program_code' => 'BSED-ENGLISH', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Secondary Education - Mathematics', 'program_code' => 'BSED-MATH', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technology Livelihood Education - Home Economics', 'program_code' => 'BTLED-HE', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technology Livelihood Education - Industrial Arts', 'program_code' => 'BTLED-IA', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technology Livelihood Education - Information and Communications Technology', 'program_code' => 'BTLED-ICT', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Automotive Technology', 'program_code' => 'BTVTED-AT', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Civil and Construction Technology', 'program_code' => 'BTVTED-CCT', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Drafting Technology', 'program_code' => 'BTVTED-DT', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Electrical Technology', 'program_code' => 'BTVTED-ELECT', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Electronics Technology', 'program_code' => 'BTVTED-ELEXT', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Food Service Management', 'program_code' => 'BTVTED-FSM', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Garments, Fashion and Design', 'program_code' => 'BTVTED-GFD', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Mechanical Technology', 'program_code' => 'BTVTED-MT', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Welding and Fabrication Technology', 'program_code' => 'BTVTED-WAFT', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Technical-Vocational Teacher Education - Heating, Ventilating and Air-Conditioning Technology', 'program_code' => 'BTVTED-HVAC', 'college' => 'College of Teacher Education (CTE)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Science in Marine Engineering', 'program_code' => 'BS MAR-E', 'college' => 'College of Maritime Education (CME)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Physical Education', 'program_code' => 'BPED', 'college' => 'College of Physical Education and Sports (CPES)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Science in Exercise and Sports Sciences major in Fitness and Sports Coaching', 'program_code' => 'BSESS-FSC', 'college' => 'College of Physical Education and Sports (CPES)', 'years' => 4, 'degree_type' => 'Bachelor'],
            ['program_name' => 'Bachelor of Science in Exercise and Sports Sciences major in Fitness and Sports Management', 'program_code' => 'BSESS-FSM', 'college' => 'College of Physical Education and Sports (CPES)', 'years' => 4, 'degree_type' => 'Bachelor']
        ];

        $stmt = $conn->prepare("INSERT INTO programs (program_name, program_code, college, years, degree_type) VALUES (?,?,?,?,?)");
        foreach ($seed as $row) {
            $stmt->bind_param(
                "sssis",
                $row['program_name'],
                $row['program_code'],
                $row['college'],
                $row['years'],
                $row['degree_type']
            );
            $stmt->execute();
        }
        $stmt->close();
    }

    // Fetch
    $items = [];
    if ($years !== null) {
        $stmt = $conn->prepare("SELECT id, program_name, program_code, college, years, degree_type FROM programs WHERE years = ? ORDER BY college, program_name");
        $stmt->bind_param("i", $years);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query("SELECT id, program_name, program_code, college, years, degree_type FROM programs ORDER BY years, college, program_name");
    }
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $items[] = $r;
        }
    }
    echo json_encode(['status' => 'success', 'years' => $years, 'data' => $items]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>



