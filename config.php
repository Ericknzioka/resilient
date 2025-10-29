<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration (production values)
define('DB_HOST', 'localhost');
define('DB_USER', 'mcucsyao_cpaneluser_resilient_kitchen');
define('DB_PASS', 'Erick@54');
define('DB_NAME', 'mcucsyao_resilient_kitchen');

// Local fallback settings for XAMPP
define('LOCAL_DB_HOST', 'localhost');
define('LOCAL_DB_USER', 'root');
define('LOCAL_DB_PASS', '');
define('LOCAL_DB_NAME', 'resilient');

function getPDO() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // FORCE CHECK: Is this a cPanel environment?
    $isCPanel = false;
    
    // Check 1: cPanel has specific directory structure
    if (isset($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        // cPanel usually has /home/username/public_html structure
        if (strpos($docRoot, '/home/') !== false && strpos($docRoot, 'public_html') !== false) {
            $isCPanel = true;
        }
    }
    
    // Check 2: cPanel sets specific environment variables
    if (isset($_SERVER['CPANEL']) || isset($_SERVER['cPanel'])) {
        $isCPanel = true;
    }
    
    // Check 3: If accessing via domain (not localhost)
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = strtolower($_SERVER['HTTP_HOST']);
        // If it's NOT localhost/127.0.0.1, assume production
        if (strpos($host, 'localhost') === false && 
            strpos($host, '127.0.0.1') === false && 
            strpos($host, '::1') === false) {
            $isCPanel = true;
        }
    }
    
    // Determine if we're local (XAMPP)
    $isLocal = false;
    if (isset($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = strtolower($_SERVER['DOCUMENT_ROOT']);
        // XAMPP specific paths
        if (strpos($docRoot, 'xampp') !== false || 
            strpos($docRoot, 'htdocs') !== false ||
            strpos($docRoot, 'wamp') !== false) {
            $isLocal = true;
            $isCPanel = false; // Override cPanel if XAMPP detected
        }
    }
    
    // Check localhost access
    if (!$isLocal && isset($_SERVER['HTTP_HOST'])) {
        $host = strtolower($_SERVER['HTTP_HOST']);
        if (strpos($host, 'localhost') !== false || 
            strpos($host, '127.0.0.1') !== false ||
            strpos($host, '::1') !== false) {
            $isLocal = true;
            $isCPanel = false;
        }
    }

    // Build connection attempts
    $attempts = [];
    
        if ($isLocal) {
            // Local development: try local then production
            $attempts = [
                ['host'=>LOCAL_DB_HOST, 'user'=>LOCAL_DB_USER, 'pass'=>LOCAL_DB_PASS, 'name'=>LOCAL_DB_NAME, 'label'=>'local'],
                ['host'=>DB_HOST, 'user'=>DB_USER, 'pass'=>DB_PASS, 'name'=>DB_NAME, 'label'=>'production']
            ];
        } else {
            // Production: only try production credentials. Avoid falling back to local on a public host
            $attempts = [
                ['host'=>DB_HOST, 'user'=>DB_USER, 'pass'=>DB_PASS, 'name'=>DB_NAME, 'label'=>'production']
            ];
        }

    $lastError = '';
    $lastLabel = '';
    $allErrors = [];
    
    foreach ($attempts as $a) {
        $dsn = "mysql:host={$a['host']};dbname={$a['name']};charset=utf8mb4";
        
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, $a['user'], $a['pass'], $options);
            return $pdo;
        } catch (PDOException $e) {
            $allErrors[] = "{$a['label']}: {$e->getMessage()}";
            $lastError = $e->getMessage();
            $lastLabel = $a['label'];
        }
    }

    // Connection failed - show detailed error
    $msg = "<!DOCTYPE html><html><head><title>Database Connection Error</title>";
    $msg .= "<style>body{font-family:Arial,sans-serif;margin:40px;background:#f5f5f5;}";
    $msg .= ".error-box{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
    $msg .= "h3{color:#d32f2f;margin-top:0;}h4{color:#333;border-bottom:2px solid #eee;padding-bottom:10px;}";
    $msg .= "code{background:#f5f5f5;padding:2px 6px;border-radius:3px;font-family:monospace;}";
    $msg .= "ul{line-height:1.8;}li{margin:5px 0;}</style></head><body><div class='error-box'>";
    
    $msg .= "<h3>⚠️ Database Connection Failed</h3>";
    $msg .= "<p><strong>Environment Detection:</strong></p>";
    $msg .= "<ul>";
    $msg .= "<li>cPanel Detected: <strong>" . ($isCPanel ? 'YES' : 'NO') . "</strong></li>";
    $msg .= "<li>Local/XAMPP Detected: <strong>" . ($isLocal ? 'YES' : 'NO') . "</strong></li>";
    $msg .= "</ul>";
    
    $msg .= "<p><strong>Last Connection Attempt ({$lastLabel}):</strong><br>";
    $msg .= "<code>" . htmlspecialchars($lastError) . "</code></p>";
    
    $msg .= "<h4>All Connection Attempts:</h4><ul>";
    foreach ($allErrors as $err) {
        $msg .= "<li>" . htmlspecialchars($err) . "</li>";
    }
    $msg .= "</ul>";
    
    $msg .= "<h4>Database Configuration:</h4>";
    $msg .= "<ul>";
    $msg .= "<li><strong>Production:</strong> Host=" . DB_HOST . ", User=" . DB_USER . ", DB=" . DB_NAME . "</li>";
    $msg .= "<li><strong>Local:</strong> Host=" . LOCAL_DB_HOST . ", User=" . LOCAL_DB_USER . ", DB=" . LOCAL_DB_NAME . "</li>";
    $msg .= "</ul>";
    
    $msg .= "<h4>Server Environment:</h4>";
    $msg .= "<ul>";
    $msg .= "<li><strong>HTTP_HOST:</strong> " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'not set') . "</li>";
    $msg .= "<li><strong>SERVER_NAME:</strong> " . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'not set') . "</li>";
    $msg .= "<li><strong>DOCUMENT_ROOT:</strong> " . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'not set') . "</li>";
    $msg .= "<li><strong>SERVER_SOFTWARE:</strong> " . (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'not set') . "</li>";
    $msg .= "<li><strong>PHP SAPI:</strong> " . php_sapi_name() . "</li>";
    $msg .= "</ul>";
    
    $msg .= "<h4>🔧 Fix for Production (cPanel):</h4>";
    $msg .= "<ol>";
    $msg .= "<li>Log in to your cPanel</li>";
    $msg .= "<li>Go to <strong>MySQL Databases</strong></li>";
    $msg .= "<li>Verify user exists: <code>mcucsyao_cpaneluser_resilient_kitchen</code></li>";
    $msg .= "<li>Verify database exists: <code>mcucsyao_resilient_kitchen</code></li>";
    $msg .= "<li>Check user has ALL PRIVILEGES on the database</li>";
    $msg .= "<li>Verify password is: <code>Erick@54</code></li>";
    $msg .= "<li>If needed, remove user and recreate with correct password</li>";
    $msg .= "</ol>";
    
    $msg .= "<h4>🔧 Fix for Local (XAMPP):</h4>";
    $msg .= "<ol>";
    $msg .= "<li>Make sure XAMPP MySQL is running</li>";
    $msg .= "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
    $msg .= "<li>Create database named: <code>resilient</code></li>";
    $msg .= "<li>Import your SQL file into the database</li>";
    $msg .= "</ol>";
    
    $msg .= "</div></body></html>";
    
    die($msg);
}

