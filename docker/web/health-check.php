<?php
/**
 * Health Check Endpoint for Docker Container
 */

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check PHP
$health['checks']['php'] = [
    'status' => 'ok',
    'version' => PHP_VERSION
];

// Check database connection
try {
    $dsn = "mysql:host=mysql;dbname=performance_evaluation;charset=utf8mb4";
    $pdo = new PDO($dsn, 'app_user', 'secure_dev_password');
    $pdo->query('SELECT 1');
    $health['checks']['database'] = [
        'status' => 'ok',
        'connection' => 'connected'
    ];
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'error',
        'message' => 'Database connection failed'
    ];
    $health['status'] = 'unhealthy';
}

// Check file system
if (is_writable('/var/www/html')) {
    $health['checks']['filesystem'] = [
        'status' => 'ok',
        'writable' => true
    ];
} else {
    $health['checks']['filesystem'] = [
        'status' => 'error',
        'writable' => false
    ];
    $health['status'] = 'unhealthy';
}

// Return appropriate HTTP status
http_response_code($health['status'] === 'healthy' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
?>