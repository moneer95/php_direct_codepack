<?php
/**
 * Security Helper Functions
 */

// Load configuration
function getConfig() {
    static $config = null;
    if ($config === null) {
        $configFile = __DIR__ . '/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        } else {
            // Fallback to defaults (not recommended for production)
            $config = require __DIR__ . '/config.php';
        }
    }
    return $config;
}

/**
 * Start secure session
 */
function startSecureSession() {
    $config = getConfig();
    
    // Configure session security
    if ($config['requireHTTPS'] && !isHttps()) {
        http_response_code(403);
        die('HTTPS required for payment processing');
    }
    
    // Set secure cookie parameters
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $config['requireHTTPS'] ? '1' : '0');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    
    // Set session name
    session_name('payment_session');
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $config['sessionExpiry'])) {
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
    
    return session_id();
}

/**
 * Check if request is over HTTPS
 */
function isHttps() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || $_SERVER['SERVER_PORT'] == 443;
}

/**
 * Set secure cookie
 */
function setSecureCookie($name, $value, $expiry = null) {
    $config = getConfig();
    
    $options = [
        'expires' => $expiry ?: (time() + $config['cookieExpiry']),
        'path' => '/',
        'domain' => '',
        'secure' => $config['requireHTTPS'],
        'httponly' => true,
        'samesite' => 'Strict'
    ];
    
    return setcookie($name, $value, $options);
}

/**
 * Log errors securely
 */
function logError($message, $context = []) {
    $config = getConfig();
    
    if (!$config['logErrors']) {
        return;
    }
    
    $logDir = dirname($config['logFile']);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Remove sensitive data from context
    $logEntry['context'] = removeSensitiveData($logEntry['context']);
    
    $logLine = date('Y-m-d H:i:s') . ' - ' . json_encode($logEntry) . PHP_EOL;
    
    file_put_contents($config['logFile'], $logLine, FILE_APPEND | LOCK_EX);
}

/**
 * Remove sensitive data from arrays (for logging)
 */
function removeSensitiveData($data) {
    $sensitive = ['cardNumber', 'cardCVV', 'CVV', 'signature', 'secret', 'password', 'key'];
    
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $data[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $data[$key] = removeSensitiveData($value);
            }
        }
    }
    
    return $data;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate card number (Luhn algorithm)
 */
function validateCardNumber($cardNumber) {
    $cardNumber = preg_replace('/\s+/', '', $cardNumber);
    
    if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
        return false;
    }
    
    // Luhn algorithm
    $sum = 0;
    $alternate = false;
    
    for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
        $n = intval($cardNumber[$i]);
        
        if ($alternate) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        
        $sum += $n;
        $alternate = !$alternate;
    }
    
    return ($sum % 10 === 0);
}

/**
 * Validate CVV
 */
function validateCVV($cvv, $cardType = 'generic') {
    $cvv = preg_replace('/\s+/', '', $cvv);
    
    // CVV is 3-4 digits
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        return false;
    }
    
    return true;
}

/**
 * Validate expiry date
 */
function validateExpiryDate($month, $year) {
    $month = intval($month);
    $year = intval($year);
    
    // Convert 2-digit year to 4-digit
    if ($year < 100) {
        $year = 2000 + $year;
    }
    
    // Validate month
    if ($month < 1 || $month > 12) {
        return false;
    }
    
    // Validate year (should be current or future)
    if ($year < intval(date('Y'))) {
        return false;
    }
    
    // If current year, month must be current or future
    if ($year == intval(date('Y')) && $month < intval(date('m'))) {
        return false;
    }
    
    return ['month' => $month, 'year' => substr($year, -2)];
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

