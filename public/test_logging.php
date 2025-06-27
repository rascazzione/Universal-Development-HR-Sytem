<?php
/**
 * Simple log reader for debugging
 */

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

if (!isHRAdmin()) {
    die('Access denied');
}

echo "<h3>Recent Error Logs (last 50 lines)</h3>";
echo "<pre>";

// Try to read PHP error log
$logFiles = [
    '/var/log/apache2/error.log',
    '/var/log/php_errors.log',
    '/tmp/php_errors.log',
    ini_get('error_log')
];

foreach ($logFiles as $logFile) {
    if ($logFile && file_exists($logFile) && is_readable($logFile)) {
        echo "=== LOG FILE: $logFile ===\n";
        $lines = file($logFile);
        $recentLines = array_slice($lines, -50);
        
        foreach ($recentLines as $line) {
            if (strpos($line, 'EMPLOYEE_UPDATE_DEBUG') !== false || 
                strpos($line, 'PROFILE_DEBUG') !== false ||
                strpos($line, 'Fatal error') !== false ||
                strpos($line, 'Warning') !== false) {
                echo htmlspecialchars($line);
            }
        }
        echo "\n";
        break;
    }
}

// Also check if we can read from error_log() output
echo "=== CHECKING ERROR LOG FUNCTION ===\n";
error_log("TEST_LOG_ENTRY - " . date('Y-m-d H:i:s'));
echo "Test log entry written. Check above for recent entries.\n";

echo "</pre>";
?>