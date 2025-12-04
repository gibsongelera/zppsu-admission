<?php
/**
 * Global initialization and configuration
 * 
 * IMPORTANT:
 * - Do NOT hard-code database passwords or Supabase secrets in this file.
 * - Use environment variables instead so the same code works locally,
 *   on GitHub, and on your cloud host (Render / Railway / etc).
 */

$dev_data = array(
    'id'          => '-1',
    'firstname'   => 'Developer',
    'lastname'    => '',
    'username'    => 'dev_oretnom',
    'password'    => '5da283a2d990e8d8512cf967df5bc0d0',
    'last_login'  => '',
    'date_updated'=> '',
    'date_added'  => ''
);

// Detect if we're on Render (cloud) or local
$isCloud = getenv('RENDER') || getenv('DB_HOST');

if ($isCloud) {
    // Cloud deployment (Render + Supabase)
    // Get the host without port if it's the default HTTPS port
    $host = $_SERVER['HTTP_HOST'] ?? 'zppsu-admission.onrender.com';
    // Remove port if it's 443 (default HTTPS) or 1000 (Render internal)
    $host = preg_replace('/:(443|1000)$/', '', $host);
    if (!defined('base_url')) define('base_url', 'https://' . $host . '/');
} else {
    // Local development
    if (!defined('base_url')) define('base_url', 'http://localhost/zppsu_admission/');
}

if (!defined('base_app'))  define('base_app', str_replace('\\','/', __DIR__) . '/');
if (!defined('dev_data'))  define('dev_data', $dev_data);

/**
 * Database configuration
 *
 * Defaults are the original local MySQL settings so the app
 * still works in XAMPP. When you deploy, override these using
 * environment variables (for Supabase PostgreSQL):
 *
 *   DB_HOST = db.xxx.supabase.co
 *   DB_PORT = 5432
 *   DB_USER = postgres
 *   DB_PASS = your_db_password
 *   DB_NAME = postgres
 *   DB_TYPE = pgsql (for PostgreSQL) or mysql (for MySQL)
 */

if (!defined('DB_SERVER')) {
    define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', getenv('DB_PASS') ?: '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'zppsu_admission');
}
if (!defined('DB_PORT')) {
    // Use Supabase connection pooler (6543) on Render for better reliability
    // The pooler avoids IPv6 issues and is optimized for serverless/cloud
    if ($isCloud && getenv('DB_TYPE') === 'pgsql') {
        define('DB_PORT', getenv('DB_PORT') ?: '6543'); // Use pooler by default on cloud
    } else {
        define('DB_PORT', getenv('DB_PORT') ?: '3306');
    }
}
if (!defined('DB_TYPE')) {
    // Auto-detect: if port is 5432 or 6543, assume PostgreSQL
    $port = getenv('DB_PORT') ?: (($isCloud && getenv('DB_TYPE') === 'pgsql') ? '6543' : '3306');
    define('DB_TYPE', ($port == '5432' || $port == '6543') ? 'pgsql' : 'mysql');
}
?>
