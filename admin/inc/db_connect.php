<?php
/**
 * Database Connection - Supports both MySQL (local) and PostgreSQL (Supabase)
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the absolute path to initialize.php
$initializePath = realpath(__DIR__ . '/../../initialize.php');

if (!$initializePath || !file_exists($initializePath)) {
    die('Error: initialize.php not found at ' . __DIR__ . '/../../initialize.php');
}

require_once $initializePath;

/**
 * Database wrapper class that provides mysqli-like interface for both MySQL and PostgreSQL
 */
class DatabaseWrapper {
    public $pdo;
    public $error;
    public $connect_error;
    private $dbType;
    private $lastResult;
    
    public function __construct($host, $user, $pass, $db, $port = '3306', $type = 'mysql') {
        $this->dbType = $type;
        
        try {
            if ($type === 'pgsql') {
                // For Supabase on cloud, force IPv4 resolution to avoid IPv6 issues
                $isCloud = getenv('RENDER') || getenv('DB_HOST');
                
                // Force IPv4 resolution - multiple methods for reliability
                $ipv4 = $this->resolveIPv4($host);
                
                if (!$ipv4 && $isCloud) {
                    // If DNS resolution fails, try alternative method
                    $ipv4 = $this->resolveIPv4Alternative($host);
                }
                
                // Use IPv4 if available, otherwise use hostname (will try IPv4 first)
                $connectHost = $ipv4 ? $ipv4 : $host;
                
                // Build DSN - always use IPv4 if available
                $dsn = "pgsql:host={$connectHost};port={$port};dbname={$db}";
                
                // Connection attempts with fallback
                $lastError = null;
                $connected = false;
                
                // Build list of hosts to try
                $attempts = [];
                if ($ipv4) {
                    $attempts[] = $ipv4; // Try IPv4 first
                }
                $attempts[] = $host; // Always try hostname as fallback
                
                foreach ($attempts as $attemptHost) {
                    try {
                        $dsn = "pgsql:host={$attemptHost};port={$port};dbname={$db}";
                        $this->pdo = new PDO($dsn, $user, $pass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_TIMEOUT => 10,
                            PDO::ATTR_PERSISTENT => false
                        ]);
                        // Success - break out of loop
                        $connected = true;
                        break;
                    } catch (PDOException $e) {
                        $lastError = $e;
                        // Continue to next attempt
                        continue;
                    }
                }
                
                // If all attempts failed, throw the last error
                if (!$connected && $lastError) {
                    throw $lastError;
                }
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            }
        } catch (PDOException $e) {
            $this->connect_error = $e->getMessage();
            throw new Exception('Connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Resolve hostname to IPv4 address to avoid IPv6 connection issues
     * Uses multiple methods for reliability
     */
    private function resolveIPv4($host) {
        // Method 1: Use gethostbyname (IPv4 only, most reliable)
        $ip = @gethostbyname($host);
        if ($ip && $ip !== $host && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }
        
        // Method 2: Use dns_get_record with DNS_A (IPv4 only)
        $records = @dns_get_record($host, DNS_A);
        if ($records && isset($records[0]['ip']) && filter_var($records[0]['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $records[0]['ip'];
        }
        
        return false;
    }
    
    /**
     * Alternative IPv4 resolution method
     */
    private function resolveIPv4Alternative($host) {
        // Try using getaddrinfo equivalent via shell command (if available)
        if (function_exists('shell_exec')) {
            $output = @shell_exec("getent hosts {$host} 2>/dev/null | awk '{print $1}' | grep -E '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$' | head -1");
            if ($output) {
                $ip = trim($output);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $ip;
                }
            }
        }
        return false;
    }
    
    /**
     * Execute a query and return result object
     */
    public function query($sql) {
        try {
            // Convert MySQL-specific syntax to PostgreSQL if needed
            if ($this->dbType === 'pgsql') {
                $sql = $this->convertToPostgres($sql);
            }
            
            $stmt = $this->pdo->query($sql);
            return new DatabaseResult($stmt, $this->dbType);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * Prepare a statement
     */
    public function prepare($sql) {
        try {
            if ($this->dbType === 'pgsql') {
                $sql = $this->convertToPostgres($sql);
            }
            return new DatabaseStatement($this->pdo->prepare($sql), $this->dbType);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Prepare error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Escape string for safe SQL
     */
    public function real_escape_string($str) {
        // PDO quote includes the quotes, so we remove them
        $quoted = $this->pdo->quote($str);
        return substr($quoted, 1, -1);
    }
    
    /**
     * Get last insert ID
     */
    public function insert_id() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get insert_id as property
     */
    public function __get($name) {
        if ($name === 'insert_id') {
            return $this->pdo->lastInsertId();
        }
        return null;
    }
    
    /**
     * Begin transaction
     */
    public function begin_transaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }
    
    /**
     * Convert MySQL syntax to PostgreSQL
     */
    private function convertToPostgres($sql) {
        // AUTO_INCREMENT -> SERIAL (handled in schema)
        // CURDATE() -> CURRENT_DATE
        $sql = str_ireplace('CURDATE()', 'CURRENT_DATE', $sql);
        // NOW() is the same in both
        // DATE() function
        $sql = preg_replace('/DATE\(([^)]+)\)/i', 'DATE($1)', $sql);
        // md5() is the same in both
        // LIMIT syntax is the same
        return $sql;
    }
    
    /**
     * Set charset (no-op for PostgreSQL)
     */
    public function set_charset($charset) {
        if ($this->dbType === 'mysql') {
            $this->pdo->exec("SET NAMES '$charset'");
        }
        return true;
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->pdo = null;
    }
}

/**
 * Result wrapper class
 */
class DatabaseResult {
    private $stmt;
    private $dbType;
    public $num_rows;
    
    public function __construct($stmt, $dbType) {
        $this->stmt = $stmt;
        $this->dbType = $dbType;
        $this->num_rows = $stmt ? $stmt->rowCount() : 0;
    }
    
    public function fetch_assoc() {
        return $this->stmt ? $this->stmt->fetch(PDO::FETCH_ASSOC) : null;
    }
    
    public function fetch_array() {
        return $this->stmt ? $this->stmt->fetch(PDO::FETCH_BOTH) : null;
    }
    
    public function fetch_row() {
        return $this->stmt ? $this->stmt->fetch(PDO::FETCH_NUM) : null;
    }
    
    public function fetch_all($mode = PDO::FETCH_ASSOC) {
        return $this->stmt ? $this->stmt->fetchAll($mode) : [];
    }
}

/**
 * Statement wrapper class
 */
class DatabaseStatement {
    private $stmt;
    private $dbType;
    private $paramTypes = '';
    private $params = [];
    public $error;
    
    public function __construct($stmt, $dbType) {
        $this->stmt = $stmt;
        $this->dbType = $dbType;
    }
    
    /**
     * Bind parameters (mysqli-style)
     */
    public function bind_param($types, &...$params) {
        $this->paramTypes = $types;
        $this->params = $params;
        return true;
    }
    
    /**
     * Execute the statement
     */
    public function execute() {
        try {
            if (!empty($this->params)) {
                return $this->stmt->execute($this->params);
            }
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Execute error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get result
     */
    public function get_result() {
        return new DatabaseResult($this->stmt, $this->dbType);
    }
    
    /**
     * Close statement
     */
    public function close() {
        $this->stmt = null;
    }
}

// Create connection
try {
    $host = DB_SERVER;
    $user = DB_USERNAME;
    $pass = DB_PASSWORD;
    $db = DB_NAME;
    $port = DB_PORT;
    $type = DB_TYPE;

    $conn = new DatabaseWrapper($host, $user, $pass, $db, $port, $type);
    
} catch (Exception $e) {
    die('Database connection error: ' . $e->getMessage());
}
?>
