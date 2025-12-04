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
        $this->pdo = null; // Initialize to null
        
        try {
            if ($type === 'pgsql') {
                // For Supabase on cloud, try multiple connection strategies
                $isCloud = getenv('RENDER') || getenv('DB_HOST');
                
                // Force IPv4 resolution - multiple methods for reliability
                $ipv4 = $this->resolveIPv4($host);
                
                if (!$ipv4 && $isCloud) {
                    // If DNS resolution fails, try alternative method
                    $ipv4 = $this->resolveIPv4Alternative($host);
                }
                
                // Build connection attempts: try different ports and hosts
                $lastError = null;
                $connected = false;
                
                // Try both ports: 5432 (direct) and 6543 (pooler)
                // Direct connection (5432) sometimes works better with IPv4
                $portsToTry = [];
                if ($port == '6543') {
                    // If pooler was requested, try direct first, then pooler
                    $portsToTry = ['5432', '6543'];
                } else {
                    // If direct was requested, try it first, then pooler as fallback
                    $portsToTry = ['5432', '6543'];
                }
                
                // Build list of connection attempts
                $attempts = [];
                foreach ($portsToTry as $tryPort) {
                    // Try IPv4 address first if available
                    if ($ipv4) {
                        $attempts[] = ['host' => $ipv4, 'port' => $tryPort];
                    }
                    // Always try hostname as fallback
                    $attempts[] = ['host' => $host, 'port' => $tryPort];
                }
                
                // Try each connection combination
                foreach ($attempts as $attempt) {
                    try {
                        $dsn = "pgsql:host={$attempt['host']};port={$attempt['port']};dbname={$db};sslmode=require";
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
                
                // If all attempts failed, try without SSL requirement as last resort
                if (!$connected) {
                    foreach ($attempts as $attempt) {
                        try {
                            $dsn = "pgsql:host={$attempt['host']};port={$attempt['port']};dbname={$db}";
                            $this->pdo = new PDO($dsn, $user, $pass, [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                PDO::ATTR_TIMEOUT => 10,
                                PDO::ATTR_PERSISTENT => false
                            ]);
                            $connected = true;
                            break;
                        } catch (PDOException $e) {
                            $lastError = $e;
                            continue;
                        }
                    }
                }
                
                // If all attempts failed, throw the last error
                if (!$connected && $lastError) {
                    $this->pdo = null;
                    throw $lastError;
                }
                
                // Final validation - ensure PDO was created
                if ($this->pdo === null) {
                    throw new Exception('Failed to establish database connection after all attempts');
                }
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                // Validate MySQL connection
                if ($this->pdo === null) {
                    throw new Exception('Failed to establish MySQL database connection');
                }
            }
        } catch (PDOException $e) {
            $this->pdo = null;
            $this->connect_error = $e->getMessage();
            error_log("Database connection PDOException: " . $e->getMessage());
            throw new Exception('Connection failed: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->pdo = null;
            $this->connect_error = $e->getMessage();
            error_log("Database connection Exception: " . $e->getMessage());
            throw $e;
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
            // Check if PDO connection exists
            if ($this->pdo === null) {
                error_log("Query error: PDO connection is null. SQL: " . $sql);
                $this->error = "Database connection is not established";
                return new DatabaseResult(null, $this->dbType);
            }
            
            // Convert MySQL-specific syntax to PostgreSQL if needed
            if ($this->dbType === 'pgsql') {
                $sql = $this->convertToPostgres($sql);
            }
            
            $stmt = $this->pdo->query($sql);
            return new DatabaseResult($stmt, $this->dbType);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            // Return empty result object instead of false to prevent "num_rows on bool" errors
            return new DatabaseResult(null, $this->dbType);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            return new DatabaseResult(null, $this->dbType);
        }
    }
    
    /**
     * Prepare a statement
     */
    public function prepare($sql) {
        try {
            if (!$this->pdo) {
                error_log("Prepare error: PDO connection is null");
                return false;
            }
            if ($this->dbType === 'pgsql') {
                $sql = $this->convertToPostgres($sql);
            }
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                error_log("Prepare error: Failed to prepare statement");
                return false;
            }
            return new DatabaseStatement($stmt, $this->dbType);
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
        // Remove MySQL backticks (PostgreSQL uses double quotes or no quotes)
        $sql = str_replace('`', '', $sql);
        
        // CURDATE() -> CURRENT_DATE
        $sql = str_ireplace('CURDATE()', 'CURRENT_DATE', $sql);
        
        // MONTH(date) -> EXTRACT(MONTH FROM date)::INTEGER
        $sql = preg_replace('/MONTH\s*\(\s*([^)]+)\s*\)/i', 'EXTRACT(MONTH FROM $1)::INTEGER', $sql);
        
        // YEAR(date) -> EXTRACT(YEAR FROM date)::INTEGER
        $sql = preg_replace('/YEAR\s*\(\s*([^)]+)\s*\)/i', 'EXTRACT(YEAR FROM $1)::INTEGER', $sql);
        
        // NOW() is the same in both
        // DATE() function - PostgreSQL supports DATE() but may need casting
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
    
    /**
     * Check if connection is valid
     */
    public function isConnected() {
        return $this->pdo !== null;
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
        // Handle null stmt gracefully
        if ($stmt === null) {
            $this->num_rows = 0;
        } else {
            try {
                $this->num_rows = $stmt->rowCount();
            } catch (Exception $e) {
                $this->num_rows = 0;
            }
        }
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

    // Log connection attempt (without password)
    error_log("Attempting database connection: host=$host, port=$port, db=$db, type=$type, user=$user");
    
    $conn = new DatabaseWrapper($host, $user, $pass, $db, $port, $type);
    
    // Validate connection was established
    if ($conn->pdo === null) {
        error_log("Connection created but PDO is null");
        throw new Exception('PDO connection is null after initialization. Check database credentials and network connectivity.');
    }
    
    error_log("Database connection established successfully");
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    error_log('Database connection error: ' . $errorMsg);
    error_log('Connection details: host=' . (defined('DB_SERVER') ? DB_SERVER : 'undefined') . 
              ', port=' . (defined('DB_PORT') ? DB_PORT : 'undefined') . 
              ', db=' . (defined('DB_NAME') ? DB_NAME : 'undefined') . 
              ', type=' . (defined('DB_TYPE') ? DB_TYPE : 'undefined'));
    die('Database connection error: ' . htmlspecialchars($errorMsg) . 
        '<br><small>Please check your database configuration in Render environment variables.</small>');
}
?>