// Site configuration
define('SITE_NAME', 'Resilient Kitchen Furniture');
define('SITE_URL', 'http://codetrust.co.ke/resilient'); 
define('WHATSAPP_NUMBER', '+254795398595');

// Ensure minimal schema exists
function ensureSchema() {
    try {
        $pdo = getPDO();
        $sql = "CREATE TABLE IF NOT EXISTS `products` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `category` VARCHAR(150) DEFAULT '',
            `image` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);
        // Create admin_users table if missing
        $sqlAdmin = "CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sqlAdmin);
        // Insert default admin if none exists
        $row = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        if (!$row) {
            $defaultUser = 'resilientmodern';
            $defaultPass = 'Resilient@modern2025';
            $hash = password_hash($defaultPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)');
            $stmt->execute([$defaultUser, $hash, NULL]);
        }
        // Create admin_logs table for auditing admin actions
        $sqlLogs = "CREATE TABLE IF NOT EXISTS `admin_logs` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `admin_id` INT UNSIGNED DEFAULT NULL,
            `action` VARCHAR(255) NOT NULL,
            `meta` TEXT DEFAULT NULL,
            `ip` VARCHAR(45) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX (`admin_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sqlLogs);
    } catch (Exception $e) {
        error_log('Schema creation failed: ' . $e->getMessage());
    }
}

ensureSchema();

// Helper to record admin actions. Call admin_log($adminId, $action, $meta)
function admin_log($adminId, $action, $meta = null) {
    try {
        $pdo = getPDO();
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, meta, ip) VALUES (?, ?, ?, ?)');
        $stmt->execute([$adminId, $action, $meta, $ip]);
    } catch (Exception $e) {
        // Don't block user actions on logging failure; write to PHP error log instead
        error_log('admin_log failed: ' . $e->getMessage());
    }
}
?>