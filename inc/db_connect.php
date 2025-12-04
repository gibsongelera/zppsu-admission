<?php
/**
 * Database Connection - Supports both MySQL (local) and PostgreSQL (Supabase)
 */
require_once(__DIR__ . '/../initialize.php');

// Include the wrapper from admin/inc if needed
if (!class_exists('DatabaseWrapper')) {
    require_once(__DIR__ . '/../admin/inc/db_connect.php');
} else {
    // Create connection using the wrapper
    try {
        $conn = new DatabaseWrapper(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT, DB_TYPE);
    } catch (Exception $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
