<?php
if(!defined('DB_SERVER')){
    require_once(__DIR__ . "/../initialize.php");
}

// Include the database wrapper
if (!class_exists('DatabaseWrapper')) {
    require_once(__DIR__ . "/../admin/inc/db_connect.php");
}

class DBConnection {
    
    public $conn;
    
    public function __construct() {
        if (!isset($this->conn)) {
            try {
                $this->conn = new DatabaseWrapper(
                    DB_SERVER, 
                    DB_USERNAME, 
                    DB_PASSWORD, 
                    DB_NAME, 
                    DB_PORT, 
                    DB_TYPE
                );
            } catch (Exception $e) {
                echo 'Cannot connect to database server: ' . $e->getMessage();
                exit;
            }
        }
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
